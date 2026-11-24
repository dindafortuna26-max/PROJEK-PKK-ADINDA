<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\PergerakanStok;
use App\Models\User;
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
     * Stok berjalan = SUM(masuk) - SUM(keluar) PER MENU PER KASIR, HANYA
     * untuk pasangan menu-kasir yang sudah pernah diisi stok masuk (supaya
     * yang memang belum dikelola tidak ikut dicap "Habis"). Nama kasir
     * ditulis di belakang nama menu.
     */
    private function hitungStokMenipis(): array
    {
        $baris = PergerakanStok::query()
            ->whereNotNull('kasir_id')
            ->selectRaw('menu_id, kasir_id, jenis, SUM(jumlah) as total')
            ->groupBy('menu_id', 'kasir_id', 'jenis')
            ->get()
            ->groupBy(fn ($r) => $r->menu_id . '-' . $r->kasir_id);

        if ($baris->isEmpty()) {
            return [collect(), 0];
        }

        $menus = Menu::whereIn('id', $baris->map(fn ($g) => $g->first()->menu_id)->unique())->get()->keyBy('id');
        $kasirs = User::whereIn('id', $baris->map(fn ($g) => $g->first()->kasir_id)->unique())->pluck('name', 'id');

        $daftar = $baris->map(function ($grup) use ($menus, $kasirs) {
            $menu = $menus->get($grup->first()->menu_id);
            $masuk = $grup->firstWhere('jenis', 'masuk');

            if (! $menu || ! $masuk) {
                return null; // belum dikelola untuk kasir ini
            }

            $stok = (int) $masuk->total - (int) $grup->firstWhere('jenis', 'keluar')?->total;

            $status = match (true) {
                $stok <= 0 => 'habis',
                $stok <= $menu->stok_minimum => 'menipis',
                default => 'aman',
            };

            return (object) [
                'nama' => $menu->nama . ' (' . ($kasirs[$grup->first()->kasir_id] ?? 'Kasir') . ')',
                'stok' => $stok,
                'status' => $status,
            ];
        })
            ->filter(fn ($item) => $item && $item->status !== 'aman')
            ->sortBy('stok')
            ->values();

        return [$daftar->take(5), $daftar->count()];
    }
}
