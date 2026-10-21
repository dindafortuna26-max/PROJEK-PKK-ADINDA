<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supaya saus/bubuk tabur bisa juga punya status Aman/Menipis/Habis
     * seperti menu (lihat Menu::getStatusStokAttribute), bukan cuma
     * dicatat pergerakannya tanpa batas bawah.
     */
    public function up(): void
    {
        Schema::table('toppings', function (Blueprint $table) {
            $table->unsignedInteger('stok_minimum')->default(5)->after('harga_tambahan');
        });
    }

    public function down(): void
    {
        Schema::table('toppings', function (Blueprint $table) {
            $table->dropColumn('stok_minimum');
        });
    }
};
