@extends('layouts.admin')

@section('title', 'Manajemen Stok - ' . config('app.name', 'Cimol Bojot AA'))

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/cimol-plus.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/bahan-operasional.css') }}?v={{ @filemtime(public_path('assets_dashboard/css/bahan-operasional.css')) }}">
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/stok-modern.css') }}?v={{ @filemtime(public_path('assets_dashboard/css/stok-modern.css')) }}">
@endpush

@php
  // Tampilan status yang dipakai bareng ketiga tab (Menu, Topping & Bumbu, Bahan).
  $statusLabel = ['aman' => 'Aman', 'menipis' => 'Menipis', 'habis' => 'Habis', 'belum_dikelola' => 'Belum Dikelola'];
  $statusTone = ['aman' => 'ok', 'menipis' => 'warn', 'habis' => 'bad', 'belum_dikelola' => 'neutral'];

  // Lebar bar stok: cuma bantuan visual. Penuh kalau stok >= 3x batas menipis.
  $persenStok = function ($stok, $minimum, $status) {
    if ($status === 'habis') return 4;
    if ($status === 'belum_dikelola') return 0;
    $acuan = max((int) $minimum * 3, 1);
    return max(8, min(100, (int) round(($stok / $acuan) * 100)));
  };
@endphp

@section('content')
<div class="ap">

  <!-- START: Page Header -->
  <div class="ap-hero">
    <div>
      <h1>Manajemen Stok</h1>
      <p>Stok dipisah per kelompok: Menu, Topping &amp; Bumbu, dan Bahan Operasional (minyak goreng &amp; gas). Pilih tab di bawah.</p>
    </div>
  </div>
  <!-- END: Page Header -->

  @if (session('success'))
    <div class="alert alert-success mb-4" role="alert">{{ session('success') }}</div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger mb-4" role="alert">{{ session('error') }}</div>
  @endif


  <div class="table-card-custom">

    <!-- Tab + Search -->
    <div class="table-header-control d-flex flex-wrap align-items-center gap-3">
      <div class="btn-group" role="group" aria-label="Tab Manajemen Stok">
        <button type="button" id="tab-btn-data-stok" class="btn-custom btn-custom-primary" onclick="tampilkanTabStok('data-stok')">
          <i class="bi bi-box-seam"></i>
          <span>Menu</span>
        </button>
        <button type="button" id="tab-btn-topping" class="btn-custom btn-custom-outline-secondary" onclick="tampilkanTabStok('topping')">
          <i class="bi bi-droplet-fill"></i>
          <span>Topping & Bumbu</span>
        </button>
        <button type="button" id="tab-btn-bahan" class="btn-custom btn-custom-outline-secondary" onclick="tampilkanTabStok('bahan')">
          <i class="bi bi-fire"></i>
          <span>Bahan Operasional</span>
        </button>
      </div>

      <form action="{{ route('admin.stok.index') }}" method="GET" class="d-flex flex-wrap gap-3 flex-grow-1">
        <div class="table-search-box">
          <i class="bi bi-search table-search-icon"></i>
          <input type="text" name="cari" value="{{ request('cari') }}" class="table-search-input" placeholder="Cari nama barang...">
        </div>
        @if (request('cari'))
          <a href="{{ route('admin.stok.index') }}" class="btn-table-action">
            <i class="bi bi-x-lg"></i>
            <span>Reset</span>
          </a>
        @endif
      </form>
    </div>

    <!-- ===================== TAB: MENU ===================== -->
    <div id="tab-pane-data-stok">

      @include('admin.stok._ringkasan', [
          'r' => $ringkasan['menu'],
          'labelTotal' => 'Total Stok Menu',
          'satuanTotal' => 'porsi',
          'satuanHabis' => 'menu',
      ])

      <div class="sm-head">
        <div class="sm-head-title">
          <span class="sm-head-ico"><i class="bi bi-box-seam-fill"></i></span>
          <div><h3>Stok Menu</h3><p>Sisa stok berjalan setiap menu</p></div>
        </div>
        <button type="button" class="btn-custom btn-custom-primary" data-bs-toggle="modal" data-bs-target="#modal-tambah-stok">
          <i class="bi bi-plus-lg"></i><span>Tambah Stok Menu</span>
        </button>
      </div>

      <div class="sm-card">
        <div class="table-responsive">
          <table class="sm-table">
            <thead>
              <tr>
                <th class="sm-col-no">No</th>
                <th>Menu</th>
                <th>Sisa Stok</th>
                <th>Status</th>
                <th>Stok Masuk Terakhir</th>
                <th class="sm-col-act">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($dataStok as $index => $item)
                @php $tone = $statusTone[$item->status]; @endphp
                <tr>
                  <td class="sm-col-no">{{ $dataStok->firstItem() + $index }}</td>
                  <td>
                    <div class="sm-item">
                      @include('partials.thumb', ['src' => $item->menu->gambar ? asset('storage/' . $item->menu->gambar) : null, 'icon' => 'bi-box-seam-fill', 'size' => 46, 'alt' => $item->menu->nama])
                      <div>
                        <b>{{ $item->menu->nama }}</b>
                        <span>{{ $item->menu->kategori->nama ?? 'Tanpa kategori' }}</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="sm-stock">
                      <div class="sm-stock-num">{{ number_format($item->stok, 0, ',', '.') }}<small>porsi</small></div>
                      <div class="sm-meter {{ $tone }}"><i style="width: {{ $persenStok($item->stok, $item->menu->stok_minimum, $item->status) }}%"></i></div>
                      <span class="sm-stock-min">Batas menipis {{ number_format($item->menu->stok_minimum, 0, ',', '.') }}</span>
                    </div>
                  </td>
                  <td><span class="sm-pill {{ $tone }}">{{ $statusLabel[$item->status] }}</span></td>
                  <td>
                    @if ($item->terakhir_masuk)
                      <div class="sm-last"><b>{{ $item->terakhir_masuk->tanggal->translatedFormat('d M Y') }}</b><span class="sm-in">+{{ number_format($item->terakhir_masuk->jumlah, 0, ',', '.') }} porsi</span></div>
                    @else
                      <span class="sm-muted">Belum ada</span>
                    @endif
                  </td>
                  <td class="sm-col-act">
                    <div class="sm-actions">
                      <button type="button" class="sm-icon-btn" title="Edit stok" data-bs-toggle="modal" data-bs-target="#modal-koreksi-{{ $item->menu->id }}"><i class="bi bi-pencil-fill"></i></button>
                      <form action="{{ route('admin.stok.hapus-riwayat', $item->menu) }}" method="POST"
                        onsubmit="return confirm('Hapus semua riwayat stok &quot;{{ $item->menu->nama }}&quot;? Stoknya bakal balik ke 0 dan tidak bisa dibatalkan.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="sm-icon-btn danger" title="Hapus semua riwayat stok"><i class="bi bi-trash3-fill"></i></button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="sm-empty"><i class="bi bi-inbox"></i>Belum ada menu. Tambahkan menu dulu lewat halaman Master Menu.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="sm-foot">
          <span>Menampilkan {{ $dataStok->count() }} dari {{ $dataStok->total() }} menu</span>
          {{ $dataStok->links() }}
        </div>
      </div>

      <div class="sm-head sm-head-sub">
        <div class="sm-head-title">
          <span class="sm-head-ico in"><i class="bi bi-arrow-down-left"></i></span>
          <div><h3>Riwayat Stok Masuk &amp; Keluar</h3><p>Restock dari admin dan stok terpakai otomatis tiap transaksi</p></div>
        </div>
        <div class="sm-seg" role="group" aria-label="Filter riwayat">
          @foreach (['' => 'Semua', 'masuk' => 'Masuk', 'keluar' => 'Keluar'] as $kunci => $teks)
            <a href="{{ request()->fullUrlWithQuery(['riwayat_menu' => $kunci ?: null, 'riwayat_page_page' => null]) }}"
              class="{{ ($filterRiwayatMenu ?? '') === $kunci ? 'active' : '' }}">{{ $teks }}</a>
          @endforeach
        </div>
      </div>

      <div class="sm-card">
        <div class="table-responsive">
          <table class="sm-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Menu</th>
                <th>Jumlah</th>
                <th>Keterangan</th>
                <th>Diinput Oleh</th>
                <th class="sm-col-act">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($riwayatMasuk as $riwayat)
                @php
                  $masuk = $riwayat->jenis === 'masuk';
                  $otomatis = ! $masuk && str_starts_with((string) $riwayat->keterangan, 'Otomatis');
                @endphp
                <tr class="{{ $otomatis ? 'sm-row-auto' : '' }}">
                  <td>
                    <div class="sm-date"><b>{{ $riwayat->tanggal->translatedFormat('d') }}</b><span>{{ $riwayat->tanggal->translatedFormat('M Y') }}</span></div>
                  </td>
                  <td>
                    <div class="sm-item sm-item-sm">
                      @include('partials.thumb', ['src' => ($riwayat->menu?->gambar) ? asset('storage/' . $riwayat->menu->gambar) : null, 'icon' => 'bi-box-seam-fill', 'size' => 36, 'alt' => $riwayat->menu->nama ?? ''])
                      <div><b>{{ $riwayat->menu->nama ?? '-' }}</b></div>
                    </div>
                  </td>
                  <td>
                    <div class="sm-flow {{ $masuk ? 'in' : 'out' }}">
                      <span class="sm-flow-ico"><i class="bi {{ $masuk ? 'bi-arrow-down-left' : 'bi-arrow-up-right' }}"></i></span>
                      <div>
                        <b>{{ $masuk ? '+' : '−' }}{{ number_format($riwayat->jumlah, 0, ',', '.') }} porsi</b>
                        <small>{{ $masuk ? 'Masuk' : 'Keluar' }}</small>
                      </div>
                    </div>
                  </td>
                  <td class="sm-note">{{ $riwayat->keterangan ?: '-' }}</td>
                  <td><span class="sm-user"><i class="sm-av">{{ strtoupper(mb_substr($riwayat->user->name ?? '?', 0, 1)) }}</i>{{ $riwayat->user->name ?? '-' }}</span></td>
                  <td class="sm-col-act">
                    @if ($masuk)
                      <div class="sm-actions">
                        <form action="{{ route('admin.stok.hapus-entri-masuk', $riwayat) }}" method="POST"
                          onsubmit="return confirm('Hapus entri stok masuk ini? Stoknya ikut berkurang sesuai jumlah di atas.')">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="sm-icon-btn danger" title="Hapus entri ini"><i class="bi bi-trash3-fill"></i></button>
                        </form>
                      </div>
                    @else
                      <span class="sm-tag {{ $otomatis ? 'auto' : 'off' }}">{{ $otomatis ? 'Otomatis' : 'Koreksi' }}</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="sm-empty"><i class="bi bi-clock-history"></i>Belum ada riwayat stok. Klik "Tambah Stok Menu" untuk mulai input.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="sm-foot">
          <span>Menampilkan {{ $riwayatMasuk->count() }} dari {{ $riwayatMasuk->total() }} riwayat</span>
          {{ $riwayatMasuk->links() }}
        </div>
      </div>
    </div>

    <!-- ===================== TAB: TOPPING & BUMBU ===================== -->
    <div id="tab-pane-topping" class="d-none">

      @include('admin.stok._ringkasan', [
          'r' => $ringkasan['topping'],
          'labelTotal' => 'Total Stok Topping & Bumbu',
          'satuanTotal' => 'stok',
          'satuanHabis' => 'item',
      ])

      <div class="sm-head">
        <div class="sm-head-title">
          <span class="sm-head-ico"><i class="bi bi-droplet-fill"></i></span>
          <div><h3>Stok Topping &amp; Bumbu</h3><p>Sisa stok saus dan bubuk tabur</p></div>
        </div>
        <button type="button" class="btn-custom btn-custom-primary" data-bs-toggle="modal" data-bs-target="#modal-tambah-stok-topping">
          <i class="bi bi-plus-lg"></i><span>Tambah Stok Topping/Bumbu</span>
        </button>
      </div>

      <div class="sm-card">
        <div class="table-responsive">
          <table class="sm-table">
            <thead>
              <tr>
                <th class="sm-col-no">No</th>
                <th>Topping / Bumbu</th>
                <th>Sisa Stok</th>
                <th>Status</th>
                <th>Stok Masuk Terakhir</th>
                <th class="sm-col-act">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($dataStokTopping as $index => $item)
                @php $tone = $statusTone[$item->status]; @endphp
                <tr>
                  <td class="sm-col-no">{{ $dataStokTopping->firstItem() + $index }}</td>
                  <td>
                    <div class="sm-item">
                      @include('partials.thumb', ['src' => $item->topping->gambar_url, 'icon' => $item->topping->jenis === 'saus' ? 'bi-droplet-fill' : 'bi-stars', 'size' => 46, 'alt' => $item->topping->nama])
                      <div>
                        <b>{{ $item->topping->nama }}</b>
                        <span><i class="sm-tag {{ $item->topping->jenis === 'saus' ? 'bumbu' : 'topping' }}">{{ $item->topping->jenis_label }}</i></span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="sm-stock">
                      <div class="sm-stock-num">{{ number_format($item->stok, 0, ',', '.') }}<small>stok</small></div>
                      <div class="sm-meter {{ $tone }}"><i style="width: {{ $persenStok($item->stok, $item->topping->stok_minimum, $item->status) }}%"></i></div>
                      <span class="sm-stock-min">Batas menipis {{ number_format($item->topping->stok_minimum, 0, ',', '.') }}</span>
                    </div>
                  </td>
                  <td><span class="sm-pill {{ $tone }}">{{ $statusLabel[$item->status] }}</span></td>
                  <td>
                    @if ($item->terakhir_masuk)
                      <div class="sm-last"><b>{{ $item->terakhir_masuk->tanggal->translatedFormat('d M Y') }}</b><span class="sm-in">+{{ number_format($item->terakhir_masuk->jumlah, 0, ',', '.') }}</span></div>
                    @else
                      <span class="sm-muted">Belum ada</span>
                    @endif
                  </td>
                  <td class="sm-col-act">
                    <div class="sm-actions">
                      <button type="button" class="sm-icon-btn" title="Edit stok" data-bs-toggle="modal" data-bs-target="#modal-koreksi-topping-{{ $item->topping->id }}"><i class="bi bi-pencil-fill"></i></button>
                      <form action="{{ route('admin.stok.topping.hapus-riwayat', $item->topping) }}" method="POST"
                        onsubmit="return confirm('Hapus semua riwayat stok &quot;{{ $item->topping->nama }}&quot;? Stoknya bakal balik ke 0 dan tidak bisa dibatalkan.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="sm-icon-btn danger" title="Hapus semua riwayat stok"><i class="bi bi-trash3-fill"></i></button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="sm-empty"><i class="bi bi-inbox"></i>Belum ada saus/bubuk tabur. Tambahkan dulu lewat halaman Master Topping &amp; Bumbu.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="sm-foot">
          <span>Menampilkan {{ $dataStokTopping->count() }} dari {{ $dataStokTopping->total() }} item</span>
          {{ $dataStokTopping->links() }}
        </div>
      </div>

      <div class="sm-head sm-head-sub">
        <div class="sm-head-title">
          <span class="sm-head-ico in"><i class="bi bi-arrow-down-left"></i></span>
          <div><h3>Riwayat Stok Masuk &amp; Keluar</h3><p>Restock dari admin dan stok terpakai otomatis tiap transaksi</p></div>
        </div>
        <div class="sm-seg" role="group" aria-label="Filter riwayat">
          @foreach (['' => 'Semua', 'masuk' => 'Masuk', 'keluar' => 'Keluar'] as $kunci => $teks)
            <a href="{{ request()->fullUrlWithQuery(['riwayat_topping' => $kunci ?: null, 'riwayat_topping_page_page' => null]) }}"
              class="{{ ($filterRiwayatTopping ?? '') === $kunci ? 'active' : '' }}">{{ $teks }}</a>
          @endforeach
        </div>
      </div>

      <div class="sm-card">
        <div class="table-responsive">
          <table class="sm-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Topping / Bumbu</th>
                <th>Jumlah</th>
                <th>Keterangan</th>
                <th>Diinput Oleh</th>
                <th class="sm-col-act">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($riwayatMasukTopping as $riwayat)
                @php
                  $masuk = $riwayat->jenis === 'masuk';
                  $otomatis = ! $masuk && str_starts_with((string) $riwayat->keterangan, 'Otomatis');
                @endphp
                <tr class="{{ $otomatis ? 'sm-row-auto' : '' }}">
                  <td>
                    <div class="sm-date"><b>{{ $riwayat->tanggal->translatedFormat('d') }}</b><span>{{ $riwayat->tanggal->translatedFormat('M Y') }}</span></div>
                  </td>
                  <td>
                    <div class="sm-item sm-item-sm">
                      @include('partials.thumb', ['src' => $riwayat->topping?->gambar_url, 'icon' => 'bi-droplet-fill', 'size' => 36, 'alt' => $riwayat->topping->nama ?? ''])
                      <div><b>{{ $riwayat->topping->nama ?? '-' }}</b></div>
                    </div>
                  </td>
                  <td>
                    <div class="sm-flow {{ $masuk ? 'in' : 'out' }}">
                      <span class="sm-flow-ico"><i class="bi {{ $masuk ? 'bi-arrow-down-left' : 'bi-arrow-up-right' }}"></i></span>
                      <div>
                        <b>{{ $masuk ? '+' : '−' }}{{ number_format($riwayat->jumlah, 0, ',', '.') }}</b>
                        <small>{{ $masuk ? 'Masuk' : 'Keluar' }}</small>
                      </div>
                    </div>
                  </td>
                  <td class="sm-note">{{ $riwayat->keterangan ?: '-' }}</td>
                  <td><span class="sm-user"><i class="sm-av">{{ strtoupper(mb_substr($riwayat->user->name ?? '?', 0, 1)) }}</i>{{ $riwayat->user->name ?? '-' }}</span></td>
                  <td class="sm-col-act">
                    @if ($masuk)
                      <div class="sm-actions">
                        <form action="{{ route('admin.stok.topping.hapus-entri-masuk', $riwayat) }}" method="POST"
                          onsubmit="return confirm('Hapus entri stok masuk ini? Stoknya ikut berkurang sesuai jumlah di atas.')">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="sm-icon-btn danger" title="Hapus entri ini"><i class="bi bi-trash3-fill"></i></button>
                        </form>
                      </div>
                    @else
                      <span class="sm-tag {{ $otomatis ? 'auto' : 'off' }}">{{ $otomatis ? 'Otomatis' : 'Koreksi' }}</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="sm-empty"><i class="bi bi-clock-history"></i>Belum ada riwayat stok topping/bumbu.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="sm-foot">
          <span>Menampilkan {{ $riwayatMasukTopping->count() }} dari {{ $riwayatMasukTopping->total() }} riwayat</span>
          {{ $riwayatMasukTopping->links() }}
        </div>
      </div>
    </div>


    <!-- ===================== TAB: BAHAN OPERASIONAL ===================== -->
    <div id="tab-pane-bahan" class="d-none">

      @include('admin.stok._ringkasan', [
          'r' => $ringkasan['bahan'],
          'labelTotal' => 'Total Bahan Operasional',
          'satuanTotal' => 'bahan',
          'satuanHabis' => 'bahan',
      ])

      <div class="bo-info">
        <i class="bi bi-info-circle-fill"></i>
        <div>
          <b>Stok berkurang otomatis tiap transaksi</b>
          <p>
            Isi stok dalam liter (minyak) atau tabung (gas), 1 sampai {{ \App\Models\BahanOperasional::MAKS_SEKALI_MASUK }} per input.
            1 liter minyak = 10 sesi goreng, 1 tabung gas 3 kg = 20 sesi (1 sesi = 1 transaksi). Setiap transaksi selesai,
            sisa sesi berkurang 1 tanpa perlu konfirmasi kasir. Saat menipis atau habis, kamu dapat notifikasi di lonceng.
          </p>
        </div>
      </div>

      <div class="sm-head">
        <div class="sm-head-title">
          <span class="sm-head-ico"><i class="bi bi-fire"></i></span>
          <div><h3>Stok Bahan Operasional</h3><p>Minyak goreng &amp; gas, dihitung dalam sesi goreng</p></div>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <a href="{{ route('admin.bahan-operasional.index') }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-gear"></i><span>Atur Master Bahan</span>
          </a>
          <button type="button" class="btn-custom btn-custom-primary" data-bs-toggle="modal" data-bs-target="#modal-tambah-stok-bahan">
            <i class="bi bi-plus-lg"></i><span>Tambah Stok Bahan</span>
          </button>
        </div>
      </div>

      <div class="sm-card">
        <div class="table-responsive">
          <table class="sm-table">
            <thead>
              <tr>
                <th class="sm-col-no">No</th>
                <th>Bahan</th>
                <th>Sisa Stok</th>
                <th>Sisa Sesi Goreng</th>
                <th>Status</th>
                <th>Stok Masuk Terakhir</th>
                <th class="sm-col-act">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($dataStokBahan as $index => $item)
                @php
                  $b = $item->bahan;
                  $tone = $statusTone[$item->status];
                @endphp
                <tr>
                  <td class="sm-col-no">{{ $index + 1 }}</td>
                  <td>
                    <div class="sm-item">
                      @include('partials.thumb', ['src' => $b->gambar_url, 'icon' => $b->ikon, 'size' => 46, 'alt' => $b->nama])
                      <div>
                        <b>{{ $b->nama }}</b>
                        <span>1 {{ $b->satuan }} = {{ $b->sesi_per_satuan }} sesi @unless ($b->is_active) · <i class="sm-tag off">Nonaktif</i> @endunless</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    @if ($item->status === 'belum_dikelola')
                      <span class="sm-muted">Belum ada stok</span>
                    @else
                      <div class="sm-stock-num">{{ $b->labelStok() }}</div>
                    @endif
                  </td>
                  <td style="min-width: 190px;">
                    @if ($item->status === 'belum_dikelola')
                      <span class="sm-muted">-</span>
                    @else
                      <div class="sm-stock">
                        <div class="sm-stock-num sm-stock-num-sm">{{ number_format($item->stok, 0, ',', '.') }}<small>sesi</small></div>
                        <div class="sm-meter {{ $tone }}"><i style="width: {{ $b->persen_berjalan }}%"></i></div>
                        <span class="sm-stock-min">{{ $b->sesi_berjalan }}/{{ $b->sesi_per_satuan }} sesi pada {{ $b->satuan }} berjalan</span>
                      </div>
                    @endif
                  </td>
                  <td><span class="sm-pill {{ $tone }}">{{ \App\Models\BahanOperasional::labelStatus($item->status) }}</span></td>
                  <td>
                    @if ($item->terakhir_masuk)
                      <div class="sm-last"><b>{{ $item->terakhir_masuk->tanggal->translatedFormat('d M Y') }}</b><span class="sm-in">+{{ $item->terakhir_masuk->jumlah ?: $item->terakhir_masuk->sesi }} {{ $item->terakhir_masuk->jumlah ? $b->satuan : 'sesi' }}</span></div>
                    @else
                      <span class="sm-muted">Belum ada</span>
                    @endif
                  </td>
                  <td class="sm-col-act">
                    <div class="sm-actions">
                      <button type="button" class="sm-icon-btn" title="Edit stok" data-bs-toggle="modal" data-bs-target="#modal-koreksi-bahan-{{ $b->id }}"><i class="bi bi-pencil-fill"></i></button>
                      <form action="{{ route('admin.stok.bahan.hapus-riwayat', $b) }}" method="POST"
                        data-confirm="Hapus semua riwayat stok &quot;{{ $b->nama }}&quot;?"
                        data-confirm-text="Sisa stoknya balik ke 0 dan tidak bisa dibatalkan."
                        data-confirm-yes="Ya, Hapus">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="sm-icon-btn danger" title="Hapus semua riwayat stok"><i class="bi bi-trash3-fill"></i></button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="sm-empty"><i class="bi bi-fire"></i>Belum ada bahan operasional. Tambahkan dulu lewat menu Master → Bahan Operasional.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="sm-head sm-head-sub">
        <div class="sm-head-title">
          <span class="sm-head-ico"><i class="bi bi-arrow-left-right"></i></span>
          <div><h3>Riwayat Stok Masuk &amp; Keluar</h3><p>Restock dari admin dan pemakaian otomatis tiap transaksi</p></div>
        </div>
        <div class="sm-seg" role="group" aria-label="Filter riwayat bahan">
          @foreach (['' => 'Semua', 'masuk' => 'Masuk', 'keluar' => 'Keluar'] as $kunci => $teks)
            <a href="{{ request()->fullUrlWithQuery(['riwayat_bahan' => $kunci ?: null, 'riwayat_bahan_page' => null]) }}"
              class="{{ ($filterRiwayatBahan ?? '') === $kunci ? 'active' : '' }}">{{ $teks }}</a>
          @endforeach
        </div>
      </div>

      <div class="sm-card">
        <div class="table-responsive">
          <table class="sm-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Bahan</th>
                <th>Jumlah</th>
                <th>Keterangan</th>
                <th>Dicatat Oleh</th>
                <th class="sm-col-act">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($riwayatBahan as $riwayat)
                @php $masuk = $riwayat->jenis === 'masuk'; $otomatis = ! $masuk && $riwayat->transaksi_id; @endphp
                <tr class="{{ $otomatis ? 'sm-row-auto' : '' }}">
                  <td>
                    <div class="sm-date"><b>{{ $riwayat->tanggal->translatedFormat('d') }}</b><span>{{ $riwayat->tanggal->translatedFormat('M Y') }}</span></div>
                  </td>
                  <td>
                    <div class="sm-item sm-item-sm">
                      @include('partials.thumb', ['src' => $riwayat->bahan?->gambar_url, 'icon' => $riwayat->bahan?->ikon ?? 'bi-fire', 'size' => 36, 'alt' => $riwayat->bahan->nama ?? ''])
                      <div><b>{{ $riwayat->bahan->nama ?? '-' }}</b></div>
                    </div>
                  </td>
                  <td>
                    <div class="sm-flow {{ $masuk ? 'in' : 'out' }}">
                      <span class="sm-flow-ico"><i class="bi {{ $masuk ? 'bi-arrow-down-left' : 'bi-arrow-up-right' }}"></i></span>
                      <div>
                        <b>{{ $masuk ? '+' : '−' }}{{ number_format($riwayat->sesi, 0, ',', '.') }} sesi</b>
                        <small>{{ $masuk ? 'Masuk' : 'Keluar' }}@if ($riwayat->jumlah > 0) · {{ $riwayat->jumlah }} {{ $riwayat->bahan->satuan ?? '' }}@endif</small>
                      </div>
                    </div>
                  </td>
                  <td class="sm-note">{{ $riwayat->keterangan ?: '-' }}</td>
                  <td><span class="sm-user"><i class="sm-av">{{ strtoupper(mb_substr($riwayat->user->name ?? '?', 0, 1)) }}</i>{{ $riwayat->user->name ?? '-' }}</span></td>
                  <td class="sm-col-act">
                    @if ($masuk)
                      <div class="sm-actions">
                        <form action="{{ route('admin.stok.bahan.hapus-entri-masuk', $riwayat) }}" method="POST"
                          data-confirm="Hapus entri stok masuk ini?"
                          data-confirm-text="Sisa stok bahannya ikut berkurang sesuai jumlah di atas."
                          data-confirm-yes="Ya, Hapus">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="sm-icon-btn danger" title="Hapus entri ini"><i class="bi bi-trash3-fill"></i></button>
                        </form>
                      </div>
                    @else
                      <span class="sm-tag {{ $otomatis ? 'auto' : 'off' }}">{{ $otomatis ? 'Otomatis' : 'Koreksi' }}</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="sm-empty"><i class="bi bi-clock-history"></i>Belum ada riwayat bahan operasional.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="sm-foot">
          <span>Menampilkan {{ $riwayatBahan->count() }} dari {{ $riwayatBahan->total() }} riwayat</span>
          {{ $riwayatBahan->links() }}
        </div>
      </div>
    </div>
  </div>

  <!-- ===================== MODAL: TAMBAH STOK ===================== -->
  <div class="modal fade" id="modal-tambah-stok" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="{{ route('admin.stok.store') }}" method="POST">
          @csrf
          <input type="hidden" name="_action" value="tambah-stok">

          <div class="modal-header modal-header-brand">
            <div class="modal-header-icon"><i class="bi bi-box-seam-fill"></i></div>
            <div>
              <h5 class="modal-title mb-0">Tambah Stok Masuk</h5>
              <small class="modal-subtitle">Catat barang yang baru masuk (restock).</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label-custom">Nama Barang</label>
              <select name="menu_id" class="form-select-custom w-100 {{ $errors->has('menu_id') ? 'is-invalid-custom' : '' }}"
                data-preview-target="#preview-stok-menu">
                <option value="">Pilih menu...</option>
                @foreach ($menuAktif as $menu)
                  <option value="{{ $menu->id }}"
                    data-foto="{{ $menu->gambar ? asset('storage/' . $menu->gambar) : '' }}"
                    data-sub="{{ $menu->kategori->nama ?? 'Menu' }}"
                    {{ (string) old('menu_id') === (string) $menu->id ? 'selected' : '' }}>
                    {{ $menu->nama }}
                  </option>
                @endforeach
              </select>
              @error('menu_id')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
              <div class="pilihan-preview d-none" id="preview-stok-menu">
                <span class="thumb-box" style="--thumb-size: 56px;">
                  <i class="bi bi-box-seam-fill thumb-box-icon"></i>
                  <img src="" alt="" class="thumb-box-img preview-img d-none">
                </span>
                <div>
                  <div class="pilihan-preview-nama"></div>
                  <div class="pilihan-preview-sub"></div>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label-custom">Jumlah Masuk</label>
              <div class="qty-wrap" data-qty>
                <div class="qty-step">
                  <button type="button" data-qty-ubah="-1" aria-label="Kurangi"><i class="bi bi-dash-lg"></i></button>
                  <input type="number" name="jumlah" min="1" value="{{ old('jumlah') }}" placeholder="0"
                    class="{{ $errors->has('jumlah') ? 'is-invalid-custom' : '' }}">
                  <button type="button" data-qty-ubah="1" aria-label="Tambah"><i class="bi bi-plus-lg"></i></button>
                </div>
              </div>
              <div class="qty-chips" data-qty-chips>
                <button type="button" data-qty-tambah="10">+10</button>
                <button type="button" data-qty-tambah="25">+25</button>
                <button type="button" data-qty-tambah="50">+50</button>
                <button type="button" data-qty-tambah="100">+100</button>
              </div>
              @error('jumlah')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label-custom">Tanggal</label>
              <input type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}"
                class="form-control-custom {{ $errors->has('tanggal') ? 'is-invalid-custom' : '' }}">
              @error('tanggal')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
            </div>

            <div class="mb-1">
              <label class="form-label-custom">Keterangan (opsional)</label>
              <input type="text" name="keterangan" value="{{ old('keterangan') }}"
                class="form-control-custom" placeholder="contoh: Restock bahan dari supplier">
            </div>
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

  @foreach ($dataStok as $item)
    <!-- ===================== MODAL: KOREKSI STOK ===================== -->
    <div class="modal fade" id="modal-koreksi-{{ $item->menu->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form action="{{ route('admin.stok.koreksi', $item->menu) }}" method="POST">
            @csrf
            @method('PATCH')
            <input type="hidden" name="_action" value="koreksi-{{ $item->menu->id }}">

            <div class="modal-header modal-header-brand">
              @include('partials.thumb', ['src' => $item->menu->gambar ? asset('storage/' . $item->menu->gambar) : null, 'icon' => 'bi-box-seam-fill', 'size' => 48, 'alt' => $item->menu->nama])
              <div>
                <h5 class="modal-title mb-0">Edit Stok — {{ $item->menu->nama }}</h5>
                <small class="modal-subtitle">Stok sekarang {{ number_format($item->stok, 0, ',', '.') }}</small>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <p class="table-user-sub">
                Stok saat ini: <strong>{{ $item->stok }}</strong>. Isi angka stok yang benar di bawah ini,
                selisihnya otomatis tercatat di riwayat Stok Masuk/Keluar.
              </p>

              <div class="mb-3">
                <label class="form-label-custom">Stok Baru</label>
                <input type="number" name="stok_baru" min="0"
                  value="{{ old('_action') === 'koreksi-' . $item->menu->id ? old('stok_baru') : $item->stok }}"
                  class="form-control-custom {{ old('_action') === 'koreksi-' . $item->menu->id && $errors->has('stok_baru') ? 'is-invalid-custom' : '' }}">
                @if (old('_action') === 'koreksi-' . $item->menu->id)
                  @error('stok_baru')
                    <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
                  @enderror
                @endif
              </div>

              <div class="mb-3">
                <label class="form-label-custom">Batas Stok Menipis</label>
                <input type="number" name="stok_minimum" min="0"
                  value="{{ old('_action') === 'koreksi-' . $item->menu->id ? old('stok_minimum') : $item->menu->stok_minimum }}"
                  class="form-control-custom {{ old('_action') === 'koreksi-' . $item->menu->id && $errors->has('stok_minimum') ? 'is-invalid-custom' : '' }}">
                <span class="form-feedback-custom">Kalau stok turun sampai batas ini, statusnya jadi "Menipis".</span>
                @if (old('_action') === 'koreksi-' . $item->menu->id)
                  @error('stok_minimum')
                    <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
                  @enderror
                @endif
              </div>

              <div class="mb-1">
                <label class="form-label-custom">Keterangan (opsional)</label>
                <input type="text" name="keterangan" class="form-control-custom" placeholder="contoh: Koreksi setelah stok opname">
              </div>
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

  <!-- ===================== MODAL: TAMBAH STOK TOPPING ===================== -->
  <div class="modal fade" id="modal-tambah-stok-topping" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="{{ route('admin.stok.topping.store') }}" method="POST">
          @csrf
          <input type="hidden" name="_action" value="tambah-stok-topping">

          <div class="modal-header modal-header-brand">
            <div class="modal-header-icon"><i class="bi bi-droplet-fill"></i></div>
            <div>
              <h5 class="modal-title mb-0">Tambah Stok Topping/Bumbu</h5>
              <small class="modal-subtitle">Catat bumbu atau topping yang baru masuk.</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label-custom">Bumbu / Topping</label>
              <select name="topping_id" class="form-select-custom w-100 {{ $errors->has('topping_id') ? 'is-invalid-custom' : '' }}"
                data-preview-target="#preview-stok-topping">
                <option value="">Pilih...</option>
                <optgroup label="Bumbu">
                  @foreach ($toppingAktif->where('jenis', 'saus') as $topping)
                    <option value="{{ $topping->id }}"
                      data-foto="{{ $topping->gambar_url }}"
                      data-sub="{{ $topping->jenis_label }}"
                      {{ (string) old('topping_id') === (string) $topping->id ? 'selected' : '' }}>
                      {{ $topping->nama }}
                    </option>
                  @endforeach
                </optgroup>
                <optgroup label="Topping">
                  @foreach ($toppingAktif->where('jenis', 'bubuk_tabur') as $topping)
                    <option value="{{ $topping->id }}"
                      data-foto="{{ $topping->gambar_url }}"
                      data-sub="{{ $topping->jenis_label }}"
                      {{ (string) old('topping_id') === (string) $topping->id ? 'selected' : '' }}>
                      {{ $topping->nama }}
                    </option>
                  @endforeach
                </optgroup>
              </select>
              @error('topping_id')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
              <div class="pilihan-preview d-none" id="preview-stok-topping">
                <span class="thumb-box" style="--thumb-size: 56px;">
                  <i class="bi bi-droplet-fill thumb-box-icon"></i>
                  <img src="" alt="" class="thumb-box-img preview-img d-none">
                </span>
                <div>
                  <div class="pilihan-preview-nama"></div>
                  <div class="pilihan-preview-sub"></div>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label-custom">Jumlah Masuk</label>
              <div class="qty-wrap" data-qty>
                <div class="qty-step">
                  <button type="button" data-qty-ubah="-1" aria-label="Kurangi"><i class="bi bi-dash-lg"></i></button>
                  <input type="number" name="jumlah" min="1" value="{{ old('jumlah') }}" placeholder="0"
                    class="{{ $errors->has('jumlah') ? 'is-invalid-custom' : '' }}">
                  <button type="button" data-qty-ubah="1" aria-label="Tambah"><i class="bi bi-plus-lg"></i></button>
                </div>
              </div>
              <div class="qty-chips" data-qty-chips>
                <button type="button" data-qty-tambah="10">+10</button>
                <button type="button" data-qty-tambah="25">+25</button>
                <button type="button" data-qty-tambah="50">+50</button>
                <button type="button" data-qty-tambah="100">+100</button>
              </div>
              @error('jumlah')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label-custom">Tanggal</label>
              <input type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}"
                class="form-control-custom {{ $errors->has('tanggal') ? 'is-invalid-custom' : '' }}">
              @error('tanggal')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
            </div>

            <div class="mb-1">
              <label class="form-label-custom">Keterangan (opsional)</label>
              <input type="text" name="keterangan" value="{{ old('keterangan') }}"
                class="form-control-custom" placeholder="contoh: Restock bahan dari supplier">
            </div>
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

  @foreach ($dataStokTopping as $item)
    <!-- ===================== MODAL: KOREKSI STOK TOPPING ===================== -->
    <div class="modal fade" id="modal-koreksi-topping-{{ $item->topping->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form action="{{ route('admin.stok.topping.koreksi', $item->topping) }}" method="POST">
            @csrf
            @method('PATCH')
            <input type="hidden" name="_action" value="koreksi-topping-{{ $item->topping->id }}">

            <div class="modal-header modal-header-brand">
              @include('partials.thumb', ['src' => $item->topping->gambar_url, 'icon' => 'bi-droplet-fill', 'size' => 48, 'alt' => $item->topping->nama])
              <div>
                <h5 class="modal-title mb-0">Edit Stok — {{ $item->topping->nama }}</h5>
                <small class="modal-subtitle">{{ $item->topping->jenis_label }} · stok sekarang {{ number_format($item->stok, 0, ',', '.') }}</small>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <p class="table-user-sub">
                Stok saat ini: <strong>{{ $item->stok }}</strong>. Isi angka stok yang benar di bawah ini,
                selisihnya otomatis tercatat di riwayat Stok Masuk/Keluar.
              </p>

              <div class="mb-3">
                <label class="form-label-custom">Stok Baru</label>
                <input type="number" name="stok_baru" min="0"
                  value="{{ old('_action') === 'koreksi-topping-' . $item->topping->id ? old('stok_baru') : $item->stok }}"
                  class="form-control-custom {{ old('_action') === 'koreksi-topping-' . $item->topping->id && $errors->has('stok_baru') ? 'is-invalid-custom' : '' }}">
                @if (old('_action') === 'koreksi-topping-' . $item->topping->id)
                  @error('stok_baru')
                    <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
                  @enderror
                @endif
              </div>

              <div class="mb-3">
                <label class="form-label-custom">Batas Stok Menipis</label>
                <input type="number" name="stok_minimum" min="0"
                  value="{{ old('_action') === 'koreksi-topping-' . $item->topping->id ? old('stok_minimum') : $item->topping->stok_minimum }}"
                  class="form-control-custom {{ old('_action') === 'koreksi-topping-' . $item->topping->id && $errors->has('stok_minimum') ? 'is-invalid-custom' : '' }}">
                <span class="form-feedback-custom">Kalau stok turun sampai batas ini, statusnya jadi "Menipis".</span>
                @if (old('_action') === 'koreksi-topping-' . $item->topping->id)
                  @error('stok_minimum')
                    <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
                  @enderror
                @endif
              </div>

              <div class="mb-1">
                <label class="form-label-custom">Keterangan (opsional)</label>
                <input type="text" name="keterangan" class="form-control-custom" placeholder="contoh: Koreksi setelah stok opname">
              </div>
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

  <!-- ===================== MODAL: TAMBAH STOK BAHAN OPERASIONAL ===================== -->
  <div class="modal fade" id="modal-tambah-stok-bahan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="{{ route('admin.stok.bahan.store') }}" method="POST">
          @csrf
          <input type="hidden" name="_action" value="tambah-stok-bahan">

          <div class="modal-header modal-header-brand">
            <div class="modal-header-icon"><i class="bi bi-fire"></i></div>
            <div>
              <h5 class="modal-title mb-0">Tambah Stok Bahan Operasional</h5>
              <small class="modal-subtitle">Catat minyak (liter) / gas (tabung) yang baru dibeli.</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label-custom">Bahan</label>
              <select name="bahan_operasional_id" class="form-select-custom w-100 {{ $errors->has('bahan_operasional_id') ? 'is-invalid-custom' : '' }}"
                data-preview-target="#preview-stok-bahan">
                <option value="">Pilih bahan...</option>
                @foreach ($bahanAktif as $bahan)
                  <option value="{{ $bahan->id }}"
                    data-foto="{{ $bahan->gambar_url }}"
                    data-sub="1 {{ $bahan->satuan }} = {{ $bahan->sesi_per_satuan }} sesi goreng"
                    {{ (string) old('bahan_operasional_id') === (string) $bahan->id ? 'selected' : '' }}>
                    {{ $bahan->nama }} ({{ $bahan->satuan }} = {{ $bahan->sesi_per_satuan }} sesi goreng)
                  </option>
                @endforeach
              </select>
              @error('bahan_operasional_id')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
              <div class="pilihan-preview d-none" id="preview-stok-bahan">
                <span class="thumb-box" style="--thumb-size: 56px;">
                  <i class="bi bi-fire thumb-box-icon"></i>
                  <img src="" alt="" class="thumb-box-img preview-img d-none">
                </span>
                <div>
                  <div class="pilihan-preview-nama"></div>
                  <div class="pilihan-preview-sub"></div>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label-custom">Jumlah Masuk</label>
              <div class="qty-wrap" data-bahan-qty>
                <div class="qty-step">
                  <button type="button" data-bahan-ubah="-1" aria-label="Kurangi"><i class="bi bi-dash-lg"></i></button>
                  <input type="number" name="jumlah" min="1" max="{{ \App\Models\BahanOperasional::MAKS_SEKALI_MASUK }}" value="{{ old('jumlah', 1) }}" placeholder="1"
                    class="{{ $errors->has('jumlah') ? 'is-invalid-custom' : '' }}">
                  <button type="button" data-bahan-ubah="1" aria-label="Tambah"><i class="bi bi-plus-lg"></i></button>
                </div>
              </div>
              <div class="qty-chips" data-bahan-chips>
                @for ($n = 1; $n <= \App\Models\BahanOperasional::MAKS_SEKALI_MASUK; $n++)
                  <button type="button" data-bahan-set="{{ $n }}">{{ $n }}</button>
                @endfor
              </div>
              <span class="form-feedback-custom">Pilih 1 sampai {{ \App\Models\BahanOperasional::MAKS_SEKALI_MASUK }} liter/tabung. <span id="bahan-konversi"></span></span>
              @error('jumlah')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label-custom">Tanggal</label>
              <input type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}"
                class="form-control-custom {{ $errors->has('tanggal') ? 'is-invalid-custom' : '' }}">
              @error('tanggal')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
            </div>

            <div class="mb-1">
              <label class="form-label-custom">Keterangan (opsional)</label>
              <input type="text" name="keterangan" value="{{ old('keterangan') }}"
                class="form-control-custom" placeholder="contoh: Beli di warung / agen gas">
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-custom btn-batal" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i><span>Batal</span></button>
            <button type="submit" class="btn-custom btn-custom-primary"><i class="bi bi-check-lg"></i><span>Simpan</span></button>
          </div>
        </form>
      </div>
    </div>
  </div>

  @foreach ($dataStokBahan as $item)
    <!-- ===================== MODAL: KOREKSI STOK BAHAN ===================== -->
    <div class="modal fade" id="modal-koreksi-bahan-{{ $item->bahan->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form action="{{ route('admin.stok.bahan.koreksi', $item->bahan) }}" method="POST">
            @csrf
            @method('PATCH')
            <input type="hidden" name="_action" value="koreksi-bahan-{{ $item->bahan->id }}">

            <div class="modal-header modal-header-brand">
              @include('partials.thumb', ['src' => $item->bahan->gambar_url, 'icon' => $item->bahan->ikon, 'size' => 48, 'alt' => $item->bahan->nama])
              <div>
                <h5 class="modal-title mb-0">Edit Stok — {{ $item->bahan->nama }}</h5>
                <small class="modal-subtitle">Sisa sekarang {{ $item->bahan->labelStok() }} ({{ number_format($item->stok, 0, ',', '.') }} sesi goreng)</small>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <p class="table-user-sub">
                Sisa saat ini: <strong>{{ $item->stok }}</strong> sesi goreng ({{ $item->bahan->labelStok() }}).
                Isi angka yang benar di bawah ini, selisihnya otomatis tercatat di riwayat.
                1 {{ $item->bahan->satuan }} = {{ $item->bahan->sesi_per_satuan }} sesi.
              </p>

              <div class="mb-3">
                <label class="form-label-custom">Sisa Sesi Goreng Baru</label>
                <input type="number" name="sesi_baru" min="0"
                  value="{{ old('_action') === 'koreksi-bahan-' . $item->bahan->id ? old('sesi_baru') : $item->stok }}"
                  class="form-control-custom {{ old('_action') === 'koreksi-bahan-' . $item->bahan->id && $errors->has('sesi_baru') ? 'is-invalid-custom' : '' }}">
                @if (old('_action') === 'koreksi-bahan-' . $item->bahan->id)
                  @error('sesi_baru')
                    <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
                  @enderror
                @endif
              </div>

              <div class="mb-3">
                <label class="form-label-custom">Batas Menipis (sesi)</label>
                <input type="number" name="stok_minimum" min="0"
                  value="{{ old('_action') === 'koreksi-bahan-' . $item->bahan->id ? old('stok_minimum') : $item->bahan->stok_minimum }}"
                  class="form-control-custom {{ old('_action') === 'koreksi-bahan-' . $item->bahan->id && $errors->has('stok_minimum') ? 'is-invalid-custom' : '' }}">
                <span class="form-feedback-custom">Kalau sisa sesi turun sampai batas ini, statusnya jadi "Menipis" dan muncul di notifikasi.</span>
                @if (old('_action') === 'koreksi-bahan-' . $item->bahan->id)
                  @error('stok_minimum')
                    <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
                  @enderror
                @endif
              </div>

              <div class="mb-1">
                <label class="form-label-custom">Keterangan (opsional)</label>
                <input type="text" name="keterangan" class="form-control-custom" placeholder="contoh: Koreksi setelah cek stok">
              </div>
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
    // Stepper & tombol jumlah cepat di modal Tambah Stok (hanya membantu isi input "jumlah").
    document.querySelectorAll('[data-qty]').forEach(function (wrap) {
      var input = wrap.querySelector('input[name="jumlah"]');
      var chips = wrap.parentElement.querySelector('[data-qty-chips]');
      function set(nilai) { input.value = Math.max(1, parseInt(nilai || 0, 10) || 1); }
      wrap.querySelectorAll('[data-qty-ubah]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          set((parseInt(input.value, 10) || 0) + parseInt(btn.dataset.qtyUbah, 10));
        });
      });
      if (chips) {
        chips.querySelectorAll('[data-qty-tambah]').forEach(function (btn) {
          btn.addEventListener('click', function () {
            set((parseInt(input.value, 10) || 0) + parseInt(btn.dataset.qtyTambah, 10));
          });
        });
      }
    });
    // Modal Tambah Stok Bahan: pilih 1-5 liter/tabung + hitung setara berapa sesi goreng.
    document.querySelectorAll('[data-bahan-qty]').forEach(function (wrap) {
      var form = wrap.closest('form');
      var input = wrap.querySelector('input[name="jumlah"]');
      var select = form.querySelector('select[name="bahan_operasional_id"]');
      var info = form.querySelector('#bahan-konversi');
      var maks = parseInt(input.getAttribute('max'), 10) || 5;
      var chips = wrap.parentElement.querySelectorAll('[data-bahan-set]');

      function set(n) {
        n = Math.min(maks, Math.max(1, parseInt(n, 10) || 1));
        input.value = n;
        chips.forEach(function (c) { c.classList.toggle('active', parseInt(c.dataset.bahanSet, 10) === n); });
        var opt = select && select.options[select.selectedIndex];
        var m = opt && opt.textContent.match(/=\s*(\d+)\s*sesi/);
        if (info) info.textContent = m ? ('= ' + (n * parseInt(m[1], 10)) + ' sesi goreng (' + (n * parseInt(m[1], 10)) + ' transaksi)') : '';
      }

      wrap.querySelectorAll('[data-bahan-ubah]').forEach(function (btn) {
        btn.addEventListener('click', function () { set((parseInt(input.value, 10) || 0) + parseInt(btn.dataset.bahanUbah, 10)); });
      });
      chips.forEach(function (c) { c.addEventListener('click', function () { set(c.dataset.bahanSet); }); });
      input.addEventListener('input', function () { set(input.value); });
      if (select) select.addEventListener('change', function () { set(input.value); });
      set(input.value);
    });

    // Preview foto + nama saat memilih barang di modal "Tambah Stok".
    document.querySelectorAll('select[data-preview-target]').forEach(function (select) {
      var box = document.querySelector(select.getAttribute('data-preview-target'));
      if (!box) return;

      function perbarui() {
        var opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) { box.classList.add('d-none'); return; }

        box.classList.remove('d-none');
        box.querySelector('.pilihan-preview-nama').textContent = opt.textContent.trim();
        box.querySelector('.pilihan-preview-sub').textContent = opt.getAttribute('data-sub') || '';

        var img = box.querySelector('.preview-img');
        var foto = opt.getAttribute('data-foto');
        if (foto) {
          img.onerror = function () { img.classList.add('d-none'); };
          img.src = foto;
          img.classList.remove('d-none');
        } else {
          img.classList.add('d-none');
          img.removeAttribute('src');
        }
      }

      select.addEventListener('change', perbarui);
      perbarui();
    });

    function tampilkanTabStok(tab) {
      const panes = {
        'data-stok': document.getElementById('tab-pane-data-stok'),
        'topping': document.getElementById('tab-pane-topping'),
        'bahan': document.getElementById('tab-pane-bahan'),
      };
      const btns = {
        'data-stok': document.getElementById('tab-btn-data-stok'),
        'topping': document.getElementById('tab-btn-topping'),
        'bahan': document.getElementById('tab-btn-bahan'),
      };

      // Nilai lama dari localStorage (mis. 'stok-masuk') balik ke tab Menu.
      if (!panes[tab]) tab = 'data-stok';

      Object.keys(panes).forEach((key) => {
        const aktif = key === tab;
        panes[key].classList.toggle('d-none', !aktif);
        btns[key].classList.toggle('btn-custom-primary', aktif);
        btns[key].classList.toggle('btn-custom-outline-secondary', !aktif);
      });

      localStorage.setItem('tabStokAktif', tab);
    }

    document.addEventListener('DOMContentLoaded', function () {
      // Balik ke tab terakhir dibuka (biar nggak reset ke Data Stok
      // terus tiap kali reload/pagination di tab lain).
      const tabTersimpan = localStorage.getItem('tabStokAktif');
      if (tabTersimpan && tabTersimpan !== 'data-stok') {
        tampilkanTabStok(tabTersimpan);
      }

      // Kalau ada error validasi, otomatis buka lagi modal yang tadi disubmit.
      const action = @json(old('_action'));
      if (!action) return;

      let modalId = null;
      if (action === 'tambah-stok') {
        modalId = 'modal-tambah-stok';
      } else if (action === 'tambah-stok-topping') {
        modalId = 'modal-tambah-stok-topping';
        tampilkanTabStok('topping');
      } else if (action === 'tambah-stok-bahan') {
        modalId = 'modal-tambah-stok-bahan';
        tampilkanTabStok('bahan');
      } else if (action.startsWith('koreksi-bahan-')) {
        modalId = 'modal-' + action;
        tampilkanTabStok('bahan');
      } else if (action.startsWith('koreksi-topping-')) {
        modalId = 'modal-' + action;
        tampilkanTabStok('topping');
      } else if (action.startsWith('koreksi-')) {
        modalId = 'modal-' + action;
        tampilkanTabStok('data-stok');
      }

      const modalEl = modalId ? document.getElementById(modalId) : null;
      if (modalEl && window.bootstrap) {
        new bootstrap.Modal(modalEl).show();
      }
    });
  </script>
@endpush
