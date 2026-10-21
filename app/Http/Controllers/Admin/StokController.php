<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BahanOperasional;
use App\Models\Menu;
use App\Models\PergerakanStok;
use App\Models\PergerakanStokBahanOperasional;
use App\Models\PergerakanStokTopping;
use App\Models\Topping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StokController extends Controller
{
    /**
     * GET /admin/stok
     * Satu halaman, dua tab: "Data Stok" (stok berjalan tiap menu) &
     * "Stok Masuk" (riwayat input stok). Keduanya dipaginasi terpisah
     * (page name beda) supaya link pagination-nya nggak bentrok.
     */
    public function index(Request $request): View
    {
        $menus = Menu::query()
            ->with('kategori')
            ->when($request->filled('cari'), function ($query) use ($request) {
                $query->where('nama', 'like', '%' . $request->input('cari') . '%');
            })
            ->orderBy('nama')
            ->get();

        // Hitung semua stok sekaligus (query per-menu di accessor lumayan
        // banyak kalau dipanggil satu-satu, jadi kita hitung agregatnya
        // di sini lewat satu query, lalu tempel ke masing-masing menu).
        $agregat = PergerakanStok::query()
            ->selectRaw('menu_id, jenis, SUM(jumlah) as total')
            ->groupBy('menu_id', 'jenis')
            ->get()
            ->groupBy('menu_id');

        $terakhirMasuk = PergerakanStok::query()
            ->where('jenis', 'masuk')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->groupBy('menu_id');

        $dataStok = $menus->map(function (Menu $menu) use ($agregat, $terakhirMasuk) {
            $baris = $agregat->get($menu->id, collect());
            $dikelola = $baris->isNotEmpty();
            $masuk = (int) $baris->firstWhere('jenis', 'masuk')?->total;
            $keluar = (int) $baris->firstWhere('jenis', 'keluar')?->total;
            $stok = $masuk - $keluar;

            $status = match (true) {
                ! $dikelola => 'belum_dikelola',
                $stok <= 0 => 'habis',
                $stok <= $menu->stok_minimum => 'menipis',
                default => 'aman',
            };

            return (object) [
                'menu' => $menu,
                'stok' => $stok,
                'status' => $status,
                'terakhir_masuk' => $terakhirMasuk->get($menu->id)?->first(),
            ];
        });

        $perPage = 10;
        $halaman = (int) $request->input('stok_page', 1);
        $dataStokPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $dataStok->forPage($halaman, $perPage)->values(),
            $dataStok->count(),
            $perPage,
            $halaman,
            ['path' => $request->url(), 'pageName' => 'stok_page', 'query' => $request->query()]
        );

        // Riwayat menampilkan stok MASUK & KELUAR. Filter: ?riwayat_menu=masuk|keluar.
        $filterRiwayatMenu = in_array($request->input('riwayat_menu'), ['masuk', 'keluar'], true)
            ? $request->input('riwayat_menu')
            : '';

        $riwayatMasuk = PergerakanStok::query()
            ->with(['menu', 'user'])
            ->when($filterRiwayatMenu !== '', fn ($q) => $q->where('jenis', $filterRiwayatMenu))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'riwayat_page')
            ->withQueryString();

        $menuAktif = Menu::where('is_active', true)->orderBy('nama')->get();

        // ===================== STOK TOPPING & BUMBU =====================
        // Pola sama persis dengan Data Stok menu di atas, cuma sumbernya
        // dari tabel pergerakan_stok_topping (lihat Topping model).
        $toppings = Topping::query()
            ->when($request->filled('cari'), function ($query) use ($request) {
                $query->where('nama', 'like', '%' . $request->input('cari') . '%');
            })
            ->orderBy('nama')
            ->get();

        $agregatTopping = PergerakanStokTopping::query()
            ->selectRaw('topping_id, jenis, SUM(jumlah) as total')
            ->groupBy('topping_id', 'jenis')
            ->get()
            ->groupBy('topping_id');

        $terakhirMasukTopping = PergerakanStokTopping::query()
            ->where('jenis', 'masuk')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->groupBy('topping_id');

        $dataStokTopping = $toppings->map(function (Topping $topping) use ($agregatTopping, $terakhirMasukTopping) {
            $baris = $agregatTopping->get($topping->id, collect());
            $dikelola = $baris->isNotEmpty();
            $masuk = (int) $baris->firstWhere('jenis', 'masuk')?->total;
            $keluar = (int) $baris->firstWhere('jenis', 'keluar')?->total;
            $stok = $masuk - $keluar;

            $status = match (true) {
                ! $dikelola => 'belum_dikelola',
                $stok <= 0 => 'habis',
                $stok <= $topping->stok_minimum => 'menipis',
                default => 'aman',
            };

            return (object) [
                'topping' => $topping,
                'stok' => $stok,
                'status' => $status,
                'terakhir_masuk' => $terakhirMasukTopping->get($topping->id)?->first(),
            ];
        });

        $perPageTopping = 10;
        $halamanTopping = (int) $request->input('topping_page', 1);
        $dataStokToppingPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $dataStokTopping->forPage($halamanTopping, $perPageTopping)->values(),
            $dataStokTopping->count(),
            $perPageTopping,
            $halamanTopping,
            ['path' => $request->url(), 'pageName' => 'topping_page', 'query' => $request->query()]
        );

        // Sama: stok MASUK & KELUAR. Filter: ?riwayat_topping=masuk|keluar.
        $filterRiwayatTopping = in_array($request->input('riwayat_topping'), ['masuk', 'keluar'], true)
            ? $request->input('riwayat_topping')
            : '';

        $riwayatMasukTopping = PergerakanStokTopping::query()
            ->with(['topping', 'user'])
            ->when($filterRiwayatTopping !== '', fn ($q) => $q->where('jenis', $filterRiwayatTopping))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'riwayat_topping_page')
            ->withQueryString();

        $toppingAktif = Topping::where('is_active', true)->orderBy('nama')->get();

        // ===================== BAHAN OPERASIONAL =====================
        // Minyak goreng & gas. Dipisah total dari menu/topping: stoknya
        // dicatat dalam liter/tabung, dihitung dalam SESI goreng, dan
        // berkurang OTOMATIS 1 sesi tiap transaksi selesai.
        $bahanList = BahanOperasional::query()
            ->when($request->filled('cari'), function ($query) use ($request) {
                $query->where('nama', 'like', '%' . $request->input('cari') . '%');
            })
            ->orderBy('nama')
            ->get();

        $dataStokBahan = $bahanList->map(function (BahanOperasional $bahan) {
            return (object) [
                'bahan' => $bahan,
                'stok' => $bahan->stok_sesi,
                'status' => $bahan->status_stok,
                'terakhir_masuk' => $bahan->pergerakanStok()
                    ->where('jenis', 'masuk')
                    ->orderByDesc('tanggal')
                    ->orderByDesc('id')
                    ->first(),
            ];
        });

        // Filter riwayat: Semua / Masuk / Keluar (?riwayat_bahan=masuk|keluar).
        $filterRiwayatBahan = in_array($request->input('riwayat_bahan'), ['masuk', 'keluar'], true)
            ? $request->input('riwayat_bahan')
            : '';

        $riwayatBahan = PergerakanStokBahanOperasional::query()
            ->with(['bahan', 'user', 'transaksi'])
            ->when($filterRiwayatBahan !== '', fn ($q) => $q->where('jenis', $filterRiwayatBahan))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'riwayat_bahan_page')
            ->withQueryString();

        $bahanAktif = BahanOperasional::where('is_active', true)->orderBy('nama')->get();

        // Kartu ringkasan SEKARANG PER KELOMPOK (menu / topping & bumbu /
        // bahan operasional) supaya angkanya tidak campur dan tidak bikin
        // pusing. Menu & topping tetap seperti sebelumnya: Aman/Menipis =
        // total KUANTITAS barang, Habis = jumlah PRODUK. Bahan operasional
        // dihitung per ITEM (satuannya beda-beda: liter, tabung).
        $hitungKuantitas = function ($koleksi) {
            $aman = $koleksi->where('status', 'aman')->sum('stok');
            $menipis = $koleksi->where('status', 'menipis')->sum('stok');

            return [
                'aman' => $aman,
                'menipis' => $menipis,
                'habis' => $koleksi->where('status', 'habis')->count(),
                'total' => $aman + $menipis,
            ];
        };

        $ringkasan = [
            'menu' => $hitungKuantitas($dataStok),
            'topping' => $hitungKuantitas($dataStokTopping),
            'bahan' => [
                'aman' => $dataStokBahan->where('status', 'aman')->count(),
                'menipis' => $dataStokBahan->where('status', 'menipis')->count(),
                'habis' => $dataStokBahan->where('status', 'habis')->count(),
                'total' => $dataStokBahan->count(),
            ],
        ];

        return view('admin.stok.index', [
            'dataStok' => $dataStokPaginated,
            'riwayatMasuk' => $riwayatMasuk,
            'ringkasan' => $ringkasan,
            'menuAktif' => $menuAktif,
            'dataStokTopping' => $dataStokToppingPaginated,
            'riwayatMasukTopping' => $riwayatMasukTopping,
            'toppingAktif' => $toppingAktif,
            'dataStokBahan' => $dataStokBahan,
            'riwayatBahan' => $riwayatBahan,
            'filterRiwayatBahan' => $filterRiwayatBahan,
            'filterRiwayatMenu' => $filterRiwayatMenu,
            'filterRiwayatTopping' => $filterRiwayatTopping,
            'bahanAktif' => $bahanAktif,
        ]);
    }

    /**
     * POST /admin/stok
     * Modal "Tambah Stok" — input stok masuk manual (restock).
     */
    public function storeMasuk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'menu_id' => ['required', 'exists:menus,id'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'menu_id.required' => 'Pilih barang/menunya dulu.',
            'menu_id.exists' => 'Menu yang dipilih tidak ditemukan.',
            'jumlah.required' => 'Jumlah stok masuk wajib diisi.',
            'jumlah.min' => 'Jumlah minimal 1.',
            'tanggal.required' => 'Tanggal wajib diisi.',
        ]);

        $menu = Menu::findOrFail($data['menu_id']);

        PergerakanStok::create([
            'menu_id' => $menu->id,
            'user_id' => Auth::id(),
            'jenis' => 'masuk',
            'jumlah' => $data['jumlah'],
            'keterangan' => $data['keterangan'] ?: 'Stok masuk',
            'tanggal' => $data['tanggal'],
        ]);

        return back()->with('success', "Stok \"{$menu->nama}\" bertambah {$data['jumlah']}.");
    }

    /**
     * PATCH /admin/stok/{menu}/koreksi
     * Modal "Edit" di baris Data Stok — set stok jadi angka baru.
     * Selisihnya otomatis dicatat sebagai pergerakan masuk/keluar biar
     * riwayat log tetap jadi satu-satunya sumber kebenaran (bukan
     * nimpa angka langsung).
     */
    public function koreksi(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate([
            'stok_baru' => ['required', 'integer', 'min:0'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'stok_baru.required' => 'Stok barunya wajib diisi.',
            'stok_baru.min' => 'Stok tidak boleh minus.',
            'stok_minimum.required' => 'Batas stok menipis wajib diisi.',
        ]);

        DB::transaction(function () use ($menu, $data) {
            $stokSekarang = $menu->stok_saat_ini;
            $selisih = $data['stok_baru'] - $stokSekarang;

            if ($selisih !== 0) {
                PergerakanStok::create([
                    'menu_id' => $menu->id,
                    'user_id' => Auth::id(),
                    'jenis' => $selisih > 0 ? 'masuk' : 'keluar',
                    'jumlah' => abs($selisih),
                    'keterangan' => $data['keterangan'] ?: 'Koreksi stok manual',
                    'tanggal' => now()->toDateString(),
                ]);
            }

            $menu->update(['stok_minimum' => $data['stok_minimum']]);
        });

        return back()->with('success', "Stok \"{$menu->nama}\" berhasil dikoreksi.");
    }

    /**
     * DELETE /admin/stok/{menu}/riwayat
     * Tombol hapus di baris Data Stok — hapus SEMUA riwayat pergerakan
     * stok menu ini (balik jadi "belum dikelola"). Dipakai kalau
     * riwayat stoknya salah total dan mau diulang dari nol.
     */
    public function hapusRiwayat(Menu $menu): RedirectResponse
    {
        $menu->pergerakanStok()->delete();

        return back()->with('success', "Riwayat stok \"{$menu->nama}\" berhasil dihapus, stoknya balik ke 0.");
    }

    /**
     * DELETE /admin/stok/riwayat/{pergerakanStok}
     * Hapus satu entri di tab "Stok Masuk" (misal salah input).
     */
    public function hapusEntriMasuk(PergerakanStok $pergerakanStok): RedirectResponse
    {
        abort_if($pergerakanStok->jenis !== 'masuk', 403);

        $nama = $pergerakanStok->menu->nama ?? 'menu';
        $pergerakanStok->delete();

        return back()->with('success', "Entri stok masuk \"{$nama}\" berhasil dihapus.");
    }

    // ========================================================
    // ============ STOK TOPPING & BUMBU (SAUS/BUBUK) ==========
    // Method di bawah ini sama persis polanya dengan method stok
    // menu di atas, cuma target tabelnya pergerakan_stok_topping.
    // ========================================================

    /**
     * POST /admin/stok/topping
     */
    public function storeMasukTopping(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'topping_id' => ['required', 'exists:toppings,id'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'topping_id.required' => 'Pilih saus/bubuk tabur dulu.',
            'jumlah.required' => 'Jumlah stok masuk wajib diisi.',
            'jumlah.min' => 'Jumlah minimal 1.',
            'tanggal.required' => 'Tanggal wajib diisi.',
        ]);

        $topping = Topping::findOrFail($data['topping_id']);

        PergerakanStokTopping::create([
            'topping_id' => $topping->id,
            'user_id' => Auth::id(),
            'jenis' => 'masuk',
            'jumlah' => $data['jumlah'],
            'keterangan' => $data['keterangan'] ?: 'Stok masuk',
            'tanggal' => $data['tanggal'],
        ]);

        return back()->with('success', "Stok \"{$topping->nama}\" bertambah {$data['jumlah']}.");
    }

    /**
     * PATCH /admin/stok/topping/{topping}/koreksi
     */
    public function koreksiTopping(Request $request, Topping $topping): RedirectResponse
    {
        $data = $request->validate([
            'stok_baru' => ['required', 'integer', 'min:0'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'stok_baru.required' => 'Stok barunya wajib diisi.',
            'stok_baru.min' => 'Stok tidak boleh minus.',
            'stok_minimum.required' => 'Batas stok menipis wajib diisi.',
        ]);

        DB::transaction(function () use ($topping, $data) {
            $stokSekarang = $topping->stok_saat_ini;
            $selisih = $data['stok_baru'] - $stokSekarang;

            if ($selisih !== 0) {
                PergerakanStokTopping::create([
                    'topping_id' => $topping->id,
                    'user_id' => Auth::id(),
                    'jenis' => $selisih > 0 ? 'masuk' : 'keluar',
                    'jumlah' => abs($selisih),
                    'keterangan' => $data['keterangan'] ?: 'Koreksi stok manual',
                    'tanggal' => now()->toDateString(),
                ]);
            }

            $topping->update(['stok_minimum' => $data['stok_minimum']]);
        });

        return back()->with('success', "Stok \"{$topping->nama}\" berhasil dikoreksi.");
    }

    /**
     * DELETE /admin/stok/topping/{topping}/riwayat
     */
    public function hapusRiwayatTopping(Topping $topping): RedirectResponse
    {
        $topping->pergerakanStok()->delete();

        return back()->with('success', "Riwayat stok \"{$topping->nama}\" berhasil dihapus, stoknya balik ke 0.");
    }

    /**
     * DELETE /admin/stok/topping/riwayat/{pergerakanStokTopping}
     */
    public function hapusEntriMasukTopping(PergerakanStokTopping $pergerakanStokTopping): RedirectResponse
    {
        abort_if($pergerakanStokTopping->jenis !== 'masuk', 403);

        $nama = $pergerakanStokTopping->topping->nama ?? 'topping';
        $pergerakanStokTopping->delete();

        return back()->with('success', "Entri stok masuk \"{$nama}\" berhasil dihapus.");
    }

    // ========================================================
    // ============== STOK BAHAN OPERASIONAL ==================
    // Minyak goreng & gas: admin restock dalam liter/tabung (1-5 per
    // input). Pemakaiannya OTOMATIS: tiap transaksi selesai = 1 sesi
    // goreng (1 liter = 10 sesi, 1 tabung 3 kg = 20 sesi), lihat
    // BahanOperasional::pakaiUntukTransaksi().
    // ========================================================

    /**
     * POST /admin/stok/bahan
     * Restock: pilih bahan, isi 1-5 liter/tabung. Disimpan juga dalam
     * sesi (jumlah x sesi_per_satuan) karena sesi inilah yang dipotong
     * otomatis oleh transaksi.
     */
    public function storeMasukBahan(Request $request): RedirectResponse
    {
        $maks = BahanOperasional::MAKS_SEKALI_MASUK;

        $data = $request->validate([
            'bahan_operasional_id' => ['required', 'exists:bahan_operasional,id'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:' . $maks],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'bahan_operasional_id.required' => 'Pilih bahan operasionalnya dulu.',
            'jumlah.required' => 'Jumlah stok masuk wajib diisi.',
            'jumlah.min' => 'Jumlah minimal 1.',
            'jumlah.max' => "Sekali input maksimal {$maks}. Kalau lebih, input lagi.",
            'tanggal.required' => 'Tanggal wajib diisi.',
        ]);

        $bahan = BahanOperasional::findOrFail($data['bahan_operasional_id']);
        $sesi = (int) $data['jumlah'] * $bahan->sesi_per_satuan;

        PergerakanStokBahanOperasional::create([
            'bahan_operasional_id' => $bahan->id,
            'user_id' => Auth::id(),
            'jenis' => 'masuk',
            'jumlah' => $data['jumlah'],
            'sesi' => $sesi,
            'keterangan' => $data['keterangan'] ?: 'Stok masuk',
            'tanggal' => $data['tanggal'],
        ]);

        return back()->with('success', "Stok \"{$bahan->nama}\" bertambah {$data['jumlah']} {$bahan->satuan} ({$sesi} sesi goreng).");
    }

    /**
     * PATCH /admin/stok/bahan/{bahanOperasional}/koreksi
     * Koreksi sisa stok (dalam SESI) bila ada selisih dengan kenyataan,
     * mis. minyak terbuang atau salah hitung. Selisihnya tercatat di riwayat.
     */
    public function koreksiBahan(Request $request, BahanOperasional $bahanOperasional): RedirectResponse
    {
        $data = $request->validate([
            'sesi_baru' => ['required', 'integer', 'min:0'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'sesi_baru.required' => 'Sisa sesi barunya wajib diisi.',
            'sesi_baru.min' => 'Sisa sesi tidak boleh minus.',
            'stok_minimum.required' => 'Batas menipis wajib diisi.',
        ]);

        DB::transaction(function () use ($bahanOperasional, $data) {
            $selisih = $data['sesi_baru'] - $bahanOperasional->stok_sesi;

            if ($selisih !== 0) {
                PergerakanStokBahanOperasional::create([
                    'bahan_operasional_id' => $bahanOperasional->id,
                    'user_id' => Auth::id(),
                    'jenis' => $selisih > 0 ? 'masuk' : 'keluar',
                    'jumlah' => 0,
                    'sesi' => abs($selisih),
                    'keterangan' => $data['keterangan'] ?: 'Koreksi stok manual',
                    'tanggal' => now()->toDateString(),
                ]);
            }

            $bahanOperasional->update(['stok_minimum' => $data['stok_minimum']]);
        });

        return back()->with('success', "Stok \"{$bahanOperasional->nama}\" berhasil dikoreksi.");
    }

    /**
     * DELETE /admin/stok/bahan/{bahanOperasional}/riwayat
     */
    public function hapusRiwayatBahan(BahanOperasional $bahanOperasional): RedirectResponse
    {
        $bahanOperasional->pergerakanStok()->delete();

        return back()->with('success', "Riwayat stok \"{$bahanOperasional->nama}\" berhasil dihapus, stoknya balik ke 0.");
    }

    /**
     * DELETE /admin/stok/bahan/riwayat/{pergerakanStokBahanOperasional}
     * Hapus satu entri stok masuk (misal salah input).
     */
    public function hapusEntriMasukBahan(PergerakanStokBahanOperasional $pergerakanStokBahanOperasional): RedirectResponse
    {
        abort_if($pergerakanStokBahanOperasional->jenis !== 'masuk', 403);

        $nama = $pergerakanStokBahanOperasional->bahan->nama ?? 'bahan';
        $pergerakanStokBahanOperasional->delete();

        return back()->with('success', "Entri stok masuk \"{$nama}\" berhasil dihapus.");
    }
}
