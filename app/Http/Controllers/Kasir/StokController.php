<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\BahanOperasional;
use App\Models\Menu;
use App\Models\PenerimaanBahanOperasional;
use App\Models\PergerakanStok;
use App\Models\PergerakanStokTopping;
use App\Models\Topping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\PergerakanStokBahanOperasional;
use App\Models\PermintaanBahanOperasional;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StokController extends Controller
{
    /**
     * GET /stok (khusus Kasir)
     *
     * Versi READ-ONLY dari Manajemen Stok admin: kasir cuma boleh LIHAT
     * stok berjalan (menu, topping/bumbu, kemasan) buat ngecek sebelum jualan.
     * Yang tampil HANYA stok MILIK kasir yang login (stok per kasir),
     * tanpa tombol Tambah/Edit/Hapus sama sekali. Logika hitung stoknya
     * sengaja disamain persis dengan App\Http\Controllers\Admin\StokController
     * supaya angkanya selalu konsisten dengan yang dilihat admin.
     */
    public function index(Request $request): View
    {
        $kasirId = (int) Auth::id();

        $menus = Menu::query()
            ->where('is_active', true) // menu nonaktif tidak ikut di Cek Stok
            ->with('kategori')
            ->when($request->filled('cari'), function ($query) use ($request) {
                $query->where('nama', 'like', '%' . $request->input('cari') . '%');
            })
            ->orderBy('nama')
            ->get();

        $agregat = PergerakanStok::query()
            ->where('kasir_id', $kasirId)
            ->selectRaw('menu_id, jenis, SUM(jumlah) as total')
            ->groupBy('menu_id', 'jenis')
            ->get()
            ->groupBy('menu_id');

        $terakhirMasuk = PergerakanStok::query()
            ->where('kasir_id', $kasirId)
            ->where('jenis', 'masuk')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->groupBy('menu_id');

        $dataStok = $menus->map(function (Menu $menu) use ($agregat, $terakhirMasuk) {
            $baris = $agregat->get($menu->id, collect());
            $dikelola = $baris->firstWhere('jenis', 'masuk') !== null;
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

        // ===================== STOK TOPPING & BUMBU (read-only) =====================
        $toppings = Topping::query()
            ->where('is_active', true) // topping/bumbu nonaktif tidak ikut
            ->when($request->filled('cari'), function ($query) use ($request) {
                $query->where('nama', 'like', '%' . $request->input('cari') . '%');
            })
            ->orderBy('nama')
            ->get();

        $agregatTopping = PergerakanStokTopping::query()
            ->where('kasir_id', $kasirId)
            ->selectRaw('topping_id, jenis, SUM(jumlah) as total')
            ->groupBy('topping_id', 'jenis')
            ->get()
            ->groupBy('topping_id');

        $terakhirMasukTopping = PergerakanStokTopping::query()
            ->where('kasir_id', $kasirId)
            ->where('jenis', 'masuk')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->groupBy('topping_id');

        $dataStokTopping = $toppings->map(function (Topping $topping) use ($agregatTopping, $terakhirMasukTopping) {
            $baris = $agregatTopping->get($topping->id, collect());
            $dikelola = $baris->firstWhere('jenis', 'masuk') !== null;
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

        // ===================== KEMASAN (cup / box) =====================
        // Kemasan berkurang OTOMATIS tiap transaksi sesuai ukuran porsi, jadi
        // diperlakukan persis seperti menu: Aman / Menipis / Habis, tanpa
        // tombol "Minta ke Admin". Stok = SUM(masuk) - SUM(keluar) milik kasir ini.
        $kemasanList = BahanOperasional::query()
            ->aktif()
            ->kemasan()
            ->with('ukuran')
            ->when($request->filled('cari'), function ($query) use ($request) {
                $query->where('nama', 'like', '%' . $request->input('cari') . '%');
            })
            ->orderBy('nama')
            ->get();

        $agregatKemasan = PergerakanStokBahanOperasional::query()
            ->where('kasir_id', $kasirId)
            ->selectRaw('bahan_operasional_id, jenis, SUM(jumlah) as total')
            ->groupBy('bahan_operasional_id', 'jenis')
            ->get()
            ->groupBy('bahan_operasional_id');

        $terakhirMasukKemasan = PergerakanStokBahanOperasional::query()
            ->where('kasir_id', $kasirId)
            ->where('jenis', 'masuk')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->groupBy('bahan_operasional_id');

        $dataStokKemasan = $kemasanList->map(function (BahanOperasional $kemasan) use ($agregatKemasan, $terakhirMasukKemasan) {
            $baris = $agregatKemasan->get($kemasan->id, collect());
            $dikelola = $baris->firstWhere('jenis', 'masuk') !== null;
            $masuk = (int) $baris->firstWhere('jenis', 'masuk')?->total;
            $keluar = (int) $baris->firstWhere('jenis', 'keluar')?->total;
            $stok = max(0, $masuk - $keluar);

            $status = match (true) {
                ! $dikelola => 'belum_dikelola',
                $stok <= 0 => 'habis',
                $stok <= (int) $kemasan->stok_minimum => 'menipis',
                default => 'aman',
            };

            return (object) [
                'kemasan' => $kemasan,
                'stok' => $stok,
                'status' => $status,
                'terakhir_masuk' => $terakhirMasukKemasan->get($kemasan->id)?->first(),
            ];
        });

        $perPageKemasan = 10;
        $halamanKemasan = (int) $request->input('kemasan_page', 1);
        $dataStokKemasanPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $dataStokKemasan->forPage($halamanKemasan, $perPageKemasan)->values(),
            $dataStokKemasan->count(),
            $perPageKemasan,
            $halamanKemasan,
            ['path' => $request->url(), 'pageName' => 'kemasan_page', 'query' => $request->query()]
        );

        // ===================== BAHAN HABIS-PAKAI (minyak goreng, gas) =====================
        // MANUAL & per kasir: jatahnya diberikan admin (mis. 1 tabung gas +
        // 5 liter minyak per hari) dan TIDAK berkurang otomatis tiap
        // transaksi. Kalau habis, kasir menekan "Minta ke Admin".
        // Kemasan TIDAK ikut di sini (sudah punya tab sendiri, gaya menu).
        $dataStokBahan = BahanOperasional::query()
            ->where('is_active', true)
            ->habisPakai()
            ->when($request->filled('cari'), function ($query) use ($request) {
                $query->where('nama', 'like', '%' . $request->input('cari') . '%');
            })
            ->orderBy('nama')
            ->get()
            ->map(fn (BahanOperasional $bahan) => (object) [
                'bahan' => $bahan,
                'stok' => $bahan->stokUntukKasir($kasirId),
                'status' => $bahan->statusUntukKasir($kasirId),
                'terakhir_masuk' => $bahan->stokMasukTerakhirUntukKasir($kasirId),
                'permintaan' => $bahan->permintaanMenungguUntukKasir($kasirId),
                // Kiriman dari admin yang menunggu jawaban "Sudah / Belum" kasir ini.
                'penerimaan' => $bahan->penerimaanMenungguUntukKasir($kasirId),
            ]);

        // Ringkasan menu + topping/bumbu + kemasan (ketiganya otomatis & punya
        // status Aman/Menipis/Habis). Minyak & gas punya kartu peringatan sendiri.
        $ringkasan = [
            'aman' => $dataStok->where('status', 'aman')->sum('stok')
                + $dataStokTopping->where('status', 'aman')->sum('stok')
                + $dataStokKemasan->where('status', 'aman')->sum('stok'),
            'menipis' => $dataStok->where('status', 'menipis')->sum('stok')
                + $dataStokTopping->where('status', 'menipis')->sum('stok')
                + $dataStokKemasan->where('status', 'menipis')->sum('stok'),
            'habis' => $dataStok->where('status', 'habis')->count()
                + $dataStokTopping->where('status', 'habis')->count()
                + $dataStokKemasan->where('status', 'habis')->count(),
        ];
        $ringkasan['total'] = $ringkasan['aman'] + $ringkasan['menipis'];

        // Badge di tab: bahan yang sudah habis, sudah diminta ke admin, atau
        // ada kiriman admin yang menunggu konfirmasi "Sudah / Belum".
        $perhatianBahan = $dataStokBahan->filter(
            fn ($i) => $i->status === 'habis' || $i->permintaan !== null || $i->penerimaan->isNotEmpty()
        )->count();

        return view('kasir.stok.index', [
            'dataStok' => $dataStokPaginated,
            'ringkasan' => $ringkasan,
            'dataStokTopping' => $dataStokToppingPaginated,
            'dataStokKemasan' => $dataStokKemasanPaginated,
            'dataStokBahan' => $dataStokBahan,
            'perhatianBahan' => $perhatianBahan,
        ]);
    }

    /**
     * POST /stok/bahan/{bahanOperasional}/minta (khusus Kasir)
     * Tombol "Minta ke Admin" (hanya minyak / gas): kasir melapor jatahnya sudah
     * habis. Sisa stok kasir ini dinolkan, permintaan muncul di notifikasi
     * admin, dan otomatis tertutup begitu admin memberi jatah baru.
     */
    public function mintaBahan(BahanOperasional $bahanOperasional): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'kasir', 403);

        if (! $bahanOperasional->is_active) {
            return back()->with('error', "\"{$bahanOperasional->nama}\" sedang dinonaktifkan.");
        }

        // Kemasan berkurang otomatis & stoknya dipantau seperti menu, jadi
        // tidak ada alur "Minta ke Admin" untuknya (hanya minyak & gas).
        if ($bahanOperasional->is_kemasan) {
            return back()->with('error', "\"{$bahanOperasional->nama}\" adalah kemasan yang berkurang otomatis, tidak perlu dilaporkan habis. Cukup pantau stoknya di tab Kemasan.");
        }

        $kasirId = (int) $user->id;

        // Ada kiriman admin yang belum dikonfirmasi: jawab dulu, jangan minta lagi.
        if ($bahanOperasional->penerimaanMenungguUntukKasir($kasirId)->isNotEmpty()) {
            return back()->with('error', "Admin sudah mengirim {$bahanOperasional->nama}. Jawab dulu \"Sudah\" atau \"Belum\" di kartunya.");
        }

        if ($bahanOperasional->permintaanMenungguUntukKasir($kasirId)) {
            return back()->with('error', "Permintaan {$bahanOperasional->nama} kamu masih menunggu admin.");
        }

        DB::transaction(function () use ($bahanOperasional, $kasirId) {
            $sisa = $bahanOperasional->stokUntukKasir($kasirId);

            // Anggap jatah yang masih tercatat sudah terpakai semua.
            if ($sisa > 0) {
                PergerakanStokBahanOperasional::create([
                    'bahan_operasional_id' => $bahanOperasional->id,
                    'user_id' => $kasirId,
                    'kasir_id' => $kasirId,
                    'jenis' => 'keluar',
                    'jumlah' => $sisa,
                    'sesi' => 0,
                    'keterangan' => 'Dilaporkan habis oleh kasir',
                    'tanggal' => now()->toDateString(),
                ]);
            }

            PermintaanBahanOperasional::create([
                'bahan_operasional_id' => $bahanOperasional->id,
                'kasir_id' => $kasirId,
            ]);
        });

        return back()->with('success', "Permintaan {$bahanOperasional->nama} terkirim ke admin.");
    }

    /**
     * POST /stok/bahan/{bahanOperasional}/konfirmasi (khusus Kasir)
     * Minyak / gas dari admin TIDAK langsung masuk stok. Kasir mengecek dulu
     * apakah barangnya benar sudah diterima, lalu menjawab:
     *  - jawaban=sudah : stok kasir ini terisi (semua kiriman yang menunggu).
     *  - jawaban=belum : kiriman tetap menunggu, admin bisa melihat bahwa
     *                    kasir belum menerimanya.
     */
    public function konfirmasiTerimaBahan(Request $request, BahanOperasional $bahanOperasional): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'kasir', 403);

        $data = $request->validate([
            'jawaban' => ['required', 'in:sudah,belum'],
        ]);

        // Hanya kiriman MILIK kasir ini untuk bahan ini yang bisa dijawab.
        $menunggu = $bahanOperasional->penerimaanMenungguUntukKasir((int) $user->id);

        if ($menunggu->isEmpty()) {
            return back()->with('error', "Tidak ada kiriman {$bahanOperasional->nama} yang menunggu konfirmasimu.");
        }

        if ($data['jawaban'] === 'sudah') {
            $total = (int) $menunggu->sum('jumlah');

            DB::transaction(function () use ($menunggu) {
                foreach ($menunggu as $penerimaan) {
                    $penerimaan->konfirmasiDiterima();
                }
            });

            return back()->with('success', "{$bahanOperasional->nama} {$bahanOperasional->labelJumlah($total)} sudah masuk ke stokmu.");
        }

        PenerimaanBahanOperasional::query()
            ->whereIn('id', $menunggu->pluck('id'))
            ->whereNull('belum_pada')
            ->update(['belum_pada' => now()]);

        return back()->with('success', "Oke, {$bahanOperasional->nama} belum masuk stokmu. Cek dulu ke Admin, lalu jawab \"Sudah\" kalau sudah diterima.");
    }
}
