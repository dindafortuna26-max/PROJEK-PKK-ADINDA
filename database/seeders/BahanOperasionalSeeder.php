<?php

namespace Database\Seeders;

use App\Models\BahanOperasional;
use Illuminate\Database\Seeder;

class BahanOperasionalSeeder extends Seeder
{
    /**
     * Bahan operasional bawaan. Angkanya bisa diubah Admin kapan saja di
     * Master -> Bahan Operasional.
     *  - Minyak goreng: stok dalam liter, 1 liter = 10 sesi goreng (10 transaksi)
     *  - Gas          : stok dalam tabung 3 kg, 1 tabung = 20 sesi goreng
     * stok_minimum = batas "menipis" dalam SESI.
     */
    public function run(): void
    {
        BahanOperasional::updateOrCreate(
            ['nama' => 'Minyak Goreng'],
            ['satuan' => 'liter', 'sesi_per_satuan' => 10, 'stok_minimum' => 3]
        );

        BahanOperasional::updateOrCreate(
            ['nama' => 'Gas'],
            ['satuan' => 'tabung', 'sesi_per_satuan' => 20, 'stok_minimum' => 6]
        );
    }
}
