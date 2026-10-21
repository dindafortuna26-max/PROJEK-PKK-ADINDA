<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modul "Riwayat Transaksi" (Admin) sudah dibangun di atas model ini.
 * Data terisi dari Kasir lewat modul Transaksi (kalau sudah dibuat) atau
 * lewat TransaksiSeeder (data contoh, lihat database/seeders).
 */
class Transaksi extends Model
{
    protected $fillable = [
        'no_transaksi',
        'user_id',
        'total',
        'uang_diterima',
        'kembalian',
        'metode_bayar',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'uang_diterima' => 'integer',
            'kembalian' => 'integer',
        ];
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(TransaksiDetail::class);
    }

    /**
     * True kalau transaksi ini dibayar tunai DAN nominal uang yang
     * diterima tercatat. Transaksi lama (sebelum fitur kembalian ada)
     * uang_diterima-nya kosong, jadi baris "Uang Diterima" tidak
     * ditampilkan dan kembaliannya dianggap Rp 0.
     */
    public function getAdaRincianTunaiAttribute(): bool
    {
        return $this->metode_bayar === 'cash' && $this->uang_diterima !== null;
    }

    public function scopeSelesai($query)
    {
        return $query->where('status', 'selesai');
    }

    /**
     * Label tampilan utk metode_bayar. Cuma 3 pilihan yang dipakai di
     * seluruh aplikasi: cash, qris, paylater (sesuai enum di migration).
     */
    public function getMetodeBayarLabelAttribute(): string
    {
        return match ($this->metode_bayar) {
            'cash' => 'Cash',
            'qris' => 'QRIS',
            'paylater' => 'PayLater',
            default => ucfirst((string) $this->metode_bayar),
        };
    }

    public function getMetodeBayarIconAttribute(): string
    {
        return match ($this->metode_bayar) {
            'cash' => 'bi-cash-stack',
            'qris' => 'bi-qr-code-scan',
            'paylater' => 'bi-credit-card-2-back',
            default => 'bi-wallet2',
        };
    }

    /**
     * Logo metode bayar (SVG di public/assets_dashboard/images/pay).
     */
    public function getMetodeBayarLogoAttribute(): string
    {
        $berkas = in_array($this->metode_bayar, ['cash', 'qris', 'paylater'], true)
            ? $this->metode_bayar
            : 'cash';

        return asset('assets_dashboard/images/pay/' . $berkas . '.svg');
    }

    /**
     * Status transaksi cuma 'selesai' & 'dibatalkan' (lihat enum di
     * migration create_transaksis_table) — kasir langsung menuntaskan
     * transaksi saat itu juga, jadi tidak ada status "diproses/pending"
     * seperti pada order online.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'selesai' ? 'Selesai' : 'Dibatalkan';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status === 'selesai' ? 'success' : 'failed';
    }
}
