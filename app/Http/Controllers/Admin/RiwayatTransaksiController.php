<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RiwayatTransaksiController extends Controller
{
    /**
     * GET /admin/riwayat-transaksi
     * Tabel riwayat semua transaksi (lintas kasir) + filter: cari
     * (no transaksi / nama kasir), kasir, tanggal, metode bayar.
     */
    public function index(Request $request): View
    {
        $transaksis = Transaksi::query()
            ->with('kasir')
            ->when($request->filled('cari'), function ($query) use ($request) {
                $cari = $request->input('cari');
                $query->where(function ($q) use ($cari) {
                    $q->where('no_transaksi', 'like', "%{$cari}%")
                        ->orWhereHas('kasir', fn ($k) => $k->where('name', 'like', "%{$cari}%"));
                });
            })
            ->when($request->filled('kasir_id'), function ($query) use ($request) {
                $query->where('user_id', $request->input('kasir_id'));
            })
            ->when($request->filled('tanggal'), function ($query) use ($request) {
                $query->whereDate('created_at', $request->input('tanggal'));
            })
            ->when($request->filled('metode_bayar'), function ($query) use ($request) {
                $query->where('metode_bayar', $request->input('metode_bayar'));
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $daftarKasir = User::kasir()->orderBy('name')->get();

        return view('admin.riwayat-transaksi.index', [
            'transaksis' => $transaksis,
            'daftarKasir' => $daftarKasir,
            'ringkasan' => $this->hitungRingkasan($request),
        ]);
    }

    /**
     * GET /admin/riwayat-transaksi/{transaksi}
     * Dipanggil lewat fetch() dari baris tabel / tombol mata. Balikan
     * berupa potongan HTML (bukan JSON) supaya tinggal ditempel ke
     * panel Detail Transaksi di sebelah kanan — tidak perlu templating
     * JS terpisah.
     */
    public function show(Transaksi $transaksi): View
    {
        $transaksi->load(['kasir', 'detail.opsi']);

        return view('admin.riwayat-transaksi._detail', [
            'transaksi' => $transaksi,
        ]);
    }

    /**
     * GET /admin/riwayat-transaksi/{transaksi}/struk
     * Halaman struk versi cetak (dibuka di tab baru dari tombol
     * "Cetak Struk"), auto memanggil window.print().
     */
    public function struk(Transaksi $transaksi): View
    {
        $transaksi->load(['kasir', 'detail.opsi']);

        return view('admin.riwayat-transaksi.struk', [
            'transaksi' => $transaksi,
        ]);
    }

    /**
     * GET /admin/riwayat-transaksi/export
     * Unduh CSV dari transaksi yang cocok dengan filter yang sedang aktif
     * (cari, kasir, tanggal, metode bayar). Pemisah titik-koma + BOM UTF-8
     * supaya langsung rapi saat dibuka di Excel versi Indonesia.
     */
    public function export(Request $request): StreamedResponse
    {
        $transaksis = $this->filterQuery($request)
            ->with('kasir')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(20000)
            ->get();

        $namaFile = 'riwayat-transaksi-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($transaksis) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['No Transaksi', 'Tanggal', 'Jam', 'Kasir', 'Metode Bayar', 'Total', 'Uang Diterima', 'Kembalian', 'Status'], ';');

            foreach ($transaksis as $trx) {
                fputcsv($out, [
                    $trx->no_transaksi,
                    $trx->created_at->format('d/m/Y'),
                    $trx->created_at->format('H:i'),
                    $trx->kasir->name ?? '-',
                    $trx->metode_bayar_label,
                    (int) $trx->total,
                    $trx->uang_diterima !== null ? (int) $trx->uang_diterima : '',
                    $trx->uang_diterima !== null ? (int) $trx->kembalian : '',
                    $trx->status,
                ], ';');
            }

            fclose($out);
        }, $namaFile, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Filter yang sama persis dengan halaman index (cari, kasir, tanggal,
     * metode bayar). Dipakai oleh ringkasan & export.
     */
    private function filterQuery(Request $request): Builder
    {
        return Transaksi::query()
            ->when($request->filled('cari'), function ($query) use ($request) {
                $cari = $request->input('cari');
                $query->where(function ($q) use ($cari) {
                    $q->where('no_transaksi', 'like', "%{$cari}%")
                        ->orWhereHas('kasir', fn ($k) => $k->where('name', 'like', "%{$cari}%"));
                });
            })
            ->when($request->filled('kasir_id'), fn ($query) => $query->where('user_id', $request->input('kasir_id')))
            ->when($request->filled('tanggal'), fn ($query) => $query->whereDate('created_at', $request->input('tanggal')))
            ->when($request->filled('metode_bayar'), fn ($query) => $query->where('metode_bayar', $request->input('metode_bayar')));
    }

    /**
     * Angka ringkas di atas daftar riwayat, mengikuti filter aktif.
     * Omzet hanya menghitung transaksi berstatus selesai.
     */
    private function hitungRingkasan(Request $request): array
    {
        $selesai = fn () => $this->filterQuery($request)->selesai();

        $jumlah = $selesai()->count();
        $omzet = (int) $selesai()->sum('total');

        return [
            'jumlah' => $jumlah,
            'omzet' => $omzet,
            'rata_rata' => $jumlah > 0 ? (int) round($omzet / $jumlah) : 0,
            'tunai' => (int) $selesai()->where('metode_bayar', 'cash')->sum('total'),
            'non_tunai' => (int) $selesai()->whereIn('metode_bayar', ['qris', 'paylater'])->sum('total'),
            'dibatalkan' => $this->filterQuery($request)->where('status', '!=', 'selesai')->count(),
        ];
    }
}
