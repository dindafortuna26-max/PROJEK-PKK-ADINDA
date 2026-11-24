<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log stok Bahan Operasional (minyak goreng, gas) yang dicatat MANUAL per kasir.
 * - 'masuk'  : admin memberi jatah ke kasir (liter / tabung) atau koreksi naik
 * - 'keluar' : kasir melapor habis, atau koreksi turun oleh admin
 *
 * `jumlah`   = jumlah satuan (liter / tabung) yang dihitung stok.
 * `kasir_id` = kasir PEMILIK stok. `user_id` = siapa yang mencatat.
 * (Kolom `sesi` & `transaksi_id` peninggalan versi otomatis, tidak dipakai lagi.)
 */
class PergerakanStokBahanOperasional extends Model
{
    protected $table = 'pergerakan_stok_bahan_operasional';

    protected $fillable = [
        'bahan_operasional_id',
        'user_id',
        'kasir_id',
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
            'kasir_id' => 'integer',
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

    // Kasir PEMILIK stok pada baris log ini (beda dari user, yaitu pencatatnya).
    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }
}
