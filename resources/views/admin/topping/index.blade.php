@extends('layouts.admin')

@section('title', 'Master Topping & Bumbu - ' . config('app.name', 'Cimol Bojot AA'))

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/cimol-plus.css') }}">
@endpush

@section('content')
<div class="ap">

  <!-- START: Page Header -->
  <div class="ap-hero">
    <div>
      <h1>Master Topping & Bumbu</h1>
      <p>Kelola bumbu dan topping lengkap dengan foto, supaya kasir gampang mengenali pilihan pembeli.</p>
    </div>
    <button type="button" class="ap-btn" data-bs-toggle="modal" data-bs-target="#modal-tambah-topping">
      <i class="bi bi-plus-lg"></i>
      <span>Tambah Bumbu/Topping</span>
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
    <div class="ap-stat"><i class="bi bi-droplet-fill"></i><div><b>{{ $sausList->count() }}</b><span>Bumbu</span></div></div>
    <div class="ap-stat"><i class="bi bi-stars"></i><div><b>{{ $bubukList->count() }}</b><span>Topping</span></div></div>
    <div class="ap-stat"><i class="bi bi-check2-circle"></i><div><b>{{ $sausList->concat($bubukList)->where('is_active', true)->count() }}</b><span>Aktif dijual</span></div></div>
    <div class="ap-stat"><i class="bi bi-image"></i><div><b>{{ $sausList->concat($bubukList)->filter(fn ($t) => $t->gambar)->count() }}</b><span>Sudah ada foto</span></div></div>
  </div>

  <div class="ap-panel">

    <!-- Tab -->
    <div class="ap-toolbar">
      <div class="ap-seg" role="group" aria-label="Tab Topping">
        <button type="button" id="tab-btn-saus" class="active" onclick="tampilkanTabTopping('saus')">
          <i class="bi bi-droplet-fill"></i><span>Bumbu</span>
        </button>
        <button type="button" id="tab-btn-bubuk" onclick="tampilkanTabTopping('bubuk')">
          <i class="bi bi-stars"></i><span>Topping</span>
        </button>
      </div>
    </div>

    <!-- ===================== TAB: SAUS ===================== -->
    <div id="tab-pane-saus">
      @include('admin.topping._tabel', [
          'list' => $sausList,
          'labelNama' => 'Nama Bumbu',
          'ikon' => 'bi-droplet-fill',
          'teksKosong' => 'Belum ada bumbu. Klik "Tambah Bumbu/Topping" untuk mulai menambahkan.',
      ])
    </div>

    <!-- ===================== TAB: BUBUK TABUR ===================== -->
    <div id="tab-pane-bubuk" class="d-none">
      @include('admin.topping._tabel', [
          'list' => $bubukList,
          'labelNama' => 'Nama Topping',
          'ikon' => 'bi-stars',
          'teksKosong' => 'Belum ada topping. Klik "Tambah Bumbu/Topping" untuk mulai menambahkan.',
      ])
    </div>

  </div>

  <!-- ===================== MODAL: TAMBAH ===================== -->
  <div class="modal fade" id="modal-tambah-topping" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <form action="{{ route('admin.topping.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="_action" value="tambah-topping">

          <div class="modal-header modal-header-brand">
            <div class="modal-header-icon"><i class="bi bi-plus-lg"></i></div>
            <div>
              <h5 class="modal-title mb-0">Tambah Bumbu / Topping</h5>
              <small class="modal-subtitle">Lengkapi data dan tambahkan foto supaya mudah dikenali.</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            @include('admin.topping._form-fields', [
                'topping' => null,
                'prefix' => 'tambah',
                'aktif' => old('_action') === 'tambah-topping',
            ])
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-custom btn-batal" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i><span>Batal</span></button>
            <button type="submit" class="btn-custom btn-custom-primary">
              <i class="bi bi-check-lg"></i>
              <span>Simpan</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  @foreach ($sausList->concat($bubukList) as $topping)
    <!-- ===================== MODAL: EDIT ===================== -->
    <div class="modal fade" id="modal-edit-topping-{{ $topping->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <form action="{{ route('admin.topping.update', $topping) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="_action" value="edit-topping-{{ $topping->id }}">

            <div class="modal-header modal-header-brand">
              <div class="modal-header-icon"><i class="bi bi-pencil-fill"></i></div>
              <div>
                <h5 class="modal-title mb-0">Edit — {{ $topping->nama }}</h5>
                <small class="modal-subtitle">{{ $topping->jenis_label }}</small>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              @include('admin.topping._form-fields', [
                  'topping' => $topping,
                  'prefix' => 'edit-' . $topping->id,
                  'aktif' => old('_action') === 'edit-topping-' . $topping->id,
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
  @endforeach

</div>
@endsection

@push('scripts')
  <script>
    function tampilkanTabTopping(tab) {
      const paneSaus = document.getElementById('tab-pane-saus');
      const paneBubuk = document.getElementById('tab-pane-bubuk');
      const btnSaus = document.getElementById('tab-btn-saus');
      const btnBubuk = document.getElementById('tab-btn-bubuk');

      const aktif = tab === 'bubuk';

      paneSaus.classList.toggle('d-none', aktif);
      paneBubuk.classList.toggle('d-none', !aktif);

      btnSaus.classList.toggle('active', !aktif);
      btnBubuk.classList.toggle('active', aktif);

      localStorage.setItem('tabToppingAktif', tab);
    }

    document.addEventListener('DOMContentLoaded', function () {
      const tabTersimpan = localStorage.getItem('tabToppingAktif');
      if (tabTersimpan === 'bubuk') {
        tampilkanTabTopping('bubuk');
      }

      const action = @json(old('_action'));
      if (!action) return;

      let modalId = null;
      if (action === 'tambah-topping') {
        modalId = 'modal-tambah-topping';
      } else if (action.startsWith('edit-topping-')) {
        modalId = 'modal-' + action;
      }

      const modalEl = modalId ? document.getElementById(modalId) : null;
      if (modalEl && window.bootstrap) {
        new bootstrap.Modal(modalEl).show();
      }
    });
  </script>
@endpush
