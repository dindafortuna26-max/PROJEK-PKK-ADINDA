<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Topping;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BerandaController extends Controller
{
    /**
     * GET /beranda
     * Dashboard Kasir: sapaan, ringkasan penjualan HARI INI milik kasir
     * yang login, grafik 7 hari, menu terlaris, peringatan stok, dan
     * transaksi terakhir. Semua angka read-only (tidak mengubah data).
     */
    public function index(Request $request): View
    {
        // Kalau yang login ternyata Admin, jangan sampai nyasar ke sini.
        if ($request->user()->role === 'admin') {
            return redirect()->route('dashboard');
        }

        $kasir = $request->user();
        $hariIni = today();

        $trxHariIni = fn () => Transaksi::query()
            ->where('user_id', $kasir->id)
            ->selesai()
            ->whereDate('created_at', $hariIni);

        $ringkasan = [
            'jumlah' => $trxHariIni()->count(),
            'omzet' => (int) $trxHariIni()->sum('total'),
            'cash' => (int) $trxHariIni()->where('metode_bayar', 'cash')->sum('total'),
            'qris' => (int) $trxHariIni()->where('metode_bayar', 'qris')->sum('total'),
            'paylater' => (int) $trxHariIni()->where('metode_bayar', 'paylater')->sum('total'),
        ];
        $ringkasan['rata_rata'] = $ringkasan['jumlah'] > 0
            ? (int) round($ringkasan['omzet'] / $ringkasan['jumlah'])
            : 0;

        $baseDetail = fn () => TransaksiDetail::query()
            ->whereHas('transaksi', function ($q) use ($kasir, $hariIni) {
                $q->where('user_id', $kasir->id)->where('status', 'selesai')->whereDate('created_at', $hariIni);
            });

        $ringkasan['porsi'] = (int) $baseDetail()->sum('jumlah');

        $terlaris = $baseDetail()
            ->select('nama_menu', DB::raw('SUM(jumlah) as total_porsi'))
            ->groupBy('nama_menu')
            ->orderByDesc('total_porsi')
            ->limit(4)
            ->get();

        // Omzet 7 hari terakhir (termasuk hari ini) — buat grafik batang mini.
        $awal = today()->subDays(6);
        $perHari = Transaksi::query()
            ->where('user_id', $kasir->id)
            ->selesai()
            ->whereDate('created_at', '>=', $awal)
            ->selectRaw('DATE(created_at) as tgl, SUM(total) as total')
            ->groupBy('tgl')
            ->pluck('total', 'tgl');

        $grafik = collect(range(0, 6))->map(function ($i) use ($awal, $perHari) {
            $tgl = $awal->copy()->addDays($i);

            return [
                'label' => $tgl->copy()->locale('id')->translatedFormat('D'),
                'tanggal' => $tgl->toDateString(),
                'total' => (int) ($perHari[$tgl->toDateString()] ?? 0),
                'hari_ini' => $tgl->isToday(),
            ];
        });

        // Peringatan stok (menu + bumbu/topping aktif) memakai accessor
        // status_stok yang sama dengan halaman Cek Stok.
        $peringatan = collect();
        Menu::where('is_active', true)->get()->each(function ($m) use ($peringatan) {
            if (in_array($m->status_stok, ['habis', 'menipis'], true)) {
                $peringatan->push(['nama' => $m->nama, 'status' => $m->status_stok, 'stok' => $m->stok_saat_ini, 'jenis' => 'Menu']);
            }
        });
        Topping::aktif()->get()->each(function ($t) use ($peringatan) {
            if (in_array($t->status_stok, ['habis', 'menipis'], true)) {
                $peringatan->push(['nama' => $t->nama, 'status' => $t->status_stok, 'stok' => $t->stok_saat_ini, 'jenis' => $t->jenis_label]);
            }
        });
        $peringatan = $peringatan->sortBy(fn ($p) => $p['status'] === 'habis' ? 0 : 1)->values();

        $terakhir = Transaksi::query()
            ->where('user_id', $kasir->id)
            ->withCount('detail')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('kasir.beranda', [
            'kasir' => $kasir,
            'ringkasan' => $ringkasan,
            'terlaris' => $terlaris,
            'grafik' => $grafik,
            'peringatan' => $peringatan,
            'terakhir' => $terakhir,
        ]);
    }
}
