@extends('layouts.admin')

@section('title', 'Dashboard - ' . config('app.name', 'Cimol Bojot AA'))

@section('content')

  @php
    /*
      Semua variabel di bawah ini pakai pola: $variabel ?? nilai_demo
      Artinya: kalau controller sudah mengirim data asli (mis. dari tabel
      transaksis, stoks, menus), Blade otomatis pakai data asli itu.
      Kalau belum, tampil data contoh supaya tampilan tetap bisa dicek.
      Silakan hapus bagian "?? ..." satu per satu begitu controller-nya jadi.
    */
    $omzetHariIni     = $omzetHariIni ?? 1248000;
    $omzetPersen      = $omzetPersen ?? 12;
    $jumlahTransaksi  = $jumlahTransaksi ?? 48;
    $transaksiPersen  = $transaksiPersen ?? 8;
    $totalMenu        = $totalMenu ?? 12;
    $stokMenipisCount = $stokMenipisCount ?? 3;

    $stokMenipis = $stokMenipis ?? collect([
        (object) ['nama' => 'Cimol Original',   'stok' => 4, 'status' => 'menipis'],
        (object) ['nama' => 'Cimol Mozzarella', 'stok' => 2, 'status' => 'menipis'],
        (object) ['nama' => 'Cimol BBQ',        'stok' => 0, 'status' => 'habis'],
    ]);

    $transaksiTerbaru = $transaksiTerbaru ?? collect([
        (object) ['no' => 'TRX-00048', 'tanggal' => '12 Jul 2025 14:32', 'kasir' => 'Siti',  'total' => 86000,  'metode' => 'Cash',     'status' => 'Selesai'],
        (object) ['no' => 'TRX-00047', 'tanggal' => '12 Jul 2025 14:17', 'kasir' => 'Rina',  'total' => 124000, 'metode' => 'QRIS',     'status' => 'Selesai'],
        (object) ['no' => 'TRX-00046', 'tanggal' => '12 Jul 2025 13:52', 'kasir' => 'Dewi',  'total' => 72000,  'metode' => 'Cash',     'status' => 'Selesai'],
        (object) ['no' => 'TRX-00045', 'tanggal' => '12 Jul 2025 13:20', 'kasir' => 'Siti',  'total' => 98000,  'metode' => 'PayLater', 'status' => 'Selesai'],
        (object) ['no' => 'TRX-00044', 'tanggal' => '12 Jul 2025 12:58', 'kasir' => 'Rina',  'total' => 65000,  'metode' => 'Cash',     'status' => 'Selesai'],
    ]);

    $grafikTanggal   = $grafikTanggal ?? ['6 Jul', '7 Jul', '8 Jul', '9 Jul', '10 Jul', '11 Jul', '12 Jul'];
    $grafikOmzet     = $grafikOmzet ?? [980000, 1050000, 870000, 760000, 1120000, 1490000, 1750000];
    $grafikTransaksi = $grafikTransaksi ?? [38, 42, 35, 30, 45, 58, 63];
  @endphp

  <!-- START: Page Header -->
  <div class="page-header">
    <div>
      <h1 class="page-title">Dashboard</h1>
      <p class="page-subtitle">Selamat datang di aplikasi {{ config('app.name', 'Cimol Bojot AA') }}</p>
    </div>
    <button class="btn-date-picker" type="button" id="date-picker-trigger">
      <i class="bi bi-calendar4-event"></i>
      <span id="selected-date-range">{{ now()->format('d M Y') }} - {{ now()->format('d M Y') }}</span>
      <i class="bi bi-chevron-down ms-1"></i>
    </button>
  </div>
  <!-- END: Page Header -->

  <div class="row g-4">

    <!-- ROW 1: Kartu Statistik -->
    <div class="col-12">
      <div class="row g-4">

        <!-- Omzet Hari Ini (kartu gelap) -->
        <div class="col-md-6 col-xl-3">
          <div class="card alert-green-card h-100 mb-0">
            <div class="position-relative z-index-2 d-flex flex-column h-100">
              <span class="alert-green-badge">Update</span>
              <div class="alert-green-date">{{ now()->translatedFormat('d F Y') }}</div>
              <div class="alert-green-text">Omzet Hari Ini</div>
              <div class="stat-value">Rp {{ number_format($omzetHariIni, 0, ',', '.') }}</div>
              <div class="trend-badge trend-up mb-3">
                <i class="bi bi-arrow-up-right"></i>
                <span>+{{ $omzetPersen }}% dari kemarin</span>
              </div>
              <a href="{{ route('admin.laporan.index', ['periode' => 'harian']) }}" class="btn-custom btn-custom-light btn-custom-sm rounded-pill mt-auto align-self-start">
                <span>Lihat Detail</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
            <svg class="alert-green-bg-shape" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
              <g transform="translate(50,50)">
                <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#FFD166" />
                <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#FFD166" transform="rotate(60)" />
                <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#FFD166" transform="rotate(120)" />
              </g>
            </svg>
          </div>
        </div>

        <!-- Jumlah Transaksi -->
        <div class="col-md-6 col-xl-3">
          <div class="card card-stat h-100 mb-0">
            <div class="stat-icon-row">
              <span class="stat-icon-badge"><i class="bi bi-receipt"></i></span>
              <span class="stat-label">Jumlah Transaksi</span>
            </div>
            <div class="stat-value">{{ $jumlahTransaksi }}</div>
            <div class="trend-badge trend-up">
              <i class="bi bi-arrow-up-right"></i>
              <span>+{{ $transaksiPersen }}% dari kemarin</span>
            </div>
          </div>
        </div>

        <!-- Total Menu -->
        <div class="col-md-6 col-xl-3">
          <div class="card card-stat h-100 mb-0">
            <div class="stat-icon-row">
              <span class="stat-icon-badge"><i class="bi bi-egg-fried"></i></span>
              <span class="stat-label">Total Menu</span>
            </div>
            <div class="stat-value">{{ $totalMenu }}</div>
            <p class="stat-note">varian menu tersedia</p>
          </div>
        </div>

        <!-- Stok Menipis -->
        <div class="col-md-6 col-xl-3">
          <div class="card card-stat h-100 mb-0">
            <div class="stat-icon-row">
              <span class="stat-icon-badge"><i class="bi bi-exclamation-triangle-fill"></i></span>
              <span class="stat-label">Stok Menipis</span>
            </div>
            <div class="stat-value">{{ $stokMenipisCount }}</div>
            <p class="stat-note">menu hampir habis</p>
          </div>
        </div>

      </div>
    </div>
    <!-- END: ROW 1 -->

    <!-- ROW 2: Grafik Penjualan + Stok Menipis -->
    <div class="col-xl-8">
      <div class="card h-100 mb-0">
        <div class="card-header mb-2">
          <h2 class="card-title">Grafik Penjualan 7 Hari Terakhir</h2>
          <div class="d-flex gap-3 align-items-center">
            <div class="chart-legend-item">
              <span class="legend-dot bg-forest-medium"></span>
              <span class="chart-legend-label">Omzet</span>
            </div>
            <div class="chart-legend-item">
              <span class="legend-dot bg-lime-accent"></span>
              <span class="chart-legend-label">Jumlah Transaksi</span>
            </div>
          </div>
        </div>
        <div id="revenue-chart"
          data-labels='@json($grafikTanggal)'
          data-omzet='@json($grafikOmzet)'
          data-transaksi='@json($grafikTransaksi)'></div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card h-100 mb-0">
        <div class="card-header">
          <h2 class="card-title">Stok Menipis</h2>
          <a href="{{ route('admin.stok.index') }}" class="link-inline">Lihat Semua <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="custom-list">
          @forelse ($stokMenipis as $item)
            <div class="custom-list-item">
              <div class="item-left">
                <span class="item-icon-wrapper"><i class="bi bi-basket2-fill"></i></span>
                <div class="item-info">
                  <p class="item-name mb-0">{{ $item->nama }}</p>
                  <p class="item-sub mb-0">Stok: {{ $item->stok }}</p>
                </div>
              </div>
              <div class="item-status-wrapper">
                <span class="stock-badge {{ $item->status }}">
                  {{ $item->status === 'habis' ? 'Habis' : 'Menipis' }}
                </span>
              </div>
            </div>
          @empty
            <p class="item-sub mb-0">Tidak ada menu dengan stok menipis. 🎉</p>
          @endforelse
        </div>
      </div>
    </div>
    <!-- END: ROW 2 -->

    <!-- ROW 3: Transaksi Terbaru + Promo Banner -->
    <div class="col-xl-8">
      <div class="table-card-custom mb-0">
        <div class="table-header-control">
          <h2 class="card-title mb-0">Transaksi Terbaru</h2>
          <a href="{{ route('admin.riwayat-transaksi.index') }}" class="link-inline">Lihat Semua <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="table-responsive">
          <table class="table-custom">
            <thead>
              <tr>
                <th>No. Transaksi</th>
                <th>Tanggal &amp; Waktu</th>
                <th>Kasir</th>
                <th>Total</th>
                <th>Metode Bayar</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($transaksiTerbaru as $trx)
                <tr>
                  <td class="table-order-id">{{ $trx->no }}</td>
                  <td>{{ $trx->tanggal }}</td>
                  <td>{{ $trx->kasir }}</td>
                  <td class="table-amount">Rp {{ number_format($trx->total, 0, ',', '.') }}</td>
                  <td>{{ $trx->metode }}</td>
                  <td><span class="badge-table success">{{ $trx->status }}</span></td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center py-4" style="color: var(--text-muted-green);">
                    Belum ada transaksi.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="promo-banner-card h-100 mb-0">
        <svg class="promo-banner-bg-shape" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
          <g transform="translate(50,50)">
            <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#FFD166" />
            <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#FFD166" transform="rotate(60)" />
            <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#FFD166" transform="rotate(120)" />
          </g>
        </svg>
        <h3 class="promo-title">Cemilan Enak Bikin Happy!</h3>
        <p class="promo-desc">Kelola penjualan cimol kamu dengan mudah, cepat, dan penuh semangat.</p>
        
      </div>
    </div>
    <!-- END: ROW 3 -->

  </div>

@endsection

@push('scripts')
  <script src="{{ asset('assets_dashboard/js/dashboard.js') }}"></script>
@endpush