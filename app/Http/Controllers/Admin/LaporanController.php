<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LaporanController extends Controller
{
    /**
     * GET /admin/laporan
     * Rekap penjualan per periode (harian/mingguan/bulanan/custom),
     * bisa disaring per varian menu & metode bayar.
     *
     * PENTING: halaman ini TIDAK punya tabel/summary sendiri di
     * database. Semua angka dihitung langsung dari tabel transaksis +
     * transaksi_detail — sumber data yang SAMA PERSIS dengan modul
     * Riwayat Transaksi. Jadi begitu ada transaksi baru (atau modul
     * Transaksi Kasir sudah jadi), Laporan ini otomatis ikut ter-update
     * tanpa perlu sinkronisasi manual apa pun.
     */
    public function index(Request $request): View
    {
        [$periode, $mulai, $akhir] = $this->resolvePeriode($request);

        $metodeBayar = $request->filled('metode_bayar') ? $request->input('metode_bayar') : null;
        $menuId = $request->filled('menu_id') ? (int) $request->input('menu_id') : null;

        $data = $this->hitungRekap($mulai, $akhir, $metodeBayar, $menuId);

        // Periode sebelumnya (durasi sama persis) buat badge naik/turun,
        // pola yang sama kayak Dashboard (bandingkan hari ini vs kemarin).
        $jumlahHari = (int) $mulai->diffInDays($akhir) + 1;
        $mulaiSebelumnya = $mulai->copy()->subDays($jumlahHari);
        $akhirSebelumnya = $mulai->copy()->subDay();
        $dataSebelumnya = $this->hitungRekap($mulaiSebelumnya, $akhirSebelumnya, $metodeBayar, $menuId);

        $daftarMenu = Menu::orderBy('nama')->get(['id', 'nama']);

        return view('admin.laporan.index', array_merge($data, [
            'periode' => $periode,
            'tanggalMulai' => $mulai,
            'tanggalAkhir' => $akhir,
            'metodeBayar' => $metodeBayar,
            'menuId' => $menuId,
            'daftarMenu' => $daftarMenu,
            'persenOmzet' => $this->persenPerubahan($data['totalOmzet'], $dataSebelumnya['totalOmzet']),
            'persenTransaksi' => $this->persenPerubahan($data['totalTransaksi'], $dataSebelumnya['totalTransaksi']),
            'persenRataRata' => $this->persenPerubahan($data['rataRata'], $dataSebelumnya['rataRata']),
            'tambahan' => $this->hitungTambahan($mulai, $akhir, $metodeBayar, $menuId),
        ]));
    }

    /**
     * Hitung semua angka rekap untuk satu rentang tanggal. Dipakai 2x
     * per request: sekali untuk periode yang dipilih, sekali lagi untuk
     * periode sebelumnya (perbandingan naik/turun).
     */
    private function hitungRekap(Carbon $mulai, Carbon $akhir, ?string $metodeBayar, ?int $menuId): array
    {
        $transaksiIds = Transaksi::selesai()
            ->whereDate('created_at', '>=', $mulai->toDateString())
            ->whereDate('created_at', '<=', $akhir->toDateString())
            ->when($metodeBayar, fn ($q) => $q->where('metode_bayar', $metodeBayar))
            ->pluck('id');

        $detailQuery = fn () => TransaksiDetail::whereIn('transaksi_id', $transaksiIds)
            ->when($menuId, fn ($q) => $q->where('menu_id', $menuId));

        // Kalau ada filter varian, transaksi yang relevan cuma yang
        // BENERAN mengandung varian itu (bukan semua transaksi periode
        // ini) — supaya Jumlah Transaksi & Metode Bayar ikut akurat.
        $transaksiIdsRelevan = $menuId
            ? $detailQuery()->distinct()->pluck('transaksi_id')
            : $transaksiIds;

        $totalTransaksi = $transaksiIdsRelevan->count();

        // Tanpa filter varian: Total Omzet = total transaksi (sudah
        // termasuk topping berbayar). Dengan filter varian: Total Omzet
        // = subtotal item itu saja (item-level, belum termasuk topping).
        $totalOmzet = $menuId
            ? (int) $detailQuery()->sum('subtotal')
            : (int) Transaksi::whereIn('id', $transaksiIds)->sum('total');

        $rataRata = $totalTransaksi > 0 ? (int) round($totalOmzet / $totalTransaksi) : 0;

        $perVarian = $detailQuery()
            ->select('nama_menu', DB::raw('SUM(jumlah) as qty'), DB::raw('SUM(subtotal) as omzet'))
            ->groupBy('nama_menu')
            ->orderByDesc('qty')
            ->get();

        $totalItemTerjual = (int) $perVarian->sum('qty');

        $perVarian = $perVarian->map(function ($item) use ($totalItemTerjual) {
            $item->qty = (int) $item->qty;
            $item->omzet = (int) $item->omzet;
            $item->persentase = $totalItemTerjual > 0 ? round(($item->qty / $totalItemTerjual) * 100, 1) : 0;

            return $item;
        })->values();

        $perUkuran = $detailQuery()
            ->whereNotNull('nama_ukuran')
            ->select('nama_ukuran', DB::raw('SUM(jumlah) as qty'))
            ->groupBy('nama_ukuran')
            ->orderByDesc('qty')
            ->get()
            ->map(function ($item) use ($totalItemTerjual) {
                $item->qty = (int) $item->qty;
                $item->persentase = $totalItemTerjual > 0 ? round(($item->qty / $totalItemTerjual) * 100, 1) : 0;

                return $item;
            })
            ->values();

        $metodeBayarBreakdown = Transaksi::whereIn('id', $transaksiIdsRelevan)
            ->select('metode_bayar', DB::raw('count(*) as jumlah'))
            ->groupBy('metode_bayar')
            ->orderByDesc('jumlah')
            ->get()
            ->map(function ($item) use ($totalTransaksi) {
                $item->jumlah = (int) $item->jumlah;
                $item->persentase = $totalTransaksi > 0 ? round(($item->jumlah / $totalTransaksi) * 100, 1) : 0;

                return $item;
            })
            ->values();

        // Tren omzet harian sepanjang rentang tanggal (buat grafik garis).
        $trenBaris = DB::table('transaksi_detail')
            ->join('transaksis', 'transaksis.id', '=', 'transaksi_detail.transaksi_id')
            ->whereIn('transaksis.id', $transaksiIds)
            ->when($menuId, fn ($q) => $q->where('transaksi_detail.menu_id', $menuId))
            ->selectRaw('date(transaksis.created_at) as tanggal, SUM(transaksi_detail.subtotal) as omzet')
            ->groupBy('tanggal')
            ->pluck('omzet', 'tanggal');

        $trenTanggal = [];
        $trenOmzet = [];
        $kursor = $mulai->copy();
        while ($kursor->lte($akhir)) {
            $trenTanggal[] = $kursor->translatedFormat('d M');
            $trenOmzet[] = (int) ($trenBaris[$kursor->toDateString()] ?? 0);
            $kursor->addDay();
        }

        return [
            'totalOmzet' => $totalOmzet,
            'totalTransaksi' => $totalTransaksi,
            'rataRata' => $rataRata,
            'totalItemTerjual' => $totalItemTerjual,
            'perVarian' => $perVarian,
            'perUkuran' => $perUkuran,
            'metodeBayarBreakdown' => $metodeBayarBreakdown,
            'trenTanggal' => $trenTanggal,
            'trenOmzet' => $trenOmzet,
            'menuTerlaris' => $perVarian->first(),
        ];
    }

    /**
     * Tentukan rentang tanggal aktif dari query string ?periode=...
     * - harian/mingguan/bulanan: rentang otomatis (dibatasi maksimal
     *   sampai hari ini, tidak pernah nembus ke masa depan).
     * - custom: pakai ?tanggal_mulai= & ?tanggal_akhir=, fallback ke
     *   7 hari terakhir kalau belum diisi.
     *
     * @return array{0: string, 1: Carbon, 2: Carbon}
     */
    private function resolvePeriode(Request $request): array
    {
        $periode = in_array($request->input('periode'), ['harian', 'mingguan', 'bulanan', 'custom'], true)
            ? $request->input('periode')
            : 'bulanan';

        $hariIni = Carbon::today();

        return match ($periode) {
            'harian' => [$periode, $hariIni->copy(), $hariIni->copy()],
            'mingguan' => [$periode, $hariIni->copy()->startOfWeek(), $hariIni->copy()->endOfWeek()->min($hariIni->copy())],
            'bulanan' => [$periode, $hariIni->copy()->startOfMonth(), $hariIni->copy()->endOfMonth()->min($hariIni->copy())],
            default => [
                'custom',
                $request->filled('tanggal_mulai') ? Carbon::parse($request->input('tanggal_mulai')) : $hariIni->copy()->subDays(6),
                $request->filled('tanggal_akhir') ? Carbon::parse($request->input('tanggal_akhir')) : $hariIni->copy(),
            ],
        };
    }

    private function persenPerubahan(int|float $sekarang, int|float $sebelumnya): int
    {
        if ($sebelumnya <= 0) {
            return $sekarang > 0 ? 100 : 0;
        }

        return (int) round((($sekarang - $sebelumnya) / $sebelumnya) * 100);
    }

    /**
     * Analisis tambahan (kinerja kasir, omzet per metode bayar, jam & hari
     * tersibuk) dengan filter periode/metode/varian yang sama dengan rekap
     * utama. Dihitung di PHP supaya tidak bergantung jenis database.
     * Kalau filter varian aktif, nominal (omzet) tidak ditampilkan karena
     * total transaksi mencakup menu lain — yang tampil hanya jumlah transaksi.
     */
    private function hitungTambahan(Carbon $mulai, Carbon $akhir, ?string $metodeBayar, ?int $menuId): array
    {
        $transaksi = Transaksi::selesai()
            ->whereDate('created_at', '>=', $mulai->toDateString())
            ->whereDate('created_at', '<=', $akhir->toDateString())
            ->when($metodeBayar, fn ($q) => $q->where('metode_bayar', $metodeBayar))
            ->get(['id', 'user_id', 'metode_bayar', 'total', 'created_at']);

        if ($menuId) {
            $idsMenu = TransaksiDetail::whereIn('transaksi_id', $transaksi->pluck('id'))
                ->where('menu_id', $menuId)
                ->distinct()
                ->pluck('transaksi_id')
                ->all();
            $transaksi = $transaksi->whereIn('id', $idsMenu)->values();
        }

        $namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        $perHari = [];
        foreach ($namaHari as $nomor => $nama) {
            $perHari[$nomor] = ['nama' => $nama, 'jumlah' => 0];
        }
        $perJam = array_fill(0, 24, 0);

        foreach ($transaksi as $trx) {
            $perJam[(int) $trx->created_at->format('G')]++;
            $perHari[(int) $trx->created_at->isoWeekday()]['jumlah']++;
        }

        $puncakJam = max($perJam);
        $jamAktif = array_keys(array_filter($perJam));
        $puncakHari = max(array_column($perHari, 'jumlah'));

        $kinerja = [];
        $perMetodeNominal = [];

        if (! $menuId) {
            $namaKasir = User::whereIn('id', $transaksi->pluck('user_id')->unique()->all())->pluck('name', 'id');

            $kinerja = $transaksi->groupBy('user_id')->map(function ($kelompok, $userId) use ($namaKasir) {
                $jumlah = $kelompok->count();
                $omzet = (int) $kelompok->sum('total');

                return [
                    'nama' => $namaKasir->get($userId, 'Akun kasir dihapus'),
                    'jumlah' => $jumlah,
                    'omzet' => $omzet,
                    'rata_rata' => $jumlah > 0 ? (int) round($omzet / $jumlah) : 0,
                ];
            })->sortByDesc('omzet')->values()->all();

            $labelMetode = ['cash' => 'Tunai', 'qris' => 'QRIS', 'paylater' => 'PayLater'];
            foreach ($labelMetode as $kode => $label) {
                $kelompok = $transaksi->where('metode_bayar', $kode);
                $perMetodeNominal[] = [
                    'label' => $label,
                    'jumlah' => $kelompok->count(),
                    'nominal' => (int) $kelompok->sum('total'),
                ];
            }
        }

        return [
            'tampilkanNominal' => ! $menuId,
            'kinerja' => $kinerja,
            'omzetKasirMax' => $kinerja ? max(1, max(array_column($kinerja, 'omzet'))) : 1,
            'perMetodeNominal' => $perMetodeNominal,
            'perJam' => $perJam,
            'puncakJam' => $puncakJam,
            'jamTersibuk' => $puncakJam > 0 ? array_search($puncakJam, $perJam, true) : null,
            'jamMulai' => $jamAktif ? min($jamAktif) : null,
            'jamAkhir' => $jamAktif ? max($jamAktif) : null,
            'perHari' => array_values($perHari),
            'puncakHari' => $puncakHari,
        ];
    }
}
