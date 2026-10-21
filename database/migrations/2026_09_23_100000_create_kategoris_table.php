<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master kategori menu, contoh: Cimol, Cimol Mix, Minuman, dll.
     * Dipisah dari tabel menus supaya admin bisa kelola daftar
     * kategori sendiri tanpa ubah struktur menu.
     */
    public function up(): void
    {
        Schema::create('kategoris', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // contoh: Cimol Original, Cimol Mix, Minuman
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategoris');
    }
};
