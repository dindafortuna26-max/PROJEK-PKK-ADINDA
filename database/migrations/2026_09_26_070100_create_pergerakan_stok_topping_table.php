<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sama persis konsepnya dengan pergerakan_stok (menu): stok saat ini
     * TIDAK disimpan sebagai kolom, tapi dihitung SUM(masuk) - SUM(keluar)
     * per topping (lihat Topping::getStokSaatIniAttribute).
     *
     * Dibuat tabel BARU (bukan menambah kolom topping_id nullable ke
     * pergerakan_stok yang sudah ada) supaya tidak perlu ALTER kolom
     * menu_id jadi nullable pada tabel yang sudah dipakai modul lain
     * (Dashboard, Manajemen Stok Menu) — lebih aman & konsisten dengan
     * pola tabel terpisah yang sudah dipakai (transaksi_detail_opsi
     * juga dipisah dari transaksi_detail).
     *
     * - 'masuk'  -> input manual admin (restock bahan)
     * - 'keluar' -> otomatis dibuat sistem saat transaksi Kasir SELESAI,
     *   sebanyak jumlah cimol yang memakai saus/bubuk tersebut.
     */
    public function up(): void
    {
        Schema::create('pergerakan_stok_topping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topping_id')->constrained('toppings')->cascadeOnDelete();
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
        Schema::dropIfExists('pergerakan_stok_topping');
    }
};
