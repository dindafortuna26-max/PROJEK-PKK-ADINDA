@extends('layouts.admin')

@section('title', 'Data Kasir - ' . config('app.name', 'Cimol Bojot AA'))

@section('content')

  <!-- ===================== HERO ===================== -->
  <div class="hero">
    <div>
      <span class="hero-eyebrow"><i class="bi bi-people-fill"></i> Master Data</span>
      <h1>Data Kasir</h1>
      <p>Kelola akun kasir {{ config('app.name', 'Cimol Bojot AA') }}: tambah, ubah data, reset password, atau nonaktifkan akun.</p>
    </div>
    <button type="button" class="hero-btn" data-bs-toggle="modal" data-bs-target="#modal-tambah-kasir">
      <i class="bi bi-person-plus-fill"></i>
      <span>Tambah Kasir</span>
    </button>
  </div>

  @if (session('success'))
    <div class="alert alert-success mb-4" role="alert">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger mb-4" role="alert">{{ session('error') }}</div>
  @endif

  <!-- ===================== RINGKASAN ===================== -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="mstat">
        <span class="mstat-ico"><i class="bi bi-people-fill"></i></span>
        <div>
          <div class="mstat-label">Total Kasir</div>
          <div class="mstat-value">{{ $ringkasan['total'] }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="mstat ok">
        <span class="mstat-ico"><i class="bi bi-person-check-fill"></i></span>
        <div>
          <div class="mstat-label">Kasir Aktif</div>
          <div class="mstat-value">{{ $ringkasan['aktif'] }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="mstat bad">
        <span class="mstat-ico"><i class="bi bi-person-x-fill"></i></span>
        <div>
          <div class="mstat-label">Tidak Aktif</div>
          <div class="mstat-value">{{ $ringkasan['nonaktif'] }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ===================== TOOLBAR ===================== -->
  <form action="{{ route('admin.kasir.index') }}" method="GET" class="toolbar">
    <div class="toolbar-search">
      <i class="bi bi-search"></i>
      <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari nama atau email kasir...">
    </div>
    @if (request('cari'))
      <a href="{{ route('admin.kasir.index') }}" class="chip"><i class="bi bi-x-lg"></i> Reset</a>
    @endif
  </form>

  <!-- ===================== KARTU KASIR ===================== -->
  <div class="card-grid">
    @forelse ($kasirs as $kasir)
      <div class="ks-card {{ $kasir->is_active ? '' : 'is-off' }}">
        <div class="ks-cover"></div>
        <img src="{{ $kasir->avatar_url }}" alt="{{ $kasir->name }}" class="ks-avatar">
        <h3 class="ks-nama">{{ $kasir->name }}</h3>
        <p class="ks-email">{{ $kasir->email }}</p>
        <span class="ks-badge {{ $kasir->is_active ? '' : 'off' }}">{{ $kasir->is_active ? 'Aktif' : 'Tidak Aktif' }}</span>

        <div class="ks-info">
          <div><i class="bi bi-telephone-fill"></i><span>{{ $kasir->no_hp ?: 'No. HP belum diisi' }}</span></div>
          <div><i class="bi bi-gender-ambiguous"></i><span>{{ $kasir->jenis_kelamin_label }}</span></div>
          <div><i class="bi bi-cake2-fill"></i><span>{{ $kasir->tanggal_lahir ? $kasir->tanggal_lahir->translatedFormat('d F Y') : 'Tanggal lahir belum diisi' }}</span></div>
        </div>

        <div class="ks-actions">
          <button type="button" class="act primary" data-bs-toggle="modal" data-bs-target="#modal-edit-kasir-{{ $kasir->id }}">
            <i class="bi bi-pencil-fill"></i><span>Edit</span>
          </button>

          <button type="button" class="act icon warn" title="Reset password" data-bs-toggle="modal" data-bs-target="#modal-reset-password-{{ $kasir->id }}">
            <i class="bi bi-key-fill"></i>
          </button>

          <form action="{{ route('admin.kasir.toggle-status', $kasir) }}" method="POST"
            data-confirm="{{ $kasir->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun ini?"
            data-confirm-text="{{ $kasir->is_active ? $kasir->name . ' tidak akan bisa login sampai diaktifkan lagi.' : $kasir->name . ' akan bisa login kembali.' }}"
            data-confirm-yes="{{ $kasir->is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan' }}"
            data-confirm-tone="{{ $kasir->is_active ? 'warn' : 'ok' }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="act icon {{ $kasir->is_active ? 'warn' : 'good' }}" title="{{ $kasir->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
              <i class="bi {{ $kasir->is_active ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' }}"></i>
            </button>
          </form>

          <form action="{{ route('admin.kasir.destroy', $kasir) }}" method="POST"
            data-confirm="Hapus akun kasir?"
            data-confirm-text="Akun {{ $kasir->name }} akan dihapus permanen dan tidak bisa dikembalikan."
            data-confirm-yes="Ya, Hapus" data-confirm-tone="danger">
            @csrf
            @method('DELETE')
            <button type="submit" class="act icon danger" title="Hapus"><i class="bi bi-trash3-fill"></i></button>
          </form>
        </div>
      </div>
    @empty
      <div class="empty-state">
        <i class="bi bi-person-plus"></i>
        <b>{{ request('cari') ? 'Kasir tidak ditemukan' : 'Belum ada akun kasir' }}</b>
        {{ request('cari') ? 'Coba kata kunci lain.' : 'Klik "Tambah Kasir" untuk mulai menambahkan.' }}
      </div>
    @endforelse
  </div>

  <div class="foot-bar">
    <span>Menampilkan {{ $kasirs->count() }} dari {{ $kasirs->total() }} kasir</span>
    {{ $kasirs->links() }}
  </div>

  <!-- ===================== MODAL: TAMBAH KASIR ===================== -->
  <div class="modal fade" id="modal-tambah-kasir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <form action="{{ route('admin.kasir.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="_action" value="tambah">

          <div class="modal-header modal-header-brand">
            <div class="modal-header-icon"><i class="bi bi-person-plus-fill"></i></div>
            <div>
              <h5 class="modal-title mb-0">Tambah Kasir</h5>
              <small class="modal-subtitle">Buat akun baru untuk kasir toko.</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            @include('admin.kasir._form-fields', [
                'kasir' => null,
                'prefix' => 'tambah-kasir',
                'aktif' => old('_action') === 'tambah',
                'denganPassword' => true,
            ])
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-custom btn-batal" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i><span>Batal</span></button>
            <button type="submit" class="btn-custom btn-custom-primary">
              <i class="bi bi-check-lg"></i>
              <span>Simpan Kasir</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  @foreach ($kasirs as $kasir)
    <!-- ===================== MODAL: EDIT KASIR ===================== -->
    <div class="modal fade" id="modal-edit-kasir-{{ $kasir->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <form action="{{ route('admin.kasir.update', $kasir) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="_action" value="edit-{{ $kasir->id }}">

            <div class="modal-header modal-header-brand">
              <img src="{{ $kasir->avatar_url }}" alt="{{ $kasir->name }}" class="modal-header-avatar">
              <div>
                <h5 class="modal-title mb-0">Edit Kasir — {{ $kasir->name }}</h5>
                <small class="modal-subtitle">{{ $kasir->email }}</small>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              @include('admin.kasir._form-fields', [
                  'kasir' => $kasir,
                  'prefix' => 'edit-kasir-' . $kasir->id,
                  'aktif' => old('_action') === 'edit-' . $kasir->id,
                  'denganPassword' => false,
              ])
            </div>

            <div class="modal-footer">
              <button type="button" class="btn-custom btn-batal" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i><span>Batal</span></button>
              <button type="submit" class="btn-custom btn-custom-primary">
                <i class="bi bi-check-lg"></i>
                <span>Simpan Perubahan</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ===================== MODAL: RESET PASSWORD ===================== -->
    <div class="modal fade" id="modal-reset-password-{{ $kasir->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form action="{{ route('admin.kasir.reset-password', $kasir) }}" method="POST">
            @csrf
            @method('PATCH')
            <input type="hidden" name="_action" value="reset-{{ $kasir->id }}">

            <div class="modal-header modal-header-brand">
              <div class="modal-header-icon"><i class="bi bi-key-fill"></i></div>
              <div>
                <h5 class="modal-title mb-0">Reset Password</h5>
                <small class="modal-subtitle">{{ $kasir->name }}</small>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label-custom">Password Baru</label>
                <input type="password" name="password"
                  class="form-control-custom {{ old('_action') === 'reset-' . $kasir->id && $errors->has('password') ? 'is-invalid-custom' : '' }}"
                  placeholder="Minimal 6 karakter">
                @if (old('_action') === 'reset-' . $kasir->id)
                  @error('password')
                    <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
                  @enderror
                @endif
              </div>

              <div class="mb-1">
                <label class="form-label-custom">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" class="form-control-custom" placeholder="Ulangi password baru">
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn-custom btn-batal" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i><span>Batal</span></button>
              <button type="submit" class="btn-custom btn-custom-primary">
                <i class="bi bi-key"></i>
                <span>Reset Password</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endforeach

@endsection

@push('scripts')
  <script>
    // Kalau ada error validasi, otomatis buka lagi modal yang tadi disubmit.
    document.addEventListener('DOMContentLoaded', function () {
      const action = @json(old('_action'));
      if (!action) return;

      const modalId = action === 'tambah' ? 'modal-tambah-kasir' : 'modal-' + action.replace('edit-', 'edit-kasir-').replace('reset-', 'reset-password-');
      const modalEl = document.getElementById(modalId);
      if (modalEl && window.bootstrap) {
        new bootstrap.Modal(modalEl).show();
      }
    });
  </script>
@endpush
