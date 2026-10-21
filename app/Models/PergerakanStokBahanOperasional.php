<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log stok masuk/keluar Bahan Operasional (minyak goreng, gas).
 * - 'masuk'  : admin restock (liter / tabung) atau koreksi naik
 * - 'keluar' : otomatis 1 sesi tiap transaksi selesai, atau koreksi turun
 *
 * `jumlah` = jumlah satuan (liter/tabung) yang diinput admin saat restock.
 * `sesi`   = nilai sebenarnya yang dihitung stok (sesi goreng).
 */
class PergerakanStokBahanOperasional extends Model
{
    protected $table = 'pergerakan_stok_bahan_operasional';

    protected $fillable = [
        'bahan_operasional_id',
        'user_id',
        'jenis',
        'jumlah',
        'sesi',
        'transaksi_id',
        'keterangan',
        'tanggal',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'sesi' => 'integer',
            'transaksi_id' => 'integer',
            'tanggal' => 'date',
        ];
    }

    public function bahan(): BelongsTo
    {
        return $this->belongsTo(BahanOperasional::class, 'bahan_operasional_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }
}
