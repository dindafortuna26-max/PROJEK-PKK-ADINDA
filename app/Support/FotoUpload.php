<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Helper upload foto (profil, menu, bumbu/topping) yang AMAN di server
 * manapun:
 *
 * - Disimpan ke disk "public" (storage/app/public/<folder>).
 * - Kalau symlink public/storage TIDAK ada (kasus XAMPP/Windows di mana
 *   public/storage cuma folder biasa), file otomatis disalin juga ke
 *   public/storage/<folder> supaya fotonya langsung kelihatan di browser.
 * - Saat hapus, salinan di public/storage ikut dihapus.
 */
class FotoUpload
{
    public static function simpan(UploadedFile $file, string $folder): string
    {
        $path = $file->store($folder, 'public');

        self::salinJikaPerlu($path);

        return $path;
    }

    public static function hapus(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk('public')->delete($path);

        if (! is_link(public_path('storage'))) {
            $tujuan = public_path('storage/' . $path);

            if (is_file($tujuan)) {
                @unlink($tujuan);
            }
        }
    }

    private static function salinJikaPerlu(string $path): void
    {
        if (is_link(public_path('storage'))) {
            return;
        }

        $sumber = storage_path('app/public/' . $path);
        $tujuan = public_path('storage/' . $path);

        if (! is_file($sumber)) {
            return;
        }

        if (! is_dir(dirname($tujuan))) {
            @mkdir(dirname($tujuan), 0755, true);
        }

        @copy($sumber, $tujuan);
    }
}
