<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransaksiDetail extends Model
{
    protected $table = 'transaksi_detail';

    protected $fillable = [
        'transaksi_id',
        'menu_id',
        'ukuran_id',
        'nama_menu',
        'nama_ukuran',
        'level_pedas',
        'pelengkap',
        'jumlah',
        'harga_satuan',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'pelengkap' => 'array',
            'jumlah' => 'integer',
            'harga_satuan' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function ukuran(): BelongsTo
    {
        return $this->belongsTo(Ukuran::class);
    }

    // Saus/bubuk tabur berbayar yang dipilih untuk item ini (bisa lebih
    // dari satu, mis. Saus Sambal + Bubuk BBQ sekaligus).
    public function opsi(): HasMany
    {
        return $this->hasMany(TransaksiDetailOpsi::class, 'transaksi_detail_id');
    }

    /**
     * Total tambahan biaya dari semua topping item ini, sudah dikali
     * jumlah porsi (setiap porsi pakai takaran topping yang sama).
     */
    public function getTotalOpsiAttribute(): int
    {
        return (int) $this->opsi->sum('harga_tambahan') * $this->jumlah;
    }

    /**
     * Subtotal murni menu (belum termasuk topping) + total topping.
     * Dipakai di ringkasan struk/detail supaya jumlahnya pas dengan
     * Transaksi::total.
     */
    public function getSubtotalDenganOpsiAttribute(): int
    {
        return $this->subtotal + $this->total_opsi;
    }
}
