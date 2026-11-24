<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * STOK PER KASIR.
     *
     * kasir_id = PEMILIK stok pada baris log tersebut (kasir yang memegang
     * barangnya). Beda dari user_id yang tetap berarti "siapa yang
     * menginput / memicu" (admin saat restock, kasir saat transaksi).
     *
     * Stok satu kasir = SUM(masuk) - SUM(keluar) dengan kasir_id = kasir itu.
     * Baris lama yang kasir_id-nya NULL (stok global sebelum fitur ini)
     * tidak dihitung ke stok kasir manapun.
     */
    public function up(): void
    {
        foreach (['pergerakan_stok', 'pergerakan_stok_topping'] as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->foreignId('kasir_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        $this->migrasikanDataLama('pergerakan_stok', 'menu_id');
        $this->migrasikanDataLama('pergerakan_stok_topping', 'topping_id');
    }

    /**
     * Data lama: pengurangan stok otomatis dari transaksi (user_id = kasir)
     * langsung diatribusikan ke kasir tersebut. Supaya stok kasir tidak
     * langsung minus, dibuatkan satu baris "Saldo awal" sebesar total
     * pengeluarannya (hasil bersih = 0). Admin tinggal mengisi stok baru
     * ke masing-masing kasir.
     */
    private function migrasikanDataLama(string $tabel, string $kolomBarang): void
    {
        $idKasir = DB::table('users')->where('role', 'kasir')->pluck('id');

        if ($idKasir->isEmpty()) {
            return;
        }

        DB::table($tabel)
            ->where('jenis', 'keluar')
            ->whereIn('user_id', $idKasir)
            ->update(['kasir_id' => DB::raw('user_id')]);

        $rekap = DB::table($tabel)
            ->selectRaw("{$kolomBarang} as barang_id, kasir_id, SUM(jumlah) as total")
            ->where('jenis', 'keluar')
            ->whereNotNull('kasir_id')
            ->groupBy($kolomBarang, 'kasir_id')
            ->get();

        $sekarang = now();

        foreach ($rekap as $baris) {
            DB::table($tabel)->insert([
                $kolomBarang => $baris->barang_id,
                'user_id' => $baris->kasir_id,
                'kasir_id' => $baris->kasir_id,
                'jenis' => 'masuk',
                'jumlah' => $baris->total,
                'keterangan' => 'Saldo awal (migrasi stok per kasir)',
                'tanggal' => $sekarang->toDateString(),
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ]);
        }
    }

    public function down(): void
    {
        foreach (['pergerakan_stok', 'pergerakan_stok_topping'] as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dropForeign(['kasir_id']);
                $table->dropColumn('kasir_id');
            });
        }
    }
};
