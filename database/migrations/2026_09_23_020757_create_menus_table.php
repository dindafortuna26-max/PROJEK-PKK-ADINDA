<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tidak ada kolom harga di sini karena harga selalu spesifik
     * per kombinasi menu + ukuran (lihat tabel menu_ukuran) —
     * sesuai keputusan: harga ukuran beda-beda tiap menu.
     */
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('nama');                     // contoh: Cimol Bojot Original
            $table->text('deskripsi')->nullable();
            $table->string('gambar')->nullable();
            $table->unsignedInteger('stok_minimum')->default(5); // batas bawah status "Menipis"
            $table->boolean('is_active')->default(true);         // menu nonaktif tak muncul di transaksi baru
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};