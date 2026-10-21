@extends('layouts.admin')

@section('title', 'Master Bahan Operasional - ' . config('app.name', 'Cimol Bojot AA'))

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/cimol-plus.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/bahan-operasional.css') }}?v={{ @filemtime(public_path('assets_dashboard/css/bahan-operasional.css')) }}">
@endpush

@section('content')
<div class="ap">

  <!-- START: Page Header -->
  <div class="ap-hero">
    <div>
      <h1>Master Bahan Operasional</h1>
      <p>Daftar bahan seperti minyak goreng dan gas. Tidak ikut terjual di transaksi, tapi stoknya berkurang otomatis tiap penggorengan dan habisnya langsung muncul di notifikasi.</p>
    </div>
    <button type="button" class="ap-btn" data-bs-toggle="modal" data-bs-target="#modal-tambah-bahan">
      <i class="bi bi-plus-lg"></i>
      <span>Tambah Bahan Operasional</span>
    </button>
  </div>
  <!-- END: Page Header -->

  @if (session('success'))
    <div class="alert alert-success mb-4" role="alert">{{ session('success') }}</div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger mb-4" role="alert">{{ session('error') }}</div>
  @endif

  <div class="ap-stats">
    <div class="ap-stat"><i class="bi bi-fire"></i><div><b>{{ $bahanList->count() }}</b><span>Total bahan</span></div></div>
    <div class="ap-stat"><i class="bi bi-check2-circle"></i><div><b>{{ $bahanList->where('is_active', true)->count() }}</b><span>Aktif dipantau</span></div></div>
  </div>

  <div class="ap-panel">

    <div class="bo-info">
      <i class="bi bi-info-circle-fill"></i>
      <div>
        <b>Cara kerjanya</b>
        <p>Kamu isi stok dalam liter (minyak) atau tabung (gas) lewat Manajemen Stok, 1 sampai 5 per input. Tiap satuan setara beberapa sesi goreng: 1 liter minyak = 10 transaksi, 1 tabung gas 3 kg = 20 transaksi. Setiap transaksi selesai, stoknya otomatis berkurang 1 sesi. Begitu sisanya menipis atau habis, notifikasi muncul di lonceng admin.</p>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table-custom">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama Bahan</th>
            <th>Satuan Stok</th>
            <th>Setara</th>
            <th>Menipis Saat Sisa ≤</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($bahanList as $i => $bahan)
            <tr>
              <td>{{ $i + 1 }}</td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  @include('partials.thumb', ['src' => $bahan->gambar_url, 'icon' => $bahan->ikon, 'size' => 44, 'alt' => $bahan->nama])
                  <span class="table-product-name mb-0">{{ $bahan->nama }}</span>
                </div>
              </td>
              <td>{{ $bahan->satuan }}</td>
              <td>1 {{ $bahan->satuan }} = {{ $bahan->sesi_per_satuan }} sesi goreng</td>
              <td>{{ $bahan->stok_minimum }} sesi</td>
              <td><span class="badge-table {{ $bahan->is_active ? 'success' : '' }}">{{ $bahan->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
              <td>
                <div class="d-flex flex-wrap gap-2">
                  <button type="button" class="btn-custom btn-custom-sm btn-custom-outline-secondary"
                    data-bs-toggle="modal" data-bs-target="#modal-edit-bahan-{{ $bahan->id }}">
                    <i class="bi bi-pencil"></i><span>Edit</span>
                  </button>

                  <form action="{{ route('admin.bahan-operasional.toggle-status', $bahan) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn-custom btn-custom-sm btn-custom-outline-secondary" title="{{ $bahan->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                      <i class="bi {{ $bahan->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                      <span>{{ $bahan->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</span>
                    </button>
                  </form>

                  <form action="{{ route('admin.bahan-operasional.destroy', $bahan) }}" method="POST"
                    data-confirm="Hapus &quot;{{ $bahan->nama }}&quot;?"
                    data-confirm-text="Yang sudah punya riwayat stok tidak bisa dihapus (nonaktifkan saja)."
                    data-confirm-yes="Ya, Hapus">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-custom btn-custom-sm btn-custom-outline-danger">
                      <i class="bi bi-trash"></i><span>Hapus</span>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4" style="color: var(--text-muted-green);">
                Belum ada bahan operasional. Klik "Tambah Bahan Operasional", contoh: Minyak Goreng (liter, 10 sesi) dan Gas (tabung, 20 sesi).
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- ===================== MODAL: TAMBAH ===================== -->
  <div class="modal fade" id="modal-tambah-bahan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <form action="{{ route('admin.bahan-operasional.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="_action" value="tambah-bahan">

          <div class="modal-header modal-header-brand">
            <div class="modal-header-icon"><i class="bi bi-plus-lg"></i></div>
            <div>
              <h5 class="modal-title mb-0">Tambah Bahan Operasional</h5>
              <small class="modal-subtitle">Minyak goreng, gas, atau bahan habis-pakai lainnya.</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            @include('admin.bahan-operasional._form-fields', [
                'bahan' => null,
                'aktif' => old('_action') === 'tambah-bahan',
            ])
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-custom btn-batal" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i><span>Batal</span></button>
            <button type="submit" class="btn-custom btn-custom-primary"><i class="bi bi-check-lg"></i><span>Simpan</span></button>
          </div>
        </form>
      </div>
    </div>
  </div>

  @foreach ($bahanList as $bahan)
    <!-- ===================== MODAL: EDIT ===================== -->
    <div class="modal fade" id="modal-edit-bahan-{{ $bahan->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <form action="{{ route('admin.bahan-operasional.update', $bahan) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="_action" value="edit-bahan-{{ $bahan->id }}">

            <div class="modal-header modal-header-brand">
              <div class="modal-header-icon"><i class="bi bi-pencil-fill"></i></div>
              <div>
                <h5 class="modal-title mb-0">Edit — {{ $bahan->nama }}</h5>
                <small class="modal-subtitle">Bahan Operasional</small>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              @include('admin.bahan-operasional._form-fields', [
                  'bahan' => $bahan,
                  'aktif' => old('_action') === 'edit-bahan-' . $bahan->id,
              ])
            </div>

            <div class="modal-footer">
              <button type="button" class="btn-custom btn-batal" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i><span>Batal</span></button>
              <button type="submit" class="btn-custom btn-custom-primary"><i class="bi bi-check-lg"></i><span>Simpan Perubahan</span></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endforeach

</div>
@endsection

@push('scripts')
  <script>
    // Preview foto + tombol Hapus foto di form Tambah/Edit bahan.
    (function () {
      var MAKS = 2 * 1024 * 1024;

      document.querySelectorAll('[data-bo-foto]').forEach(function (box) {
        var input = box.querySelector('[data-bo-foto-input]');
        var img = box.querySelector('.bo-foto-img');
        var ikon = box.querySelector('.bo-foto-ikon');
        var teks = box.querySelector('[data-bo-foto-teks]');
        var hapus = box.querySelector('[data-bo-foto-hapus]');
        var err = box.querySelector('[data-bo-foto-error]');

        function tampil(src) {
          if (src) { img.src = src; img.classList.remove('d-none'); ikon.classList.add('d-none'); }
          else { img.removeAttribute('src'); img.classList.add('d-none'); ikon.classList.remove('d-none'); }
        }

        input.addEventListener('change', function () {
          err.classList.add('d-none');
          var f = input.files && input.files[0];
          if (!f) { tampil(hapus && hapus.checked ? '' : img.dataset.awal); return; }
          if (f.size > MAKS) {
            input.value = '';
            err.textContent = 'Ukuran foto maksimal 2 MB.';
            err.classList.remove('d-none');
            return;
          }
          var reader = new FileReader();
          reader.onload = function (e) { tampil(e.target.result); if (teks) teks.textContent = 'Ganti Foto'; if (hapus) hapus.checked = false; };
          reader.readAsDataURL(f);
        });

        if (hapus) {
          hapus.addEventListener('change', function () {
            if (hapus.checked) { input.value = ''; tampil(''); }
            else { tampil(img.dataset.awal); }
          });
        }
      });
    })();

    document.addEventListener('DOMContentLoaded', function () {
      const action = @json(old('_action'));
      if (!action) return;

      let modalId = null;
      if (action === 'tambah-bahan') {
        modalId = 'modal-tambah-bahan';
      } else if (action.startsWith('edit-bahan-')) {
        modalId = 'modal-' + action;
      }

      const modalEl = modalId ? document.getElementById(modalId) : null;
      if (modalEl && window.bootstrap) {
        new bootstrap.Modal(modalEl).show();
      }
    });
  </script>
@endpush
