<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Saus & bubuk tabur (jenis dibedakan kolom "jenis"). Dipakai kasir
 * sebagai pilihan tambahan (bukan menu tersendiri) saat memesan cimol —
 * lihat Kasir\TransaksiController.
 */
class Topping extends Model
{
    use HasFactory;

    protected $fillable = [
        'jenis',
        'nama',
        'gambar',
        'harga_tambahan',
        'stok_minimum',
        'jatah_harian',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'harga_tambahan' => 'integer',
            'stok_minimum' => 'integer',
            'jatah_harian' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeSaus($query)
    {
        return $query->where('jenis', 'saus');
    }

    public function scopeBubukTabur($query)
    {
        return $query->where('jenis', 'bubuk_tabur');
    }

    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }

    // Semua log pergerakan stok (masuk & keluar) punya topping ini.
    public function pergerakanStok(): HasMany
    {
        return $this->hasMany(PergerakanStokTopping::class);
    }

    /**
     * URL foto bumbu/topping, atau null kalau belum ada foto (view yang
     * menampilkan placeholder ikon sendiri, lihat komponen thumb-topping).
     */
    public function getGambarUrlAttribute(): ?string
    {
        return $this->gambar ? asset('storage/' . $this->gambar) : null;
    }

    public function getJenisLabelAttribute(): string
    {
        return $this->jenis === 'saus' ? 'Bumbu' : 'Topping';
    }

    /**
     * Stok berjalan = SUM(masuk) - SUM(keluar), sama pola dengan
     * Menu::getStokSaatIniAttribute.
     */
    public function getStokSaatIniAttribute(): int
    {
        $masuk = $this->pergerakanStok()->where('jenis', 'masuk')->sum('jumlah');
        $keluar = $this->pergerakanStok()->where('jenis', 'keluar')->sum('jumlah');

        return (int) ($masuk - $keluar);
    }

    public function getStatusStokAttribute(): string
    {
        if (! $this->pergerakanStok()->exists()) {
            return 'belum_dikelola';
        }

        $stok = $this->stok_saat_ini;

        return match (true) {
            $stok <= 0 => 'habis',
            $stok <= $this->stok_minimum => 'menipis',
            default => 'aman',
        };
    }

    public function getStokMasukTerakhirAttribute(): ?PergerakanStokTopping
    {
        return $this->pergerakanStok()
            ->where('jenis', 'masuk')
            ->latest('tanggal')
            ->latest('id')
            ->first();
    }

    // ================= STOK PER KASIR =================
    // Tiap kasir punya stok sendiri: SUM(masuk) - SUM(keluar) pada baris
    // log dengan kasir_id = kasir itu. Accessor stok_saat_ini / status_stok
    // di atas tetap berarti TOTAL gabungan semua kasir (dipakai admin).

    public function stokUntukKasir(int $kasirId): int
    {
        $masuk = $this->pergerakanStok()->where('kasir_id', $kasirId)->where('jenis', 'masuk')->sum('jumlah');
        $keluar = $this->pergerakanStok()->where('kasir_id', $kasirId)->where('jenis', 'keluar')->sum('jumlah');

        return (int) ($masuk - $keluar);
    }

    /**
     * Dianggap "dikelola" untuk kasir ini kalau admin pernah mengisi stok
     * (baris masuk) untuknya. Kalau belum, tidak diblokir di transaksi
     * (sama filosofi "Belum Dikelola" yang sudah ada).
     */
    public function dikelolaUntukKasir(int $kasirId): bool
    {
        return $this->pergerakanStok()->where('kasir_id', $kasirId)->where('jenis', 'masuk')->exists();
    }

    public function statusStokUntukKasir(int $kasirId): string
    {
        if (! $this->dikelolaUntukKasir($kasirId)) {
            return 'belum_dikelola';
        }

        $stok = $this->stokUntukKasir($kasirId);

        return match (true) {
            $stok <= 0 => 'habis',
            $stok <= $this->stok_minimum => 'menipis',
            default => 'aman',
        };
    }

    public function stokMasukTerakhirUntukKasir(int $kasirId)
    {
        return $this->pergerakanStok()
            ->where('kasir_id', $kasirId)
            ->where('jenis', 'masuk')
            ->latest('tanggal')
            ->latest('id')
            ->first();
    }
}
