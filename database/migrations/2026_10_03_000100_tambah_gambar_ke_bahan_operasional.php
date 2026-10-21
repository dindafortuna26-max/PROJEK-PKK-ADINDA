<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Foto bahan operasional (minyak goreng, gas). Disimpan di storage/app/public/bahan-operasional. */
    public function up(): void
    {
        if (! Schema::hasColumn('bahan_operasional', 'gambar')) {
            Schema::table('bahan_operasional', function (Blueprint $table) {
                $table->string('gambar')->nullable()->after('nama');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bahan_operasional', 'gambar')) {
            Schema::table('bahan_operasional', function (Blueprint $table) {
                $table->dropColumn('gambar');
            });
        }
    }
};
