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
{{-- Dimuat di sini (bukan di @push styles) supaya urutannya SETELAH cimol-final & cimol-premium. --}}
<link rel="stylesheet" href="{{ asset('assets_dashboard/css/stok-rapi.css') }}?v={{ @filemtime(public_path('assets_dashboard/css/stok-rapi.css')) }}">
<div class="ap stok-page">

  <!-- START: Page Header -->
  <div class="ap-hero">
    <div>
      <h1>Manajemen Stok</h1>
      <p>Stok dipisah per kelompok: Menu, Topping &amp; Bumbu, dan Bahan Operasional (minyak goreng, gas, dan kemasan). Pilih tab di bawah.</p>
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
        <select name="kasir_id" class="form-select-custom" style="max-width: 220px;" onchange="this.form.submit()" title="Lihat stok per kasir">
          <option value="">Semua kasir</option>
          @foreach ($kasirs as $k)
            <option value="{{ $k->id }}" {{ (string) request('kasir_id') === (string) $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
          @endforeach
        </select>
        @if (request('cari') || request('kasir_id'))
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
          <div><h3>Stok Menu</h3><p>Sisa stok setiap menu, dipisah per kasir</p></div>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <form action="{{ route('admin.stok.jatah-harian') }}" method="POST"
            data-confirm="Bagikan jatah harian menu ke semua kasir aktif?"
            data-confirm-text="Tiap kasir aktif dapat stok sesuai jatah harian tiap menu (atur lewat tombol Edit pada baris menu). Kasir yang hari ini sudah menerima stok menu itu dilewati."
            data-confirm-yes="Ya, Bagikan">
            @csrf
            <button type="submit" class="btn-custom btn-custom-outline-secondary">
              <i class="bi bi-calendar-check"></i><span>Bagikan Jatah Harian</span>
            </button>
          </form>
          <button type="button" class="btn-custom btn-custom-primary" data-bs-toggle="modal" data-bs-target="#modal-tambah-stok">
            <i class="bi bi-plus-lg"></i><span>Tambah Stok Menu</span>
          </button>
        </div>
      </div>

      <div class="sm-card">
        <div class="table-responsive">
          <table class="sm-table">
            <thead>
              <tr>
                <th class="sm-col-no">No</th>
                <th>Menu</th>
                <th>Kasir</th>
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
                    @if ($item->kasir)
                      <span class="sm-user"><i class="sm-av">{{ strtoupper(mb_substr($item->kasir->name, 0, 1)) }}</i>{{ $item->kasir->name }}</span>
                    @else
                      <span class="sm-muted">Belum dibagi</span>
                    @endif
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
                    @if ($item->kasir)
                      <div class="sm-actions">
                        <button type="button" class="sm-icon-btn" title="Edit stok {{ $item->kasir->name }}" data-bs-toggle="modal" data-bs-target="#modal-koreksi-{{ $item->menu->id }}-{{ $item->kasir->id }}"><i class="bi bi-pencil-fill"></i></button>
                        <form action="{{ route('admin.stok.hapus-riwayat', $item->menu) }}" method="POST"
                          onsubmit="return confirm('Hapus semua riwayat stok &quot;{{ $item->menu->nama }}&quot; milik {{ $item->kasir->name }}? Stoknya bakal balik ke 0 dan tidak bisa dibatalkan.')">
                          @csrf
                          @method('DELETE')
                          <input type="hidden" name="kasir_id" value="{{ $item->kasir->id }}">
                          <button type="submit" class="sm-icon-btn danger" title="Hapus semua riwayat stok kasir ini"><i class="bi bi-trash3-fill"></i></button>
                        </form>
                      </div>
                    @else
                      <span class="sm-muted">—</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="sm-empty"><i class="bi bi-inbox"></i>Belum ada menu. Tambahkan menu dulu lewat halaman Master Menu.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="sm-foot">
          <span>Menampilkan {{ $dataStok->count() }} dari {{ $dataStok->total() }} data stok</span>
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
                <th>Kasir</th>
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
                  <td>{{ $riwayat->kasir->name ?? '—' }}</td>
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
                <tr><td colspan="7" class="sm-empty"><i class="bi bi-clock-history"></i>Belum ada riwayat stok. Klik "Tambah Stok Menu" untuk mulai input.</td></tr>
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
        <div class="d-flex flex-wrap gap-2">
          <form action="{{ route('admin.stok.topping.jatah-harian') }}" method="POST"
            data-confirm="Bagikan jatah harian topping &amp; bumbu ke semua kasir aktif?"
            data-confirm-text="Tiap kasir aktif dapat stok sesuai jatah harian tiap topping/bumbu (atur lewat tombol Edit pada baris). Kasir yang hari ini sudah menerima stok topping/bumbu itu dilewati."
            data-confirm-yes="Ya, Bagikan">
            @csrf
            <button type="submit" class="btn-custom btn-custom-outline-secondary">
              <i class="bi bi-calendar-check"></i><span>Bagikan Jatah Harian</span>
            </button>
          </form>
          <button type="button" class="btn-custom btn-custom-primary" data-bs-toggle="modal" data-bs-target="#modal-tambah-stok-topping">
            <i class="bi bi-plus-lg"></i><span>Tambah Stok Topping/Bumbu</span>
          </button>
        </div>
      </div>

      <div class="sm-card">
        <div class="table-responsive">
          <table class="sm-table">
            <thead>
              <tr>
                <th class="sm-col-no">No</th>
                <th>Topping / Bumbu</th>
                <th>Kasir</th>
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
                    @if ($item->kasir)
                      <span class="sm-user"><i class="sm-av">{{ strtoupper(mb_substr($item->kasir->name, 0, 1)) }}</i>{{ $item->kasir->name }}</span>
                    @else
                      <span class="sm-muted">Belum dibagi</span>
                    @endif
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
                    @if ($item->kasir)
                      <div class="sm-actions">
                        <button type="button" class="sm-icon-btn" title="Edit stok {{ $item->kasir->name }}" data-bs-toggle="modal" data-bs-target="#modal-koreksi-topping-{{ $item->topping->id }}-{{ $item->kasir->id }}"><i class="bi bi-pencil-fill"></i></button>
                        <form action="{{ route('admin.stok.topping.hapus-riwayat', $item->topping) }}" method="POST"
                          onsubmit="return confirm('Hapus semua riwayat stok &quot;{{ $item->topping->nama }}&quot; milik {{ $item->kasir->name }}? Stoknya bakal balik ke 0 dan tidak bisa dibatalkan.')">
                          @csrf
                          @method('DELETE')
                          <input type="hidden" name="kasir_id" value="{{ $item->kasir->id }}">
                          <button type="submit" class="sm-icon-btn danger" title="Hapus semua riwayat stok kasir ini"><i class="bi bi-trash3-fill"></i></button>
                        </form>
                      </div>
                    @else
                      <span class="sm-muted">—</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="sm-empty"><i class="bi bi-inbox"></i>Belum ada saus/bubuk tabur. Tambahkan dulu lewat halaman Master Topping &amp; Bumbu.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="sm-foot">
          <span>Menampilkan {{ $dataStokTopping->count() }} dari {{ $dataStokTopping->total() }} data stok</span>
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
                <th>Kasir</th>
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
                  <td>{{ $riwayat->kasir->name ?? '—' }}</td>
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
                <tr><td colspan="7" class="sm-empty"><i class="bi bi-clock-history"></i>Belum ada riwayat stok topping/bumbu.</td></tr>
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
          'labelTotal' => 'Total Jatah Kasir',
          'satuanTotal' => 'bahan × kasir',
          'labelAman' => 'Masih Ada',
          'labelMenipis' => 'Minta Tambahan',
          'iconMenipis' => 'bi-hand-index-thumb-fill',
          'satuanMenipis' => 'permintaan kasir',
          'satuanHabis' => 'bahan × kasir',
      ])

      <div class="sm-head">
        <div class="sm-head-title">
          <span class="sm-head-ico"><i class="bi bi-box-seam"></i></span>
          <div><h3>Jatah Bahan &amp; Kemasan per Kasir</h3><p>Liter (minyak), tabung (gas), dan pcs (kemasan), diberikan oleh admin</p></div>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <a href="{{ route('admin.bahan-operasional.index') }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-gear"></i><span>Atur Master Bahan</span>
          </a>
          <form action="{{ route('admin.stok.bahan.jatah-harian') }}" method="POST"
            data-confirm="Bagikan jatah harian KEMASAN ke semua kasir aktif?"
            data-confirm-text="Tiap kasir aktif dapat kemasan sesuai angka di Master Bahan. Kasir yang hari ini sudah menerima kemasan itu dilewati."
            data-confirm-yes="Ya, Bagikan">
            @csrf
            <input type="hidden" name="jenis" value="kemasan">
            <button type="submit" class="btn-custom btn-custom-outline-secondary">
              <i class="bi bi-calendar-check"></i><span>Bagikan Jatah Kemasan</span>
            </button>
          </form>
          <form action="{{ route('admin.stok.bahan.jatah-harian') }}" method="POST"
            data-confirm="Bagikan jatah harian minyak &amp; gas ke semua kasir aktif?"
            data-confirm-text="Tiap kasir aktif dapat jatah sesuai angka di Master Bahan. Kasir yang hari ini sudah menerima bahan itu dilewati."
            data-confirm-yes="Ya, Bagikan">
            @csrf
            <input type="hidden" name="jenis" value="habis_pakai">
            <button type="submit" class="btn-custom btn-custom-outline-secondary">
              <i class="bi bi-calendar-check"></i><span>Bagikan Jatah Minyak &amp; Gas</span>
            </button>
          </form>
          <button type="button" class="btn-custom btn-custom-primary" data-bs-toggle="modal" data-bs-target="#modal-tambah-stok-bahan">
            <i class="bi bi-plus-lg"></i><span>Beri Jatah</span>
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
                <th>Kasir</th>
                <th>Sisa Stok</th>
                <th>Status</th>
                <th>Diberi Terakhir</th>
                <th class="sm-col-act">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($dataStokBahan as $index => $item)
                @php
                  $b = $item->bahan;
                  $k = $item->kasir;
                  $tone = $statusTone[$item->status];
                @endphp
                <tr>
                  <td class="sm-col-no">{{ $index + 1 }}</td>
                  <td>
                    <div class="sm-item">
                      @include('partials.thumb', ['src' => $b->gambar_url, 'icon' => $b->ikon, 'size' => 46, 'alt' => $b->nama])
                      <div>
                        <b>{{ $b->nama }}</b>
                        <span>
                          @if ($b->is_kemasan) <i class="sm-tag auto">Kemasan</i> {{ $b->aturan_pakai }} ·
                          @endif
                          @if ($b->jatah_harian > 0) Jatah harian {{ $b->labelJumlah($b->jatah_harian) }} @else Tanpa jatah tetap @endif
                          @unless ($b->is_active) · <i class="sm-tag off">Nonaktif</i> @endunless
                        </span>
                      </div>
                    </div>
                  </td>
                  <td><span class="sm-user"><i class="sm-av">{{ strtoupper(mb_substr($k->name, 0, 1)) }}</i>{{ $k->name }}</span></td>
                  <td>
                    @if ($item->status === 'belum_dikelola')
                      <span class="sm-muted">Belum diberi</span>
                    @else
                      <div class="sm-stock-num sm-stock-ringan">{{ number_format($item->stok, 0, ',', '.') }}<small>{{ $b->satuan }}</small></div>
                    @endif
                  </td>
                  <td>
                    <span class="sm-pill {{ $tone }}">{{ \App\Models\BahanOperasional::labelStatus($item->status) }}</span>
                    @if ($item->permintaan)
                      <span class="sm-tag auto" title="Kasir minta tambahan sejak {{ $item->permintaan->created_at->translatedFormat('d M H:i') }}">Minta tambahan</span>
                    @endif
                    @if ($item->menunggu_terima->isNotEmpty())
                      @php $kirimanBelum = $item->menunggu_terima->contains(fn ($p) => $p->belum_pada !== null); @endphp
                      <span class="sm-tag auto" title="{{ $b->labelJumlah((int) $item->menunggu_terima->sum('jumlah')) }} sudah dikirim, stok kasir terisi setelah kasir konfirmasi Sudah">
                        {{ $kirimanBelum ? 'Kasir: belum menerima' : 'Menunggu konfirmasi kasir' }}
                      </span>
                    @endif
                  </td>
                  <td>
                    @if ($item->terakhir_masuk)
                      <div class="sm-last"><b>{{ $item->terakhir_masuk->tanggal->translatedFormat('d M Y') }}</b><span class="sm-in">+{{ $b->labelJumlah($item->terakhir_masuk->jumlah) }}</span></div>
                    @else
                      <span class="sm-muted">Belum ada</span>
                    @endif
                  </td>
                  <td class="sm-col-act">
                    <div class="sm-actions">
                      @if ($b->is_active && ! $b->is_kemasan)
                        <button type="button" class="btn-custom btn-custom-sm btn-custom-primary" title="Beri jatah ke {{ $k->name }}"
                          data-bs-toggle="modal" data-bs-target="#modal-tambah-stok-bahan"
                          data-beri-bahan="{{ $b->id }}" data-beri-kasir="{{ $k->id }}">
                          <i class="bi bi-plus-lg"></i><span>Beri</span>
                        </button>
                      @endif
                      <button type="button" class="sm-icon-btn" title="Edit stok {{ $k->name }}" data-bs-toggle="modal" data-bs-target="#modal-koreksi-bahan-{{ $b->id }}-{{ $k->id }}"><i class="bi bi-pencil-fill"></i></button>
                      <form action="{{ route('admin.stok.bahan.hapus-riwayat', $b) }}" method="POST"
                        data-confirm="Hapus semua riwayat &quot;{{ $b->nama }}&quot; milik {{ $k->name }}?"
                        data-confirm-text="Stoknya balik ke 0 dan tidak bisa dibatalkan."
                        data-confirm-yes="Ya, Hapus">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="kasir_id" value="{{ $k->id }}">
                        <button type="submit" class="sm-icon-btn danger" title="Hapus semua riwayat kasir ini"><i class="bi bi-trash3-fill"></i></button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="sm-empty"><i class="bi bi-fire"></i>Belum ada bahan operasional atau kasir aktif. Tambahkan dulu lewat Master → Bahan Operasional dan Data Kasir.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="sm-head sm-head-sub">
        <div class="sm-head-title">
          <span class="sm-head-ico"><i class="bi bi-arrow-left-right"></i></span>
          <div><h3>Riwayat Pemberian &amp; Laporan Habis</h3><p>Jatah dari admin (masuk) dan laporan habis dari kasir (keluar)</p></div>
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
                <th>Kasir</th>
                <th>Jumlah</th>
                <th>Keterangan</th>
                <th>Dicatat Oleh</th>
                <th class="sm-col-act">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($riwayatBahan as $riwayat)
                @php $masuk = $riwayat->jenis === 'masuk'; @endphp
                <tr>
                  <td>
                    <div class="sm-date"><b>{{ $riwayat->tanggal->translatedFormat('d') }}</b><span>{{ $riwayat->tanggal->translatedFormat('M Y') }}</span></div>
                  </td>
                  <td>
                    <div class="sm-item sm-item-sm">
                      @include('partials.thumb', ['src' => $riwayat->bahan?->gambar_url, 'icon' => $riwayat->bahan?->ikon ?? 'bi-fire', 'size' => 36, 'alt' => $riwayat->bahan->nama ?? ''])
                      <div><b>{{ $riwayat->bahan->nama ?? '-' }}</b></div>
                    </div>
                  </td>
                  <td>{{ $riwayat->kasir->name ?? '—' }}</td>
                  <td>
                    <div class="sm-flow {{ $masuk ? 'in' : 'out' }}">
                      <span class="sm-flow-ico"><i class="bi {{ $masuk ? 'bi-arrow-down-left' : 'bi-arrow-up-right' }}"></i></span>
                      <div>
                        <b>{{ $masuk ? '+' : '−' }}{{ number_format($riwayat->jumlah, 0, ',', '.') }} {{ $riwayat->bahan->satuan ?? '' }}</b>
                        <small>{{ $masuk ? 'Masuk' : 'Keluar' }}</small>
                      </div>
                    </div>
                  </td>
                  <td class="sm-note">{{ $riwayat->keterangan ?: '-' }}</td>
                  <td><span class="sm-user"><i class="sm-av">{{ strtoupper(mb_substr($riwayat->user->name ?? '?', 0, 1)) }}</i>{{ $riwayat->user->name ?? '-' }}</span></td>
                  <td class="sm-col-act">
                    @if ($masuk)
                      <div class="sm-actions">
                        <form action="{{ route('admin.stok.bahan.hapus-entri-masuk', $riwayat) }}" method="POST"
                          data-confirm="Hapus entri pemberian ini?"
                          data-confirm-text="Sisa stok kasirnya ikut berkurang sesuai jumlah di atas."
                          data-confirm-yes="Ya, Hapus">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="sm-icon-btn danger" title="Hapus entri ini"><i class="bi bi-trash3-fill"></i></button>
                        </form>
                      </div>
                    @else
                      <span class="sm-tag {{ $riwayat->transaksi_id ? 'auto' : 'off' }}">{{ $riwayat->transaksi_id ? 'Otomatis' : (str_contains((string) $riwayat->keterangan, 'kasir') ? 'Lapor habis' : 'Koreksi') }}</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="sm-empty"><i class="bi bi-clock-history"></i>Belum ada riwayat bahan operasional.</td></tr>
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
              <label class="form-label-custom">Untuk Kasir</label>
              <select name="kasir_id" class="form-select-custom w-100 {{ $errors->has('kasir_id') ? 'is-invalid-custom' : '' }}">
                <option value="">Pilih kasir...</option>
                @foreach ($kasirAktif as $k)
                  <option value="{{ $k->id }}" {{ (string) old('kasir_id') === (string) $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                @endforeach
              </select>
              @error('kasir_id')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
              <span class="form-feedback-custom">Stok ini hanya bisa dipakai oleh kasir yang dipilih.</span>
            </div>

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

  @foreach ($dataStok->getCollection()->filter(fn ($i) => $i->kasir) as $item)
    <!-- ===================== MODAL: KOREKSI STOK ===================== -->
    <div class="modal fade" id="modal-koreksi-{{ $item->menu->id }}-{{ $item->kasir->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form action="{{ route('admin.stok.koreksi', $item->menu) }}" method="POST">
            @csrf
            @method('PATCH')
            <input type="hidden" name="_action" value="koreksi-{{ $item->menu->id }}-{{ $item->kasir->id }}">
            <input type="hidden" name="kasir_id" value="{{ $item->kasir->id }}">

            <div class="modal-header modal-header-brand">
              @include('partials.thumb', ['src' => $item->menu->gambar ? asset('storage/' . $item->menu->gambar) : null, 'icon' => 'bi-box-seam-fill', 'size' => 48, 'alt' => $item->menu->nama])
              <div>
                <h5 class="modal-title mb-0">Edit Stok — {{ $item->menu->nama }}</h5>
                <small class="modal-subtitle">Kasir {{ $item->kasir->name }} · stok sekarang {{ number_format($item->stok, 0, ',', '.') }}</small>
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

              <div class="mb-3">
                <label class="form-label-custom">Jatah Harian per Kasir</label>
                <input type="number" name="jatah_harian" min="0" max="10000"
                  value="{{ old('_action') === 'koreksi-' . $item->menu->id . '-' . $item->kasir->id ? old('jatah_harian') : (int) $item->menu->jatah_harian }}"
                  class="form-control-custom {{ old('_action') === 'koreksi-' . $item->menu->id . '-' . $item->kasir->id && $errors->has('jatah_harian') ? 'is-invalid-custom' : '' }}">
                <span class="form-feedback-custom">Dipakai tombol "Bagikan Jatah Harian" (porsi per kasir per hari). Isi 0 kalau tidak ada jatah tetap. Berlaku untuk menu ini di semua kasir.</span>
                @if (old('_action') === 'koreksi-' . $item->menu->id . '-' . $item->kasir->id)
                  @error('jatah_harian')
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
              <label class="form-label-custom">Untuk Kasir</label>
              <select name="kasir_id" class="form-select-custom w-100 {{ $errors->has('kasir_id') ? 'is-invalid-custom' : '' }}">
                <option value="">Pilih kasir...</option>
                @foreach ($kasirAktif as $k)
                  <option value="{{ $k->id }}" {{ (string) old('kasir_id') === (string) $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                @endforeach
              </select>
              @error('kasir_id')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
              <span class="form-feedback-custom">Stok ini hanya bisa dipakai oleh kasir yang dipilih.</span>
            </div>

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

  @foreach ($dataStokTopping->getCollection()->filter(fn ($i) => $i->kasir) as $item)
    <!-- ===================== MODAL: KOREKSI STOK TOPPING ===================== -->
    <div class="modal fade" id="modal-koreksi-topping-{{ $item->topping->id }}-{{ $item->kasir->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form action="{{ route('admin.stok.topping.koreksi', $item->topping) }}" method="POST">
            @csrf
            @method('PATCH')
            <input type="hidden" name="_action" value="koreksi-topping-{{ $item->topping->id }}-{{ $item->kasir->id }}">
            <input type="hidden" name="kasir_id" value="{{ $item->kasir->id }}">

            <div class="modal-header modal-header-brand">
              @include('partials.thumb', ['src' => $item->topping->gambar_url, 'icon' => 'bi-droplet-fill', 'size' => 48, 'alt' => $item->topping->nama])
              <div>
                <h5 class="modal-title mb-0">Edit Stok — {{ $item->topping->nama }}</h5>
                <small class="modal-subtitle">{{ $item->topping->jenis_label }} · Kasir {{ $item->kasir->name }} · stok sekarang {{ number_format($item->stok, 0, ',', '.') }}</small>
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

              <div class="mb-3">
                <label class="form-label-custom">Jatah Harian per Kasir</label>
                <input type="number" name="jatah_harian" min="0" max="10000"
                  value="{{ old('_action') === 'koreksi-topping-' . $item->topping->id . '-' . $item->kasir->id ? old('jatah_harian') : (int) $item->topping->jatah_harian }}"
                  class="form-control-custom {{ old('_action') === 'koreksi-topping-' . $item->topping->id . '-' . $item->kasir->id && $errors->has('jatah_harian') ? 'is-invalid-custom' : '' }}">
                <span class="form-feedback-custom">Dipakai tombol "Bagikan Jatah Harian" (stok per kasir per hari). Isi 0 kalau tidak ada jatah tetap. Berlaku untuk item ini di semua kasir.</span>
                @if (old('_action') === 'koreksi-topping-' . $item->topping->id . '-' . $item->kasir->id)
                  @error('jatah_harian')
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

  <!-- ===================== MODAL: BERI JATAH BAHAN OPERASIONAL ===================== -->
  <div class="modal fade" id="modal-tambah-stok-bahan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="{{ route('admin.stok.bahan.store') }}" method="POST">
          @csrf
          <input type="hidden" name="_action" value="tambah-stok-bahan">

          <div class="modal-header modal-header-brand">
            <div class="modal-header-icon"><i class="bi bi-box-seam"></i></div>
            <div>
              <h5 class="modal-title mb-0">Beri Jatah Bahan Operasional</h5>
              <small class="modal-subtitle">Minyak (liter) atau gas (tabung) untuk satu kasir.</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label-custom">Untuk Kasir</label>
              <select name="kasir_id" class="form-select-custom w-100 {{ $errors->has('kasir_id') ? 'is-invalid-custom' : '' }}">
                <option value="">Pilih kasir...</option>
                @foreach ($kasirAktif as $k)
                  <option value="{{ $k->id }}" {{ (string) old('kasir_id') === (string) $k->id ? 'selected' : '' }}>{{ $k->name }}</option>
                @endforeach
              </select>
              @error('kasir_id')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label-custom">Bahan</label>
              <select name="bahan_operasional_id" class="form-select-custom w-100 {{ $errors->has('bahan_operasional_id') ? 'is-invalid-custom' : '' }}"
                data-preview-target="#preview-stok-bahan">
                <option value="">Pilih bahan...</option>
                @foreach ($bahanAktif->reject(fn ($bhn) => $bhn->is_kemasan) as $bahan)
                  <option value="{{ $bahan->id }}"
                    data-foto="{{ $bahan->gambar_url }}"
                    data-sub="{{ $bahan->label_jenis }} · Satuan: {{ $bahan->satuan }}{{ $bahan->jatah_harian > 0 ? ' · jatah harian ' . $bahan->jatah_harian : '' }}"
                    data-jatah="{{ $bahan->jatah_harian }}"
                    data-satuan="{{ $bahan->satuan }}"
                    data-maks="{{ $bahan->maksSekaliMasuk() }}"
                    {{ (string) old('bahan_operasional_id') === (string) $bahan->id ? 'selected' : '' }}>
                    {{ $bahan->nama }} ({{ $bahan->satuan }}){{ $bahan->is_kemasan ? ' · Kemasan' . ($bahan->ukuran ? ' ' . $bahan->ukuran->nama : '') : '' }}
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
              <label class="form-label-custom">Jumlah <span id="bahan-satuan" class="bo-opsional"></span></label>
              <div class="qty-wrap" data-bahan-qty>
                <div class="qty-step">
                  <button type="button" data-bahan-ubah="-1" aria-label="Kurangi"><i class="bi bi-dash-lg"></i></button>
                  <input type="number" name="jumlah" min="1" max="{{ \App\Models\BahanOperasional::MAKS_SEKALI_MASUK }}" value="{{ old('jumlah', 1) }}" placeholder="1"
                    class="{{ $errors->has('jumlah') ? 'is-invalid-custom' : '' }}">
                  <button type="button" data-bahan-ubah="1" aria-label="Tambah"><i class="bi bi-plus-lg"></i></button>
                </div>
              </div>
              <span class="form-feedback-custom">Terisi otomatis sesuai jatah harian bahan yang dipilih, tapi bisa kamu ubah.</span>
              <span class="form-feedback-custom">Khusus minyak &amp; gas: stok kasir baru terisi setelah kasir konfirmasi &quot;Sudah&quot; menerimanya di Cek Stok.</span>
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
                class="form-control-custom" placeholder="contoh: Jatah tambahan karena ramai">
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
    @php
      $mb = $item->bahan;
      $mk = $item->kasir;
      $idKoreksi = 'koreksi-bahan-' . $mb->id . '-' . $mk->id;
      $pakaiOldKoreksi = old('_action') === $idKoreksi;
    @endphp
    <!-- ===================== MODAL: KOREKSI STOK BAHAN ===================== -->
    <div class="modal fade" id="modal-{{ $idKoreksi }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form action="{{ route('admin.stok.bahan.koreksi', $mb) }}" method="POST">
            @csrf
            @method('PATCH')
            <input type="hidden" name="_action" value="{{ $idKoreksi }}">
            <input type="hidden" name="kasir_id" value="{{ $mk->id }}">

            <div class="modal-header modal-header-brand">
              @include('partials.thumb', ['src' => $mb->gambar_url, 'icon' => $mb->ikon, 'size' => 48, 'alt' => $mb->nama])
              <div>
                <h5 class="modal-title mb-0">Edit Stok — {{ $mb->nama }}</h5>
                <small class="modal-subtitle">Milik {{ $mk->name }} · sisa sekarang {{ $mb->labelJumlah($item->stok) }}</small>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <p class="table-user-sub">
                Isi sisa yang benar di bawah ini, selisihnya otomatis tercatat di riwayat.
              </p>

              <div class="mb-3">
                <label class="form-label-custom">Sisa Stok Baru ({{ $mb->satuan }})</label>
                <input type="number" name="stok_baru" min="0"
                  value="{{ $pakaiOldKoreksi ? old('stok_baru') : $item->stok }}"
                  class="form-control-custom {{ $pakaiOldKoreksi && $errors->has('stok_baru') ? 'is-invalid-custom' : '' }}">
                @if ($pakaiOldKoreksi)
                  @error('stok_baru')
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
    // Modal Beri Jatah Bahan: jumlah otomatis terisi jatah harian bahan yang dipilih (bisa diubah),
    // dan tombol "Beri" di tabel langsung memilihkan kasir + bahan-nya.
    (function () {
      var modal = document.getElementById('modal-tambah-stok-bahan');
      if (!modal) return;
      var form = modal.querySelector('form');
      var wrap = form.querySelector('[data-bahan-qty]');
      var input = wrap.querySelector('input[name="jumlah"]');
      var selectBahan = form.querySelector('select[name="bahan_operasional_id"]');
      var selectKasir = form.querySelector('select[name="kasir_id"]');
      var infoSatuan = form.querySelector('#bahan-satuan');
      var maks = parseInt(input.getAttribute('max'), 10) || 50;

      function set(n) {
        n = Math.min(maks, Math.max(1, parseInt(n, 10) || 1));
        input.value = n;
      }

      // Batas sekali input beda per jenis (liter/tabung kecil, kemasan dalam pcs lebih besar).
      function aturMaks() {
        var opt = selectBahan.options[selectBahan.selectedIndex];
        var m = opt ? parseInt(opt.getAttribute('data-maks'), 10) : 0;
        maks = m > 0 ? m : 50;
        input.setAttribute('max', maks);
      }

      function labelSatuan() {
        var opt = selectBahan.options[selectBahan.selectedIndex];
        infoSatuan.textContent = opt && opt.value ? '(' + opt.getAttribute('data-satuan') + ')' : '';
      }

      function isiJatah() {
        var opt = selectBahan.options[selectBahan.selectedIndex];
        var jatah = opt ? parseInt(opt.getAttribute('data-jatah'), 10) : 0;
        aturMaks();
        if (jatah > 0) set(jatah);
        labelSatuan();
      }

      wrap.querySelectorAll('[data-bahan-ubah]').forEach(function (btn) {
        btn.addEventListener('click', function () { set((parseInt(input.value, 10) || 0) + parseInt(btn.dataset.bahanUbah, 10)); });
      });
      input.addEventListener('input', function () { if (input.value !== '') set(input.value); });
      selectBahan.addEventListener('change', isiJatah);
      aturMaks();
      labelSatuan();

      // Tombol "Beri" di baris tabel: pilih kasir & bahan otomatis.
      document.querySelectorAll('[data-beri-bahan]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          selectKasir.value = btn.getAttribute('data-beri-kasir');
          selectBahan.value = btn.getAttribute('data-beri-bahan');
          selectBahan.dispatchEvent(new Event('change'));
        });
      });
    })();

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
      // ?tab= (dari klik notifikasi) menang atas tab terakhir yang tersimpan.
      const tabUrl = new URLSearchParams(window.location.search).get('tab');
      const tabTersimpan = tabUrl || localStorage.getItem('tabStokAktif');
      if (tabTersimpan && tabTersimpan !== 'data-stok') {
        tampilkanTabStok(tabTersimpan);
      } else if (tabUrl === 'data-stok') {
        tampilkanTabStok('data-stok');
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
