<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master nama ukuran saja (Kecil/Sedang/Besar). Harga TIDAK
     * disimpan di sini karena harga per ukuran beda-beda tiap menu
     * (lihat tabel menu_ukuran).
     */
    public function up(): void
    {
        Schema::create('ukurans', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // contoh: Kecil, Sedang, Besar
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ukurans');
    }
};