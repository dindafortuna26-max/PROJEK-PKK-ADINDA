<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategoris = ['Cimol Original', 'Cimol Mix'];

        foreach ($kategoris as $nama) {
            Kategori::create(['nama' => $nama]);
        }
    }
}
