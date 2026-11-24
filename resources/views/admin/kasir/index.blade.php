@extends('layouts.admin')

@section('title', 'Data Kasir - ' . config('app.name', 'Cimol Bojot AA'))

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/kasir-profil-modern.css') }}?v={{ @filemtime(public_path('assets_dashboard/css/kasir-profil-modern.css')) }}">
@endpush

@section('content')
  @php
    $total = $ringkasan['total'];
    $aktif = $ringkasan['aktif'];
    $nonaktif = $ringkasan['nonaktif'];
    $persen = $total > 0 ? round($aktif / $total * 100) : 0;
    $tim = ($tim ?? $kasirs->getCollection()->where('is_active', true))->take(5);
    $statusAktif = in_array(request('status'), ['aktif', 'nonaktif'], true) ? request('status') : null;
    $urlFilter = fn ($s) => route('admin.kasir.index', array_filter(['cari' => request('cari'), 'status' => $s]));
  @endphp

  <div class="kp">

    <!-- ===================== JUDUL ===================== -->
    <header class="kp-head">
      <div>
        <h1>Data Kasir</h1>
        <p>Tambah akun kasir, ubah datanya, reset password, atau nonaktifkan akun yang sudah tidak bertugas.</p>
      </div>
      <button type="button" class="kp-btn kp-btn-primary" data-bs-toggle="modal" data-bs-target="#modal-tambah-kasir">
        <i class="bi bi-person-plus-fill"></i>
        <span>Tambah Kasir</span>
      </button>
    </header>

    <!-- ===================== PAPAN TIM ===================== -->
    <section class="kp-board" aria-label="Ringkasan kasir">
      <div>
        <div class="kp-stack">
          @forelse ($tim as $t)
            <img src="{{ $t->avatar_url }}" alt="{{ $t->name }}" title="{{ $t->name }}">
          @empty
            <span class="kp-stack-empty">Belum ada kasir aktif</span>
          @endforelse
          @if ($aktif > $tim->count() && $tim->count() > 0)
            <span class="kp-more">+{{ $aktif - $tim->count() }}</span>
          @endif
        </div>
        <h2 class="kp-team-title">
          @if ($total > 0)
            {{ $aktif }} dari {{ $total }} kasir aktif
          @else
            Tim kasir masih kosong
          @endif
        </h2>
        <p class="kp-team-sub">Hanya kasir yang aktif yang bisa login dan melayani transaksi.</p>
        <div class="kp-meter" role="progressbar" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100" aria-label="Persentase kasir aktif">
          <i style="--w: {{ $persen }}%"></i>
        </div>
      </div>

      <div class="kp-metrics">
        <div class="kp-metric"><b>{{ $total }}</b><span>Total kasir</span></div>
        <div class="kp-metric is-gold"><b>{{ $aktif }}</b><span>Aktif</span></div>
        <div class="kp-metric"><b>{{ $nonaktif }}</b><span>Tidak aktif</span></div>
      </div>
    </section>

    <!-- ===================== TOOLBAR ===================== -->
    <div class="kp-toolbar">
      <form action="{{ route('admin.kasir.index') }}" method="GET" class="kp-search" role="search">
        @if ($statusAktif)
          <input type="hidden" name="status" value="{{ $statusAktif }}">
        @endif
        <i class="bi bi-search"></i>
        <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari nama atau email kasir" aria-label="Cari kasir">
        @if (request('cari'))
          <a href="{{ route('admin.kasir.index', array_filter(['status' => $statusAktif])) }}" class="kp-clear" aria-label="Hapus pencarian"><i class="bi bi-x-lg"></i></a>
        @endif
      </form>

      <nav class="kp-filter" aria-label="Filter status">
        <a href="{{ $urlFilter(null) }}" class="{{ ! $statusAktif ? 'on' : '' }}">Semua <em>{{ $total }}</em></a>
        <a href="{{ $urlFilter('aktif') }}" class="{{ $statusAktif === 'aktif' ? 'on' : '' }}">Aktif <em>{{ $aktif }}</em></a>
        <a href="{{ $urlFilter('nonaktif') }}" class="{{ $statusAktif === 'nonaktif' ? 'on' : '' }}">Tidak aktif <em>{{ $nonaktif }}</em></a>
      </nav>
    </div>

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
        <div class="kp-empty">
          <i class="bi {{ request('cari') || $statusAktif ? 'bi-search' : 'bi-person-plus' }}"></i>
          <b>{{ request('cari') || $statusAktif ? 'Kasir tidak ditemukan' : 'Belum ada akun kasir' }}</b>
          <span>{{ request('cari') || $statusAktif ? 'Coba kata kunci atau filter yang lain.' : 'Buat akun pertama supaya kasir bisa mulai melayani pembeli.' }}</span>
          @if (! request('cari') && ! $statusAktif)
            <button type="button" class="kp-btn kp-btn-primary" data-bs-toggle="modal" data-bs-target="#modal-tambah-kasir">
              <i class="bi bi-person-plus-fill"></i><span>Tambah Kasir</span>
            </button>
          @else
            <a href="{{ route('admin.kasir.index') }}" class="kp-btn kp-btn-ghost"><i class="bi bi-arrow-counterclockwise"></i><span>Reset pencarian</span></a>
          @endif
        </div>
      @endforelse
    </div>

    <div class="kp-foot">
      <span>Menampilkan {{ $kasirs->count() }} dari {{ $kasirs->total() }} kasir</span>
      {{ $kasirs->links() }}
    </div>
  </div>

  <!-- ===================== MODAL: TAMBAH KASIR ===================== -->
  <div class="modal fade kp-modal" id="modal-tambah-kasir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <form action="{{ route('admin.kasir.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="_action" value="tambah">

          <div class="kp-mhead">
            <div class="kp-mico"><i class="bi bi-person-plus-fill"></i></div>
            <div>
              <h5>Tambah Kasir</h5>
              <small>Buat akun baru untuk kasir toko.</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
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
            <button type="button" class="kp-btn kp-btn-ghost" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="kp-btn kp-btn-primary"><i class="bi bi-check-lg"></i><span>Simpan Kasir</span></button>
          </div>
        </form>
      </div>
    </div>
  </div>

  @foreach ($kasirs as $kasir)
    <!-- ===================== MODAL: EDIT KASIR ===================== -->
    <div class="modal fade kp-modal" id="modal-edit-kasir-{{ $kasir->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <form action="{{ route('admin.kasir.update', $kasir) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="_action" value="edit-{{ $kasir->id }}">

            <div class="kp-mhead">
              <img src="{{ $kasir->avatar_url }}" alt="{{ $kasir->name }}" class="kp-mavatar">
              <div>
                <h5>Edit Kasir</h5>
                <small>{{ $kasir->name }} &middot; {{ $kasir->email }}</small>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
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
              <button type="button" class="kp-btn kp-btn-ghost" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="kp-btn kp-btn-primary"><i class="bi bi-check-lg"></i><span>Simpan Perubahan</span></button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ===================== MODAL: RESET PASSWORD ===================== -->
    <div class="modal fade kp-modal" id="modal-reset-password-{{ $kasir->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form action="{{ route('admin.kasir.reset-password', $kasir) }}" method="POST">
            @csrf
            @method('PATCH')
            <input type="hidden" name="_action" value="reset-{{ $kasir->id }}">

            <div class="kp-mhead">
              <div class="kp-mico"><i class="bi bi-key-fill"></i></div>
              <div>
                <h5>Reset Password</h5>
                <small>{{ $kasir->name }}</small>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
              <div class="kp-cols">
                <div class="span2">
                  <label class="kp-label" for="reset-pw-{{ $kasir->id }}">Password baru</label>
                  <div class="kp-field has-eye">
                    <i class="bi bi-lock-fill lead"></i>
                    <input type="password" id="reset-pw-{{ $kasir->id }}" name="password" autocomplete="new-password"
                      class="kp-input {{ old('_action') === 'reset-' . $kasir->id && $errors->has('password') ? 'kp-invalid' : '' }}"
                      placeholder="Minimal 6 karakter, ada angka/simbol">
                    <button type="button" class="kp-eye" data-kp-eye aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                  </div>
                  @if (old('_action') === 'reset-' . $kasir->id)
                    @error('password')
                      <span class="kp-err">{{ $message }}</span>
                    @enderror
                  @endif
                </div>

                <div class="span2">
                  <label class="kp-label" for="reset-pw2-{{ $kasir->id }}">Ulangi password baru</label>
                  <div class="kp-field has-eye">
                    <i class="bi bi-shield-lock-fill lead"></i>
                    <input type="password" id="reset-pw2-{{ $kasir->id }}" name="password_confirmation" autocomplete="new-password" class="kp-input" placeholder="Ketik ulang password baru">
                    <button type="button" class="kp-eye" data-kp-eye aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="kp-btn kp-btn-ghost" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="kp-btn kp-btn-primary"><i class="bi bi-key"></i><span>Reset Password</span></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endforeach

@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Tombol mata: tampilkan / sembunyikan password.
      document.addEventListener('click', function (e) {
        var tombol = e.target.closest('[data-kp-eye]');
        if (!tombol) return;
        var input = tombol.parentElement.querySelector('input');
        var buka = input.type === 'password';
        input.type = buka ? 'text' : 'password';
        tombol.querySelector('i').className = buka ? 'bi bi-eye-slash' : 'bi bi-eye';
        tombol.setAttribute('aria-label', buka ? 'Sembunyikan password' : 'Tampilkan password');
      });

      // Kalau ada error validasi, otomatis buka lagi modal yang tadi disubmit.
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
