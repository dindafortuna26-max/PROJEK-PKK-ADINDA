<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Kiriman minyak goreng / gas dari admin yang menunggu konfirmasi kasir.
 * Stok kasir baru bertambah setelah kasir menjawab "Sudah" (lihat
 * konfirmasiDiterima). Selama menunggu, kiriman tidak dihitung ke stok.
 *
 * diterima_pada NULL = masih menunggu. belum_pada terisi kalau kasir
 * sempat menjawab "Belum".
 */
class PenerimaanBahanOperasional extends Model
{
    protected $table = 'penerimaan_bahan_operasional';

    protected $fillable = [
        'bahan_operasional_id',
        'kasir_id',
        'user_id',
        'jumlah',
        'tanggal',
        'keterangan',
        'belum_pada',
        'diterima_pada',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'tanggal' => 'date',
            'belum_pada' => 'datetime',
            'diterima_pada' => 'datetime',
        ];
    }

    public function scopeMenunggu($query)
    {
        return $query->whereNull('diterima_pada');
    }

    public function bahan(): BelongsTo
    {
        return $this->belongsTo(BahanOperasional::class, 'bahan_operasional_id');
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }

    public function pemberi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Kasir menjawab "Sudah": kiriman ditandai diterima dan baris stok
     * 'masuk' dibuat, jadi sisa stok di Cek Stok kasir ikut bertambah.
     */
    public function konfirmasiDiterima(): void
    {
        DB::transaction(function () {
            // Kunci ulang supaya dobel klik tidak menambah stok dua kali.
            $baris = static::query()->whereKey($this->getKey())->lockForUpdate()->first();

            if (! $baris || $baris->diterima_pada !== null) {
                return;
            }

            PergerakanStokBahanOperasional::create([
                'bahan_operasional_id' => $baris->bahan_operasional_id,
                'user_id' => $baris->user_id ?? $baris->kasir_id,
                'kasir_id' => $baris->kasir_id, // stok ini milik kasir tersebut
                'jenis' => 'masuk',
                'jumlah' => $baris->jumlah,
                'sesi' => 0,
                'keterangan' => $baris->keterangan ?: 'Jatah dari admin',
                'tanggal' => $baris->tanggal->toDateString(),
            ]);

            $baris->update(['diterima_pada' => now()]);
        });
    }
}
