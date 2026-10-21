<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi')->unique(); // contoh: TRX-00048
            $table->foreignId('user_id')->constrained('users'); // kasir yang melayani
            $table->unsignedBigInteger('total');
            $table->enum('metode_bayar', ['cash', 'qris', 'paylater']);
            $table->enum('status', ['selesai', 'dibatalkan'])->default('selesai');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};