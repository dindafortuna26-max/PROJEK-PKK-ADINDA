<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saus dan bubuk tabur digabung satu tabel, dibedakan kolom
     * "jenis". Di UI Admin ditampilkan sebagai 2 menu sidebar
     * terpisah (Saus / Bubuk Tabur), tinggal difilter jenisnya.
     */
    public function up(): void
    {
        Schema::create('toppings', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis', ['saus', 'bubuk_tabur']);
            $table->string('nama');              // contoh: Asin Pedas, BBQ, Balado
            $table->unsignedBigInteger('harga_tambahan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toppings');
    }
};