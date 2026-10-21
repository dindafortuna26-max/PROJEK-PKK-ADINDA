<?php

namespace App\Support;

use App\Models\BahanOperasional;
use App\Models\Menu;
use App\Models\Topping;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Daftar peringatan stok (menipis / habis) untuk lonceng notifikasi di
 * navbar. Datanya asli dari database (bukan teks contoh), dan hanya
 * menghitung barang yang stoknya memang sudah dikelola (pernah ada
 * riwayat stok) — sama aturan dengan status "Belum Dikelola".
 */
class NotifikasiStok
{
    public static function ambil(int $batas = 8): Collection
    {
        try {
            $menus = Menu::query()
                ->where('is_active', true)
                ->withCount('pergerakanStok')
                ->withSum(['pergerakanStok as total_masuk' => fn ($q) => $q->where('jenis', 'masuk')], 'jumlah')
                ->withSum(['pergerakanStok as total_keluar' => fn ($q) => $q->where('jenis', 'keluar')], 'jumlah')
                ->get()
                ->map(fn ($m) => self::bentuk($m, 'Menu'));

            $toppings = Topping::query()
                ->where('is_active', true)
                ->withCount('pergerakanStok')
                ->withSum(['pergerakanStok as total_masuk' => fn ($q) => $q->where('jenis', 'masuk')], 'jumlah')
                ->withSum(['pergerakanStok as total_keluar' => fn ($q) => $q->where('jenis', 'keluar')], 'jumlah')
                ->get()
                ->map(fn ($t) => self::bentuk($t, $t->jenis_label));

            // Bahan operasional (minyak goreng, gas): stoknya berkurang
            // otomatis tiap transaksi (1 transaksi = 1 sesi goreng), jadi
            // peringatan "menipis" / "habis" muncul begitu sisa sesinya
            // menyentuh batas, tanpa menunggu konfirmasi kasir.
            $bahans = BahanOperasional::query()
                ->where('is_active', true)
                ->get()
                ->map(function (BahanOperasional $b) {
                    $status = $b->status_stok;

                    if (! in_array($status, ['habis', 'menipis'], true)) {
                        return null;
                    }

                    return [
                        'nama' => $b->nama,
                        'tipe' => 'Bahan Operasional',
                        'stok' => $b->stok_sesi,
                        'status' => $status,
                        'pesan' => $status === 'habis'
                            ? 'sudah habis, segera restock'
                            : 'hampir habis, sisa ' . $b->stok_sesi . ' sesi goreng (' . $b->labelStok() . ')',
                    ];
                });

            return $menus->concat($toppings)->concat($bahans)
                ->filter()
                ->sortBy(fn ($n) => [$n['status'] === 'habis' ? 0 : 1, $n['stok']])
                ->take($batas)
                ->values();
        } catch (Throwable $e) {
            // Notifikasi tidak boleh sampai merusak halaman.
            return collect();
        }
    }

    private static function bentuk($model, string $tipe): ?array
    {
        if ($model->pergerakan_stok_count < 1) {
            return null;
        }

        $stok = (int) $model->total_masuk - (int) $model->total_keluar;

        if ($stok > $model->stok_minimum) {
            return null;
        }

        return [
            'nama' => $model->nama,
            'tipe' => $tipe,
            'stok' => $stok,
            'status' => $stok <= 0 ? 'habis' : 'menipis',
        ];
    }
}
