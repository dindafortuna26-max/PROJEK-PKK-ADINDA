<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stok saat ini TIDAK disimpan sebagai kolom terpisah, tapi
     * dihitung dari SUM(jenis='masuk') - SUM(jenis='keluar') per
     * menu (di Model), supaya satu-satunya sumber kebenaran ada
     * di tabel log ini (sesuai aturan: riwayat stok wajib ada).
     *
     * - 'masuk'  -> input manual admin (restock)
     * - 'keluar' -> otomatis dibuat sistem saat transaksi SELESAI
     *   (bukan saat barang masuk keranjang)
     */
    public function up(): void
    {
        Schema::create('pergerakan_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users'); // siapa yang input / pemicu
            $table->enum('jenis', ['masuk', 'keluar']);
            $table->unsignedInteger('jumlah');
            $table->string('keterangan')->nullable(); // "Restock bahan" / "Otomatis: TRX-00048"
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pergerakan_stok');
    }
};