<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\PergerakanStok;
use App\Models\Transaksi;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Dashboard sekarang sudah baca data ASLI dari database (bukan
     * angka contoh lagi). Karena modul "Transaksi" & "Manajemen Stok"
     * belum dibangun, tabel transaksis & pergerakan_stok memang masih
     * kosong untuk saat ini — jadi Omzet, Jumlah Transaksi, Grafik,
     * Transaksi Terbaru, dan Stok Menipis akan realistis tampil 0/kosong
     * dulu. Begitu 2 modul itu jadi (kasir menyimpan transaksi & stok
     * masuk), angka-angka ini otomatis terisi tanpa perlu ubah view ini.
     *
     * Yang SUDAH langsung nyambung ke data asli sekarang: Total Menu
     * (dari Master Menu yang sudah kamu buat).
     */
    public function index(): View
    {
        $hariIni = Carbon::today();
        $kemarin = Carbon::yesterday();

        $omzetHariIni = (int) Transaksi::selesai()->whereDate('created_at', $hariIni)->sum('total');
        $omzetKemarin = (int) Transaksi::selesai()->whereDate('created_at', $kemarin)->sum('total');
        $omzetPersen = $this->persenPerubahan($omzetHariIni, $omzetKemarin);

        $jumlahTransaksi = Transaksi::selesai()->whereDate('created_at', $hariIni)->count();
        $transaksiKemarin = Transaksi::selesai()->whereDate('created_at', $kemarin)->count();
        $transaksiPersen = $this->persenPerubahan($jumlahTransaksi, $transaksiKemarin);

        $totalMenu = Menu::count();

        [$stokMenipis, $stokMenipisCount] = $this->hitungStokMenipis();

        $transaksiTerbaru = Transaksi::selesai()
            ->with('kasir')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Transaksi $trx) => (object) [
                'no' => $trx->no_transaksi,
                'tanggal' => $trx->created_at->translatedFormat('d M Y H:i'),
                'kasir' => $trx->kasir->name ?? '-',
                'total' => $trx->total,
                'metode' => strtoupper($trx->metode_bayar),
                'status' => ucfirst($trx->status),
            ]);

        // Grafik 7 hari terakhir (termasuk hari ini).
        $grafikTanggal = [];
        $grafikOmzet = [];
        $grafikTransaksi = [];

        for ($i = 6; $i >= 0; $i--) {
            $tanggal = Carbon::today()->subDays($i);
            $grafikTanggal[] = $tanggal->translatedFormat('d M');
            $grafikOmzet[] = (int) Transaksi::selesai()->whereDate('created_at', $tanggal)->sum('total');
            $grafikTransaksi[] = Transaksi::selesai()->whereDate('created_at', $tanggal)->count();
        }

        return view('dashboard', compact(
            'omzetHariIni',
            'omzetPersen',
            'jumlahTransaksi',
            'transaksiPersen',
            'totalMenu',
            'stokMenipis',
            'stokMenipisCount',
            'transaksiTerbaru',
            'grafikTanggal',
            'grafikOmzet',
            'grafikTransaksi',
        ));
    }

    private function persenPerubahan(int|float $sekarang, int|float $sebelumnya): int
    {
        if ($sebelumnya <= 0) {
            return $sekarang > 0 ? 100 : 0;
        }

        return (int) round((($sekarang - $sebelumnya) / $sebelumnya) * 100);
    }

    /**
     * Stok berjalan = SUM(masuk) - SUM(keluar) per menu, HANYA untuk
     * menu yang sudah pernah punya pergerakan stok sama sekali (supaya
     * menu yang memang belum dikelola stoknya tidak ikut dicap "Habis").
     */
    private function hitungStokMenipis(): array
    {
        $menuIdsDikelola = PergerakanStok::query()->distinct()->pluck('menu_id');

        if ($menuIdsDikelola->isEmpty()) {
            return [collect(), 0];
        }

        $menus = Menu::whereIn('id', $menuIdsDikelola)->get();

        $daftar = $menus->map(function (Menu $menu) {
            $masuk = PergerakanStok::where('menu_id', $menu->id)->where('jenis', 'masuk')->sum('jumlah');
            $keluar = PergerakanStok::where('menu_id', $menu->id)->where('jenis', 'keluar')->sum('jumlah');
            $stok = $masuk - $keluar;

            $status = match (true) {
                $stok <= 0 => 'habis',
                $stok <= $menu->stok_minimum => 'menipis',
                default => 'aman',
            };

            return (object) [
                'nama' => $menu->nama,
                'stok' => $stok,
                'status' => $status,
            ];
        })
            ->filter(fn ($item) => $item->status !== 'aman')
            ->sortBy('stok')
            ->values();

        return [$daftar->take(5), $daftar->count()];
    }
}
