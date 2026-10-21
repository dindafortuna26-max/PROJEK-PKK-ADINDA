<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\PergerakanStok;
use App\Models\PergerakanStokTopping;
use App\Models\Transaksi;
use App\Support\FotoUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        // Ringkasan aktivitas kecil buat kartu profil (dipakai view).
        $jumlahTransaksi = Transaksi::where('user_id', $user->id)->count();

        return view('profile.edit', [
            'user' => $user,
            'jumlahTransaksi' => $jumlahTransaksi,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->safe()->only([
            'name', 'email', 'no_hp', 'jenis_kelamin', 'tanggal_lahir', 'alamat',
        ]);

        // Input kosong dari form disimpan sebagai NULL, bukan string kosong.
        foreach (['no_hp', 'jenis_kelamin', 'tanggal_lahir', 'alamat'] as $kolom) {
            if (($data[$kolom] ?? null) === '') {
                $data[$kolom] = null;
            }
        }

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Foto profil ini punya masing-masing akun sendiri (kolom
        // avatar_url per baris user), jadi upload di sini HANYA
        // mengganti foto milik user yang sedang login.
        // Path lama disimpan mentah di kolom (bukan hasil accessor).
        $fotoLama = $user->getRawOriginal('avatar_url');

        if ($request->hasFile('foto')) {
            $user->avatar_url = FotoUpload::simpan($request->file('foto'), 'avatars');
            FotoUpload::hapus($fotoLama);
        } elseif ($request->boolean('hapus_foto') && $fotoLama) {
            $user->avatar_url = null;
            FotoUpload::hapus($fotoLama);
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Admin tidak boleh menghapus akunnya sendiri lewat sini (bisa
        // bikin aplikasi tidak punya admin sama sekali).
        if ($user->role === 'admin') {
            return Redirect::route('profile.edit')
                ->with('error', 'Akun admin tidak bisa dihapus dari halaman ini.');
        }

        // Akun yang sudah punya transaksi/riwayat stok tidak bisa dihapus
        // (ditahan foreign key di database) — tanpa cek ini halaman
        // langsung error 500. Arahkan ke Admin untuk menonaktifkan.
        $punyaRiwayat = Transaksi::where('user_id', $user->id)->exists()
            || PergerakanStok::where('user_id', $user->id)->exists()
            || PergerakanStokTopping::where('user_id', $user->id)->exists();

        if ($punyaRiwayat) {
            return Redirect::route('profile.edit')
                ->with('error', 'Akunmu sudah punya riwayat transaksi/stok sehingga tidak bisa dihapus. Minta Admin untuk menonaktifkan akunmu.');
        }

        $fotoLama = $user->getRawOriginal('avatar_url');

        Auth::logout();

        $user->delete();
        FotoUpload::hapus($fotoLama);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
