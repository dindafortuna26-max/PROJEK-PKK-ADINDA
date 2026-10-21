<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'kategori_id',
        'nama',
        'deskripsi',
        'gambar',
        'stok_minimum',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'stok_minimum' => 'integer',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    // Semua log pergerakan stok (masuk & keluar) punya menu ini.
    // Dipakai halaman Manajemen Stok utk hitung stok berjalan.
    public function pergerakanStok(): HasMany
    {
        return $this->hasMany(PergerakanStok::class);
    }

    // Baris mentah menu_ukuran (dipakai kalau butuh akses id pivot-nya langsung,
    // misalnya di halaman "Atur Ukuran & Harga").
    public function menuUkurans(): HasMany
    {
        return $this->hasMany(MenuUkuran::class);
    }

    // Relasi many-to-many ke ukuran, sekaligus ambil kolom harga & is_active
    // dari tabel pivot menu_ukuran lewat withPivot.
    public function ukurans(): BelongsToMany
    {
        return $this->belongsToMany(Ukuran::class, 'menu_ukuran')
            ->withPivot(['id', 'harga', 'is_active'])
            ->withTimestamps();
    }

    // Hanya ukuran yang aktif — ini yang dipakai untuk tampil di
    // halaman Daftar Menu & di halaman Transaksi kasir nanti.
    public function ukuranAktif(): BelongsToMany
    {
        return $this->ukurans()->wherePivot('is_active', true);
    }

    /**
     * Harga terendah dari semua ukuran aktif menu ini.
     * Dipakai untuk kolom "Harga" (format "Rp X - Rp Y") di Daftar Menu.
     */
    public function getHargaMinAttribute(): ?int
    {
        return $this->ukuranAktif()->min('menu_ukuran.harga');
    }

    /**
     * Harga tertinggi dari semua ukuran aktif menu ini.
     */
    public function getHargaMaxAttribute(): ?int
    {
        return $this->ukuranAktif()->max('menu_ukuran.harga');
    }

    /**
     * Teks siap tampil untuk kolom Harga, contoh:
     * "Rp 6.000 - Rp 12.000" atau "Rp 6.000" kalau cuma 1 ukuran,
     * atau "Belum ada harga" kalau belum diatur sama sekali.
     */
    public function getHargaLabelAttribute(): string
    {
        $min = $this->harga_min;
        $max = $this->harga_max;

        if ($min === null) {
            return 'Belum ada harga';
        }

        if ($min === $max) {
            return 'Rp ' . number_format($min, 0, ',', '.');
        }

        return 'Rp ' . number_format($min, 0, ',', '.') . ' - Rp ' . number_format($max, 0, ',', '.');
    }

    /**
     * Stok berjalan = SUM(masuk) - SUM(keluar) dari log pergerakan_stok
     * (satu-satunya sumber kebenaran, lihat komentar di migration-nya).
     * Dipakai di halaman Manajemen Stok & Dashboard.
     */
    public function getStokSaatIniAttribute(): int
    {
        $masuk = $this->pergerakanStok()->where('jenis', 'masuk')->sum('jumlah');
        $keluar = $this->pergerakanStok()->where('jenis', 'keluar')->sum('jumlah');

        return (int) ($masuk - $keluar);
    }

    /**
     * 'belum_dikelola' -> belum pernah ada input stok sama sekali (beda
     * dari 'habis' supaya menu yang memang belum dikelola stoknya tidak
     * ikut dicap kehabisan barang).
     */
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

    /**
     * Entri "stok masuk" terakhir buat menu ini (bukan termasuk
     * pengurangan otomatis pas transaksi) — dipakai kolom "Stok Masuk
     * Terakhir" di halaman Manajemen Stok.
     */
    public function getStokMasukTerakhirAttribute(): ?PergerakanStok
    {
        return $this->pergerakanStok()
            ->where('jenis', 'masuk')
            ->latest('tanggal')
            ->latest('id')
            ->first();
    }
}
