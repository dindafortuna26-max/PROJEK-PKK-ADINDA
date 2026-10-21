<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * KategoriSeeder dulu sempat membuat 2 kategori menu "Bumbu" dan
     * "Topping" — ini keliru, karena bumbu/topping sekarang punya
     * modul sendiri (tabel toppings + halaman Master Topping & Bumbu),
     * bukan kategori menu. Migration ini HANYA menghapus 2 baris itu
     * dari tabel kategoris, tidak menyentuh kategori lain (Cimol
     * Original, Cimol Mix, dst) ataupun data menu — kalau ada menu
     * yang kebetulan terlanjur dipasang ke salah satu kategori ini,
     * kategori menu itu otomatis jadi "Tanpa Kategori" (FK
     * nullOnDelete di migration add_kategori_id_to_menus_table),
     * BUKAN ikut terhapus.
     */
    public function up(): void
    {
        DB::table('kategoris')->whereIn('nama', ['Bumbu', 'Topping'])->delete();
    }

    /**
     * Kalau perlu di-rollback, kembalikan 2 kategori itu (id baru,
     * karena id lama sudah tidak bisa dipastikan).
     */
    public function down(): void
    {
        foreach (['Bumbu', 'Topping'] as $nama) {
            DB::table('kategoris')->insertOrIgnore([
                'nama' => $nama,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
