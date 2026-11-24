<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  
    public function up(): void
    {
        Schema::create('bahan_operasional', function (Blueprint $table) {
            $table->id();
            $table->string('nama');                                   // Minyak Goreng, Gas
            $table->string('satuan')->default('botol');               // botol / tabung / liter
            $table->enum('jenis_batas', ['kali', 'jam'])->default('kali'); // diukur dari kali goreng atau jam
            $table->unsignedInteger('batas_pakai')->default(10);      // 10 (kali) / 6 (jam)
            $table->unsignedInteger('stok_minimum')->default(1);      // cadangan <= ini = Menipis
            $table->timestamp('dipakai_sejak')->nullable();           // awal siklus pakai sekarang
            $table->unsignedInteger('pemakaian_kali')->default(0);    // hitungan kali goreng di siklus ini
            $table->boolean('is_habis')->default(false);              // kasir lapor habis & cadangan kosong
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pergerakan_stok_bahan_operasional', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bahan_operasional_id')->constrained('bahan_operasional')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('jenis', ['masuk', 'keluar']);
            $table->unsignedInteger('jumlah');
            $table->string('keterangan')->nullable();
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pergerakan_stok_bahan_operasional');
        Schema::dropIfExists('bahan_operasional');
    }
};
