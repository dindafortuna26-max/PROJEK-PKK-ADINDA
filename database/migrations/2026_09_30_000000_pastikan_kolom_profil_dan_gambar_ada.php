<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PENGAMAN untuk error "Unknown column 'no_hp' / 'gambar'" saat:
     *  - mengisi jenis kelamin, tanggal lahir, alamat di halaman Profil
     *  - menambah foto Bumbu/Topping
     *
     * Penyebabnya: kolom baru dari migration 2026_09_29_100000 belum
     * ada di database. Migration ini mengecek satu per satu (hasColumn)
     * dan hanya menambah yang belum ada, jadi aman dijalankan berapa
     * kali pun dan tidak menyentuh data yang sudah ada.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'no_hp')) {
                $table->string('no_hp', 20)->nullable();
            }
            if (! Schema::hasColumn('users', 'jenis_kelamin')) {
                $table->string('jenis_kelamin', 1)->nullable();
            }
            if (! Schema::hasColumn('users', 'tanggal_lahir')) {
                $table->date('tanggal_lahir')->nullable();
            }
            if (! Schema::hasColumn('users', 'alamat')) {
                $table->text('alamat')->nullable();
            }
            if (! Schema::hasColumn('users', 'avatar_url')) {
                $table->string('avatar_url')->nullable();
            }
        });

        Schema::table('toppings', function (Blueprint $table) {
            if (! Schema::hasColumn('toppings', 'gambar')) {
                $table->string('gambar')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Sengaja kosong: migration ini hanya pengaman, kolomnya
        // dikelola oleh migration aslinya.
    }
};
