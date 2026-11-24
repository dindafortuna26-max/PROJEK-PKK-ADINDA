<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BAHAN OPERASIONAL (minyak goreng & gas) = MANUAL, PER KASIR.
     *
     *  - Stok TIDAK lagi berkurang otomatis tiap transaksi.
     *  - Admin memberi jatah ke tiap kasir (mis. 1 tabung gas + 5 liter minyak
     *    per hari). Satuannya langsung liter / tabung, tersimpan di kolom `jumlah`.
     *  - Kalau habis, kasir menekan "Minta ke Admin" -> tercatat di tabel
     *    permintaan_bahan_operasional dan muncul di notifikasi admin.
     *    Admin memberi jatah lagi -> permintaan otomatis tertutup.
     *
     * kasir_id = PEMILIK stok pada baris log (sama polanya dengan stok menu &
     * topping). Baris lama yang kasir_id-nya NULL (stok global versi otomatis)
     * tidak dihitung ke kasir manapun, tapi tidak dihapus.
     *
     * Kolom lama (sesi_per_satuan, stok_minimum, sesi, transaksi_id) dibiarkan
     * supaya aman di semua database; tidak dipakai lagi oleh aplikasi.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('bahan_operasional', 'jatah_harian')) {
            Schema::table('bahan_operasional', function (Blueprint $table) {
                // Jatah standar per kasir per hari (liter / tabung). Dipakai
                // tombol "Bagikan Jatah Harian" dan sebagai isian awal form.
                $table->unsignedInteger('jatah_harian')->default(0)->after('satuan');
            });

            foreach (DB::table('bahan_operasional')->get() as $bahan) {
                $isGas = stripos($bahan->nama, 'gas') !== false;

                DB::table('bahan_operasional')->where('id', $bahan->id)->update([
                    'jatah_harian' => $isGas ? 1 : 5,
                ]);
            }
        }

        if (! Schema::hasColumn('pergerakan_stok_bahan_operasional', 'kasir_id')) {
            Schema::table('pergerakan_stok_bahan_operasional', function (Blueprint $table) {
                $table->foreignId('kasir_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('permintaan_bahan_operasional')) {
            Schema::create('permintaan_bahan_operasional', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bahan_operasional_id')->constrained('bahan_operasional')->cascadeOnDelete();
                $table->foreignId('kasir_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('dipenuhi_pada')->nullable(); // NULL = masih menunggu admin
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_bahan_operasional');

        if (Schema::hasColumn('pergerakan_stok_bahan_operasional', 'kasir_id')) {
            Schema::table('pergerakan_stok_bahan_operasional', function (Blueprint $table) {
                $table->dropForeign(['kasir_id']);
                $table->dropColumn('kasir_id');
            });
        }

        if (Schema::hasColumn('bahan_operasional', 'jatah_harian')) {
            Schema::table('bahan_operasional', function (Blueprint $table) {
                $table->dropColumn('jatah_harian');
            });
        }
    }
};
