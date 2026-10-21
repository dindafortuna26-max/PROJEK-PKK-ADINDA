<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TransaksiSeeder extends Seeder
{
    /**
     * Data CONTOH riwayat transaksi supaya halaman "Riwayat Transaksi"
     * (Admin) langsung ada isinya waktu dicoba — bukan pengganti modul
     * Transaksi (Kasir) yang sungguhan. Begitu modul itu jadi (kasir
     * bisa input transaksi asli), seeder ini tinggal tidak usah dipanggil
     * lagi. Dibuat DINAMIS berdasar Menu + Ukuran aktif yang ada di
     * database saat seeding — jadi otomatis ikut menu asli, bukan
     * data menu yang di-hardcode di sini.
     */
    public function run(): void
    {
        $kasirs = User::kasir()->get();
        if ($kasirs->isEmpty()) {
            $this->command?->warn('TransaksiSeeder dilewati: belum ada akun kasir (jalankan UserSeeder dulu).');

            return;
        }

        $itemTersedia = collect();
        foreach (Menu::with('ukuranAktif')->get() as $menu) {
            foreach ($menu->ukuranAktif as $ukuran) {
                $itemTersedia->push([
                    'menu_id' => $menu->id,
                    'ukuran_id' => $ukuran->id,
                    'nama_menu' => $menu->nama,
                    'nama_ukuran' => $ukuran->nama,
                    'harga' => (int) $ukuran->pivot->harga,
                ]);
            }
        }

        if ($itemTersedia->isEmpty()) {
            $this->command?->warn('TransaksiSeeder dilewati: belum ada menu + harga ukuran aktif (jalankan MenuSeeder dulu).');

            return;
        }

        $metodeBayarPilihan = ['cash', 'qris', 'paylater'];
        $levelPedasPilihan = ['tidak_pedas', 'level_1', 'level_2', 'level_3'];
        $pelengkapPilihan = ['Bawang goreng', 'Daun jeruk', 'Seledri'];

        $jumlahTransaksi = 30;

        for ($nomorUrut = 1; $nomorUrut <= $jumlahTransaksi; $nomorUrut++) {
            $tanggal = Carbon::now()
                ->subDays(random_int(0, 9))
                ->setTime(random_int(8, 20), random_int(0, 59));

            $kasir = $kasirs->random();
            $status = random_int(1, 100) <= 90 ? 'selesai' : 'dibatalkan';
            $metodeBayar = $metodeBayarPilihan[array_rand($metodeBayarPilihan)];

            $jumlahItem = random_int(1, min(4, $itemTersedia->count()));
            $itemTransaksi = $itemTersedia->random($jumlahItem);

            $total = 0;
            $barisDetail = [];

            foreach ($itemTransaksi as $item) {
                $qty = random_int(1, 3);
                $subtotal = $item['harga'] * $qty;
                $total += $subtotal;

                $pakaiPelengkap = random_int(0, 1) === 1;

                $barisDetail[] = [
                    'menu_id' => $item['menu_id'],
                    'ukuran_id' => $item['ukuran_id'],
                    'nama_menu' => $item['nama_menu'],
                    'nama_ukuran' => $item['nama_ukuran'],
                    'level_pedas' => $levelPedasPilihan[array_rand($levelPedasPilihan)],
                    'pelengkap' => $pakaiPelengkap
                        ? collect($pelengkapPilihan)->random(random_int(1, 2))->values()->all()
                        : null,
                    'jumlah' => $qty,
                    'harga_satuan' => $item['harga'],
                    'subtotal' => $subtotal,
                ];
            }

            $transaksi = new Transaksi([
                'no_transaksi' => 'TRX-'.str_pad((string) $nomorUrut, 5, '0', STR_PAD_LEFT),
                'user_id' => $kasir->id,
                'total' => $total,
                'metode_bayar' => $metodeBayar,
                'status' => $status,
            ]);
            // Timestamps di-set manual (tersebar mundur 0-9 hari) supaya
            // data contoh kelihatan realistis di filter tanggal, bukan
            // numpuk semua di "hari ini".
            $transaksi->timestamps = false;
            $transaksi->created_at = $tanggal;
            $transaksi->updated_at = $tanggal;
            $transaksi->save();

            foreach ($barisDetail as $detail) {
                $detailModel = new TransaksiDetail(array_merge($detail, [
                    'transaksi_id' => $transaksi->id,
                ]));
                $detailModel->timestamps = false;
                $detailModel->created_at = $tanggal;
                $detailModel->updated_at = $tanggal;
                $detailModel->save();
            }
        }
    }
}
