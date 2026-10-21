<?php

namespace App\Http\Controllers;

use App\Models\BahanOperasional;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Menyajikan foto bahan operasional langsung lewat Laravel, jadi foto tetap
 * tampil walau `php artisan storage:link` belum dijalankan atau APP_URL
 * tidak sama dengan alamat yang dibuka di browser.
 * Dipakai bersama oleh Admin dan Kasir (cukup login).
 */
class BahanOperasionalFotoController extends Controller
{
    public function __invoke(BahanOperasional $bahanOperasional): BinaryFileResponse
    {
        $path = $bahanOperasional->gambar;

        abort_if(! $path, 404);

        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            $file = $disk->path($path);
        } elseif (is_file(public_path('storage/' . $path))) {
            $file = public_path('storage/' . $path);
        } else {
            abort(404);
        }

        return response()->file($file, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
