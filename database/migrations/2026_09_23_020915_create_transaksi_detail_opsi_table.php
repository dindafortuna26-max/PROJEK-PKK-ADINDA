<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Topping (saus / bubuk tabur) berbayar yang dipilih untuk satu
     * item transaksi. Dipisah dari transaksi_detail (bukan kolom
     * saus_id/bubuk_id) supaya satu item bisa punya lebih dari satu
     * topping sekaligus, mis. Saus Sambal + Bubuk BBQ bersamaan.
     *
     * nama_topping, jenis_topping, harga_tambahan: SNAPSHOT saat
     * transaksi, sama alasannya kayak di transaksi_detail.
     */
    public function up(): void
    {
        Schema::create('transaksi_detail_opsi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_detail_id')->constrained('transaksi_detail')->cascadeOnDelete();
            $table->foreignId('topping_id')->nullable()->constrained('toppings')->nullOnDelete();

            $table->string('nama_topping');                 // snapshot
            $table->enum('jenis_topping', ['saus', 'bubuk_tabur']); // snapshot
            $table->unsignedBigInteger('harga_tambahan');   // snapshot

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_detail_opsi');
    }
};