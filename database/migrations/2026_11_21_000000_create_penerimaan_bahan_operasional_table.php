<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * KIRIMAN minyak goreng / gas dari admin ke kasir yang MENUNGGU KONFIRMASI.
     *
     * Karena minyak & gas manual, begitu admin memberi jatah, stok kasir
     * TIDAK langsung terisi. Kiriman dicatat di sini dulu. Kasir menjawab di
     * Cek Stok:
     *  - "Sudah"  -> diterima_pada terisi dan baris stok 'masuk' dibuat,
     *                baru saat itu stok kasir bertambah.
     *  - "Belum"  -> belum_pada terisi (admin bisa melihatnya), kiriman
     *                tetap menunggu.
     *
     * diterima_pada NULL = masih menunggu konfirmasi kasir.
     */
    public function up(): void
    {
        if (! Schema::hasTable('penerimaan_bahan_operasional')) {
            Schema::create('penerimaan_bahan_operasional', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bahan_operasional_id')->constrained('bahan_operasional')->cascadeOnDelete();
                $table->foreignId('kasir_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // admin pemberi
                $table->unsignedInteger('jumlah');
                $table->date('tanggal');
                $table->string('keterangan')->nullable();
                $table->timestamp('belum_pada')->nullable();
                $table->timestamp('diterima_pada')->nullable();
                $table->timestamps();

                $table->index(['kasir_id', 'diterima_pada']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('penerimaan_bahan_operasional');
    }
};
