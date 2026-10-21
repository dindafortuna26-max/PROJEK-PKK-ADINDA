<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuUkuran extends Model
{
    use HasFactory;

    protected $table = 'menu_ukuran';

    protected $fillable = [
        'menu_id',
        'ukuran_id',
        'harga',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function ukuran(): BelongsTo
    {
        return $this->belongsTo(Ukuran::class);
    }
}
