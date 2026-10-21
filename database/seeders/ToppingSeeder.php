<?php

namespace Database\Seeders;

use App\Models\Topping;
use Illuminate\Database\Seeder;

class ToppingSeeder extends Seeder
{
    public function run(): void
    {
        $saus = ['Asin Pedas', 'Ayam Bawang', 'Jagung Bakar', 'Balado', 'Barbeque'];
        foreach ($saus as $nama) {
            Topping::firstOrCreate(
                ['jenis' => 'saus', 'nama' => $nama],
                ['harga_tambahan' => 0, 'stok_minimum' => 5, 'is_active' => true]
            );
        }

        $bubuk = ['Daun Jeruk', 'Bawang Goreng'];
        foreach ($bubuk as $nama) {
            Topping::firstOrCreate(
                ['jenis' => 'bubuk_tabur', 'nama' => $nama],
                ['harga_tambahan' => 0, 'stok_minimum' => 5, 'is_active' => true]
            );
        }
    }
}
