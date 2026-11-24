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
      <p>Daftar bahan pendukung usaha: minyak goreng, gas, dan kemasan (cup/box). Tidak ikut terjual di transaksi. Minyak &amp; gas diberi manual ke tiap kasir, sedangkan kemasan berkurang otomatis sesuai ukuran porsi yang terjual.</p>
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
    <div class="ap-stat"><i class="bi bi-boxes"></i><div><b>{{ $jumlahSemua }}</b><span>Total bahan</span></div></div>
    <div class="ap-stat"><i class="bi bi-box-seam"></i><div><b>{{ $jumlahKemasan }}</b><span>Kemasan</span></div></div>
    <div class="ap-stat"><i class="bi bi-check2-circle"></i><div><b>{{ $jumlahAktif }}</b><span>Aktif dipantau</span></div></div>
  </div>

  <div class="ap-panel">

    <div class="bo-filter">
      <div class="bo-seg" role="group" aria-label="Filter jenis bahan">
        @foreach (['' => 'Semua', 'habis_pakai' => 'Bahan Habis-Pakai', 'kemasan' => 'Kemasan'] as $kunci => $teks)
          <a href="{{ request()->fullUrlWithQuery(['jenis' => $kunci ?: null]) }}" class="{{ $filterJenis === $kunci ? 'active' : '' }}">{{ $teks }}</a>
        @endforeach
      </div>
    </div>

    <div class="table-responsive">
      <table class="table-custom">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama Bahan</th>
            <th>Jenis</th>
            <th>Satuan Stok</th>
            <th>Pemakaian</th>
            <th>Jatah Harian per Kasir</th>
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
              <td>
                <span class="bo-jenis-badge {{ $bahan->is_kemasan ? 'kemasan' : '' }}">
                  <i class="bi {{ $bahan->is_kemasan ? 'bi-box-seam' : 'bi-droplet-half' }}"></i>{{ $bahan->label_jenis }}
                </span>
              </td>
              <td>{{ $bahan->satuan }}</td>
              <td>
                @if ($bahan->is_kemasan)
                  <span class="bo-pakai auto"><i class="bi bi-magic"></i>{{ $bahan->aturan_pakai }}</span>
                @else
                  <span class="bo-pakai">Manual</span>
                @endif
              </td>
              <td>{{ $bahan->jatah_harian > 0 ? $bahan->labelJumlah($bahan->jatah_harian) : '-' }}</td>
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
              <td colspan="8" class="text-center py-4" style="color: var(--text-muted-green);">
                Belum ada bahan operasional{{ $filterJenis ? " pada jenis ini" : "" }}. Klik "Tambah Bahan Operasional", contoh: Minyak Goreng (liter, jatah 5), Gas (tabung, jatah 1), atau Cup Kecil (pcs, ukuran Kecil).
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <datalist id="bo-satuan-saran">
    <option value="liter"></option>
    <option value="tabung"></option>
    <option value="pcs"></option>
    <option value="pack"></option>
    <option value="dus"></option>
  </datalist>

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
              <small class="modal-subtitle">Minyak goreng, gas, atau kemasan (cup, box) per ukuran.</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            @include('admin.bahan-operasional._form-fields', [
                'bahan' => null,
                'aktif' => old('_action') === 'tambah-bahan',
                'ukuranList' => $ukuranList,
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
                  'ukuranList' => $ukuranList,
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
    // Ganti jenis bahan (habis-pakai / kemasan): tampilkan isian khusus kemasan,
    // ubah teks bantuan, dan sesuaikan satuan bawaan (liter <-> pcs).
    (function () {
      var SATUAN_BAWAAN = { habis_pakai: 'liter', kemasan: 'pcs' };

      document.querySelectorAll('[data-bo-form]').forEach(function (root) {
        var radios = root.querySelectorAll('input[name="jenis"]');
        var satuan = root.querySelector('[data-bo-satuan]');
        var nama = root.querySelector('input[name="nama"]');
        var sufiks = root.querySelectorAll('[data-bo-satuan-teks]');
        var jenisAwal = root.getAttribute('data-jenis') || 'habis_pakai';

        function jenisTerpilih() {
          var r = root.querySelector('input[name="jenis"]:checked');
          return r ? r.value : 'habis_pakai';
        }

        function teksSatuan() {
          sufiks.forEach(function (el) { el.textContent = (satuan.value || '').trim() || 'satuan'; });
        }

        function terapkan(jenis) {
          root.setAttribute('data-jenis', jenis);

          root.querySelectorAll('[data-bo-hanya]').forEach(function (el) {
            el.classList.toggle('d-none', el.getAttribute('data-bo-hanya') !== jenis);
          });
          root.querySelectorAll('[data-t-' + jenis + ']').forEach(function (el) {
            el.textContent = el.getAttribute('data-t-' + jenis);
          });
          if (nama && nama.hasAttribute('data-p-' + jenis)) {
            nama.placeholder = nama.getAttribute('data-p-' + jenis);
          }
        }

        var sebelumnya = jenisAwal;
        radios.forEach(function (r) {
          r.addEventListener('change', function () {
            var jenis = jenisTerpilih();

            // Satuan hanya diganti kalau masih isi bawaan jenis sebelumnya (atau kosong),
            // supaya satuan yang sudah diketik sendiri tidak tertimpa.
            var sekarang = (satuan.value || '').trim().toLowerCase();
            if (sekarang === '' || sekarang === SATUAN_BAWAAN[sebelumnya]) {
              satuan.value = SATUAN_BAWAAN[jenis];
            }

            sebelumnya = jenis;
            terapkan(jenis);
            teksSatuan();
          });
        });

        satuan.addEventListener('input', teksSatuan);
        terapkan(jenisTerpilih());
        teksSatuan();
      });
    })();

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
