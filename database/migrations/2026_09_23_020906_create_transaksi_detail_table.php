<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu baris = satu item menu di dalam satu transaksi.
     *
     * - menu_id / ukuran_id: FK nullable (referensi saja). Kalau menu
     *   atau ukurannya dihapus/dinonaktifkan nanti, baris riwayat ini
     *   tetap aman karena nama & harga sudah di-snapshot di kolom lain.
     * - nama_menu, nama_ukuran, harga_satuan: SNAPSHOT saat transaksi
     *   terjadi. Kalau harga menu berubah besok, struk lama tetap
     *   menunjukkan harga waktu itu.
     * - level_pedas & pelengkap: gratis (tidak pengaruh harga), makanya
     *   tidak ada tabel master atau kolom harga untuk keduanya.
     *   level_pedas cuma 4 pilihan tetap -> enum. pelengkap bisa pilih
     *   lebih dari satu -> disimpan sebagai JSON array nama.
     * - Topping (saus/bubuk tabur) yang BERBAYAR disimpan terpisah di
     *   transaksi_detail_opsi (1 item bisa >1 topping).
     */
    public function up(): void
    {
        Schema::create('transaksi_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksis')->cascadeOnDelete();
            $table->foreignId('menu_id')->nullable()->constrained('menus')->nullOnDelete();
            $table->foreignId('ukuran_id')->nullable()->constrained('ukurans')->nullOnDelete();

            $table->string('nama_menu');            // snapshot
            $table->string('nama_ukuran')->nullable(); // snapshot

            $table->enum('level_pedas', ['tidak_pedas', 'level_1', 'level_2', 'level_3'])
                ->default('tidak_pedas');
            $table->json('pelengkap')->nullable();  // contoh: ["Bawang goreng","Daun jeruk"]

            $table->unsignedInteger('jumlah');
            $table->unsignedBigInteger('harga_satuan'); // snapshot dari menu_ukuran.harga saat itu
            $table->unsignedBigInteger('subtotal');     // jumlah x harga_satuan (belum termasuk topping)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_detail');
    }
};