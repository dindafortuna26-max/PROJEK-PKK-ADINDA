<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RiwayatTransaksiController extends Controller
{
    /**
     * GET /riwayat-transaksi (khusus Kasir)
     *
     * Versi READ-ONLY & MILIK SENDIRI dari Riwayat Transaksi admin:
     * - Kasir cuma bisa lihat transaksi yang DIA SENDIRI input
     *   (where user_id = Auth::id(), bukan lintas kasir kayak punya admin).
     * - Default difilter HARI INI (sesuai kebutuhan cross-check pas
     *   closing kasir/shift), tapi tetap bisa ganti tanggal atau buka
     *   "Semua Tanggal" kalau perlu cek shift sebelumnya.
     * - Tidak ada tombol tambah/edit/hapus sama sekali — cuma lihat
     *   detail & cetak ulang struk, sama seperti kasir.stok (Cek Stok).
     */
    public function index(Request $request): View
    {
        $tampilkanSemua = $request->boolean('semua');
        $tanggal = $request->input('tanggal') ?: today()->toDateString();

        $transaksis = $this->baseQuery($request, $tanggal, $tampilkanSemua)
            ->withSum('detail as jumlah_item', 'jumlah')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        // Ringkasan buat bantu cross-check closing kasir: jumlah transaksi
        // selesai, total omzet, dan pecahan Cash vs Non-Tunai (QRIS+PayLater)
        // — cash yang perlu dicocokkan fisik ke laci kasir, non-tunai tidak.
        $ringkasanQuery = fn () => $this->baseQuery($request, $tanggal, $tampilkanSemua)->selesai();

        $ringkasan = [
            'jumlah_transaksi' => $ringkasanQuery()->count(),
            'total_omzet' => (int) $ringkasanQuery()->sum('total'),
            'total_cash' => (int) $ringkasanQuery()->where('metode_bayar', 'cash')->sum('total'),
            'total_non_tunai' => (int) $ringkasanQuery()->whereIn('metode_bayar', ['qris', 'paylater'])->sum('total'),
        ];

        return view('kasir.riwayat-transaksi.index', [
            'transaksis' => $transaksis,
            'tanggal' => $tanggal,
            'tampilkanSemua' => $tampilkanSemua,
            'ringkasan' => $ringkasan,
        ]);
    }

    /**
     * GET /riwayat-transaksi/{transaksi} (khusus Kasir)
     * Dipanggil lewat fetch() dari baris tabel, sama pola dengan punya
     * admin. abort_if di sini yang mengunci supaya kasir tidak bisa
     * intip transaksi kasir lain walau ID-nya ditebak manual di URL.
     */
    public function show(Transaksi $transaksi): View
    {
        abort_if($transaksi->user_id !== Auth::id(), 403, 'Transaksi ini bukan milikmu.');

        $transaksi->load(['detail.opsi']);

        return view('kasir.riwayat-transaksi._detail', [
            'transaksi' => $transaksi,
        ]);
    }

    /**
     * Query dasar riwayat transaksi milik kasir yang sedang login,
     * dipakai bareng untuk tabel (paginate) & kartu ringkasan (count/sum)
     * supaya filternya selalu konsisten satu sama lain.
     */
    private function baseQuery(Request $request, string $tanggal, bool $tampilkanSemua)
    {
        return Transaksi::query()
            ->where('user_id', Auth::id())
            ->when($request->filled('cari'), function ($query) use ($request) {
                $query->where('no_transaksi', 'like', '%' . $request->input('cari') . '%');
            })
            ->when($request->filled('metode_bayar'), function ($query) use ($request) {
                $query->where('metode_bayar', $request->input('metode_bayar'));
            })
            ->when(! $tampilkanSemua, function ($query) use ($tanggal) {
                $query->whereDate('created_at', $tanggal);
            });
    }
}
