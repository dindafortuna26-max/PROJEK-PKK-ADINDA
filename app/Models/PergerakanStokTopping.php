<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log stok masuk/keluar khusus Topping (saus/bubuk tabur). Sama pola
 * dengan PergerakanStok (menu), lihat komentar di migration-nya.
 */
class PergerakanStokTopping extends Model
{
    protected $table = 'pergerakan_stok_topping';

    protected $fillable = [
        'topping_id',
        'user_id',
        'jenis',
        'jumlah',
        'keterangan',
        'tanggal',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'tanggal' => 'date',
        ];
    }

    public function topping(): BelongsTo
    {
        return $this->belongsTo(Topping::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
