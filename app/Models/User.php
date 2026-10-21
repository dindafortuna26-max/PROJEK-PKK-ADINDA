<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'no_hp',
        'jenis_kelamin',
        'tanggal_lahir',
        'alamat',
        'password',
        'role',
        'is_active',
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    /**
     * avatar_url ini kolom asli di tabel users, isinya path relatif file
     * yang diupload lewat halaman Profil (mis. "avatars/xxx.jpg"),
     * disimpan di disk "public" (storage/app/public/...).
     *
     * Selama user belum pernah upload foto sendiri, kolomnya kosong dan
     * kita GENERATE avatar inisial SENDIRI di sini (SVG lokal, base64 —
     * bukan panggil layanan luar seperti ui-avatars.com). Ini sengaja
     * dibuat lokal supaya foto tidak pernah "hilang"/nyangkut sama kalau
     * internet mati atau layanan luar itu diblokir, dan tetap beda
     * inisial + warna per akun (merah utk admin, hijau utk kasir).
     */
    public function getAvatarUrlAttribute(?string $value): string
    {
        if ($value) {
            return asset('storage/' . $value);
        }

        return $this->buatAvatarInisial();
    }

    private function buatAvatarInisial(): string
    {
        $inisial = collect(explode(' ', trim($this->name)))
            ->filter()
            ->map(fn ($kata) => mb_strtoupper(mb_substr($kata, 0, 1)))
            ->take(2)
            ->implode('');

        $inisial = $inisial !== '' ? $inisial : '?';
        $warna = $this->role === 'admin' ? '#7A1F1F' : '#2F6B4F';

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">'
            . '<rect width="128" height="128" fill="' . $warna . '" />'
            . '<text x="50%" y="50%" fill="#fff" font-family="Arial, sans-serif" font-size="48" '
            . 'font-weight="bold" text-anchor="middle" dominant-baseline="central">'
            . htmlspecialchars($inisial, ENT_QUOTES, 'UTF-8') . '</text>'
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'tanggal_lahir' => 'date',
        ];
    }

    /**
     * True kalau user sudah upload foto sendiri (bukan avatar inisial).
     */
    public function punyaFoto(): bool
    {
        return (bool) $this->getRawOriginal('avatar_url');
    }

    /**
     * Label jenis kelamin siap tampil: "Laki-laki" / "Perempuan" / "-".
     */
    public function getJenisKelaminLabelAttribute(): string
    {
        return match ($this->jenis_kelamin) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => '-',
        };
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->role === 'admin' ? 'Admin' : 'Kasir';
    }

    public function pergerakanStokTopping()
    {
        return $this->hasMany(PergerakanStokTopping::class);
    }

    /**
     * Hanya akun dengan role kasir (dipakai di halaman Data Kasir,
     * supaya akun admin tidak ikut kemunculan/ke-nonaktifkan di sana).
     */
    public function scopeKasir($query)
    {
        return $query->where('role', 'kasir');
    }

    public function transaksis()
    {
        return $this->hasMany(Transaksi::class);
    }

    public function pergerakanStok()
    {
        return $this->hasMany(PergerakanStok::class);
    }
}
