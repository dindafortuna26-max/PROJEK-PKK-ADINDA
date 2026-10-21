<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Topping (saus/bubuk tabur) yang dipilih untuk satu item transaksi.
 * Harga & jumlah TIDAK disimpan per baris (jumlahnya sama dengan
 * TransaksiDetail::jumlah induknya — satu porsi = satu takaran topping),
 * jadi biaya tambahan topping ini dihitung: harga_tambahan x jumlah induk.
 * Lihat komentar di migration create_transaksi_detail_opsi_table.
 */
class TransaksiDetailOpsi extends Model
{
    protected $table = 'transaksi_detail_opsi';

    protected $fillable = [
        'transaksi_detail_id',
        'topping_id',
        'nama_topping',
        'jenis_topping',
        'harga_tambahan',
    ];

    protected function casts(): array
    {
        return [
            'harga_tambahan' => 'integer',
        ];
    }

    public function transaksiDetail(): BelongsTo
    {
        return $this->belongsTo(TransaksiDetail::class, 'transaksi_detail_id');
    }

    public function topping(): BelongsTo
    {
        return $this->belongsTo(Topping::class);
    }

    public function getJenisLabelAttribute(): string
    {
        return $this->jenis_topping === 'saus' ? 'Bumbu' : 'Topping';
    }
}
