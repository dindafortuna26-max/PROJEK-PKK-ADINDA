<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jatah harian per kasir untuk TOPPING & BUMBU, dipakai tombol
     * "Bagikan Jatah Harian" di Manajemen Stok admin. 0 = tidak ada jatah tetap.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('toppings', 'jatah_harian')) {
            Schema::table('toppings', function (Blueprint $table) {
                $table->unsignedInteger('jatah_harian')->default(0)->after('stok_minimum');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('toppings', 'jatah_harian')) {
            Schema::table('toppings', function (Blueprint $table) {
                $table->dropColumn('jatah_harian');
            });
        }
    }
};
