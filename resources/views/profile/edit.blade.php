@extends('layouts.admin')

@section('title', 'Profil Saya - ' . config('app.name', 'Cimol Bojot AA'))

@section('content')

  <div class="page-header">
    <div>
      <h1 class="page-title">Profil Saya</h1>
      <p class="page-subtitle">Kelola data diri, foto, dan keamanan akunmu.</p>
    </div>
  </div>

  @if (session('status') === 'profile-updated')
    <div class="alert alert-success mb-4" role="alert"><i class="bi bi-check-circle-fill me-2"></i>Profil berhasil diperbarui.</div>
  @endif

  @if (session('status') === 'password-updated')
    <div class="alert alert-success mb-4" role="alert"><i class="bi bi-check-circle-fill me-2"></i>Password berhasil diperbarui.</div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger mb-4" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}</div>
  @endif

  <!-- ===================== KARTU PROFIL (HERO) ===================== -->
  <div class="profile-hero mb-4">
    <div class="profile-hero-cover"></div>
    <div class="profile-hero-body">
      <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="profile-hero-avatar">

      <div class="profile-hero-info">
        <h2 class="profile-hero-name">{{ $user->name }}</h2>
        <div class="profile-hero-meta">
          <span class="profile-role-badge {{ $user->role === 'admin' ? 'role-admin' : 'role-kasir' }}">
            <i class="bi {{ $user->role === 'admin' ? 'bi-shield-fill-check' : 'bi-person-badge-fill' }}"></i>
            {{ $user->role_label }}
          </span>
          <span><i class="bi bi-envelope"></i> {{ $user->email }}</span>
          @if ($user->no_hp)
            <span><i class="bi bi-telephone"></i> {{ $user->no_hp }}</span>
          @endif
        </div>
      </div>

      <div class="profile-hero-stats">
        <div class="profile-stat">
          <span class="profile-stat-value">{{ number_format($jumlahTransaksi, 0, ',', '.') }}</span>
          <span class="profile-stat-label">Transaksi</span>
        </div>
        <div class="profile-stat">
          <span class="profile-stat-value">{{ $user->created_at ? $user->created_at->translatedFormat('M Y') : '-' }}</span>
          <span class="profile-stat-label">Bergabung</span>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">

    <!-- ===================== DATA DIRI ===================== -->
    <div class="col-xl-7">
      <div class="table-card-custom h-100 mb-0">
        <form action="{{ route('profile.update') }}" method="POST" class="p-4" enctype="multipart/form-data">
          @csrf
          @method('PATCH')

          <h2 class="card-title mb-1"><i class="bi bi-person-lines-fill me-2"></i>Data Diri</h2>
          <p class="table-user-sub mb-4">Foto ini hanya berlaku untuk akunmu sendiri.</p>

          <div class="mb-4">
            <label class="form-label-custom">Foto Profil</label>
            @include('partials.foto-upload', [
                'id' => 'profil-foto',
                'name' => 'foto',
                'src' => $user->punyaFoto() ? $user->avatar_url : null,
                'icon' => 'bi-person-fill',
                'hapus' => 'hapus_foto',
                'shape' => 'circle',
                'hint' => 'JPG/PNG, maksimal 2MB. Foto persegi hasilnya paling bagus.',
                'error' => $errors->first('foto'),
            ])
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label-custom">Nama Lengkap</label>
              <input type="text" name="name" value="{{ old('name', $user->name) }}"
                class="form-control-custom {{ $errors->has('name') ? 'is-invalid-custom' : '' }}">
              @error('name')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label-custom">Email</label>
              <input type="email" name="email" value="{{ old('email', $user->email) }}"
                class="form-control-custom {{ $errors->has('email') ? 'is-invalid-custom' : '' }}">
              <span class="form-feedback-custom">Email ini juga dipakai untuk login.</span>
              @error('email')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label-custom">No. HP</label>
              <input type="text" name="no_hp" inputmode="tel" value="{{ old('no_hp', $user->no_hp) }}"
                class="form-control-custom {{ $errors->has('no_hp') ? 'is-invalid-custom' : '' }}" placeholder="contoh: 0812 3456 7890">
              @error('no_hp')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label-custom">Tanggal Lahir</label>
              <input type="date" name="tanggal_lahir" max="{{ now()->subDay()->toDateString() }}"
                value="{{ old('tanggal_lahir', $user->tanggal_lahir?->toDateString()) }}"
                class="form-control-custom {{ $errors->has('tanggal_lahir') ? 'is-invalid-custom' : '' }}">
              @error('tanggal_lahir')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
            </div>

            <div class="col-12">
              <label class="form-label-custom">Jenis Kelamin</label>
              @php $jkNilai = old('jenis_kelamin', $user->jenis_kelamin); @endphp
              <div class="radio-cards">
                <label class="radio-card">
                  <input type="radio" name="jenis_kelamin" value="L" {{ $jkNilai === 'L' ? 'checked' : '' }}>
                  <span class="radio-card-body"><i class="bi bi-gender-male"></i> Laki-laki</span>
                </label>
                <label class="radio-card">
                  <input type="radio" name="jenis_kelamin" value="P" {{ $jkNilai === 'P' ? 'checked' : '' }}>
                  <span class="radio-card-body"><i class="bi bi-gender-female"></i> Perempuan</span>
                </label>
              </div>
              @error('jenis_kelamin')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
            </div>

            <div class="col-12">
              <label class="form-label-custom">Alamat</label>
              <textarea name="alamat" rows="3"
                class="form-control-custom {{ $errors->has('alamat') ? 'is-invalid-custom' : '' }}"
                placeholder="Alamat lengkap (opsional)">{{ old('alamat', $user->alamat) }}</textarea>
              @error('alamat')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
            </div>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn-custom btn-custom-primary">
              <i class="bi bi-check-lg"></i>
              <span>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ===================== KANAN: INFO AKUN + PASSWORD ===================== -->
    <div class="col-xl-5">
      <div class="d-flex flex-column gap-4 h-100">

        <div class="table-card-custom mb-0 p-4">
          <h2 class="card-title mb-3"><i class="bi bi-info-circle me-2"></i>Info Akun</h2>
          <dl class="info-list mb-0">
            <div><dt>Role</dt><dd>{{ $user->role_label }}</dd></div>
            <div><dt>Status</dt><dd><span class="badge-table {{ $user->is_active ? 'success' : 'failed' }}">{{ $user->is_active ? 'Aktif' : 'Tidak Aktif' }}</span></dd></div>
            <div><dt>Jenis Kelamin</dt><dd>{{ $user->jenis_kelamin_label }}</dd></div>
            <div><dt>Tanggal Lahir</dt><dd>{{ $user->tanggal_lahir ? $user->tanggal_lahir->translatedFormat('d F Y') : '-' }}</dd></div>
            <div><dt>Terdaftar</dt><dd>{{ $user->created_at ? $user->created_at->translatedFormat('d F Y') : '-' }}</dd></div>
          </dl>
        </div>

        <div class="table-card-custom mb-0 flex-grow-1">
          <form action="{{ route('password.update') }}" method="POST" class="p-4">
            @csrf
            @method('PUT')

            <h2 class="card-title mb-1"><i class="bi bi-shield-lock me-2"></i>Ganti Password</h2>
            <p class="table-user-sub mb-3">Gunakan password yang kuat dan jangan dibagikan ke orang lain.</p>

            <div class="mb-3">
              <label class="form-label-custom">Password Saat Ini</label>
              <input type="password" name="current_password" autocomplete="current-password"
                class="form-control-custom {{ $errors->updatePassword->has('current_password') ? 'is-invalid-custom' : '' }}">
              @error('current_password', 'updatePassword')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label-custom">Password Baru</label>
              <input type="password" name="password" autocomplete="new-password"
                class="form-control-custom {{ $errors->updatePassword->has('password') ? 'is-invalid-custom' : '' }}"
                placeholder="Minimal 8 karakter">
              @error('password', 'updatePassword')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
            </div>

            <div class="mb-1">
              <label class="form-label-custom">Konfirmasi Password Baru</label>
              <input type="password" name="password_confirmation" autocomplete="new-password" class="form-control-custom" placeholder="Ulangi password baru">
            </div>

            <div class="mt-3">
              <button type="submit" class="btn-custom btn-custom-primary">
                <i class="bi bi-key"></i>
                <span>Update Password</span>
              </button>
            </div>
          </form>
        </div>

      </div>
    </div>

    @if ($user->role !== 'admin')
      <!-- Hapus Akun (khusus kasir) -->
      <div class="col-12">
        <div class="table-card-custom mb-0 p-4 danger-zone">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
              <h2 class="card-title mb-1" style="color: var(--bs-danger, #dc3545);">Hapus Akun</h2>
              <p class="table-user-sub mb-0">
                Akun yang dihapus tidak bisa dikembalikan. Kalau kamu sudah pernah melayani transaksi,
                minta Admin menonaktifkan akunmu lewat menu Data Kasir.
              </p>
            </div>
            <button type="button" class="btn-custom btn-custom-outline-danger" data-bs-toggle="modal" data-bs-target="#modal-hapus-akun">
              <i class="bi bi-trash"></i>
              <span>Hapus Akun Saya</span>
            </button>
          </div>
        </div>
      </div>
    @endif

  </div>

  @if ($user->role !== 'admin')
    <!-- Modal konfirmasi hapus akun -->
    <div class="modal fade" id="modal-hapus-akun" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form action="{{ route('profile.destroy') }}" method="POST">
            @csrf
            @method('DELETE')

            <div class="modal-header">
              <h5 class="modal-title">Yakin hapus akun?</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <p class="table-user-sub">Masukkan password kamu untuk konfirmasi. Tindakan ini permanen.</p>
              <input type="password" name="password" class="form-control-custom {{ $errors->userDeletion->has('password') ? 'is-invalid-custom' : '' }}" placeholder="Password">
              @error('password', 'userDeletion')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
            </div>

            <div class="modal-footer">
              <button type="button" class="btn-custom btn-batal" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i><span>Batal</span></button>
              <button type="submit" class="btn-custom btn-custom-outline-danger">
                <i class="bi bi-trash"></i>
                <span>Ya, Hapus Akun</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endif

@endsection

@push('scripts')
  @if ($errors->userDeletion->any())
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('modal-hapus-akun');
        if (el && window.bootstrap) new bootstrap.Modal(el).show();
      });
    </script>
  @endif
@endpush
