<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BahanOperasional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BahanOperasionalController extends Controller
{
    /**
     * GET /admin/bahan-operasional
     * Master Bahan Operasional (minyak goreng, gas): nama, satuan, berapa
     * sesi goreng per satuan (minyak 1 liter = 10, gas 1 tabung = 20), dan
     * batas menipis. Stoknya dikelola di Manajemen Stok (tab "Bahan
     * Operasional") dan berkurang otomatis tiap transaksi.
     */
    public function index(Request $request): View
    {
        $bahanList = BahanOperasional::query()
            ->when($request->filled('cari'), function ($query) use ($request) {
                $query->where('nama', 'like', '%' . $request->input('cari') . '%');
            })
            ->orderBy('nama')
            ->get();

        return view('admin.bahan-operasional.index', [
            'bahanList' => $bahanList,
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
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hapus_gambar' => ['nullable', 'boolean'],
            'satuan' => ['required', 'string', 'max:30'],
            'sesi_per_satuan' => ['required', 'integer', 'min:1', 'max:1000'],
            'stok_minimum' => ['nullable', 'integer', 'min:0'],
        ], [
            'nama.required' => 'Nama bahan wajib diisi.',
            'gambar.image' => 'File harus berupa gambar.',
            'gambar.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'gambar.max' => 'Ukuran foto maksimal 2 MB.',
            'gambar.uploaded' => 'Foto gagal diunggah. Pastikan ukurannya maksimal 2 MB.',
            'satuan.required' => 'Satuan wajib diisi (contoh: liter, tabung).',
            'sesi_per_satuan.required' => 'Isi berapa sesi goreng per satuan (contoh: 10 untuk 1 liter minyak).',
            'sesi_per_satuan.min' => 'Minimal 1 sesi goreng per satuan.',
        ]);

        $data['stok_minimum'] = $data['stok_minimum'] ?? ($bahan->stok_minimum ?? 3);

        // File & checkbox foto diproses terpisah di store()/update().
        unset($data['gambar'], $data['hapus_gambar']);

        return $data;
    }
}
