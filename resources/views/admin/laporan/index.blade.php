@extends('layouts.admin')

@section('title', 'Laporan Penjualan - ' . config('app.name', 'Cimol Bojot AA'))

@push('styles')
  <style>
    .periode-tabs {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .periode-tab {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.5rem 1rem;
      border-radius: 50rem;
      font-size: 0.82rem;
      font-weight: 700;
      text-decoration: none;
      color: var(--text-muted-green);
      background-color: var(--bs-body-bg);
      border: 1px solid var(--border-light);
      transition: all 0.15s ease;
    }

    .periode-tab:hover { color: var(--brand-forest-medium); }

    .periode-tab.active {
      color: #fff;
      background-color: var(--brand-forest-medium);
      border-color: var(--brand-forest-medium);
    }

    .periode-aktif-text {
      font-size: 0.8rem;
      color: var(--text-muted-green);
    }

    .btn-export-excel {
      background-color: #1E7145;
      color: #fff;
      border: 1px solid #1E7145;
    }

    .btn-export-excel:hover { background-color: #175a37; color: #fff; }

    .btn-export-excel:disabled { opacity: 0.65; cursor: wait; }

    .varian-list-item { margin-bottom: 1.1rem; }
    .varian-list-item:last-child { margin-bottom: 0; }

    .table-ringkasan-menu { width: 100%; font-size: 0.85rem; }
    .table-ringkasan-menu th {
      text-align: left; font-size: 0.72rem; text-transform: uppercase;
      color: var(--text-muted-green); font-weight: 700;
      border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;
    }
    .table-ringkasan-menu td { padding: 0.55rem 0; border-bottom: 1px dashed var(--border-light); }
    .table-ringkasan-menu tr:last-child td { border-bottom: none; }
    .table-ringkasan-menu td.text-end, .table-ringkasan-menu th.text-end { text-align: right; }

    /* .chart-legends-container aslinya dirancang utk 2 item sejajar (lihat
       Grafik Penjualan di Dashboard) — di sini bisa 3-4 item, jadi boleh
       turun baris supaya tidak terpotong. */
    #chart-ukuran + .chart-legends-container,
    #chart-metode + .chart-legends-container {
      justify-content: flex-start;
      flex-wrap: wrap;
      row-gap: 0.5rem;
    }
  </style>
@endpush

@section('content')

  @php
    $trend = function (int $persen) {
      return [
        'class' => $persen >= 0 ? 'trend-up' : 'trend-down',
        'icon' => $persen >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right',
        'text' => ($persen >= 0 ? '+' : '') . $persen . '% dari periode sebelumnya',
      ];
    };

    $trendOmzet = $trend($persenOmzet);
    $trendTransaksi = $trend($persenTransaksi);
    $trendRataRata = $trend($persenRataRata);

    $paletteVarian = ['bg-forest-medium', 'bg-lime-accent', 'bg-brand-orange'];

    $tabQuery = fn (string $p) => array_merge(
        request()->only(['menu_id', 'metode_bayar']),
        ['periode' => $p],
        $p === 'custom' ? ['tanggal_mulai' => $tanggalMulai->toDateString(), 'tanggal_akhir' => $tanggalAkhir->toDateString()] : []
    );
  @endphp

  <!-- START: Page Header -->
  <div class="page-header">
    <div>
      <h1 class="page-title">Laporan Penjualan</h1>
      <p class="page-subtitle">Rekap data penjualan {{ config('app.name', 'Cimol Bojot AA') }}</p>
    </div>
  </div>
  <!-- END: Page Header -->

  <!-- START: Filter & Export Card -->
  <div class="card mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
      <div class="periode-tabs">
        <a href="{{ route('admin.laporan.index', $tabQuery('harian')) }}" class="periode-tab {{ $periode === 'harian' ? 'active' : '' }}">
          <i class="bi bi-calendar-day"></i> Harian
        </a>
        <a href="{{ route('admin.laporan.index', $tabQuery('mingguan')) }}" class="periode-tab {{ $periode === 'mingguan' ? 'active' : '' }}">
          <i class="bi bi-calendar-week"></i> Mingguan
        </a>
        <a href="{{ route('admin.laporan.index', $tabQuery('bulanan')) }}" class="periode-tab {{ $periode === 'bulanan' ? 'active' : '' }}">
          <i class="bi bi-calendar-month"></i> Bulanan
        </a>
        <a href="{{ route('admin.laporan.index', $tabQuery('custom')) }}" class="periode-tab {{ $periode === 'custom' ? 'active' : '' }}">
          <i class="bi bi-calendar-range"></i> Custom
        </a>
      </div>

      <div class="d-flex gap-2">
        <button type="button" class="btn-custom btn-export-excel" id="btn-export-excel">
          <i class="bi bi-file-earmark-excel-fill"></i>
          <span>Export Excel</span>
        </button>
        <button type="button" class="btn-custom btn-custom-danger" id="btn-export-pdf">
          <i class="bi bi-file-earmark-pdf-fill"></i>
          <span>Export PDF</span>
        </button>
      </div>
    </div>

    <p class="periode-aktif-text mb-3">
      <i class="bi bi-info-circle"></i>
      Periode aktif: <strong>{{ $tanggalMulai->translatedFormat('d M Y') }} - {{ $tanggalAkhir->translatedFormat('d M Y') }}</strong>
    </p>

    <form action="{{ route('admin.laporan.index') }}" method="GET" class="row g-3 align-items-end">
      <input type="hidden" name="periode" value="{{ $periode }}">

      @if ($periode === 'custom')
        <div class="col-6 col-lg-2">
          <label class="form-label-custom">Dari Tanggal</label>
          <input type="text" name="tanggal_mulai" id="filter-tanggal-mulai"
            value="{{ $tanggalMulai->toDateString() }}" class="form-control-custom w-100" autocomplete="off">
        </div>
        <div class="col-6 col-lg-2">
          <label class="form-label-custom">Sampai Tanggal</label>
          <input type="text" name="tanggal_akhir" id="filter-tanggal-akhir"
            value="{{ $tanggalAkhir->toDateString() }}" class="form-control-custom w-100" autocomplete="off">
        </div>
      @endif

      <div class="col-6 col-lg-3">
        <label class="form-label-custom">Varian Menu</label>
        <select name="menu_id" class="form-select-custom w-100">
          <option value="">Semua Varian</option>
          @foreach ($daftarMenu as $menu)
            <option value="{{ $menu->id }}" {{ (string) $menuId === (string) $menu->id ? 'selected' : '' }}>
              {{ $menu->nama }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-6 col-lg-2">
        <label class="form-label-custom">Metode Bayar</label>
        <select name="metode_bayar" class="form-select-custom w-100">
          <option value="">Semua Metode</option>
          <option value="cash" {{ $metodeBayar === 'cash' ? 'selected' : '' }}>Cash</option>
          <option value="qris" {{ $metodeBayar === 'qris' ? 'selected' : '' }}>QRIS</option>
          <option value="paylater" {{ $metodeBayar === 'paylater' ? 'selected' : '' }}>PayLater</option>
        </select>
      </div>

      <div class="col-12 col-lg-auto">
        <div class="d-flex gap-2">
          <button type="submit" class="btn-custom btn-custom-primary flex-grow-1">
            <i class="bi bi-sliders"></i>
            <span>Terapkan Filter</span>
          </button>

          @if (request()->anyFilled(['menu_id', 'metode_bayar']))
            @php
              $resetQuery = array_merge(
                  ['periode' => $periode],
                  $periode === 'custom' ? ['tanggal_mulai' => $tanggalMulai->toDateString(), 'tanggal_akhir' => $tanggalAkhir->toDateString()] : []
              );
            @endphp
            <a href="{{ route('admin.laporan.index', $resetQuery) }}" class="table-btn-action" title="Reset varian/metode">
              <i class="bi bi-arrow-counterclockwise"></i>
            </a>
          @endif
        </div>
      </div>
    </form>
  </div>
  <!-- END: Filter & Export Card -->

  <div id="area-cetak-laporan">

    <div class="row g-4">

      <!-- ROW 1: Kartu Statistik -->
      <div class="col-12">
        <div class="row g-4">

          <!-- Total Omzet (kartu gelap) -->
          <div class="col-md-6 col-xl-3">
            <div class="card alert-green-card h-100 mb-0">
              <div class="position-relative z-index-2 d-flex flex-column h-100">
                <span class="alert-green-badge">{{ ucfirst($periode) }}</span>
                <div class="alert-green-date">{{ $tanggalMulai->translatedFormat('d M Y') }} - {{ $tanggalAkhir->translatedFormat('d M Y') }}</div>
                <div class="alert-green-text">Total Omzet</div>
                <div class="stat-value">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</div>
                <div class="trend-badge {{ $trendOmzet['class'] }} mb-0">
                  <i class="bi {{ $trendOmzet['icon'] }}"></i>
                  <span>{{ $trendOmzet['text'] }}</span>
                </div>
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
              <div class="stat-value">{{ $totalTransaksi }}</div>
              <div class="trend-badge {{ $trendTransaksi['class'] }}">
                <i class="bi {{ $trendTransaksi['icon'] }}"></i>
                <span>{{ $trendTransaksi['text'] }}</span>
              </div>
            </div>
          </div>

          <!-- Rata-rata per Transaksi -->
          <div class="col-md-6 col-xl-3">
            <div class="card card-stat h-100 mb-0">
              <div class="stat-icon-row">
                <span class="stat-icon-badge"><i class="bi bi-calculator-fill"></i></span>
                <span class="stat-label">Rata-rata per Transaksi</span>
              </div>
              <div class="stat-value">Rp {{ number_format($rataRata, 0, ',', '.') }}</div>
              <div class="trend-badge {{ $trendRataRata['class'] }}">
                <i class="bi {{ $trendRataRata['icon'] }}"></i>
                <span>{{ $trendRataRata['text'] }}</span>
              </div>
            </div>
          </div>

          <!-- Menu Terlaris -->
          <div class="col-md-6 col-xl-3">
            <div class="card card-stat h-100 mb-0">
              <div class="stat-icon-row">
                <span class="stat-icon-badge"><i class="bi bi-trophy-fill"></i></span>
                <span class="stat-label">Menu Terlaris</span>
              </div>
              @if ($menuTerlaris)
                <div class="stat-value" style="font-size: 1.15rem; line-height: 1.3;">{{ $menuTerlaris->nama_menu }}</div>
                <p class="stat-note">{{ $menuTerlaris->qty }} porsi</p>
              @else
                <div class="stat-value" style="font-size: 1.15rem;">-</div>
                <p class="stat-note">Belum ada penjualan</p>
              @endif
            </div>
          </div>

        </div>
      </div>
      <!-- END: ROW 1 -->

      <!-- ROW 2: Per Varian Menu + Per Ukuran + Metode Pembayaran -->
      <div class="col-12 col-xl-4">
        <div class="card h-100 mb-0">
          <div class="card-header mb-2">
            <h2 class="card-title">Penjualan per Varian Menu</h2>
          </div>

          @forelse ($perVarian as $item)
            <div class="varian-list-item">
              <div class="progress-label-row">
                <span class="progress-label">{{ $item->nama_menu }}</span>
                <span class="progress-value">{{ $item->qty }} ({{ $item->persentase }}%)</span>
              </div>
              <div class="progress">
                <div class="progress-bar {{ $paletteVarian[$loop->index % count($paletteVarian)] }}" style="width: {{ $item->persentase }}%"></div>
              </div>
            </div>
          @empty
            <p class="item-sub mb-0">Belum ada penjualan pada periode ini.</p>
          @endforelse
        </div>
      </div>

      <div class="col-12 col-xl-4">
        <div class="card h-100 mb-0">
          <div class="card-header mb-2">
            <h2 class="card-title">Penjualan per Ukuran</h2>
          </div>
          @if ($perUkuran->isEmpty())
            <p class="item-sub mb-0">Belum ada penjualan pada periode ini.</p>
          @else
            <div id="chart-ukuran"
              data-labels='@json($perUkuran->pluck('nama_ukuran'))'
              data-series='@json($perUkuran->pluck('qty'))'></div>
            <div class="chart-legends-container mt-2">
              @foreach ($perUkuran as $item)
                <div class="chart-legend-item">
                  <span class="legend-dot" style="background-color: {{ ['#8C1F1F', '#FFD166', '#B23A3A', '#5C1212'][$loop->index % 4] }};"></span>
                  <span class="chart-legend-label">{{ $item->nama_ukuran }} ({{ $item->persentase }}%)</span>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>

      <div class="col-12 col-xl-4">
        <div class="card h-100 mb-0">
          <div class="card-header mb-2">
            <h2 class="card-title">Metode Pembayaran</h2>
          </div>
          @if ($metodeBayarBreakdown->isEmpty())
            <p class="item-sub mb-0">Belum ada penjualan pada periode ini.</p>
          @else
            <div id="chart-metode"
              data-labels='@json($metodeBayarBreakdown->pluck('metode_bayar_label'))'
              data-series='@json($metodeBayarBreakdown->pluck('jumlah'))'></div>
            <div class="chart-legends-container mt-2">
              @foreach ($metodeBayarBreakdown as $item)
                <div class="chart-legend-item">
                  <span class="legend-dot" style="background-color: {{ ['#8C1F1F', '#FFD166', '#B23A3A', '#5C1212'][$loop->index % 4] }};"></span>
                  <span class="chart-legend-label">{{ $item->metode_bayar_label }} ({{ $item->jumlah }}, {{ $item->persentase }}%)</span>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>
      <!-- END: ROW 2 -->

      <!-- ROW 3: Tren Penjualan + Detail Ringkasan per Menu -->
      <div class="col-xl-8">
        <div class="card h-100 mb-0">
          <div class="card-header mb-2">
            <h2 class="card-title">Tren Penjualan</h2>
          </div>
          <div id="chart-tren"
            data-labels='@json($trenTanggal)'
            data-omzet='@json($trenOmzet)'></div>
        </div>
      </div>

      <div class="col-xl-4">
        <div class="table-card-custom mb-0">
          <div class="table-header-control">
            <h2 class="card-title mb-0">Detail Ringkasan per Menu</h2>
          </div>
          <div class="table-responsive">
            <table class="table-ringkasan-menu">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Nama Menu</th>
                  <th class="text-end">Jml</th>
                  <th class="text-end">Total Omzet</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($perVarian as $item)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->nama_menu }}</td>
                    <td class="text-end">{{ $item->qty }}</td>
                    <td class="text-end">Rp {{ number_format($item->omzet, 0, ',', '.') }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center py-4" style="color: var(--text-muted-green);">
                      Belum ada transaksi pada periode ini.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <!-- END: ROW 3 -->

    </div>

    <p class="periode-aktif-text mt-4 mb-0">
      <i class="bi bi-info-circle"></i>
      Data diambil dari seluruh transaksi semua kasir pada periode yang dipilih.
    </p>

  </div>

  <!-- ===================== ANALISIS TAMBAHAN ===================== -->
  @isset($tambahan)
    @php
      $rpt = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
      $totalNominalMetode = collect($tambahan['perMetodeNominal'])->sum('nominal');
    @endphp
    <div class="row g-4 mt-1 mb-4" id="analisis-tambahan">

      @if ($tambahan['tampilkanNominal'])
        <div class="col-12 col-xl-6">
          <div class="card h-100"><div class="card-body p-4">
            <h5 class="card-title mb-3"><i class="bi bi-people-fill"></i> Kinerja Kasir</h5>
            @forelse ($tambahan['kinerja'] as $i => $k)
              <div class="lp-rank">
                <span class="lp-rank-no">{{ $i + 1 }}</span>
                <div class="lp-rank-info">
                  <b>{{ $k['nama'] }}</b>
                  <small class="text-muted">{{ number_format($k['jumlah'], 0, ',', '.') }} transaksi • rata-rata {{ $rpt($k['rata_rata']) }}</small>
                  <div class="lp-bar"><i style="width: {{ round(($k['omzet'] / $tambahan['omzetKasirMax']) * 100) }}%"></i></div>
                </div>
                <strong>{{ $rpt($k['omzet']) }}</strong>
              </div>
            @empty
              <div class="lp-kosong"><i class="bi bi-inbox"></i> Belum ada transaksi pada periode ini.</div>
            @endforelse
          </div></div>
        </div>

        <div class="col-12 col-xl-6">
          <div class="card h-100"><div class="card-body p-4">
            <h5 class="card-title mb-3"><i class="bi bi-wallet2"></i> Omzet per Metode Bayar</h5>
            <div class="lp-metode">
              @foreach ($tambahan['perMetodeNominal'] as $m)
                <div class="lp-metode-row">
                  <div class="lp-metode-info">
                    <b>{{ $m['label'] }}</b>
                    <small>{{ number_format($m['jumlah'], 0, ',', '.') }} transaksi • {{ $totalNominalMetode > 0 ? round(($m['nominal'] / $totalNominalMetode) * 100, 1) : 0 }}%</small>
                    <div class="lp-bar"><i style="width: {{ $totalNominalMetode > 0 ? round(($m['nominal'] / $totalNominalMetode) * 100) : 0 }}%"></i></div>
                  </div>
                  <strong>{{ $rpt($m['nominal']) }}</strong>
                </div>
              @endforeach
            </div>
          </div></div>
        </div>
      @endif

      <div class="col-12 col-xl-6">
        <div class="card h-100"><div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <h5 class="card-title mb-0"><i class="bi bi-clock-fill"></i> Jam Tersibuk</h5>
            @if ($tambahan['jamTersibuk'] !== null)
              <span class="text-muted small">Puncak jam {{ sprintf('%02d.00', $tambahan['jamTersibuk']) }} • {{ $tambahan['puncakJam'] }} transaksi</span>
            @endif
          </div>
          @if ($tambahan['jamMulai'] !== null)
            <div class="lp-chart">
              @for ($jam = $tambahan['jamMulai']; $jam <= $tambahan['jamAkhir']; $jam++)
                <div class="lp-col {{ $jam === $tambahan['jamTersibuk'] ? 'puncak' : '' }}" title="{{ sprintf('%02d.00', $jam) }}: {{ $tambahan['perJam'][$jam] }} transaksi">
                  <span class="lp-val">{{ $tambahan['perJam'][$jam] ?: '' }}</span>
                  <i style="height: {{ max(4, round(($tambahan['perJam'][$jam] / $tambahan['puncakJam']) * 100)) }}%"></i>
                  <em>{{ sprintf('%02d', $jam) }}</em>
                </div>
              @endfor
            </div>
          @else
            <div class="lp-kosong"><i class="bi bi-inbox"></i> Belum ada transaksi pada periode ini.</div>
          @endif
        </div></div>
      </div>

      <div class="col-12 col-xl-6">
        <div class="card h-100"><div class="card-body p-4">
          <h5 class="card-title mb-3"><i class="bi bi-calendar-week-fill"></i> Hari Tersibuk</h5>
          @if ($tambahan['puncakHari'] > 0)
            <div class="lp-chart">
              @foreach ($tambahan['perHari'] as $h)
                <div class="lp-col {{ $h['jumlah'] === $tambahan['puncakHari'] ? 'puncak' : '' }}" title="{{ $h['nama'] }}: {{ $h['jumlah'] }} transaksi">
                  <span class="lp-val">{{ $h['jumlah'] ?: '' }}</span>
                  <i style="height: {{ max(4, round(($h['jumlah'] / $tambahan['puncakHari']) * 100)) }}%"></i>
                  <em>{{ \Illuminate\Support\Str::substr($h['nama'], 0, 3) }}</em>
                </div>
              @endforeach
            </div>
          @else
            <div class="lp-kosong"><i class="bi bi-inbox"></i> Belum ada transaksi pada periode ini.</div>
          @endif
        </div></div>
      </div>

    </div>
  @endisset

  <script type="application/json" id="data-laporan">{!! json_encode([
    'toko' => config('app.name', 'Cimol Bojot AA'),
    'periode' => ucfirst($periode),
    'tanggal_mulai' => $tanggalMulai->translatedFormat('d M Y'),
    'tanggal_akhir' => $tanggalAkhir->translatedFormat('d M Y'),
    'tanggal_export' => now()->translatedFormat('d M Y H:i'),
    'total_omzet' => $totalOmzet,
    'total_transaksi' => $totalTransaksi,
    'rata_rata' => $rataRata,
    'per_varian' => $perVarian->map(fn ($i) => ['nama' => $i->nama_menu, 'qty' => $i->qty, 'omzet' => $i->omzet, 'persentase' => $i->persentase])->values(),
    'per_ukuran' => $perUkuran->map(fn ($i) => ['nama' => $i->nama_ukuran, 'qty' => $i->qty, 'persentase' => $i->persentase])->values(),
    'metode_bayar' => $metodeBayarBreakdown->map(fn ($i) => ['nama' => $i->metode_bayar_label, 'jumlah' => $i->jumlah, 'persentase' => $i->persentase])->values(),
  ]) !!}</script>

@endsection

@push('scripts')
  <script src="{{ asset('assets_dashboard/js/laporan.js') }}"></script>
@endpush
