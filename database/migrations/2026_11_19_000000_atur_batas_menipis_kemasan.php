<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kemasan (cup / box) sekarang tampil di Cek Stok kasir seperti MENU:
     * ada status Aman / Menipis / Habis. Batas "Menipis" memakai kolom
     * `stok_minimum` yang sudah ada di tabel bahan_operasional.
     *
     * Kemasan lama masih membawa nilai bawaan lama (1), terlalu kecil untuk
     * barang yang dihitung per pcs, jadi dinaikkan jadi 10. Admin bisa
     * mengubahnya per kemasan lewat form Bahan Operasional.
     * Minyak & gas tidak disentuh.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('bahan_operasional', 'stok_minimum')) {
            Schema::table('bahan_operasional', function (Blueprint $table) {
                $table->unsignedInteger('stok_minimum')->default(10)->after('jatah_harian');
            });
        }

        DB::table('bahan_operasional')
            ->where('jenis', 'kemasan')
            ->where('stok_minimum', '<=', 1)
            ->update(['stok_minimum' => 10]);
    }

    public function down(): void
    {
        // Sengaja kosong: kolom stok_minimum sudah ada sejak awal dan dipakai
        // bagian lain, jadi tidak dihapus saat rollback.
    }
};
