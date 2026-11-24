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
        $ukurans = Ukuran::where('is_active', true)->urutKecilKeBesar()->get();


        $menuUkurans = $menu->menuUkurans()->get()->keyBy('ukuran_id');

        return view('admin.menu.ukuran', [
            'menu' => $menu,
            'ukurans' => $ukurans,
            'menuUkurans' => $menuUkurans,
        ]);
    }

 
    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate([
            'harga' => ['array'],
            'harga.*' => ['nullable', 'integer', 'min:0'],
            'aktif' => ['array'],
        ]);

        $harga = $data['harga'] ?? [];
        $aktif = $data['aktif'] ?? [];

        //* Kode ini digunakan untuk melakukan perulangan pada semua ID ukuran yang ada di database, kemudian mengambil harga berdasarkan ID ukuran tersebut. Jika harga tidak ditemukan, maka nilainya akan diisi nul
        foreach (Ukuran::pluck('id') as $ukuranId) {
            $hargaUkuran = $harga[$ukuranId] ?? null;


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
