<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bahan operasional sekarang punya dua jenis:
     *  - habis_pakai : minyak goreng, gas, dst. Diberi manual per kasir (cara lama, tidak berubah).
     *  - kemasan     : cup / box / plastik per ukuran. Diberi jatah manual per kasir juga,
     *                  tapi BERKURANG OTOMATIS tiap transaksi sesuai ukuran porsi yang terjual.
     *
     * ukuran_id       : kemasan ini dipakai untuk ukuran apa (Kecil / Sedang / Besar).
     * pakai_per_porsi : berapa kemasan terpakai untuk 1 porsi ukuran itu (biasanya 1).
     *
     * Data lama (minyak, gas) otomatis jadi 'habis_pakai' lewat nilai default.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('bahan_operasional', 'jenis')) {
            Schema::table('bahan_operasional', function (Blueprint $table) {
                $table->string('jenis', 20)->default('habis_pakai')->after('nama');
            });
        }

        if (! Schema::hasColumn('bahan_operasional', 'ukuran_id')) {
            Schema::table('bahan_operasional', function (Blueprint $table) {
                $table->foreignId('ukuran_id')->nullable()->after('satuan')
                    ->constrained('ukurans')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('bahan_operasional', 'pakai_per_porsi')) {
            Schema::table('bahan_operasional', function (Blueprint $table) {
                $table->unsignedInteger('pakai_per_porsi')->default(1)->after('ukuran_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bahan_operasional', 'ukuran_id')) {
            Schema::table('bahan_operasional', function (Blueprint $table) {
                $table->dropForeign(['ukuran_id']);
                $table->dropColumn('ukuran_id');
            });
        }

        $kolom = array_values(array_filter(
            ['jenis', 'pakai_per_porsi'],
            fn ($k) => Schema::hasColumn('bahan_operasional', $k)
        ));

        if ($kolom) {
            Schema::table('bahan_operasional', function (Blueprint $table) use ($kolom) {
                $table->dropColumn($kolom);
            });
        }
    }
};
