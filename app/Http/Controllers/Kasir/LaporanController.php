<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * GET /laporan-saya
 * Rekap penjualan MILIK KASIR YANG LOGIN (read-only): ringkasan omzet,
 * rincian per metode bayar (buat cocokkan laci kasir), menu terlaris,
 * jam tersibuk, dan tren harian. Semua query dikunci ke user_id kasir
 * yang login, jadi tidak pernah menampilkan data kasir lain.
 */
class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        $hariIni = Carbon::today();

        $periode = in_array($request->input('periode'), ['hari-ini', 'kemarin', '7-hari', 'bulan-ini', 'custom'], true)
            ? $request->input('periode')
            : 'hari-ini';

        switch ($periode) {
            case 'kemarin':
                $mulai = $hariIni->copy()->subDay();
                $akhir = $mulai->copy();
                break;
            case '7-hari':
                $mulai = $hariIni->copy()->subDays(6);
                $akhir = $hariIni->copy();
                break;
            case 'bulan-ini':
                $mulai = $hariIni->copy()->startOfMonth();
                $akhir = $hariIni->copy();
                break;
            case 'custom':
                $mulai = $this->parseTanggal($request->input('tanggal_mulai'), $hariIni->copy());
                $akhir = $this->parseTanggal($request->input('tanggal_akhir'), $hariIni->copy());
                if ($akhir->lt($mulai)) {
                    [$mulai, $akhir] = [$akhir, $mulai];
                }
                if ($mulai->diffInDays($akhir) > 365) {
                    $mulai = $akhir->copy()->subDays(365);
                }
                break;
            default:
                $mulai = $hariIni->copy();
                $akhir = $hariIni->copy();
        }

        $base = fn () => Transaksi::selesai()
            ->where('user_id', Auth::id())
            ->whereDate('created_at', '>=', $mulai->toDateString())
            ->whereDate('created_at', '<=', $akhir->toDateString());

        $transaksiIds = $base()->pluck('id');
        $jumlahTransaksi = $transaksiIds->count();
        $omzet = (int) $base()->sum('total');
        $rataRata = $jumlahTransaksi > 0 ? (int) round($omzet / $jumlahTransaksi) : 0;
        $itemTerjual = (int) TransaksiDetail::whereIn('transaksi_id', $transaksiIds)->sum('jumlah');

        // ---- rincian per metode bayar ----
        $labelMetode = ['cash' => 'Tunai', 'qris' => 'QRIS', 'paylater' => 'PayLater'];
        $iconMetode = ['cash' => 'bi-cash-stack', 'qris' => 'bi-qr-code-scan', 'paylater' => 'bi-clock-history'];

        $barisMetode = $base()
            ->select('metode_bayar', DB::raw('COUNT(*) as jumlah'), DB::raw('SUM(total) as nominal'))
            ->groupBy('metode_bayar')
            ->get()
            ->keyBy('metode_bayar');

        $perMetode = [];
        foreach ($labelMetode as $kode => $label) {
            $baris = $barisMetode->get($kode);
            $nominal = $baris ? (int) $baris->nominal : 0;
            $perMetode[] = [
                'kode' => $kode,
                'label' => $label,
                'icon' => $iconMetode[$kode],
                'jumlah' => $baris ? (int) $baris->jumlah : 0,
                'nominal' => $nominal,
                'persen' => $omzet > 0 ? round(($nominal / $omzet) * 100, 1) : 0,
            ];
        }

        $tunaiDiterima = (int) $base()->where('metode_bayar', 'cash')->sum('uang_diterima');
        $kembalian = (int) $base()->where('metode_bayar', 'cash')->sum('kembalian');

        // ---- menu terlaris (5 teratas) ----
        $terlaris = TransaksiDetail::whereIn('transaksi_id', $transaksiIds)
            ->select('nama_menu', DB::raw('SUM(jumlah) as qty'))
            ->groupBy('nama_menu')
            ->orderByDesc('qty')
            ->limit(5)
            ->get()
            ->map(fn ($baris) => ['nama' => $baris->nama_menu, 'qty' => (int) $baris->qty])
            ->values();
        $qtyMax = max(1, (int) ($terlaris->max('qty') ?? 0));

        // ---- jam tersibuk (dihitung di PHP supaya tidak bergantung jenis database) ----
        $perJam = array_fill(0, 24, 0);
        foreach ($base()->get(['created_at']) as $trx) {
            $perJam[(int) $trx->created_at->format('G')]++;
        }
        $puncakJam = max($perJam);
        $jamTersibuk = $puncakJam > 0 ? array_search($puncakJam, $perJam, true) : null;
        $jamAktif = array_keys(array_filter($perJam));
        $jamMulai = $jamAktif ? min($jamAktif) : null;
        $jamAkhir = $jamAktif ? max($jamAktif) : null;

        // ---- tren harian (hanya kalau rentangnya lebih dari 1 hari & tidak terlalu panjang) ----
        $tren = [];
        $jumlahHari = (int) $mulai->diffInDays($akhir) + 1;
        if ($jumlahHari > 1 && $jumlahHari <= 31) {
            $perTanggal = $base()->get(['created_at', 'total'])
                ->groupBy(fn ($trx) => $trx->created_at->toDateString());

            $kursor = $mulai->copy();
            while ($kursor->lte($akhir)) {
                $kelompok = $perTanggal->get($kursor->toDateString());
                $tren[] = [
                    'label' => $kursor->translatedFormat('d M'),
                    'total' => $kelompok ? (int) $kelompok->sum('total') : 0,
                    'jumlah' => $kelompok ? $kelompok->count() : 0,
                ];
                $kursor->addDay();
            }
        }
        $trenMax = $tren ? max(1, max(array_column($tren, 'total'))) : 1;

        $terbesar = $base()->orderByDesc('total')->first(['id', 'no_transaksi', 'total', 'created_at']);

        return view('kasir.laporan.index', [
            'periode' => $periode,
            'mulai' => $mulai,
            'akhir' => $akhir,
            'jumlahTransaksi' => $jumlahTransaksi,
            'omzet' => $omzet,
            'rataRata' => $rataRata,
            'itemTerjual' => $itemTerjual,
            'perMetode' => $perMetode,
            'tunaiDiterima' => $tunaiDiterima,
            'kembalian' => $kembalian,
            'terlaris' => $terlaris,
            'qtyMax' => $qtyMax,
            'perJam' => $perJam,
            'puncakJam' => $puncakJam,
            'jamTersibuk' => $jamTersibuk,
            'jamMulai' => $jamMulai,
            'jamAkhir' => $jamAkhir,
            'tren' => $tren,
            'trenMax' => $trenMax,
            'terbesar' => $terbesar,
        ]);
    }

    private function parseTanggal(?string $nilai, Carbon $cadangan): Carbon
    {
        if (! $nilai) {
            return $cadangan;
        }

        try {
            return Carbon::parse($nilai)->startOfDay();
        } catch (\Throwable $e) {
            return $cadangan;
        }
    }
}
