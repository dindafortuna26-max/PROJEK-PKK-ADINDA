<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Bahan operasional (minyak goreng, gas) yang TIDAK ikut terjual di
 * transaksi tapi terpakai tiap penggorengan.
 *
 * Alurnya otomatis & saling terhubung:
 *  1. Admin restock dalam liter / tabung (1-5 per input).
 *  2. Tiap satuan = sesi_per_satuan sesi goreng
 *     (minyak 1 liter = 10 sesi, gas 1 tabung 3 kg = 20 sesi).
 *  3. Tiap transaksi selesai = 1 sesi goreng -> stok berkurang otomatis.
 *  4. Sisa sesi <= batas menipis -> "Menipis"; 0 -> "Habis"; keduanya
 *     muncul di lonceng notifikasi (admin & kasir) dan di Manajemen Stok.
 */
class BahanOperasional extends Model
{
    use HasFactory;

    /** Batas stok yang bisa dimasukkan admin dalam sekali input (1-5). */
    public const MAKS_SEKALI_MASUK = 5;

    protected $table = 'bahan_operasional';

    protected $fillable = [
        'nama',
        'gambar',
        'satuan',
        'sesi_per_satuan',
        'stok_minimum',
        'is_active',
    ];

    /** Cache sisa sesi per-instance supaya satu halaman tidak query berulang. */
    protected ?int $memoSesi = null;

    protected function casts(): array
    {
        return [
            'sesi_per_satuan' => 'integer',
            'stok_minimum' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }

    public function pergerakanStok(): HasMany
    {
        return $this->hasMany(PergerakanStokBahanOperasional::class, 'bahan_operasional_id');
    }

    /** URL foto bahan, null kalau belum ada foto (tampilan pakai ikon cadangan). */
    public function getGambarUrlAttribute(): ?string
    {
        if (! $this->gambar) {
            return null;
        }

        // URL relatif lewat route Laravel: tidak butuh storage:link & tidak
        // terpengaruh APP_URL. ?v= supaya foto baru langsung tampil setelah diganti.
        return route('bahan-operasional.foto', [
            'bahanOperasional' => $this->getKey(),
            'v' => $this->updated_at?->timestamp ?? crc32($this->gambar),
        ], false);
    }

    /** Hapus file foto dari storage (aman dipanggil kalau tidak ada foto). */
    public function hapusFileGambar(): void
    {
        if ($this->gambar && Storage::disk('public')->exists($this->gambar)) {
            Storage::disk('public')->delete($this->gambar);
        }
    }

    public function getIkonAttribute(): string
    {
        return stripos($this->nama, 'gas') !== false ? 'bi-fire' : 'bi-droplet-half';
    }

    /** Sisa stok dalam SESI goreng = SUM(sesi masuk) - SUM(sesi keluar). */
    public function getStokSesiAttribute(): int
    {
        if ($this->memoSesi === null) {
            $masuk = (int) $this->pergerakanStok()->where('jenis', 'masuk')->sum('sesi');
            $keluar = (int) $this->pergerakanStok()->where('jenis', 'keluar')->sum('sesi');
            $this->memoSesi = max(0, $masuk - $keluar);
        }

        return $this->memoSesi;
    }

    public function segarkan(): static
    {
        $this->memoSesi = null;

        return $this;
    }

    /** Sisa stok dalam liter / tabung (desimal 1 angka). */
    public function getStokSaatIniAttribute(): float
    {
        return round($this->stok_sesi / max(1, $this->sesi_per_satuan), 1);
    }

    /** Teks sisa stok, mis. "2,3 liter" / "1 tabung". */
    public function labelStok(): string
    {
        $angka = number_format($this->stok_saat_ini, 1, ',', '.');
        $angka = rtrim(rtrim($angka, '0'), ',');

        return ($angka === '' ? '0' : $angka) . ' ' . $this->satuan;
    }

    /** Sesi tersisa pada satuan yang sedang dipakai (mis. 4 dari 10). */
    public function getSesiBerjalanAttribute(): int
    {
        $sisa = $this->stok_sesi;
        $per = max(1, $this->sesi_per_satuan);

        if ($sisa <= 0) {
            return 0;
        }

        $sisaBagi = $sisa % $per;

        return $sisaBagi === 0 ? $per : $sisaBagi;
    }

    public function getPersenBerjalanAttribute(): int
    {
        return (int) round($this->sesi_berjalan / max(1, $this->sesi_per_satuan) * 100);
    }

    /**
     * Status untuk tampilan & notifikasi:
     * - belum_dikelola : belum pernah ada catatan stok
     * - habis          : sisa sesi 0
     * - menipis        : sisa sesi <= batas menipis
     * - aman
     */
    public function getStatusStokAttribute(): string
    {
        if (! $this->pergerakanStok()->exists()) {
            return 'belum_dikelola';
        }

        return match (true) {
            $this->stok_sesi <= 0 => 'habis',
            $this->stok_sesi <= $this->stok_minimum => 'menipis',
            default => 'aman',
        };
    }

    /**
     * Dipanggil otomatis tiap transaksi selesai (di dalam DB::transaction
     * TransaksiController::store). 1 transaksi = 1 sesi goreng untuk semua
     * bahan aktif yang stoknya sudah dikelola. Stok tidak dibuat minus.
     */
    public static function pakaiUntukTransaksi(string $noTransaksi, int $transaksiId, int $userId, int $sesi = 1): void
    {
        static::query()->where('is_active', true)->get()->each(
            function (BahanOperasional $bahan) use ($noTransaksi, $transaksiId, $userId, $sesi) {
                if (! $bahan->pergerakanStok()->exists()) {
                    return; // belum dikelola: jangan dipotong
                }

                $ambil = min($sesi, $bahan->stok_sesi);

                if ($ambil < 1) {
                    return; // sudah habis: admin sudah dapat notifikasi
                }

                $bahan->pergerakanStok()->create([
                    'user_id' => $userId,
                    'jenis' => 'keluar',
                    'jumlah' => 0,
                    'sesi' => $ambil,
                    'transaksi_id' => $transaksiId,
                    'keterangan' => "Otomatis: {$noTransaksi} ({$ambil} sesi goreng)",
                    'tanggal' => now()->toDateString(),
                ]);
            }
        );
    }

    public static function labelStatus(string $status): string
    {
        return [
            'aman' => 'Aman',
            'menipis' => 'Menipis',
            'habis' => 'Habis',
            'belum_dikelola' => 'Belum Dikelola',
        ][$status] ?? $status;
    }
}
