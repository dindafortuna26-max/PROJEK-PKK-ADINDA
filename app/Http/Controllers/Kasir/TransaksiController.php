<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\BahanOperasional;
use App\Models\Kategori;
use App\Models\Menu;
use App\Models\PergerakanStok;
use App\Models\PergerakanStokBahanOperasional;
use App\Models\PergerakanStokTopping;
use App\Models\Topping;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\TransaksiDetailOpsi;
use App\Models\Ukuran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TransaksiController extends Controller
{
    
    public function index(Request $request): View
    {
        $kategoris = Kategori::where('is_active', true)->orderBy('nama')->get();

        $menus = Menu::query()
            ->with(['kategori', 'ukuranAktif'])
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();

        $sausList = Topping::aktif()->saus()->orderBy('nama')->get();
        $bubukList = Topping::aktif()->bubukTabur()->orderBy('nama')->get();

        // Data mentah utk JS (harga per ukuran, status stok, dst) —
        // ditempel sebagai <script type="application/json"> di view,
        // pola yang sama dipakai di admin.riwayat-transaksi._detail.
        // Stok yang tampil = stok MILIK kasir yang sedang login.
        $kasirId = (int) Auth::id();

        // Kemasan aktif dikelompokkan per ukuran (Kecil / Sedang / Besar). Fotonya
        // tampil di tombol ukuran; kalau stok kemasan kasir ini habis, ukuran itu
        // ditandai habis supaya tidak terjual tanpa kemasan.
        $kemasanPerUkuran = BahanOperasional::aktif()
            ->kemasan()
            ->whereNotNull('ukuran_id')
            ->orderBy('id')
            ->get()
            ->groupBy('ukuran_id');

        // Dihitung sekali per ukuran (bukan per menu) supaya tidak boros query.
        $tabelKemasan = $kemasanPerUkuran->map(fn ($daftar) => [
            'kemasan_foto' => $daftar->first(fn ($k) => $k->gambar_url)?->gambar_url,
            'kemasan_nama' => $daftar->pluck('nama')->implode(', ') ?: null,
            'kemasan_habis' => $daftar->contains(fn ($k) => $k->statusUntukKasir($kasirId) === 'habis'),
        ]);

        $infoKemasan = fn (int $ukuranId) => $tabelKemasan->get($ukuranId, [
            'kemasan_foto' => null,
            'kemasan_nama' => null,
            'kemasan_habis' => false,
        ]);

        $menuData = $menus->map(function (Menu $menu) use ($kasirId, $infoKemasan) {
            $statusStok = $menu->statusStokUntukKasir($kasirId);

            return [
                'id' => $menu->id,
                'nama' => $menu->nama,
                'deskripsi' => $menu->deskripsi,
                'gambar' => $menu->gambar ? asset('storage/' . $menu->gambar) : asset('assets_dashboard/images/logo3.png'),
                'kategori_id' => $menu->kategori_id,
                'status_stok' => $statusStok,
                'stok_saat_ini' => $menu->stokUntukKasir($kasirId),
                'habis' => $statusStok === 'habis',
                'ukurans' => $menu->ukuranAktif->map(fn ($ukuran) => [
                    'id' => $ukuran->id,
                    'nama' => $ukuran->nama,
                    'harga' => (int) $ukuran->pivot->harga,
                ] + $infoKemasan((int) $ukuran->id))->values(),
            ];
        })->values();

        $toppingData = function ($toppings) use ($kasirId) {
            return $toppings->map(fn (Topping $topping) => [
                'id' => $topping->id,
                'nama' => $topping->nama,
                'harga_tambahan' => (int) $topping->harga_tambahan,
                'foto' => $topping->gambar_url,
                'habis' => $topping->statusStokUntukKasir($kasirId) === 'habis',
            ])->values();
        };

        return view('kasir.transaksi.index', [
            'kategoris' => $kategoris,
            'menus' => $menus,
            'sausList' => $sausList,
            'bubukList' => $bubukList,
            'menuDataJson' => $menuData,
            'sausDataJson' => $toppingData($sausList),
            'bubukDataJson' => $toppingData($bubukList),
        ]);
    }

    /**
     * POST /transaksi
     * Simpan transaksi baru dari pesanan kasir. Divalidasi dua kali:
     * (1) aturan bentuk data lewat validate(), (2) aturan bisnis
     * (menu/ukuran/topping masih aktif, stok cukup) lewat resolveCart().
     * Kalau salah satu gagal, TIDAK ADA baris yang tersimpan sama sekali
     * (rollback) — supaya tidak ada transaksi "setengah jalan".
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'metode_bayar' => ['required', 'in:cash,qris'],
            'uang_diterima' => ['nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_id' => ['required', 'integer', 'exists:menus,id'],
            'items.*.ukuran_id' => ['required', 'integer', 'exists:ukurans,id'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
            'items.*.saus_ids' => ['nullable', 'array'],
            'items.*.saus_ids.*' => ['integer', 'exists:toppings,id'],
            'items.*.level_pedas' => ['nullable', 'in:tidak_pedas,level_1,level_2,level_3'],
            'items.*.bubuk_ids' => ['nullable', 'array'],
            'items.*.bubuk_ids.*' => ['integer', 'exists:toppings,id'],
        ], [
            'metode_bayar.required' => 'Pilih metode bayar dulu.',
            'uang_diterima.integer' => 'Nominal uang diterima harus berupa angka.',
            'items.required' => 'Pesanan masih kosong. Tambahkan menu dulu.',
            'items.min' => 'Pesanan masih kosong. Tambahkan menu dulu.',
        ]);

        // Bisa lempar ValidationException (mis. stok kurang) — otomatis
        // ditangkap Laravel & redirect back dengan pesan error, tidak
        // perlu try/catch manual di sini.
        $resolved = $this->resolveCart($data['items']);

        // Total dihitung di server (bukan percaya angka dari browser),
        // lalu dipakai untuk cek uang tunai & hitung kembalian.
        $total = 0;
        foreach ($resolved['lines'] as $line) {
            $total += $line['subtotal'];
            foreach ($line['opsi'] as $opsi) {
                $total += $opsi->harga_tambahan * $line['jumlah'];
            }
        }

        // Pembayaran tunai: pembeli boleh bayar lebih, selisihnya jadi
        // kembalian. Kalau uang kurang dari total, transaksi ditolak.
        // QRIS tidak punya kembalian (uang_diterima kosong).
        $uangDiterima = null;
        $kembalian = 0;

        if ($data['metode_bayar'] === 'cash') {
            if (! isset($data['uang_diterima'])) {
                throw ValidationException::withMessages([
                    'uang_diterima' => 'Isi nominal uang yang diterima dari pembeli.',
                ]);
            }

            $uangDiterima = (int) $data['uang_diterima'];

            if ($uangDiterima < $total) {
                $kurang = number_format($total - $uangDiterima, 0, ',', '.');
                throw ValidationException::withMessages([
                    'uang_diterima' => "Uang diterima kurang Rp {$kurang} dari total belanja.",
                ]);
            }

            $kembalian = $uangDiterima - $total;
        }

        $transaksi = null;

        DB::transaction(function () use ($resolved, $data, $total, $uangDiterima, $kembalian, &$transaksi) {
            $noTransaksi = $this->nomorTransaksiBerikutnya();

            $transaksi = Transaksi::create([
                'no_transaksi' => $noTransaksi,
                'user_id' => Auth::id(),
                'total' => $total,
                'uang_diterima' => $uangDiterima,
                'kembalian' => $kembalian,
                'metode_bayar' => $data['metode_bayar'],
                'status' => 'selesai',
            ]);

            foreach ($resolved['lines'] as $line) {
                $detail = TransaksiDetail::create([
                    'transaksi_id' => $transaksi->id,
                    'menu_id' => $line['menu']->id,
                    'ukuran_id' => $line['ukuran']->id,
                    'nama_menu' => $line['menu']->nama,
                    'nama_ukuran' => $line['ukuran']->nama,
                    'level_pedas' => $line['level_pedas'],
                    'jumlah' => $line['jumlah'],
                    'harga_satuan' => $line['harga_satuan'],
                    'subtotal' => $line['subtotal'],
                ]);

                foreach ($line['opsi'] as $opsi) {
                    TransaksiDetailOpsi::create([
                        'transaksi_detail_id' => $detail->id,
                        'topping_id' => $opsi->id,
                        'nama_topping' => $opsi->nama,
                        'jenis_topping' => $opsi->jenis,
                        'harga_tambahan' => $opsi->harga_tambahan,
                    ]);
                }
            }

            // ===== Otomatis kurangi stok — inilah bagian intinya =====
            foreach ($resolved['stokMenuNeeded'] as $menuId => $qty) {
                PergerakanStok::create([
                    'menu_id' => $menuId,
                    'user_id' => Auth::id(),
                    'kasir_id' => Auth::id(), // mengurangi stok milik kasir ini saja
                    'jenis' => 'keluar',
                    'jumlah' => $qty,
                    'keterangan' => "Otomatis: {$noTransaksi}",
                    'tanggal' => now()->toDateString(),
                ]);
            }

            foreach ($resolved['stokToppingNeeded'] as $toppingId => $qty) {
                PergerakanStokTopping::create([
                    'topping_id' => $toppingId,
                    'user_id' => Auth::id(),
                    'kasir_id' => Auth::id(), // mengurangi stok milik kasir ini saja
                    'jenis' => 'keluar',
                    'jumlah' => $qty,
                    'keterangan' => "Otomatis: {$noTransaksi}",
                    'tanggal' => now()->toDateString(),
                ]);
            }

            // Kemasan (cup / box per ukuran) ikut berkurang otomatis sesuai
            // ukuran porsi yang terjual. Minyak & gas tetap manual.
            foreach ($resolved['stokKemasanNeeded'] as $bahanId => $qty) {
                PergerakanStokBahanOperasional::create([
                    'bahan_operasional_id' => $bahanId,
                    'user_id' => Auth::id(),
                    'kasir_id' => Auth::id(), // mengurangi stok milik kasir ini saja
                    'jenis' => 'keluar',
                    'jumlah' => $qty,
                    'sesi' => 0,
                    'transaksi_id' => $transaksi->id,
                    'keterangan' => "Otomatis: {$noTransaksi}",
                    'tanggal' => now()->toDateString(),
                ]);
            }
        });

        // Dipanggil dari tombol "Cetak Struk" via fetch: balas JSON berisi
        // nomor transaksi + URL struk. Halaman transaksi yang memunculkan
        // dialog cetak (nama file = "Struk TRX-xxxxx"), lalu kembali sendiri.
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'no_transaksi' => $transaksi->no_transaksi,
                'struk_url' => route('kasir.transaksi.struk', ['transaksi' => $transaksi, 'embed' => 1]),
                'struk_url_penuh' => route('kasir.transaksi.struk', $transaksi),
                'kembali_url' => route('kasir.transaksi.index'),
            ]);
        }

        return redirect()
            ->route('kasir.transaksi.struk', $transaksi);
    }

    /**
     * GET /transaksi/{transaksi}/struk
     * Halaman struk versi cetak, dibuka setelah transaksi selesai
     * (auto window.print(), lalu otomatis kembali ke halaman transaksi). Kasir cuma boleh lihat
     * struk transaksi yang dia layani sendiri; admin boleh lihat semua.
     */
    public function struk(Request $request, Transaksi $transaksi): View
    {
        abort_if(
            (int) $transaksi->user_id !== (int) Auth::id() && Auth::user()->role !== 'admin',
            403,
            'Struk transaksi ini bukan milikmu.'
        );

        $transaksi->load(['kasir', 'detail.opsi']);

        return view('admin.riwayat-transaksi.struk', [
            'transaksi' => $transaksi,
            // Setelah struk dicetak, kasir otomatis kembali ke halaman transaksi.
            'kembaliUrl' => route('kasir.transaksi.index'),
            // ?embed=1 -> dimuat di iframe tersembunyi; halaman transaksi yang memanggil print().
            'embed' => $request->boolean('embed'),
        ]);
    }

    /**
     * Validasi ATURAN BISNIS pesanan: menu/ukuran/topping harus masih
     * aktif, dan stok harus cukup (kalau memang sedang dikelola —
     * menu/topping yang belum pernah diisi stoknya tidak diblokir,
     * sama seperti filosofi status "Belum Dikelola" di Manajemen Stok).
     * Dijumlahkan dulu per menu/topping (in case 1 menu dipesan di
     * beberapa baris) sebelum dicek, supaya tidak lolos kalau baris
     * per baris kelihatan cukup tapi totalnya melebihi stok.
     *
     * @throws ValidationException
     */
    private function resolveCart(array $items): array
    {
        $lines = [];
        $stokMenuNeeded = [];
        $stokToppingNeeded = [];
        $stokKemasanNeeded = [];
        $kasirId = (int) Auth::id(); // stok yang dicek = stok kasir yang sedang transaksi

        // Kemasan aktif per ukuran: 1 porsi ukuran X memakai sekian kemasan.
        $kemasanPerUkuran = BahanOperasional::aktif()
            ->kemasan()
            ->whereNotNull('ukuran_id')
            ->get()
            ->groupBy('ukuran_id');

        foreach ($items as $index => $item) {
            $baris = $index + 1;

            $menu = Menu::find($item['menu_id']);
            if (! $menu || ! $menu->is_active) {
                throw ValidationException::withMessages([
                    'items' => "Menu di baris pesanan ke-{$baris} sudah tidak tersedia.",
                ]);
            }

            $menuUkuran = $menu->menuUkurans()
                ->where('ukuran_id', $item['ukuran_id'])
                ->where('is_active', true)
                ->first();

            if (! $menuUkuran) {
                throw ValidationException::withMessages([
                    'items' => "Ukuran yang dipilih untuk \"{$menu->nama}\" sudah tidak tersedia.",
                ]);
            }

            $ukuran = Ukuran::find($item['ukuran_id']);
            $jumlah = (int) $item['jumlah'];

            $stokMenuNeeded[$menu->id] = ($stokMenuNeeded[$menu->id] ?? 0) + $jumlah;

            foreach ($kemasanPerUkuran->get((int) $ukuran->id, collect()) as $kemasan) {
                $stokKemasanNeeded[$kemasan->id] = ($stokKemasanNeeded[$kemasan->id] ?? 0)
                    + ($jumlah * max(1, (int) $kemasan->pakai_per_porsi));
            }

            $opsi = [];

            // Bumbu boleh dipilih lebih dari satu (mis. Balado + Keju + BBQ).
            // array_unique supaya bumbu yang sama tidak terhitung dobel.
            foreach (array_unique($item['saus_ids'] ?? []) as $sausId) {
                $saus = Topping::where('id', $sausId)
                    ->where('jenis', 'saus')
                    ->where('is_active', true)
                    ->first();

                if (! $saus) {
                    throw ValidationException::withMessages([
                        'items' => "Bumbu yang dipilih untuk \"{$menu->nama}\" sudah tidak tersedia.",
                    ]);
                }

                $opsi[] = $saus;
                $stokToppingNeeded[$saus->id] = ($stokToppingNeeded[$saus->id] ?? 0) + $jumlah;
            }

            foreach (array_unique($item['bubuk_ids'] ?? []) as $bubukId) {
                $bubuk = Topping::where('id', $bubukId)
                    ->where('jenis', 'bubuk_tabur')
                    ->where('is_active', true)
                    ->first();

                if (! $bubuk) {
                    throw ValidationException::withMessages([
                        'items' => "Topping yang dipilih untuk \"{$menu->nama}\" sudah tidak tersedia.",
                    ]);
                }

                $opsi[] = $bubuk;
                $stokToppingNeeded[$bubuk->id] = ($stokToppingNeeded[$bubuk->id] ?? 0) + $jumlah;
            }

            $lines[] = [
                'menu' => $menu,
                'ukuran' => $ukuran,
                'level_pedas' => $item['level_pedas'] ?? 'tidak_pedas',
                'harga_satuan' => (int) $menuUkuran->harga,
                'jumlah' => $jumlah,
                'subtotal' => (int) $menuUkuran->harga * $jumlah,
                'opsi' => $opsi,
            ];
        }

        foreach ($stokMenuNeeded as $menuId => $qty) {
            $menu = Menu::find($menuId);
            $sisa = $menu->stokUntukKasir($kasirId);
            if ($menu->dikelolaUntukKasir($kasirId) && $sisa < $qty) {
                throw ValidationException::withMessages([
                    'items' => "Stok \"{$menu->nama}\" milikmu tidak cukup (sisa {$sisa}, dipesan {$qty}).",
                ]);
            }
        }

        foreach ($stokToppingNeeded as $toppingId => $qty) {
            $topping = Topping::find($toppingId);
            $sisa = $topping->stokUntukKasir($kasirId);
            if ($topping->dikelolaUntukKasir($kasirId) && $sisa < $qty) {
                throw ValidationException::withMessages([
                    'items' => "Stok \"{$topping->nama}\" milikmu tidak cukup (sisa {$sisa}, dibutuhkan {$qty}).",
                ]);
            }
        }

        foreach ($stokKemasanNeeded as $bahanId => $qty) {
            $kemasan = BahanOperasional::find($bahanId);
            $sisa = $kemasan->stokUntukKasir($kasirId);
            if ($kemasan->sudahDikelolaUntukKasir($kasirId) && $sisa < $qty) {
                throw ValidationException::withMessages([
                    'items' => "Kemasan \"{$kemasan->nama}\" milikmu tidak cukup (sisa {$sisa}, dibutuhkan {$qty}). Hubungi Admin untuk menambah stok kemasan.",
                ]);
            }
        }

        return [
            'lines' => $lines,
            'stokMenuNeeded' => $stokMenuNeeded,
            'stokToppingNeeded' => $stokToppingNeeded,
            'stokKemasanNeeded' => $stokKemasanNeeded,
        ];
    }

    /**
     * Nomor urut TRX-00001, TRX-00002, dst — sama format dengan yang
     * sudah dipakai TransaksiSeeder. lockForUpdate() supaya dua kasir
     * yang checkout barengan tidak kebagian nomor sama (harus dipanggil
     * di dalam DB::transaction, lihat store()).
     */
    private function nomorTransaksiBerikutnya(): string
    {
        $terakhir = Transaksi::orderByDesc('id')->lockForUpdate()->first();
        $nomorUrut = $terakhir ? ((int) substr($terakhir->no_transaksi, 4)) + 1 : 1;

        return 'TRX-' . str_pad((string) $nomorUrut, 5, '0', STR_PAD_LEFT);
    }
}
