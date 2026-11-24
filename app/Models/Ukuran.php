<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ukuran extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Urutkan ukuran dari kecil ke besar: Kecil -> Sedang -> Besar.
     * Ukuran lain yang ditambah belakangan (di luar tiga itu) ditaruh
     * setelahnya, diurutkan menurut id.
     */
    public function scopeUrutKecilKeBesar($query)
    {
        return $query
            ->orderByRaw("CASE LOWER(ukurans.nama) WHEN 'kecil' THEN 1 WHEN 'sedang' THEN 2 WHEN 'besar' THEN 3 ELSE 4 END")
            ->orderBy('ukurans.id');
    }

    public function menuUkurans(): HasMany
    {
        return $this->hasMany(MenuUkuran::class);
    }
}
