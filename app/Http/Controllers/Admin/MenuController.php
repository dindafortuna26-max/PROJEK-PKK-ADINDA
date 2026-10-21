<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\FotoUpload;
use Illuminate\View\View;

class MenuController extends Controller
{
    /**
     * GET /admin/menu
     * Halaman "Daftar Menu": cari nama, filter kategori, pagination.
     */
    public function index(Request $request): View
    {
        $kategoris = Kategori::where('is_active', true)->orderBy('nama')->get();

        $menus = Menu::query()
            ->with(['kategori', 'ukuranAktif'])
            ->when($request->filled('cari'), function ($query) use ($request) {
                $query->where('nama', 'like', '%' . $request->input('cari') . '%');
            })
            ->when($request->filled('kategori_id'), function ($query) use ($request) {
                $query->where('kategori_id', $request->input('kategori_id'));
            })
            ->orderBy('nama')
            ->paginate(12)
            ->withQueryString();

        $ringkasan = [
            'total' => Menu::count(),
            'aktif' => Menu::where('is_active', true)->count(),
            'nonaktif' => Menu::where('is_active', false)->count(),
            'kategori' => $kategoris->count(),
        ];

        return view('admin.menu.index', [
            'menus' => $menus,
            'kategoris' => $kategoris,
            'ringkasan' => $ringkasan,
        ]);
    }

    /**
     * GET /admin/menu/create
     */
    public function create(): View
    {
        $kategoris = Kategori::where('is_active', true)->orderBy('nama')->get();

        return view('admin.menu.create', [
            'kategoris' => $kategoris,
        ]);
    }

    /**
     * POST /admin/menu
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('gambar')) {
            // Disimpan ke storage/app/public/menu, bisa diakses lewat
            // /storage/menu/xxx.jpg setelah "php artisan storage:link".
            $data['gambar'] = FotoUpload::simpan($request->file('gambar'), 'menu');
        }

        $menu = Menu::create($data);

        return redirect()
            ->route('admin.menu.index')
            ->with('success', "Menu \"{$menu->nama}\" berhasil ditambahkan. Jangan lupa atur ukuran & harganya.");
    }

    /**
     * GET /admin/menu/{menu}/edit
     */
    public function edit(Menu $menu): View
    {
        $kategoris = Kategori::where('is_active', true)->orderBy('nama')->get();

        return view('admin.menu.edit', [
            'menu' => $menu,
            'kategoris' => $kategoris,
        ]);
    }

    /**
     * PUT /admin/menu/{menu}
     */
    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('gambar')) {
            // Hapus foto lama supaya storage tidak numpuk file yatim.
            FotoUpload::hapus($menu->gambar);
            $data['gambar'] = FotoUpload::simpan($request->file('gambar'), 'menu');
        }

        $menu->update($data);

        return redirect()
            ->route('admin.menu.index')
            ->with('success', "Menu \"{$menu->nama}\" berhasil diperbarui.");
    }

    /**
     * PATCH /admin/menu/{menu}/toggle-status
     * Tombol "Nonaktifkan" / "Aktifkan" di setiap baris.
     */
    public function toggleStatus(Menu $menu): RedirectResponse
    {
        $menu->update(['is_active' => ! $menu->is_active]);

        $pesan = $menu->is_active
            ? "Menu \"{$menu->nama}\" diaktifkan kembali."
            : "Menu \"{$menu->nama}\" dinonaktifkan.";

        return back()->with('success', $pesan);
    }

    /**
     * Aturan validasi dipakai bareng oleh store() & update().
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'kategori_id' => ['nullable', 'exists:kategoris,id'],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'gambar' => ['nullable', 'image', 'max:2048'], // max 2MB
            'stok_minimum' => ['nullable', 'integer', 'min:0'],
        ], [
            'nama.required' => 'Nama menu wajib diisi.',
            'gambar.image' => 'File yang diupload harus berupa gambar.',
            'gambar.max' => 'Ukuran gambar maksimal 2MB.',
        ]);
    }
}
