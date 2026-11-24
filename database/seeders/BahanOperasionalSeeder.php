<?php

namespace Database\Seeders;

use App\Models\BahanOperasional;
use Illuminate\Database\Seeder;

class BahanOperasionalSeeder extends Seeder
{
    /**
     * Bahan operasional bawaan. Angkanya bisa diubah Admin kapan saja di
     * Master -> Bahan Operasional. Pemberiannya MANUAL per kasir:
     *  - Minyak goreng: jatah harian 5 liter per kasir
     *  - Gas          : jatah harian 1 tabung per kasir
     */
    public function run(): void
    {
        BahanOperasional::updateOrCreate(
            ['nama' => 'Minyak Goreng'],
            ['satuan' => 'liter', 'jatah_harian' => 5]
        );

        BahanOperasional::updateOrCreate(
            ['nama' => 'Gas'],
            ['satuan' => 'tabung', 'jatah_harian' => 1]
        );
    }
}
