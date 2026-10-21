<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ini "jembatan" many-to-many antara menus <-> ukurans, sekaligus
     * tempat harga jual final disimpan. Contoh:
     *   Cimol Bojot Original + Kecil -> Rp6.000
     *   Cimol Bojot Original + Besar -> Rp12.000
     *   Cimol Mix Mozzarella + Ayam + Kecil -> Rp16.000
     * Satu menu bisa punya beberapa baris ukuran dengan harga masing-masing.
     */
    public function up(): void
    {
        Schema::create('menu_ukuran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('ukuran_id')->constrained('ukurans')->cascadeOnDelete();
            $table->unsignedBigInteger('harga');           // harga jual final kombinasi ini
            $table->boolean('is_active')->default(true);   // bisa nonaktifkan 1 ukuran tanpa nonaktifkan menunya
            $table->timestamps();

            $table->unique(['menu_id', 'ukuran_id']); // 1 menu tidak boleh punya 2 harga untuk ukuran yang sama
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_ukuran');
    }
};