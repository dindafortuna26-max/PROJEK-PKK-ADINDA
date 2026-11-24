<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BahanOperasional;
use App\Models\Menu;
use App\Models\PenerimaanBahanOperasional;
use App\Models\PergerakanStok;
use App\Models\PergerakanStokBahanOperasional;
use App\Models\PergerakanStokTopping;
use App\Models\PermintaanBahanOperasional;
use App\Models\Topping;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StokController extends Controller
{
    /**
     * GET /admin/stok
     * Satu halaman, dua tab: "Data Stok" (stok berjalan tiap menu) &
     * "Stok Masuk" (riwayat input stok). Keduanya dipaginasi terpisah
     * (page name beda) supaya link pagination-nya nggak bentrok.
     */
    public function index(Request $request): View
    {
        // STOK PER KASIR: tiap kasir punya stok sendiri. Satu baris Data Stok
        // = satu pasangan (menu, kasir). Filter ?kasir_id= menampilkan SEMUA
        // menu untuk satu kasir; tanpa filter, tampil pasangan yang sudah
        // punya riwayat (menu yang belum dibagi ke kasir manapun tampil
        // satu baris tanpa kasir).
        // Kasir NONAKTIF tidak ikut di Manajemen Stok (dropdown filter, baris
        // Data Stok, maupun pilihan kasir di modal) — jadi $kasirs sengaja
        // hanya berisi kasir aktif. Riwayat lama milik kasir nonaktif tetap
        // tersimpan di database.
        $kasirs = User::kasir()->where('is_active', true)->orderBy('name')->get();
        $kasirAktif = $kasirs;
        $kasirTerpilih = $request->filled('kasir_id')
            ? $kasirs->firstWhere('id', (int) $request->input('kasir_id'))
            : null;

        $menus = Menu::query()
            ->where('is_active', true) // menu nonaktif tidak ikut di Manajemen Stok
            ->with('kategori')
            ->when($request->filled('cari'), function ($query) use ($request) {
                $query->where('nama', 'like', '%' . $request->input('cari') . '%');
            })
            ->orderBy('nama')
            ->get();

        $agregat = PergerakanStok::query()
            ->whereNotNull('kasir_id')
            ->when($kasirTerpilih, fn ($q) => $q->where('kasir_id', $kasirTerpilih->id))
            ->selectRaw('menu_id, kasir_id, jenis, SUM(jumlah) as total')
            ->groupBy('menu_id', 'kasir_id', 'jenis')
            ->get()
            ->groupBy(fn ($r) => $r->menu_id . '-' . $r->kasir_id);

        $terakhirMasuk = PergerakanStok::query()
            ->whereNotNull('kasir_id')
            ->when($kasirTerpilih, fn ($q) => $q->where('kasir_id', $kasirTerpilih->id))
            ->where('jenis', 'masuk')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn ($r) => $r->menu_id . '-' . $r->kasir_id);

        $buatBarisMenu = function (Menu $menu, ?User $kasir) use ($agregat, $terakhirMasuk) {
            $kunci = $menu->id . '-' . ($kasir?->id);
            $baris = $agregat->get($kunci, collect());
            $dikelola = $baris->firstWhere('jenis', 'masuk') !== null;
            $masuk = (int) $baris->firstWhere('jenis', 'masuk')?->total;
            $keluar = (int) $baris->firstWhere('jenis', 'keluar')?->total;
            $stok = $masuk - $keluar;

            $status = match (true) {
                ! $dikelola => 'belum_dikelola',
                $stok <= 0 => 'habis',
                $stok <= $menu->stok_minimum => 'menipis',
                default => 'aman',
            };

            return (object) [
                'menu' => $menu,
                'kasir' => $kasir,
                'stok' => $stok,
                'status' => $status,
                'terakhir_masuk' => $terakhirMasuk->get($kunci)?->first(),
            ];
        };

        $dataStok = collect();
        foreach ($menus as $menu) {
            if ($kasirTerpilih) {
                $dataStok->push($buatBarisMenu($menu, $kasirTerpilih));
                continue;
            }

            $idKasirMenu = $agregat
                ->filter(fn ($g) => (int) $g->first()->menu_id === $menu->id)
                ->map(fn ($g) => (int) $g->first()->kasir_id)
                ->sort()
                ->values();

            $barisMenu = $idKasirMenu
                ->map(fn ($kid) => $kasirs->firstWhere('id', $kid))
                ->filter();

            if ($barisMenu->isEmpty()) {
                $dataStok->push($buatBarisMenu($menu, null));
            } else {
                foreach ($barisMenu as $kasir) {
                    $dataStok->push($buatBarisMenu($menu, $kasir));
                }
            }
        }

        $perPage = 10;
        $halaman = (int) $request->input('stok_page', 1);
        $dataStokPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $dataStok->forPage($halaman, $perPage)->values(),
            $dataStok->count(),
            $perPage,
            $halaman,
            ['path' => $request->url(), 'pageName' => 'stok_page', 'query' => $request->query()]
        );

        // Riwayat menampilkan stok MASUK & KELUAR. Filter: ?riwayat_menu=masuk|keluar.
        $filterRiwayatMenu = in_array($request->input('riwayat_menu'), ['masuk', 'keluar'], true)
            ? $request->input('riwayat_menu')
            : '';

        $riwayatMasuk = PergerakanStok::query()
            ->with(['menu', 'user', 'kasir'])
            ->whereHas('menu', fn ($q) => $q->where('is_active', true))
            ->when($kasirTerpilih, fn ($q) => $q->where('kasir_id', $kasirTerpilih->id))
            ->when($filterRiwayatMenu !== '', fn ($q) => $q->where('jenis', $filterRiwayatMenu))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'riwayat_page')
            ->withQueryString();

        $menuAktif = Menu::where('is_active', true)->orderBy('nama')->get();

        // ===================== STOK TOPPING & BUMBU =====================
        // Pola sama persis dengan Data Stok menu di atas, cuma sumbernya
        // dari tabel pergerakan_stok_topping (lihat Topping model).
        $toppings = Topping::query()
            ->where('is_active', true) // topping/bumbu nonaktif tidak ikut
            ->when($request->filled('cari'), function ($query) use ($request) {
                $query->where('nama', 'like', '%' . $request->input('cari') . '%');
            })
            ->orderBy('nama')
            ->get();

        $agregatTopping = PergerakanStokTopping::query()
            ->whereNotNull('kasir_id')
            ->when($kasirTerpilih, fn ($q) => $q->where('kasir_id', $kasirTerpilih->id))
            ->selectRaw('topping_id, kasir_id, jenis, SUM(jumlah) as total')
            ->groupBy('topping_id', 'kasir_id', 'jenis')
            ->get()
            ->groupBy(fn ($r) => $r->topping_id . '-' . $r->kasir_id);

        $terakhirMasukTopping = PergerakanStokTopping::query()
            ->whereNotNull('kasir_id')
            ->when($kasirTerpilih, fn ($q) => $q->where('kasir_id', $kasirTerpilih->id))
            ->where('jenis', 'masuk')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn ($r) => $r->topping_id . '-' . $r->kasir_id);

        $buatBarisTopping = function (Topping $topping, ?User $kasir) use ($agregatTopping, $terakhirMasukTopping) {
            $kunci = $topping->id . '-' . ($kasir?->id);
            $baris = $agregatTopping->get($kunci, collect());
            $dikelola = $baris->firstWhere('jenis', 'masuk') !== null;
            $masuk = (int) $baris->firstWhere('jenis', 'masuk')?->total;
            $keluar = (int) $baris->firstWhere('jenis', 'keluar')?->total;
            $stok = $masuk - $keluar;

            $status = match (true) {
                ! $dikelola => 'belum_dikelola',
                $stok <= 0 => 'habis',
                $stok <= $topping->stok_minimum => 'menipis',
                default => 'aman',
            };

            return (object) [
                'topping' => $topping,
                'kasir' => $kasir,
                'stok' => $stok,
                'status' => $status,
                'terakhir_masuk' => $terakhirMasukTopping->get($kunci)?->first(),
            ];
        };

        $dataStokTopping = collect();
        foreach ($toppings as $topping) {
            if ($kasirTerpilih) {
                $dataStokTopping->push($buatBarisTopping($topping, $kasirTerpilih));
                continue;
            }

            $barisTopping = $agregatTopping
                ->filter(fn ($g) => (int) $g->first()->topping_id === $topping->id)
                ->map(fn ($g) => (int) $g->first()->kasir_id)
                ->sort()
                ->values()
                ->map(fn ($kid) => $kasirs->firstWhere('id', $kid))
                ->filter();

            if ($barisTopping->isEmpty()) {
                $dataStokTopping->push($buatBarisTopping($topping, null));
            } else {
                foreach ($barisTopping as $kasir) {
                    $dataStokTopping->push($buatBarisTopping($topping, $kasir));
                }
            }
        }

        $perPageTopping = 10;
        $halamanTopping = (int) $request->input('topping_page', 1);
        $dataStokToppingPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $dataStokTopping->forPage($halamanTopping, $perPageTopping)->values(),
            $dataStokTopping->count(),
            $perPageTopping,
            $halamanTopping,
            ['path' => $request->url(), 'pageName' => 'topping_page', 'query' => $request->query()]
        );

        // Sama: stok MASUK & KELUAR. Filter: ?riwayat_topping=masuk|keluar.
        $filterRiwayatTopping = in_array($request->input('riwayat_topping'), ['masuk', 'keluar'], true)
            ? $request->input('riwayat_topping')
            : '';

        $riwayatMasukTopping = PergerakanStokTopping::query()
            ->with(['topping', 'user', 'kasir'])
            ->whereHas('topping', fn ($q) => $q->where('is_active', true))
            ->when($kasirTerpilih, fn ($q) => $q->where('kasir_id', $kasirTerpilih->id))
            ->when($filterRiwayatTopping !== '', fn ($q) => $q->where('jenis', $filterRiwayatTopping))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'riwayat_topping_page')
            ->withQueryString();

        $toppingAktif = Topping::where('is_active', true)->orderBy('nama')->get();

        // ===================== BAHAN OPERASIONAL =====================
        // Minyak goreng & gas: MANUAL dan PER KASIR (tidak berkurang otomatis
        // tiap transaksi). Admin memberi jatah (mis. 1 tabung gas + 5 liter
        // minyak per hari) ke tiap kasir. Kalau habis, kasir "Minta ke Admin"
        // dan permintaannya muncul di sini + lonceng notifikasi.
        $bahanList = BahanOperasional::query()
            ->where('is_active', true) // bahan nonaktif tidak ikut
            ->with('ukuran')
            ->when($request->filled('cari'), function ($query) use ($request) {
                $query->where('nama', 'like', '%' . $request->input('cari') . '%');
            })
            ->orderBy('jenis')
            ->orderBy('nama')
            ->get();

        $agregatBahan = PergerakanStokBahanOperasional::query()
            ->whereNotNull('kasir_id')
            ->when($kasirTerpilih, fn ($q) => $q->where('kasir_id', $kasirTerpilih->id))
            ->selectRaw('bahan_operasional_id, kasir_id, jenis, SUM(jumlah) as total')
            ->groupBy('bahan_operasional_id', 'kasir_id', 'jenis')
            ->get()
            ->groupBy(fn ($r) => $r->bahan_operasional_id . '-' . $r->kasir_id);

        $terakhirMasukBahan = PergerakanStokBahanOperasional::query()
            ->whereNotNull('kasir_id')
            ->when($kasirTerpilih, fn ($q) => $q->where('kasir_id', $kasirTerpilih->id))
            ->where('jenis', 'masuk')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn ($r) => $r->bahan_operasional_id . '-' . $r->kasir_id);

        $permintaanBahan = PermintaanBahanOperasional::query()
            ->menunggu()
            ->when($kasirTerpilih, fn ($q) => $q->where('kasir_id', $kasirTerpilih->id))
            ->get()
            ->keyBy(fn ($p) => $p->bahan_operasional_id . '-' . $p->kasir_id);

        // Kiriman minyak & gas yang belum dikonfirmasi kasir (per bahan-kasir).
        $menungguTerima = PenerimaanBahanOperasional::query()
            ->menunggu()
            ->when($kasirTerpilih, fn ($q) => $q->where('kasir_id', $kasirTerpilih->id))
            ->get()
            ->groupBy(fn ($p) => $p->bahan_operasional_id . '-' . $p->kasir_id);

        // Kasir yang ditampilkan: yang dipilih di filter, atau semua kasir aktif.
        $kasirBahan = $kasirTerpilih
            ? collect([$kasirTerpilih])
            : $kasirs;

        $dataStokBahan = collect();
        foreach ($bahanList as $bahan) {
            foreach ($kasirBahan as $kasir) {
                $kunci = $bahan->id . '-' . $kasir->id;
                $baris = $agregatBahan->get($kunci, collect());
                $dikelola = $baris->firstWhere('jenis', 'masuk') !== null;
                $stok = max(0, (int) $baris->firstWhere('jenis', 'masuk')?->total - (int) $baris->firstWhere('jenis', 'keluar')?->total);

                $dataStokBahan->push((object) [
                    'bahan' => $bahan,
                    'kasir' => $kasir,
                    'stok' => $stok,
                    'status' => ! $dikelola ? 'belum_dikelola' : ($stok <= 0 ? 'habis' : 'aman'),
                    'terakhir_masuk' => $terakhirMasukBahan->get($kunci)?->first(),
                    'permintaan' => $permintaanBahan->get($kunci),
                    'menunggu_terima' => $menungguTerima->get($kunci, collect()),
                ]);
            }
        }

        // Filter riwayat: Semua / Masuk / Keluar (?riwayat_bahan=masuk|keluar).
        $filterRiwayatBahan = in_array($request->input('riwayat_bahan'), ['masuk', 'keluar'], true)
            ? $request->input('riwayat_bahan')
            : '';

        $riwayatBahan = PergerakanStokBahanOperasional::query()
            ->with(['bahan', 'user', 'kasir'])
            ->whereHas('bahan', fn ($q) => $q->where('is_active', true))
            ->whereNotNull('kasir_id')
            ->when($kasirTerpilih, fn ($q) => $q->where('kasir_id', $kasirTerpilih->id))
            ->when($filterRiwayatBahan !== '', fn ($q) => $q->where('jenis', $filterRiwayatBahan))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'riwayat_bahan_page')
            ->withQueryString();

        $bahanAktif = BahanOperasional::with('ukuran')->where('is_active', true)->orderBy('jenis')->orderBy('nama')->get();

        // Kartu ringkasan SEKARANG PER KELOMPOK (menu / topping & bumbu /
        // bahan operasional) supaya angkanya tidak campur dan tidak bikin
        // pusing. Menu & topping tetap seperti sebelumnya: Aman/Menipis =
        // total KUANTITAS barang, Habis = jumlah PRODUK. Bahan operasional
        // dihitung per ITEM (satuannya beda-beda: liter, tabung).
        $hitungKuantitas = function ($koleksi) {
            $aman = $koleksi->where('status', 'aman')->sum('stok');
            $menipis = $koleksi->where('status', 'menipis')->sum('stok');

            return [
                'aman' => $aman,
                'menipis' => $menipis,
                'habis' => $koleksi->where('status', 'habis')->count(),
                'total' => $aman + $menipis,
            ];
        };

        $ringkasan = [
            'menu' => $hitungKuantitas($dataStok),
            'topping' => $hitungKuantitas($dataStokTopping),
            // Bahan operasional dihitung per pasangan (bahan, kasir). Kolom
            // "menipis" dipakai untuk jumlah kasir yang sedang MINTA ke admin.
            'bahan' => [
                'aman' => $dataStokBahan->where('status', 'aman')->count(),
                'menipis' => $dataStokBahan->filter(fn ($i) => $i->permintaan !== null)->count(),
                'habis' => $dataStokBahan->where('status', 'habis')->count(),
                'total' => $dataStokBahan->count(),
            ],
        ];

        return view('admin.stok.index', [
            'dataStok' => $dataStokPaginated,
            'riwayatMasuk' => $riwayatMasuk,
            'ringkasan' => $ringkasan,
            'menuAktif' => $menuAktif,
            'dataStokTopping' => $dataStokToppingPaginated,
            'riwayatMasukTopping' => $riwayatMasukTopping,
            'toppingAktif' => $toppingAktif,
            'dataStokBahan' => $dataStokBahan,
            'riwayatBahan' => $riwayatBahan,
            'filterRiwayatBahan' => $filterRiwayatBahan,
            'filterRiwayatMenu' => $filterRiwayatMenu,
            'filterRiwayatTopping' => $filterRiwayatTopping,
            'bahanAktif' => $bahanAktif,
            'kasirs' => $kasirs,
            'kasirAktif' => $kasirAktif,
            'kasirTerpilih' => $kasirTerpilih,
        ]);
    }

    /**
     * POST /admin/stok
     * Modal "Tambah Stok" — input stok masuk manual (restock).
     */
    public function storeMasuk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kasir_id' => ['required', Rule::exists('users', 'id')->where('role', 'kasir')],
            'menu_id' => ['required', 'exists:menus,id'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'kasir_id.required' => 'Pilih kasir yang menerima stok dulu.',
            'kasir_id.exists' => 'Kasir yang dipilih tidak ditemukan.',
            'menu_id.required' => 'Pilih barang/menunya dulu.',
            'menu_id.exists' => 'Menu yang dipilih tidak ditemukan.',
            'jumlah.required' => 'Jumlah stok masuk wajib diisi.',
            'jumlah.min' => 'Jumlah minimal 1.',
            'tanggal.required' => 'Tanggal wajib diisi.',
        ]);

        $menu = Menu::findOrFail($data['menu_id']);
        $kasir = User::findOrFail($data['kasir_id']);

        PergerakanStok::create([
            'menu_id' => $menu->id,
            'user_id' => Auth::id(),
            'kasir_id' => $kasir->id, // stok ini milik kasir tersebut
            'jenis' => 'masuk',
            'jumlah' => $data['jumlah'],
            'keterangan' => $data['keterangan'] ?: 'Stok masuk',
            'tanggal' => $data['tanggal'],
        ]);

        return back()->with('success', "Stok \"{$menu->nama}\" untuk {$kasir->name} bertambah {$data['jumlah']}.");
    }

    /**
     * POST /admin/stok/jatah-harian
     * Satu klik: tiap kasir aktif dapat jatah harian tiap menu aktif (angka
     * jatah diatur lewat tombol Edit stok pada baris menu). Kasir yang hari
     * ini sudah menerima stok masuk menu itu dilewati supaya tidak dobel.
     */
    public function bagikanJatahHarianMenu(): RedirectResponse
    {
        $kasirs = User::kasir()->where('is_active', true)->get();
        $menus = Menu::query()->where('is_active', true)->where('jatah_harian', '>', 0)->get();

        if ($kasirs->isEmpty()) {
            return back()->with('error', 'Belum ada kasir aktif untuk diberi jatah.');
        }

        if ($menus->isEmpty()) {
            return back()->with('error', 'Belum ada menu dengan jatah harian. Pilih kasir di filter, lalu isi lewat tombol Edit (pensil) pada baris menu.');
        }

        $hariIni = now()->toDateString();
        $diberi = 0;
        $dilewati = 0;

        DB::transaction(function () use ($kasirs, $menus, $hariIni, &$diberi, &$dilewati) {
            foreach ($menus as $menu) {
                foreach ($kasirs as $kasir) {
                    $sudah = PergerakanStok::query()
                        ->where('menu_id', $menu->id)
                        ->where('kasir_id', $kasir->id)
                        ->where('jenis', 'masuk')
                        ->whereDate('tanggal', $hariIni)
                        ->exists();

                    if ($sudah) {
                        $dilewati++;
                        continue;
                    }

                    PergerakanStok::create([
                        'menu_id' => $menu->id,
                        'user_id' => Auth::id(),
                        'kasir_id' => $kasir->id,
                        'jenis' => 'masuk',
                        'jumlah' => $menu->jatah_harian,
                        'keterangan' => 'Jatah harian',
                        'tanggal' => $hariIni,
                    ]);

                    $diberi++;
                }
            }
        });

        if ($diberi === 0) {
            return back()->with('error', 'Semua kasir sudah menerima jatah menu hari ini.');
        }

        $pesan = "Jatah harian menu dibagikan ({$diberi} pemberian).";
        if ($dilewati > 0) {
            $pesan .= " {$dilewati} dilewati karena sudah menerima hari ini.";
        }

        return back()->with('success', $pesan);
    }

    /**
     * PATCH /admin/stok/{menu}/koreksi
     * Modal "Edit" di baris Data Stok — set stok jadi angka baru.
     * Selisihnya otomatis dicatat sebagai pergerakan masuk/keluar biar
     * riwayat log tetap jadi satu-satunya sumber kebenaran (bukan
     * nimpa angka langsung).
     */
    public function koreksi(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate([
            'kasir_id' => ['required', Rule::exists('users', 'id')->where('role', 'kasir')],
            'stok_baru' => ['required', 'integer', 'min:0'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'jatah_harian' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'stok_baru.required' => 'Stok barunya wajib diisi.',
            'stok_baru.min' => 'Stok tidak boleh minus.',
            'stok_minimum.required' => 'Batas stok menipis wajib diisi.',
            'jatah_harian.min' => 'Jatah harian tidak boleh minus.',
            'jatah_harian.max' => 'Jatah harian terlalu besar (maksimal 10000).',
        ]);

        DB::transaction(function () use ($menu, $data) {
            // Koreksi hanya menyentuh stok MILIK kasir yang dipilih.
            $stokSekarang = $menu->stokUntukKasir((int) $data['kasir_id']);
            $selisih = $data['stok_baru'] - $stokSekarang;

            if ($selisih !== 0) {
                PergerakanStok::create([
                    'menu_id' => $menu->id,
                    'user_id' => Auth::id(),
                    'kasir_id' => $data['kasir_id'],
                    'jenis' => $selisih > 0 ? 'masuk' : 'keluar',
                    'jumlah' => abs($selisih),
                    'keterangan' => $data['keterangan'] ?: 'Koreksi stok manual',
                    'tanggal' => now()->toDateString(),
                ]);
            }

            $menu->update([
                'stok_minimum' => $data['stok_minimum'],
                // Kosong = jatah lama dipertahankan.
                'jatah_harian' => $data['jatah_harian'] ?? $menu->jatah_harian,
            ]);
        });

        return back()->with('success', "Stok \"{$menu->nama}\" berhasil dikoreksi.");
    }

    /**
     * DELETE /admin/stok/{menu}/riwayat
     * Tombol hapus di baris Data Stok — hapus SEMUA riwayat pergerakan
     * stok menu ini (balik jadi "belum dikelola"). Dipakai kalau
     * riwayat stoknya salah total dan mau diulang dari nol.
     */
    public function hapusRiwayat(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate([
            'kasir_id' => ['required', 'exists:users,id'],
        ]);

        // Hanya riwayat stok milik kasir yang dipilih; kasir lain tidak tersentuh.
        $menu->pergerakanStok()->where('kasir_id', $data['kasir_id'])->delete();

        return back()->with('success', "Riwayat stok \"{$menu->nama}\" untuk kasir tersebut berhasil dihapus, stoknya balik ke 0.");
    }

    /**
     * DELETE /admin/stok/riwayat/{pergerakanStok}
     * Hapus satu entri di tab "Stok Masuk" (misal salah input).
     */
    public function hapusEntriMasuk(PergerakanStok $pergerakanStok): RedirectResponse
    {
        abort_if($pergerakanStok->jenis !== 'masuk', 403);

        $nama = $pergerakanStok->menu->nama ?? 'menu';
        $pergerakanStok->delete();

        return back()->with('success', "Entri stok masuk \"{$nama}\" berhasil dihapus.");
    }

    // ========================================================
    // ============ STOK TOPPING & BUMBU (SAUS/BUBUK) ==========
    // Method di bawah ini sama persis polanya dengan method stok
    // menu di atas, cuma target tabelnya pergerakan_stok_topping.
    // ========================================================

    /**
     * POST /admin/stok/topping
     */
    public function storeMasukTopping(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kasir_id' => ['required', Rule::exists('users', 'id')->where('role', 'kasir')],
            'topping_id' => ['required', 'exists:toppings,id'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'kasir_id.required' => 'Pilih kasir yang menerima stok dulu.',
            'kasir_id.exists' => 'Kasir yang dipilih tidak ditemukan.',
            'topping_id.required' => 'Pilih saus/bubuk tabur dulu.',
            'jumlah.required' => 'Jumlah stok masuk wajib diisi.',
            'jumlah.min' => 'Jumlah minimal 1.',
            'tanggal.required' => 'Tanggal wajib diisi.',
        ]);

        $topping = Topping::findOrFail($data['topping_id']);
        $kasir = User::findOrFail($data['kasir_id']);

        PergerakanStokTopping::create([
            'topping_id' => $topping->id,
            'user_id' => Auth::id(),
            'kasir_id' => $kasir->id, // stok ini milik kasir tersebut
            'jenis' => 'masuk',
            'jumlah' => $data['jumlah'],
            'keterangan' => $data['keterangan'] ?: 'Stok masuk',
            'tanggal' => $data['tanggal'],
        ]);

        return back()->with('success', "Stok \"{$topping->nama}\" untuk {$kasir->name} bertambah {$data['jumlah']}.");
    }

    /**
     * PATCH /admin/stok/topping/{topping}/koreksi
     */
    public function koreksiTopping(Request $request, Topping $topping): RedirectResponse
    {
        $data = $request->validate([
            'kasir_id' => ['required', Rule::exists('users', 'id')->where('role', 'kasir')],
            'stok_baru' => ['required', 'integer', 'min:0'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'jatah_harian' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'stok_baru.required' => 'Stok barunya wajib diisi.',
            'stok_baru.min' => 'Stok tidak boleh minus.',
            'stok_minimum.required' => 'Batas stok menipis wajib diisi.',
            'jatah_harian.min' => 'Jatah harian tidak boleh minus.',
            'jatah_harian.max' => 'Jatah harian terlalu besar (maksimal 10000).',
        ]);

        DB::transaction(function () use ($topping, $data) {
            // Koreksi hanya menyentuh stok MILIK kasir yang dipilih.
            $stokSekarang = $topping->stokUntukKasir((int) $data['kasir_id']);
            $selisih = $data['stok_baru'] - $stokSekarang;

            if ($selisih !== 0) {
                PergerakanStokTopping::create([
                    'topping_id' => $topping->id,
                    'user_id' => Auth::id(),
                    'kasir_id' => $data['kasir_id'],
                    'jenis' => $selisih > 0 ? 'masuk' : 'keluar',
                    'jumlah' => abs($selisih),
                    'keterangan' => $data['keterangan'] ?: 'Koreksi stok manual',
                    'tanggal' => now()->toDateString(),
                ]);
            }

            $topping->update([
                'stok_minimum' => $data['stok_minimum'],
                // Kosong = jatah lama dipertahankan.
                'jatah_harian' => $data['jatah_harian'] ?? $topping->jatah_harian,
            ]);
        });

        return back()->with('success', "Stok \"{$topping->nama}\" berhasil dikoreksi.");
    }

    /**
     * POST /admin/stok/topping/jatah-harian
     * Satu klik: tiap kasir aktif dapat jatah harian tiap topping/bumbu aktif
     * (angka jatah diatur lewat tombol Edit pada baris topping). Kasir yang
     * hari ini sudah menerima stok masuk topping itu dilewati supaya tidak dobel.
     */
    public function bagikanJatahHarianTopping(): RedirectResponse
    {
        $kasirs = User::kasir()->where('is_active', true)->get();
        $toppings = Topping::query()->where('is_active', true)->where('jatah_harian', '>', 0)->get();

        if ($kasirs->isEmpty()) {
            return back()->with('error', 'Belum ada kasir aktif untuk diberi jatah.');
        }

        if ($toppings->isEmpty()) {
            return back()->with('error', 'Belum ada topping/bumbu dengan jatah harian. Pilih kasir di filter, lalu isi lewat tombol Edit (pensil) pada baris topping/bumbu.');
        }

        $hariIni = now()->toDateString();
        $diberi = 0;
        $dilewati = 0;

        DB::transaction(function () use ($kasirs, $toppings, $hariIni, &$diberi, &$dilewati) {
            foreach ($toppings as $topping) {
                foreach ($kasirs as $kasir) {
                    $sudah = PergerakanStokTopping::query()
                        ->where('topping_id', $topping->id)
                        ->where('kasir_id', $kasir->id)
                        ->where('jenis', 'masuk')
                        ->whereDate('tanggal', $hariIni)
                        ->exists();

                    if ($sudah) {
                        $dilewati++;
                        continue;
                    }

                    PergerakanStokTopping::create([
                        'topping_id' => $topping->id,
                        'user_id' => Auth::id(),
                        'kasir_id' => $kasir->id,
                        'jenis' => 'masuk',
                        'jumlah' => $topping->jatah_harian,
                        'keterangan' => 'Jatah harian',
                        'tanggal' => $hariIni,
                    ]);

                    $diberi++;
                }
            }
        });

        if ($diberi === 0) {
            return back()->with('error', 'Semua kasir sudah menerima jatah topping/bumbu hari ini.');
        }

        $pesan = "Jatah harian topping & bumbu dibagikan ({$diberi} pemberian).";
        if ($dilewati > 0) {
            $pesan .= " {$dilewati} dilewati karena sudah menerima hari ini.";
        }

        return back()->with('success', $pesan);
    }

    /**
     * DELETE /admin/stok/topping/{topping}/riwayat
     */
    public function hapusRiwayatTopping(Request $request, Topping $topping): RedirectResponse
    {
        $data = $request->validate([
            'kasir_id' => ['required', 'exists:users,id'],
        ]);

        // Hanya riwayat stok milik kasir yang dipilih; kasir lain tidak tersentuh.
        $topping->pergerakanStok()->where('kasir_id', $data['kasir_id'])->delete();

        return back()->with('success', "Riwayat stok \"{$topping->nama}\" untuk kasir tersebut berhasil dihapus, stoknya balik ke 0.");
    }

    /**
     * DELETE /admin/stok/topping/riwayat/{pergerakanStokTopping}
     */
    public function hapusEntriMasukTopping(PergerakanStokTopping $pergerakanStokTopping): RedirectResponse
    {
        abort_if($pergerakanStokTopping->jenis !== 'masuk', 403);

        $nama = $pergerakanStokTopping->topping->nama ?? 'topping';
        $pergerakanStokTopping->delete();

        return back()->with('success', "Entri stok masuk \"{$nama}\" berhasil dihapus.");
    }

    // ========================================================
    // ============== STOK BAHAN OPERASIONAL ==================
    // Minyak goreng & gas: MANUAL per kasir. Admin memberi jatah dalam
    // liter / tabung (mis. 5 liter minyak + 1 tabung gas per hari).
    // Stok TIDAK berkurang otomatis tiap transaksi. Kalau habis, kasir
    // menekan "Minta ke Admin" (lihat Kasir\StokController::mintaBahan).
    // ========================================================

    /**
     * POST /admin/stok/bahan
     * Beri jatah: pilih kasir, bahan, dan jumlah (liter / tabung).
     */
    public function storeMasukBahan(Request $request): RedirectResponse
    {
        // Batas sekali input beda per jenis: liter/tabung kecil, kemasan (pcs) lebih besar.
        $bahanDipilih = BahanOperasional::find($request->input('bahan_operasional_id'));
        $maks = $bahanDipilih?->maksSekaliMasuk() ?? BahanOperasional::MAKS_SEKALI_MASUK;

        $data = $request->validate([
            'kasir_id' => ['required', Rule::exists('users', 'id')->where('role', 'kasir')],
            'bahan_operasional_id' => ['required', 'exists:bahan_operasional,id'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:' . $maks],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'kasir_id.required' => 'Pilih kasir yang menerima dulu.',
            'kasir_id.exists' => 'Kasir yang dipilih tidak ditemukan.',
            'bahan_operasional_id.required' => 'Pilih bahan operasionalnya dulu.',
            'jumlah.required' => 'Jumlah wajib diisi.',
            'jumlah.min' => 'Jumlah minimal 1.',
            'jumlah.max' => "Sekali input maksimal {$maks}. Kalau lebih, input lagi.",
            'tanggal.required' => 'Tanggal wajib diisi.',
        ]);

        $bahan = BahanOperasional::findOrFail($data['bahan_operasional_id']);
        $kasir = User::findOrFail($data['kasir_id']);

        // Minyak & gas (manual): TIDAK langsung mengisi stok kasir. Dicatat
        // sebagai kiriman yang menunggu konfirmasi; stok kasir baru terisi
        // setelah kasir menjawab "Sudah" di Cek Stok. Kemasan tetap langsung.
        $menungguKonfirmasi = ! $bahan->is_kemasan;

        DB::transaction(function () use ($bahan, $kasir, $data, $menungguKonfirmasi) {
            if ($menungguKonfirmasi) {
                PenerimaanBahanOperasional::create([
                    'bahan_operasional_id' => $bahan->id,
                    'kasir_id' => $kasir->id,
                    'user_id' => Auth::id(),
                    'jumlah' => $data['jumlah'],
                    'tanggal' => $data['tanggal'],
                    'keterangan' => $data['keterangan'] ?: 'Jatah dari admin',
                ]);
            } else {
                PergerakanStokBahanOperasional::create([
                    'bahan_operasional_id' => $bahan->id,
                    'user_id' => Auth::id(),
                    'kasir_id' => $kasir->id, // stok ini milik kasir tersebut
                    'jenis' => 'masuk',
                    'jumlah' => $data['jumlah'],
                    'sesi' => 0,
                    'keterangan' => $data['keterangan'] ?: 'Jatah dari admin',
                    'tanggal' => $data['tanggal'],
                ]);
            }

            // Kalau kasir ini tadi minta, permintaannya selesai.
            $bahan->tutupPermintaan($kasir->id);
        });

        if ($menungguKonfirmasi) {
            return back()->with('success', "{$bahan->nama} {$bahan->labelJumlah($data['jumlah'])} dikirim ke {$kasir->name}. Stok kasir terisi setelah kasir konfirmasi \"Sudah\" di Cek Stok.");
        }

        return back()->with('success', "{$bahan->nama} {$bahan->labelJumlah($data['jumlah'])} diberikan ke {$kasir->name}.");
    }

    /**
     * POST /admin/stok/bahan/jatah-harian
     * Satu klik: tiap kasir aktif dapat jatah harian tiap bahan aktif
     * (angka jatah diatur di Master Bahan Operasional). Kasir yang hari ini
     * sudah menerima bahan itu dilewati supaya tidak dobel.
     */
    public function bagikanJatahHarian(Request $request): RedirectResponse
    {
        // ?jenis=kemasan | habis_pakai (minyak & gas). Kosong = semuanya.
        $jenis = $request->input('jenis');

        $kasirs = User::kasir()->where('is_active', true)->get();
        $bahans = BahanOperasional::aktif()
            ->where('jatah_harian', '>', 0)
            ->when($jenis === BahanOperasional::JENIS_KEMASAN, fn ($q) => $q->kemasan())
            ->when($jenis === BahanOperasional::JENIS_HABIS_PAKAI, fn ($q) => $q->habisPakai())
            ->get();

        if ($kasirs->isEmpty()) {
            return back()->with('error', 'Belum ada kasir aktif untuk diberi jatah.');
        }

        if ($bahans->isEmpty()) {
            return back()->with('error', $jenis === BahanOperasional::JENIS_KEMASAN
                ? 'Belum ada kemasan dengan jatah harian. Atur dulu di Master → Bahan Operasional.'
                : 'Belum ada bahan dengan jatah harian. Atur dulu di Master → Bahan Operasional.');
        }

        $hariIni = now()->toDateString();
        $diberi = 0;
        $dilewati = 0;

        DB::transaction(function () use ($kasirs, $bahans, $hariIni, &$diberi, &$dilewati) {
            foreach ($bahans as $bahan) {
                foreach ($kasirs as $kasir) {
                    $sudah = PergerakanStokBahanOperasional::query()
                        ->where('bahan_operasional_id', $bahan->id)
                        ->where('kasir_id', $kasir->id)
                        ->where('jenis', 'masuk')
                        ->whereDate('tanggal', $hariIni)
                        ->exists();

                    // Minyak & gas: kiriman hari ini yang masih menunggu konfirmasi
                    // kasir juga dihitung "sudah diberi", supaya tidak dobel.
                    if (! $sudah && ! $bahan->is_kemasan) {
                        $sudah = PenerimaanBahanOperasional::query()
                            ->where('bahan_operasional_id', $bahan->id)
                            ->where('kasir_id', $kasir->id)
                            ->whereDate('tanggal', $hariIni)
                            ->exists();
                    }

                    if ($sudah) {
                        $dilewati++;
                        continue;
                    }

                    if ($bahan->is_kemasan) {
                        PergerakanStokBahanOperasional::create([
                            'bahan_operasional_id' => $bahan->id,
                            'user_id' => Auth::id(),
                            'kasir_id' => $kasir->id,
                            'jenis' => 'masuk',
                            'jumlah' => $bahan->jatah_harian,
                            'sesi' => 0,
                            'keterangan' => 'Jatah harian',
                            'tanggal' => $hariIni,
                        ]);
                    } else {
                        // Minyak & gas: menunggu konfirmasi kasir dulu.
                        PenerimaanBahanOperasional::create([
                            'bahan_operasional_id' => $bahan->id,
                            'kasir_id' => $kasir->id,
                            'user_id' => Auth::id(),
                            'jumlah' => $bahan->jatah_harian,
                            'tanggal' => $hariIni,
                            'keterangan' => 'Jatah harian',
                        ]);
                    }

                    $bahan->tutupPermintaan($kasir->id);
                    $diberi++;
                }
            }
        });

        if ($diberi === 0) {
            return back()->with('error', 'Semua kasir sudah menerima jatah harian hari ini.');
        }

        $pesan = "Jatah harian dibagikan ({$diberi} pemberian).";
        if ($jenis !== BahanOperasional::JENIS_KEMASAN) {
            $pesan .= ' Minyak & gas baru masuk ke stok kasir setelah kasir konfirmasi "Sudah" di Cek Stok.';
        }
        if ($dilewati > 0) {
            $pesan .= " {$dilewati} dilewati karena sudah menerima hari ini.";
        }

        return back()->with('success', $pesan);
    }

    /**
     * PATCH /admin/stok/bahan/{bahanOperasional}/koreksi
     * Set ulang sisa stok SATU kasir (liter / tabung). Selisihnya otomatis
     * tercatat di riwayat.
     */
    public function koreksiBahan(Request $request, BahanOperasional $bahanOperasional): RedirectResponse
    {
        $data = $request->validate([
            'kasir_id' => ['required', Rule::exists('users', 'id')->where('role', 'kasir')],
            'stok_baru' => ['required', 'integer', 'min:0', 'max:9999'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'stok_baru.required' => 'Sisa stok barunya wajib diisi.',
            'stok_baru.min' => 'Sisa stok tidak boleh minus.',
        ]);

        DB::transaction(function () use ($bahanOperasional, $data) {
            $kasirId = (int) $data['kasir_id'];
            $selisih = $data['stok_baru'] - $bahanOperasional->stokUntukKasir($kasirId);

            if ($selisih !== 0) {
                PergerakanStokBahanOperasional::create([
                    'bahan_operasional_id' => $bahanOperasional->id,
                    'user_id' => Auth::id(),
                    'kasir_id' => $kasirId,
                    'jenis' => $selisih > 0 ? 'masuk' : 'keluar',
                    'jumlah' => abs($selisih),
                    'sesi' => 0,
                    'keterangan' => $data['keterangan'] ?: 'Koreksi stok manual',
                    'tanggal' => now()->toDateString(),
                ]);
            }

            if ($data['stok_baru'] > 0) {
                $bahanOperasional->tutupPermintaan($kasirId);
            }
        });

        return back()->with('success', "Stok \"{$bahanOperasional->nama}\" berhasil dikoreksi.");
    }

    /**
     * DELETE /admin/stok/bahan/{bahanOperasional}/riwayat
     * Hapus semua riwayat bahan ini untuk SATU kasir (balik "belum diberi").
     */
    public function hapusRiwayatBahan(Request $request, BahanOperasional $bahanOperasional): RedirectResponse
    {
        $data = $request->validate([
            'kasir_id' => ['required', 'exists:users,id'],
        ]);

        DB::transaction(function () use ($bahanOperasional, $data) {
            $bahanOperasional->pergerakanStok()->where('kasir_id', $data['kasir_id'])->delete();
            $bahanOperasional->permintaan()->where('kasir_id', $data['kasir_id'])->delete();
            $bahanOperasional->penerimaan()->where('kasir_id', $data['kasir_id'])->delete();
        });

        return back()->with('success', "Riwayat \"{$bahanOperasional->nama}\" untuk kasir tersebut berhasil dihapus, stoknya balik ke 0.");
    }

    /**
     * DELETE /admin/stok/bahan/riwayat/{pergerakanStokBahanOperasional}
     * Hapus satu entri pemberian (misal salah input).
     */
    public function hapusEntriMasukBahan(PergerakanStokBahanOperasional $pergerakanStokBahanOperasional): RedirectResponse
    {
        abort_if($pergerakanStokBahanOperasional->jenis !== 'masuk', 403);

        $nama = $pergerakanStokBahanOperasional->bahan->nama ?? 'bahan';
        $pergerakanStokBahanOperasional->delete();

        return back()->with('success', "Entri pemberian \"{$nama}\" berhasil dihapus.");
    }
}
