<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log stok masuk/keluar. Modul "Manajemen Stok" belum dibangun, tapi
 * model ini dipakai Dashboard untuk menghitung stok berjalan tiap menu
 * (SUM masuk - SUM keluar) supaya widget "Stok Menipis" jujur: kalau
 * belum pernah ada input stok sama sekali, menu itu tidak dianggap
 * "menipis/habis", cuma "belum dikelola".
 */
class PergerakanStok extends Model
{
    protected $table = 'pergerakan_stok';

    protected $fillable = [
        'menu_id',
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

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
