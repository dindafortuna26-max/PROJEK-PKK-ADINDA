<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Menu;
use App\Models\Ukuran;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriOriginal = Kategori::where('nama', 'Cimol Original')->first();
        $kategoriMix = Kategori::where('nama', 'Cimol Mix')->first();

        $kecil = Ukuran::where('nama', 'Kecil')->first();
        $besar = Ukuran::where('nama', 'Besar')->first();

        $menu1 = Menu::create([
            'kategori_id' => $kategoriOriginal?->id,
            'nama' => 'Cimol Bojot Original',
            'deskripsi' => 'Cimol kenyal dengan bumbu khas Cimol Bojot AA, rasa original yang selalu jadi favorit!',
            'stok_minimum' => 5,
        ]);
        $menu1->ukurans()->attach([
            $kecil->id => ['harga' => 6000],
            $besar->id => ['harga' => 12000],
        ]);

        $menu2 = Menu::create([
            'kategori_id' => $kategoriMix?->id,
            'nama' => 'Cimol Mix Mozzarella + Ayam',
            'deskripsi' => 'Perpaduan cimol, mozzarella leleh, dan ayam gurih yang bikin ketagihan!',
            'stok_minimum' => 5,
        ]);
        $menu2->ukurans()->attach([
            $kecil->id => ['harga' => 16000],
            $besar->id => ['harga' => 20000],
        ]);
    }
}
