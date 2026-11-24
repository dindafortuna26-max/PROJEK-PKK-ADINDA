<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;


class BahanOperasional extends Model
{
    use HasFactory;
     /**konstanta untuk jenis bahan operasional. Nilainya tetap, yaitu habis_pakai untuk minyak dan gas, serta kemasan untuk cup dan box */
    public const JENIS_HABIS_PAKAI = 'habis_pakai';
    public const JENIS_KEMASAN = 'kemasan';

    /** Batas jumlah yang bisa diberikan admin dalam sekali input (liter / tabung). */
    public const MAKS_SEKALI_MASUK = 50;

    /** Kemasan dihitung per pcs, jadi batasnya lebih besar. */
    public const MAKS_SEKALI_MASUK_KEMASAN = 2000;

    protected $table = 'bahan_operasional';

    protected $fillable = [
        'nama',
        'jenis',
        'gambar',
        'satuan',
        'ukuran_id',
        'pakai_per_porsi',
        'jatah_harian',
        'stok_minimum',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'jatah_harian' => 'integer',
            'ukuran_id' => 'integer',
            'pakai_per_porsi' => 'integer',
            'stok_minimum' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeKemasan($query)
    {
        return $query->where('jenis', self::JENIS_KEMASAN);
    }

    public function scopeHabisPakai($query)
    {
        return $query->where(function ($q) {
            $q->where('jenis', self::JENIS_HABIS_PAKAI)->orWhereNull('jenis');
        });
    }

    public function ukuran(): BelongsTo
    {
        return $this->belongsTo(Ukuran::class);
    }

    public function getIsKemasanAttribute(): bool
    {
        return $this->jenis === self::JENIS_KEMASAN;
    }

    public function getLabelJenisAttribute(): string
    {
        return $this->is_kemasan ? 'Kemasan' : 'Bahan Habis-Pakai';
    }

    /** Batas input sekali beri jatah, tergantung jenis. */
    public function maksSekaliMasuk(): int
    {
        return $this->is_kemasan ? self::MAKS_SEKALI_MASUK_KEMASAN : self::MAKS_SEKALI_MASUK;
    }

    /** Keterangan singkat aturan pemakaian, mis. "Otomatis −1 / porsi ukuran Kecil". */
    public function getAturanPakaiAttribute(): string
    {
        if (! $this->is_kemasan) {
            return 'Manual, diberi admin';
        }

        $ukuran = $this->ukuran?->nama;

        return 'Otomatis −' . $this->pakai_per_porsi . ' / porsi' . ($ukuran ? ' ukuran ' . $ukuran : '');
    }

    public function pergerakanStok(): HasMany
    {
        return $this->hasMany(PergerakanStokBahanOperasional::class, 'bahan_operasional_id');
    }

    public function permintaan(): HasMany
    {
        return $this->hasMany(PermintaanBahanOperasional::class, 'bahan_operasional_id');
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

    /** Kemasan pakai ikon kotak, gas ikon api, selain itu (minyak) ikon tetesan. */
    public function getIkonAttribute(): string
    {
        if ($this->is_kemasan) {
            return 'bi-box-seam';
        }

        return stripos($this->nama, 'gas') !== false ? 'bi-fire' : 'bi-droplet-half';
    }

    // ===================== STOK PER KASIR =====================

    /** Sisa stok milik satu kasir (liter / tabung). Tidak pernah minus. */
    public function stokUntukKasir(int $kasirId): int
    {
        $masuk = (int) $this->pergerakanStok()->where('kasir_id', $kasirId)->where('jenis', 'masuk')->sum('jumlah');
        $keluar = (int) $this->pergerakanStok()->where('kasir_id', $kasirId)->where('jenis', 'keluar')->sum('jumlah');

        return max(0, $masuk - $keluar);
    }

    /** "Dikelola" untuk kasir ini = admin pernah memberi jatah (ada baris masuk). */
    public function sudahDikelolaUntukKasir(int $kasirId): bool
    {
        return $this->pergerakanStok()->where('kasir_id', $kasirId)->where('jenis', 'masuk')->exists();
    }

    /** belum_dikelola | habis | aman (tidak ada status "menipis": pemakaian tidak dihitung otomatis). */
    public function statusUntukKasir(int $kasirId): string
    {
        if (! $this->sudahDikelolaUntukKasir($kasirId)) {
            return 'belum_dikelola';
        }

        return $this->stokUntukKasir($kasirId) <= 0 ? 'habis' : 'aman';
    }

    public function stokMasukTerakhirUntukKasir(int $kasirId)
    {
        return $this->pergerakanStok()
            ->where('kasir_id', $kasirId)
            ->where('jenis', 'masuk')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->first();
    }

    // ===================== KIRIMAN MENUNGGU KONFIRMASI =====================

    public function penerimaan(): HasMany
    {
        return $this->hasMany(PenerimaanBahanOperasional::class, 'bahan_operasional_id');
    }

    /** Kiriman dari admin yang belum dijawab "Sudah" oleh kasir ini (lama ke baru). */
    public function penerimaanMenungguUntukKasir(int $kasirId)
    {
        return $this->penerimaan()->menunggu()->where('kasir_id', $kasirId)->orderBy('id')->get();
    }

    // ===================== PERMINTAAN KASIR =====================

    public function permintaanMenungguUntukKasir(int $kasirId): ?PermintaanBahanOperasional
    {
        return $this->permintaan()->menunggu()->where('kasir_id', $kasirId)->latest('id')->first();
    }

    /** Dipanggil saat admin memberi jatah baru: permintaan kasir itu dianggap selesai. */
    public function tutupPermintaan(int $kasirId): void
    {
        $this->permintaan()->menunggu()->where('kasir_id', $kasirId)->update(['dipenuhi_pada' => now()]);
    }

    // ===================== TAMPILAN =====================

    /** Teks jumlah, mis. "5 liter" / "1 tabung". */
    public function labelJumlah(int|float $jumlah): string
    {
        return number_format($jumlah, 0, ',', '.') . ' ' . $this->satuan;
    }

    public static function labelStatus(string $status): string
    {
        return [
            'aman' => 'Ada',
            'habis' => 'Habis',
            'belum_dikelola' => 'Belum Diberi',
        ][$status] ?? $status;
    }
}
