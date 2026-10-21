<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyimpan nominal uang yang diterima kasir & kembalian untuk
 * pembayaran tunai (cash). Kolom nullable supaya transaksi lama
 * (sebelum fitur kembalian ada) tetap aman.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksis', 'uang_diterima')) {
                $table->unsignedBigInteger('uang_diterima')->nullable()->after('total');
            }
            if (! Schema::hasColumn('transaksis', 'kembalian')) {
                $table->unsignedBigInteger('kembalian')->default(0)->after('uang_diterima');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropColumn(['uang_diterima', 'kembalian']);
        });
    }
};
