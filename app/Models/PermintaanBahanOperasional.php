<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Permintaan kasir ke admin untuk minyak goreng / gas yang sudah habis.
 * dipenuhi_pada NULL = masih menunggu; terisi otomatis begitu admin memberi
 * jatah baru ke kasir tersebut.
 */
class PermintaanBahanOperasional extends Model
{
    protected $table = 'permintaan_bahan_operasional';

    protected $fillable = [
        'bahan_operasional_id',
        'kasir_id',
        'dipenuhi_pada',
    ];

    protected function casts(): array
    {
        return [
            'dipenuhi_pada' => 'datetime',
        ];
    }

    public function scopeMenunggu($query)
    {
        return $query->whereNull('dipenuhi_pada');
    }

    public function bahan(): BelongsTo
    {
        return $this->belongsTo(BahanOperasional::class, 'bahan_operasional_id');
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }
}
