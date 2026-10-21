<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 1) users: tambah data profil (no HP, jenis kelamin, tanggal lahir,
     *    alamat) supaya halaman Profil Admin/Kasir lebih lengkap.
     * 2) toppings: tambah kolom "gambar" supaya bumbu & topping punya foto
     *    (dipakai di Master Topping, Manajemen Stok, dan Cek Stok kasir).
     *
     * Semua kolom nullable, jadi data lama tetap aman. Ada pengecekan
     * hasColumn supaya migration ini aman dijalankan ulang.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'no_hp')) {
                $table->string('no_hp', 20)->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'jenis_kelamin')) {
                $table->string('jenis_kelamin', 1)->nullable()->after('no_hp'); // L / P
            }
            if (! Schema::hasColumn('users', 'tanggal_lahir')) {
                $table->date('tanggal_lahir')->nullable()->after('jenis_kelamin');
            }
            if (! Schema::hasColumn('users', 'alamat')) {
                $table->text('alamat')->nullable()->after('tanggal_lahir');
            }
        });

        Schema::table('toppings', function (Blueprint $table) {
            if (! Schema::hasColumn('toppings', 'gambar')) {
                $table->string('gambar')->nullable()->after('nama');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['no_hp', 'jenis_kelamin', 'tanggal_lahir', 'alamat'] as $kolom) {
                if (Schema::hasColumn('users', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });

        Schema::table('toppings', function (Blueprint $table) {
            if (Schema::hasColumn('toppings', 'gambar')) {
                $table->dropColumn('gambar');
            }
        });
    }
};
