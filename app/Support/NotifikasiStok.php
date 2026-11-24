<?php

namespace App\Support;

use App\Models\BahanOperasional;
use App\Models\Menu;
use App\Models\PergerakanStok;
use App\Models\PenerimaanBahanOperasional;
use App\Models\PergerakanStokBahanOperasional;
use App\Models\PermintaanBahanOperasional;
use App\Models\PergerakanStokTopping;
use App\Models\Topping;
use App\Models\User;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Daftar peringatan stok (menipis / habis) untuk lonceng notifikasi di
 * navbar. Datanya asli dari database (bukan teks contoh), dan hanya
 * menghitung barang yang stoknya memang sudah dikelola (pernah ada
 * riwayat stok) — sama aturan dengan status "Belum Dikelola".
 * Stok menu & topping dihitung PER KASIR.
 */
class NotifikasiStok
{
    public static function ambil(int $batas = 8): Collection
    {
        try {
            // Stok menu & topping sekarang PER KASIR: kasir hanya melihat
            // peringatan stoknya sendiri, admin melihat semua kasir (nama
            // kasir ditulis di belakang nama barang).
            $user = auth()->user();
            $untukKasirId = ($user && $user->role === 'kasir') ? (int) $user->id : null;

            $menus = self::peringatanPerKasir(
                Menu::query()->where('is_active', true)->get(),
                PergerakanStok::query(),
                'menu_id',
                $untukKasirId,
                fn ($m) => 'Menu'
            );

            $toppings = self::peringatanPerKasir(
                Topping::query()->where('is_active', true)->get(),
                PergerakanStokTopping::query(),
                'topping_id',
                $untukKasirId,
                fn ($t) => $t->jenis_label
            );

            // Kemasan (cup / box): berkurang otomatis, jadi peringatannya sama
            // seperti menu (Menipis / Habis) dan tidak ada alur "minta ke admin".
            $kemasans = self::peringatanPerKasir(
                BahanOperasional::query()->where('is_active', true)->kemasan()->get(),
                PergerakanStokBahanOperasional::query(),
                'bahan_operasional_id',
                $untukKasirId,
                fn ($k) => 'Kemasan'
            );

            // Bahan habis-pakai (minyak goreng, gas): MANUAL per kasir. Yang
            // muncul di sini hanya yang sudah habis / sedang diminta ke admin.
            $bahans = self::peringatanBahan($untukKasirId);

            // 'tab' = tab di halaman stok yang dibuka saat notifikasi diklik.
            $menus = $menus->map(fn ($n) => $n + ['tab' => 'data-stok']);
            $toppings = $toppings->map(fn ($n) => $n + ['tab' => 'topping']);
            // Kasir punya tab Kemasan sendiri; di halaman admin kemasan ada di tab bahan.
            $tabKemasan = $untukKasirId ? 'kemasan' : 'bahan';
            $kemasans = $kemasans->map(fn ($n) => $n + ['tab' => $tabKemasan]);
            $bahans = $bahans->map(fn ($n) => $n + ['tab' => 'bahan']);

            // Minyak & gas (permintaan kasir) diurutkan PALING ATAS supaya
            // tidak tenggelam oleh banyaknya menu yang habis.
            return $menus->concat($toppings)->concat($kemasans)->concat($bahans)
                ->filter()
                ->sortBy(fn ($n) => [
                    $n['tab'] === 'bahan' ? 0 : 1,
                    $n['status'] === 'habis' ? 0 : 1,
                    $n['stok'],
                ])
                ->take($batas)
                ->values();
        } catch (Throwable $e) {
            // Notifikasi tidak boleh sampai merusak halaman.
            return collect();
        }
    }

    /**
     * Peringatan bahan habis-pakai (minyak, gas) per kasir. Sebuah pasangan (bahan, kasir)
     * muncul kalau jatahnya sudah habis atau kasir sudah menekan "Minta ke
     * Admin" yang belum dipenuhi. Kasir hanya melihat miliknya, admin melihat semua.
     */
    private static function peringatanBahan(?int $untukKasirId): Collection
    {
        // Kemasan tidak ikut: peringatannya lewat jalur menu (lihat ambil()).
        $bahans = BahanOperasional::query()->where('is_active', true)->habisPakai()->get()->keyBy('id');

        $menunggu = PermintaanBahanOperasional::query()
            ->menunggu()
            ->when($untukKasirId, fn ($q) => $q->where('kasir_id', $untukKasirId))
            ->get()
            ->keyBy(fn ($p) => $p->bahan_operasional_id . '-' . $p->kasir_id);

        $baris = PergerakanStokBahanOperasional::query()
            ->whereNotNull('kasir_id')
            ->when($untukKasirId, fn ($q) => $q->where('kasir_id', $untukKasirId))
            ->selectRaw('bahan_operasional_id, kasir_id, jenis, SUM(jumlah) as total')
            ->groupBy('bahan_operasional_id', 'kasir_id', 'jenis')
            ->get()
            ->groupBy(fn ($r) => $r->bahan_operasional_id . '-' . $r->kasir_id);

        // Pasangan yang habis (pernah diberi, sisanya 0) + yang sedang meminta.
        $kunciHabis = $baris->filter(function ($grup) {
            $masuk = (int) $grup->firstWhere('jenis', 'masuk')?->total;
            $keluar = (int) $grup->firstWhere('jenis', 'keluar')?->total;

            return $grup->firstWhere('jenis', 'masuk') && ($masuk - $keluar) <= 0;
        })->keys();

        // Kasir: kiriman admin yang menunggu konfirmasi "Sudah / Belum" jadi
        // pengingat sendiri. Bahan yang punya kiriman tidak ditulis "habis"
        // lagi karena yang perlu dilakukan kasir adalah konfirmasi.
        $kirimanKasir = $untukKasirId
            ? PenerimaanBahanOperasional::query()->menunggu()->where('kasir_id', $untukKasirId)->get()->groupBy('bahan_operasional_id')
            : collect();

        $kunci = $kunciHabis->merge($menunggu->keys())->unique()
            ->reject(fn ($k) => $kirimanKasir->has((int) explode('-', $k)[0]))
            ->values();

        $namaKasir = $untukKasirId
            ? collect()
            : User::whereIn('id', $kunci->map(fn ($k) => (int) explode('-', $k)[1])->unique())->pluck('name', 'id');

        $pengingatKiriman = $kirimanKasir->map(function ($daftar, $bahanId) use ($bahans) {
            $bahan = $bahans->get((int) $bahanId);

            if (! $bahan) {
                return null;
            }

            $jumlah = (int) $daftar->sum('jumlah');

            return [
                'nama' => $bahan->nama,
                'tipe' => 'Bahan Operasional',
                'stok' => $jumlah,
                'status' => 'menipis',
                'pesan' => 'dikirim admin ' . $bahan->labelJumlah($jumlah) . ', konfirmasi sudah diterima atau belum',
            ];
        })->filter()->values();

        return $pengingatKiriman->concat($kunci->map(function ($k) use ($bahans, $menunggu, $namaKasir, $untukKasirId) {
            [$bahanId, $kasirId] = array_map('intval', explode('-', $k));
            $bahan = $bahans->get($bahanId);

            if (! $bahan) {
                return null;
            }

            $nama = $bahan->nama;
            if (! $untukKasirId) {
                $nama .= ' (' . ($namaKasir[$kasirId] ?? 'Kasir') . ')';
            }

            $sedangMinta = $menunggu->has($k);

            if ($untukKasirId) {
                $pesan = $sedangMinta
                    ? 'sudah habis, permintaanmu menunggu admin'
                    : 'sudah habis, minta lagi ke admin';
            } else {
                $pesan = $sedangMinta
                    ? 'habis, kasir minta tambahan'
                    : 'sudah habis, belum ada jatah baru';
            }

            return [
                'nama' => $nama,
                'tipe' => 'Bahan Operasional',
                'stok' => 0,
                'status' => 'habis',
                'pesan' => $pesan,
            ];
        }))->filter()->values();
    }

    /**
     * Hitung stok tiap pasangan (barang, kasir) dari log, lalu ambil yang
     * sudah menipis/habis. Hanya pasangan yang pernah diisi stok masuk
     * (sudah "dikelola") yang dihitung.
     */
    private static function peringatanPerKasir(Collection $barangs, $queryLog, string $kolomBarang, ?int $untukKasirId, \Closure $tipe): Collection
    {
        $baris = $queryLog
            ->whereNotNull('kasir_id')
            ->when($untukKasirId, fn ($q) => $q->where('kasir_id', $untukKasirId))
            ->selectRaw("{$kolomBarang} as barang_id, kasir_id, jenis, SUM(jumlah) as total")
            ->groupBy($kolomBarang, 'kasir_id', 'jenis')
            ->get()
            ->groupBy(fn ($r) => $r->barang_id . '-' . $r->kasir_id);

        $namaKasir = $untukKasirId
            ? collect()
            : User::whereIn('id', $baris->map(fn ($g) => $g->first()->kasir_id)->unique())->pluck('name', 'id');

        $hasil = collect();

        foreach ($baris as $grup) {
            $barang = $barangs->firstWhere('id', $grup->first()->barang_id);
            if (! $barang) {
                continue;
            }

            $masukRow = $grup->firstWhere('jenis', 'masuk');
            if (! $masukRow) {
                continue; // belum dikelola untuk kasir ini
            }

            $stok = (int) $masukRow->total - (int) $grup->firstWhere('jenis', 'keluar')?->total;

            if ($stok > $barang->stok_minimum) {
                continue;
            }

            $nama = $barang->nama;
            if (! $untukKasirId) {
                $nama .= ' (' . ($namaKasir[$grup->first()->kasir_id] ?? 'Kasir') . ')';
            }

            $hasil->push([
                'nama' => $nama,
                'tipe' => $tipe($barang),
                'stok' => $stok,
                'status' => $stok <= 0 ? 'habis' : 'menipis',
            ]);
        }

        return $hasil;
    }
}
