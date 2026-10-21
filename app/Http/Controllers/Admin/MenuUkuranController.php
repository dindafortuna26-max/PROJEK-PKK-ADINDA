<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuUkuran;
use App\Models\Ukuran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuUkuranController extends Controller
{
    /**
     * GET /admin/menu/{menu}/ukuran
     * Tampilkan SEMUA ukuran master (Kecil/Sedang/Besar), masing-masing
     * dengan checkbox "aktifkan untuk menu ini" + input harga.
     * Kalau menu ini belum punya harga untuk ukuran tertentu, input harga
     * dikosongkan (belum diatur).
     */
    public function edit(Menu $menu): View
    {
        $ukurans = Ukuran::where('is_active', true)->orderBy('nama')->get();

        // Ambil baris menu_ukuran yang sudah ada punya menu ini,
        // di-index by ukuran_id biar mudah dicocokkan di view.
        $menuUkurans = $menu->menuUkurans()->get()->keyBy('ukuran_id');

        return view('admin.menu.ukuran', [
            'menu' => $menu,
            'ukurans' => $ukurans,
            'menuUkurans' => $menuUkurans,
        ]);
    }

    /**
     * PUT /admin/menu/{menu}/ukuran
     * Form kirim array per ukuran: aktif[ukuran_id] (checkbox) dan
     * harga[ukuran_id] (angka). Kalau harga dikosongkan, baris untuk
     * ukuran itu dilewati (tidak dibuat/diubah) supaya tidak ada
     * kombinasi menu+ukuran dengan harga 0 secara tidak sengaja.
     */
    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate([
            'harga' => ['array'],
            'harga.*' => ['nullable', 'integer', 'min:0'],
            'aktif' => ['array'],
        ]);

        $harga = $data['harga'] ?? [];
        $aktif = $data['aktif'] ?? [];

        foreach (Ukuran::pluck('id') as $ukuranId) {
            $hargaUkuran = $harga[$ukuranId] ?? null;

            // Belum diisi harganya sama sekali & belum pernah ada baris
            // sebelumnya -> lewati, tidak usah dibuat baris kosong.
            if ($hargaUkuran === null || $hargaUkuran === '') {
                continue;
            }

            MenuUkuran::updateOrCreate(
                ['menu_id' => $menu->id, 'ukuran_id' => $ukuranId],
                [
                    'harga' => $hargaUkuran,
                    'is_active' => isset($aktif[$ukuranId]),
                ]
            );
        }

        return redirect()
            ->route('admin.menu.index')
            ->with('success', "Ukuran & harga untuk \"{$menu->nama}\" berhasil disimpan.");
    }
}
