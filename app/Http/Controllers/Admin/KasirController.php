<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PergerakanStok;
use App\Models\PergerakanStokTopping;
use App\Models\Transaksi;
use App\Models\User;
use App\Support\FotoUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class KasirController extends Controller
{
    /**
     * GET /admin/kasir
     * Halaman "Data Kasir": cari nama/email, pagination. Hanya
     * menampilkan akun berrole kasir — akun admin tidak ikut di sini.
     */
    public function index(Request $request): View
    {
        $kasirs = User::query()
            ->kasir()
            ->when($request->filled('cari'), function ($query) use ($request) {
                $cari = $request->input('cari');
                $query->where(function ($q) use ($cari) {
                    $q->where('name', 'like', "%{$cari}%")
                        ->orWhere('email', 'like', "%{$cari}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $ringkasan = [
            'total' => User::kasir()->count(),
            'aktif' => User::kasir()->where('is_active', true)->count(),
            'nonaktif' => User::kasir()->where('is_active', false)->count(),
        ];

        return view('admin.kasir.index', [
            'kasirs' => $kasirs,
            'ringkasan' => $ringkasan,
        ]);
    }

    /**
     * POST /admin/kasir
     * Tambah akun kasir baru lewat modal "Tambah Kasir".
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(array_merge([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], $this->aturanProfil()), $this->pesanValidasi());

        $kasir = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'no_hp' => $data['no_hp'] ?? null,
            'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
            'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
            'alamat' => $data['alamat'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'kasir',
            'is_active' => true,
        ]);

        if ($request->hasFile('foto')) {
            $kasir->avatar_url = FotoUpload::simpan($request->file('foto'), 'avatars');
            $kasir->save();
        }

        return back()->with('success', "Akun kasir \"{$kasir->name}\" berhasil ditambahkan.");
    }

    /**
     * PUT /admin/kasir/{kasir}
     * Ubah nama & email lewat modal "Edit Kasir". Password TIDAK
     * diubah di sini — ganti password lewat aksi "Reset Password".
     */
    public function update(Request $request, User $kasir): RedirectResponse
    {
        $this->tolakJikaBukanKasir($kasir);

        $data = $request->validate(array_merge([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $kasir->id],
        ], $this->aturanProfil()), $this->pesanValidasi());

        $fotoLama = $kasir->getRawOriginal('avatar_url');

        $kasir->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'no_hp' => $data['no_hp'] ?? null,
            'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
            'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
            'alamat' => $data['alamat'] ?? null,
        ]);

        if ($request->hasFile('foto')) {
            $kasir->avatar_url = FotoUpload::simpan($request->file('foto'), 'avatars');
            FotoUpload::hapus($fotoLama);
        } elseif ($request->boolean('hapus_foto') && $fotoLama) {
            $kasir->avatar_url = null;
            FotoUpload::hapus($fotoLama);
        }

        $kasir->save();

        return back()->with('success', "Data kasir \"{$kasir->name}\" berhasil diperbarui.");
    }

    /**
     * PATCH /admin/kasir/{kasir}/toggle-status
     * Nonaktifkan / aktifkan kembali. Kasir yang statusnya "Tidak
     * Aktif" otomatis tidak bisa login (lihat LoginRequest).
     */
    public function toggleStatus(User $kasir): RedirectResponse
    {
        $this->tolakJikaBukanKasir($kasir);

        $kasir->update(['is_active' => ! $kasir->is_active]);

        $pesan = $kasir->is_active
            ? "Akun \"{$kasir->name}\" diaktifkan kembali."
            : "Akun \"{$kasir->name}\" dinonaktifkan. Kasir ini tidak bisa login sampai diaktifkan lagi.";

        return back()->with('success', $pesan);
    }

    /**
     * PATCH /admin/kasir/{kasir}/reset-password
     * Modal "Reset Password" — admin set password baru langsung.
     */
    public function resetPassword(Request $request, User $kasir): RedirectResponse
    {
        $this->tolakJikaBukanKasir($kasir);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $kasir->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', "Password akun \"{$kasir->name}\" berhasil direset.");
    }

    /**
     * DELETE /admin/kasir/{kasir}
     * Hapus permanen — hanya diizinkan kalau kasir belum pernah
     * punya transaksi/riwayat stok (FK di database memang menahan
     * ini). Kalau sudah pernah, arahkan admin pakai "Nonaktifkan" saja
     * supaya riwayat transaksinya tetap aman.
     */
    public function destroy(User $kasir): RedirectResponse
    {
        $this->tolakJikaBukanKasir($kasir);

        $punyaRiwayat = Transaksi::where('user_id', $kasir->id)->exists()
            || PergerakanStok::where('user_id', $kasir->id)->exists()
            || PergerakanStokTopping::where('user_id', $kasir->id)->exists();

        if ($punyaRiwayat) {
            return back()->with('error', "Akun \"{$kasir->name}\" tidak bisa dihapus karena sudah punya riwayat transaksi/stok. Nonaktifkan saja akunnya.");
        }

        $nama = $kasir->name;
        $fotoLama = $kasir->getRawOriginal('avatar_url');
        $kasir->delete();
        FotoUpload::hapus($fotoLama);

        return back()->with('success', "Akun kasir \"{$nama}\" berhasil dihapus.");
    }

    /**
     * Aturan validasi data profil yang dipakai bareng oleh store() & update().
     */
    private function aturanProfil(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'tanggal_lahir' => ['nullable', 'date', 'before:today'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'hapus_foto' => ['nullable', 'boolean'],
        ];
    }

    private function pesanValidasi(): array
    {
        return [
            'name.required' => 'Nama kasir wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'no_hp.regex' => 'No. HP hanya boleh berisi angka, spasi, dan tanda + - ( ).',
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid.',
            'tanggal_lahir.date' => 'Tanggal lahir tidak valid.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'foto.image' => 'File yang diupload harus berupa gambar.',
            'foto.max' => 'Ukuran foto maksimal 2MB.',
        ];
    }

    private function tolakJikaBukanKasir(User $user): void
    {
        abort_if($user->role !== 'kasir', 403, 'Akun ini bukan akun kasir.');
    }
}
