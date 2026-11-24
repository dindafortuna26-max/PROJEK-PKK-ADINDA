<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BahanOperasional;
use App\Models\Ukuran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BahanOperasionalController extends Controller
{
    /**
     * GET /admin/bahan-operasional
     * Master Bahan Operasional. Dua jenis:
     *  - Bahan habis-pakai (minyak goreng, gas): nama, foto, satuan, jatah harian
     *    per kasir. Diberi MANUAL, tidak berkurang otomatis.
     *  - Kemasan (cup / box): dipasangkan ke satu ukuran (Kecil / Sedang / Besar)
     *    dan BERKURANG OTOMATIS tiap transaksi sesuai ukuran yang terjual.
     * Pemberian jatah ke kasir tetap dilakukan di Manajemen Stok.
     */
    public function index(Request $request): View
    {
        $filterJenis = in_array($request->input('jenis'), [BahanOperasional::JENIS_HABIS_PAKAI, BahanOperasional::JENIS_KEMASAN], true)
            ? $request->input('jenis')
            : '';

        $semua = BahanOperasional::query()->get();

        $bahanList = BahanOperasional::query()
            ->with('ukuran')
            ->when($request->filled('cari'), function ($query) use ($request) {
                $query->where('nama', 'like', '%' . $request->input('cari') . '%');
            })
            ->when($filterJenis === BahanOperasional::JENIS_KEMASAN, fn ($q) => $q->kemasan())
            ->when($filterJenis === BahanOperasional::JENIS_HABIS_PAKAI, fn ($q) => $q->habisPakai())
            ->orderBy('jenis')
            ->orderBy('nama')
            ->get();

        return view('admin.bahan-operasional.index', [
            'bahanList' => $bahanList,
            'filterJenis' => $filterJenis,
            'jumlahSemua' => $semua->count(),
            'jumlahKemasan' => $semua->where('jenis', BahanOperasional::JENIS_KEMASAN)->count(),
            'jumlahAktif' => $semua->where('is_active', true)->count(),
            'ukuranList' => Ukuran::where('is_active', true)->urutKecilKeBesar()->get(),
        ]);
    }

    /** POST /admin/bahan-operasional */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('bahan-operasional', 'public');
        }

        $bahan = BahanOperasional::create($data);

        return back()->with('success', "\"{$bahan->nama}\" berhasil ditambahkan ke bahan operasional.");
    }

    /** PUT /admin/bahan-operasional/{bahanOperasional} */
    public function update(Request $request, BahanOperasional $bahanOperasional): RedirectResponse
    {
        $data = $this->validated($request, $bahanOperasional);

        if ($request->hasFile('gambar')) {
            // Ganti foto: file lama dibuang supaya storage tidak menumpuk.
            $bahanOperasional->hapusFileGambar();
            $data['gambar'] = $request->file('gambar')->store('bahan-operasional', 'public');
        } elseif ($request->boolean('hapus_gambar')) {
            $bahanOperasional->hapusFileGambar();
            $data['gambar'] = null;
        }

        $bahanOperasional->update($data);

        return back()->with('success', "\"{$bahanOperasional->nama}\" berhasil diperbarui.");
    }

    /** PATCH /admin/bahan-operasional/{bahanOperasional}/toggle-status */
    public function toggleStatus(BahanOperasional $bahanOperasional): RedirectResponse
    {
        $bahanOperasional->update(['is_active' => ! $bahanOperasional->is_active]);

        $pesan = $bahanOperasional->is_active
            ? "\"{$bahanOperasional->nama}\" diaktifkan kembali."
            : "\"{$bahanOperasional->nama}\" dinonaktifkan.";

        return back()->with('success', $pesan);
    }

    /**
     * DELETE /admin/bahan-operasional/{bahanOperasional}
     * Hanya bisa dihapus kalau belum punya riwayat stok (selain itu,
     * nonaktifkan saja).
     */
    public function destroy(BahanOperasional $bahanOperasional): RedirectResponse
    {
        if ($bahanOperasional->pergerakanStok()->exists()) {
            return back()->with('error', "\"{$bahanOperasional->nama}\" tidak bisa dihapus karena sudah punya riwayat stok/pemakaian. Nonaktifkan saja.");
        }

        $nama = $bahanOperasional->nama;
        $bahanOperasional->hapusFileGambar();
        $bahanOperasional->delete();

        return back()->with('success', "\"{$nama}\" berhasil dihapus.");
    }

    private function validated(Request $request, ?BahanOperasional $bahan = null): array
    {
        $kemasan = $request->input('jenis') === BahanOperasional::JENIS_KEMASAN;
        $maksJatah = $kemasan ? BahanOperasional::MAKS_SEKALI_MASUK_KEMASAN : BahanOperasional::MAKS_SEKALI_MASUK;

        $data = $request->validate([
            'jenis' => ['required', Rule::in([BahanOperasional::JENIS_HABIS_PAKAI, BahanOperasional::JENIS_KEMASAN])],
            'nama' => ['required', 'string', 'max:255'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hapus_gambar' => ['nullable', 'boolean'],
            'satuan' => ['required', 'string', 'max:30'],
            'ukuran_id' => [Rule::requiredIf($kemasan), 'nullable', 'integer', 'exists:ukurans,id'],
            'pakai_per_porsi' => [Rule::requiredIf($kemasan), 'nullable', 'integer', 'min:1', 'max:20'],
            'jatah_harian' => ['required', 'integer', 'min:0', 'max:' . $maksJatah],
            'stok_minimum' => [Rule::requiredIf($kemasan), 'nullable', 'integer', 'min:0', 'max:' . BahanOperasional::MAKS_SEKALI_MASUK_KEMASAN],
        ], [
            'jenis.required' => 'Pilih jenis bahan dulu: bahan habis-pakai atau kemasan.',
            'jenis.in' => 'Jenis bahan tidak dikenal.',
            'nama.required' => 'Nama bahan wajib diisi.',
            'gambar.image' => 'File harus berupa gambar.',
            'gambar.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'gambar.max' => 'Ukuran foto maksimal 2 MB.',
            'gambar.uploaded' => 'Foto gagal diunggah. Pastikan ukurannya maksimal 2 MB.',
            'satuan.required' => 'Satuan wajib diisi (contoh: liter, tabung, pcs).',
            'ukuran_id.required' => 'Pilih kemasan ini dipakai untuk ukuran apa (Kecil / Sedang / Besar).',
            'ukuran_id.exists' => 'Ukuran yang dipilih tidak ditemukan.',
            'pakai_per_porsi.required' => 'Isi berapa kemasan yang terpakai untuk 1 porsi (biasanya 1).',
            'pakai_per_porsi.min' => 'Minimal 1 kemasan per porsi.',
            'pakai_per_porsi.max' => 'Terlalu banyak. Maksimal 20 kemasan per porsi.',
            'jatah_harian.required' => 'Isi jatah harian per kasir. Isi 0 kalau tidak ada jatah tetap.',
            'jatah_harian.min' => 'Jatah harian tidak boleh minus.',
            'jatah_harian.max' => 'Jatah harian terlalu besar (maksimal ' . $maksJatah . ').',
            'stok_minimum.required' => 'Isi batas menipis kemasan (stok di bawah angka ini ditandai Menipis di kasir).',
            'stok_minimum.integer' => 'Batas menipis harus berupa angka bulat.',
            'stok_minimum.min' => 'Batas menipis tidak boleh minus.',
            'stok_minimum.max' => 'Batas menipis terlalu besar.',
        ]);

        // Bahan habis-pakai tidak terikat ukuran dan tidak berkurang otomatis.
        if ($data['jenis'] !== BahanOperasional::JENIS_KEMASAN) {
            $data['ukuran_id'] = null;
            $data['pakai_per_porsi'] = 1;
            // Batas menipis hanya dipakai kemasan; minyak & gas tidak diubah.
            unset($data['stok_minimum']);
        }

        // File & checkbox foto diproses terpisah di store()/update().
        unset($data['gambar'], $data['hapus_gambar']);

        return $data;
    }
}
