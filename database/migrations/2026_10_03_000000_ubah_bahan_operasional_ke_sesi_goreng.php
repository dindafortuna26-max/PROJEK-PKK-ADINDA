<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alur baru Bahan Operasional (minyak goreng & gas):
     *  - Admin mengisi stok dalam LITER (minyak) / TABUNG (gas).
     *  - Tiap satuan setara sejumlah "sesi goreng" (1 transaksi = 1 sesi):
     *      1 liter minyak       = 10 sesi
     *      1 tabung gas 3 kg    = 20 sesi
     *  - Setiap transaksi selesai, stok otomatis berkurang 1 sesi.
     *
     * Kolom lama (jenis_batas, batas_pakai, dipakai_sejak, pemakaian_kali,
     * is_habis) sengaja TIDAK dihapus supaya aman di semua database; kolom
     * itu tidak dipakai lagi oleh aplikasi.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('bahan_operasional', 'sesi_per_satuan')) {
            Schema::table('bahan_operasional', function (Blueprint $table) {
                $table->unsignedInteger('sesi_per_satuan')->default(10)->after('satuan');
            });
        }

        if (! Schema::hasColumn('pergerakan_stok_bahan_operasional', 'sesi')) {
            Schema::table('pergerakan_stok_bahan_operasional', function (Blueprint $table) {
                $table->unsignedInteger('sesi')->default(0)->after('jumlah');
                $table->unsignedBigInteger('transaksi_id')->nullable()->index()->after('sesi');
            });
        }

        // Rapikan data lama: gas = tabung 20 sesi, selain itu (minyak) = liter 10 sesi.
        foreach (DB::table('bahan_operasional')->get() as $bahan) {
            $isGas = stripos($bahan->nama, 'gas') !== false;
            $sesiPerSatuan = $isGas ? 20 : 10;

            DB::table('bahan_operasional')->where('id', $bahan->id)->update([
                'sesi_per_satuan' => $sesiPerSatuan,
                'satuan' => $isGas ? 'tabung' : ($bahan->satuan === 'botol' ? 'liter' : $bahan->satuan),
                // Batas "menipis" sekarang dihitung dalam SESI (bukan botol/tabung).
                'stok_minimum' => max(1, intdiv($sesiPerSatuan, 3)),
            ]);

            // Riwayat lama dicatat per satuan -> konversi ke sesi.
            DB::table('pergerakan_stok_bahan_operasional')
                ->where('bahan_operasional_id', $bahan->id)
                ->where('sesi', 0)
                ->update(['sesi' => DB::raw('jumlah * ' . $sesiPerSatuan)]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pergerakan_stok_bahan_operasional', 'sesi')) {
            Schema::table('pergerakan_stok_bahan_operasional', function (Blueprint $table) {
                $table->dropColumn(['sesi', 'transaksi_id']);
            });
        }

        if (Schema::hasColumn('bahan_operasional', 'sesi_per_satuan')) {
            Schema::table('bahan_operasional', function (Blueprint $table) {
                $table->dropColumn('sesi_per_satuan');
            });
        }
    }
};
