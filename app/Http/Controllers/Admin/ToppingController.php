<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Topping;
use App\Support\FotoUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ToppingController extends Controller
{
    /**
     * GET /admin/topping
     * Halaman "Master Topping & Bumbu": dua tab (Bumbu / Topping),
     * masing-masing dengan CRUD lewat modal (nama, harga tambahan,
     * batas stok menipis, status aktif). Stok berjalannya sendiri
     * dikelola di halaman Manajemen Stok (tab "Topping & Bumbu"),
     * sama seperti pemisahan Master Menu vs Manajemen Stok.
     */
    public function index(Request $request): View
    {
        $toppings = Topping::query()
            ->when($request->filled('cari'), function ($query) use ($request) {
                $query->where('nama', 'like', '%' . $request->input('cari') . '%');
            })
            ->orderBy('nama')
            ->get();

        return view('admin.topping.index', [
            'sausList' => $toppings->where('jenis', 'saus')->values(),
            'bubukList' => $toppings->where('jenis', 'bubuk_tabur')->values(),
        ]);
    }

    /**
     * POST /admin/topping
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('gambar')) {
            $data['gambar'] = FotoUpload::simpan($request->file('gambar'), 'topping');
        }

        $topping = Topping::create($data);

        return back()->with('success', "\"{$topping->nama}\" berhasil ditambahkan ke master {$topping->jenis_label}.");
    }

    /**
     * PUT /admin/topping/{topping}
     */
    public function update(Request $request, Topping $topping): RedirectResponse
    {
        $data = $this->validated($request, $topping);

        if ($request->hasFile('gambar')) {
            // Ganti foto: hapus file lama supaya storage tidak numpuk.
            FotoUpload::hapus($topping->gambar);
            $data['gambar'] = FotoUpload::simpan($request->file('gambar'), 'topping');
        } elseif ($request->boolean('hapus_gambar') && $topping->gambar) {
            FotoUpload::hapus($topping->gambar);
            $data['gambar'] = null;
        }

        $topping->update($data);

        return back()->with('success', "\"{$topping->nama}\" berhasil diperbarui.");
    }

    /**
     * PATCH /admin/topping/{topping}/toggle-status
     * Topping nonaktif tidak muncul lagi sebagai pilihan di halaman
     * Transaksi Kasir, tapi riwayat transaksi lama tetap aman (snapshot).
     */
    public function toggleStatus(Topping $topping): RedirectResponse
    {
        $topping->update(['is_active' => ! $topping->is_active]);

        $pesan = $topping->is_active
            ? "\"{$topping->nama}\" diaktifkan kembali."
            : "\"{$topping->nama}\" dinonaktifkan.";

        return back()->with('success', $pesan);
    }

    /**
     * DELETE /admin/topping/{topping}
     * Hanya bisa dihapus kalau belum pernah dipakai di transaksi atau
     * riwayat stok manapun — kalau sudah pernah, arahkan pakai
     * "Nonaktifkan" saja (sama pola dengan KasirController::destroy).
     */
    public function destroy(Topping $topping): RedirectResponse
    {
        $punyaRiwayat = $topping->pergerakanStok()->exists()
            || \App\Models\TransaksiDetailOpsi::where('topping_id', $topping->id)->exists();

        if ($punyaRiwayat) {
            return back()->with('error', "\"{$topping->nama}\" tidak bisa dihapus karena sudah punya riwayat stok/transaksi. Nonaktifkan saja.");
        }

        $nama = $topping->nama;
        $gambarLama = $topping->gambar;
        $topping->delete();
        FotoUpload::hapus($gambarLama);

        return back()->with('success', "\"{$nama}\" berhasil dihapus.");
    }

    private function validated(Request $request, ?Topping $topping = null): array
    {
        $data = $request->validate([
            'jenis' => ['required', 'in:saus,bubuk_tabur'],
            'nama' => ['required', 'string', 'max:255'],
            'harga_tambahan' => ['nullable', 'integer', 'min:0'],
            'stok_minimum' => ['nullable', 'integer', 'min:0'],
            'gambar' => ['nullable', 'image', 'max:2048'],
            'hapus_gambar' => ['nullable', 'boolean'],
        ], [
            'jenis.required' => 'Jenis wajib dipilih.',
            'nama.required' => 'Nama wajib diisi.',
            'harga_tambahan.integer' => 'Harga tambahan harus berupa angka.',
            'gambar.image' => 'File yang diupload harus berupa gambar.',
            'gambar.max' => 'Ukuran foto maksimal 2MB.',
            'gambar.uploaded' => 'Foto gagal diupload. Ukuran file mungkin melebihi batas server (upload_max_filesize), coba foto yang lebih kecil.',
        ]);

        // Kolom angka kosong -> pakai nilai bawaan (kalau dibiarkan null,
        // insert ke kolom NOT NULL akan error).
        $data['harga_tambahan'] = $data['harga_tambahan'] ?? 0;
        $data['stok_minimum'] = $data['stok_minimum'] ?? ($topping->stok_minimum ?? 5);

        // Field file/pembantu diproses manual di store()/update().
        unset($data['gambar'], $data['hapus_gambar']);

        return $data;
    }
}
