<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\BahanOperasional;
use App\Models\Menu;
use App\Models\PergerakanStok;
use App\Models\PergerakanStokTopping;
use App\Models\Topping;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StokController extends Controller
{
    /**
     * GET /stok (khusus Kasir)
     *
     * Versi READ-ONLY dari Manajemen Stok admin: kasir cuma boleh LIHAT
     * stok berjalan (menu & topping/bumbu) buat ngecek sebelum jualan,
     * tanpa tombol Tambah/Edit/Hapus sama sekali. Logika hitung stoknya
     * sengaja disamain persis dengan App\Http\Controllers\Admin\StokController
     * supaya angkanya selalu konsisten dengan yang dilihat admin.
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

        // ===================== STOK TOPPING & BUMBU (read-only) =====================
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

        // ===================== BAHAN OPERASIONAL (minyak goreng, gas) =====================
        // READ ONLY juga: stok minyak & gas berkurang OTOMATIS 1 sesi tiap
        // transaksi selesai (lihat BahanOperasional::pakaiUntukTransaksi),
        // jadi kasir cukup memantau, tidak ada tombol konfirmasi lagi.
        $dataStokBahan = BahanOperasional::query()
            ->where('is_active', true)
            ->when($request->filled('cari'), function ($query) use ($request) {
                $query->where('nama', 'like', '%' . $request->input('cari') . '%');
            })
            ->orderBy('nama')
            ->get()
            ->map(fn (BahanOperasional $bahan) => (object) [
                'bahan' => $bahan,
                'stok' => $bahan->stok_sesi,
                'status' => $bahan->status_stok,
                'terakhir_masuk' => $bahan->pergerakanStok()
                    ->where('jenis', 'masuk')
                    ->orderByDesc('tanggal')
                    ->orderByDesc('id')
                    ->first(),
            ]);

        // Ringkasan menu + topping/bumbu saja (bahan operasional punya
        // kartu peringatan sendiri di tabnya supaya angkanya tidak campur).
        $ringkasan = [
            'aman' => $dataStok->where('status', 'aman')->sum('stok')
                + $dataStokTopping->where('status', 'aman')->sum('stok'),
            'menipis' => $dataStok->where('status', 'menipis')->sum('stok')
                + $dataStokTopping->where('status', 'menipis')->sum('stok'),
            'habis' => $dataStok->where('status', 'habis')->count()
                + $dataStokTopping->where('status', 'habis')->count(),
        ];
        $ringkasan['total'] = $ringkasan['aman'] + $ringkasan['menipis'];

        $perhatianBahan = $dataStokBahan->whereIn('status', ['habis', 'menipis'])->count();

        return view('kasir.stok.index', [
            'dataStok' => $dataStokPaginated,
            'ringkasan' => $ringkasan,
            'dataStokTopping' => $dataStokToppingPaginated,
            'dataStokBahan' => $dataStokBahan,
            'perhatianBahan' => $perhatianBahan,
        ]);
    }
}
