<?php

namespace Database\Seeders;

use App\Models\Ukuran;
use Illuminate\Database\Seeder;

class UkuranSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Kecil', 'Sedang', 'Besar'] as $nama) {
            Ukuran::create(['nama' => $nama]);
        }
    }
}
