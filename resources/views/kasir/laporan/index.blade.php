@extends('layouts.admin')

@section('title', 'Laporan Saya - ' . config('app.name', 'Cimol Bojot AA'))

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/kasir-modern.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/cimol-plus.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/kasir-polish.css') }}?v={{ @filemtime(public_path('assets_dashboard/css/kasir-polish.css')) }}">
@endpush

@section('content')
@php
  $rp = fn ($angka) => 'Rp ' . number_format((int) $angka, 0, ',', '.');
  $satuHari = $mulai->isSameDay($akhir);
  $labelPeriode = $satuHari
    ? $mulai->translatedFormat('l, d F Y')
    : $mulai->translatedFormat('d M Y') . ' – ' . $akhir->translatedFormat('d M Y');
  $namaPeriode = ['hari-ini' => 'Hari ini', 'kemarin' => 'Kemarin', '7-hari' => '7 hari terakhir', 'bulan-ini' => 'Bulan ini', 'custom' => 'Rentang tanggal'][$periode];
@endphp

<div class="km lp-page">

  <!-- Kop khusus cetak -->
  <div class="lp-print-head">
    <h2>Rekap Penjualan Kasir</h2>
    <p>{{ auth()->user()->name }} • {{ $labelPeriode }}</p>
    <p>Dicetak {{ now()->translatedFormat('d F Y, H:i') }}</p>
  </div>

  <!-- ===================== HERO ===================== -->
  <div class="hero">
    <div>
      <span class="hero-eyebrow"><i class="bi bi-graph-up-arrow"></i> Laporan Saya</span>
      <h1>Rekap Penjualan</h1>
      <p>Ringkasan penjualan yang kamu layani sendiri. Cocok untuk closing kasir dan evaluasi harian.</p>
      <div class="rk-hero-meta">
        <span class="rk-hero-pill"><i class="bi bi-calendar3"></i> {{ $labelPeriode }}</span>
        <span class="rk-hero-pill"><i class="bi bi-receipt"></i> {{ number_format($jumlahTransaksi, 0, ',', '.') }} transaksi</span>
      </div>
    </div>
    <div class="rk-hero-total">
      <small>Total Omzet</small>
      <strong>{{ $rp($omzet) }}</strong>
      <span>{{ $namaPeriode }}</span>
    </div>
  </div>

  <!-- ===================== FILTER ===================== -->
  <div class="toolbar rk-toolbar lp-no-print">
    <div class="chips">
      <a class="chip {{ $periode === 'hari-ini' ? 'active' : '' }}" href="{{ route('kasir.laporan.index', ['periode' => 'hari-ini']) }}"><i class="bi bi-sun"></i> Hari ini</a>
      <a class="chip {{ $periode === 'kemarin' ? 'active' : '' }}" href="{{ route('kasir.laporan.index', ['periode' => 'kemarin']) }}"><i class="bi bi-clock-history"></i> Kemarin</a>
      <a class="chip {{ $periode === '7-hari' ? 'active' : '' }}" href="{{ route('kasir.laporan.index', ['periode' => '7-hari']) }}"><i class="bi bi-calendar-week"></i> 7 hari</a>
      <a class="chip {{ $periode === 'bulan-ini' ? 'active' : '' }}" href="{{ route('kasir.laporan.index', ['periode' => 'bulan-ini']) }}"><i class="bi bi-calendar3"></i> Bulan ini</a>
    </div>

    <form action="{{ route('kasir.laporan.index') }}" method="GET" class="rk-filter">
      <input type="hidden" name="periode" value="custom">
      <div>
        <label>Dari</label>
        <input type="date" name="tanggal_mulai" value="{{ $mulai->toDateString() }}" max="{{ today()->toDateString() }}" class="rk-input">
      </div>
      <div>
        <label>Sampai</label>
        <input type="date" name="tanggal_akhir" value="{{ $akhir->toDateString() }}" max="{{ today()->toDateString() }}" class="rk-input">
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="act primary"><i class="bi bi-funnel-fill"></i> Terapkan</button>
        <button type="button" class="act" onclick="cetakRekap()"><i class="bi bi-printer-fill"></i> Cetak</button>
      </div>
    </form>
  </div>

  <!-- ===================== RINGKASAN ===================== -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="mstat">
        <span class="mstat-ico"><i class="bi bi-receipt"></i></span>
        <div>
          <div class="mstat-label">Transaksi</div>
          <div class="mstat-value">{{ number_format($jumlahTransaksi, 0, ',', '.') }}</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="mstat ok">
        <span class="mstat-ico"><i class="bi bi-cash-coin"></i></span>
        <div>
          <div class="mstat-label">Omzet</div>
          <div class="mstat-value">{{ $rp($omzet) }}</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="mstat">
        <span class="mstat-ico"><i class="bi bi-calculator"></i></span>
        <div>
          <div class="mstat-label">Rata-rata / Transaksi</div>
          <div class="mstat-value">{{ $rp($rataRata) }}</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="mstat">
        <span class="mstat-ico"><i class="bi bi-bag-check"></i></span>
        <div>
          <div class="mstat-label">Porsi Terjual</div>
          <div class="mstat-value">{{ number_format($itemTerjual, 0, ',', '.') }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <!-- ===================== METODE BAYAR ===================== -->
    <div class="col-12 col-xl-6">
      <div class="km-card h-100">
        <h2 class="km-card-title mb-3"><i class="bi bi-credit-card-2-front-fill"></i> Rincian Metode Bayar</h2>
        <div class="lp-metode">
          @foreach ($perMetode as $m)
            <div class="lp-metode-row">
              <span class="lp-metode-ico {{ $m['kode'] }}"><i class="bi {{ $m['icon'] }}"></i></span>
              <div class="lp-metode-info">
                <b>{{ $m['label'] }}</b>
                <small>{{ number_format($m['jumlah'], 0, ',', '.') }} transaksi • {{ $m['persen'] }}%</small>
                <div class="lp-bar"><i style="width: {{ min(100, $m['persen']) }}%"></i></div>
              </div>
              <strong>{{ $rp($m['nominal']) }}</strong>
            </div>
          @endforeach
        </div>

        <div class="lp-laci">
          <div class="lp-laci-judul"><i class="bi bi-safe2-fill"></i> Cocokkan laci kasir (tunai)</div>
          <div class="lp-laci-baris"><span>Uang diterima</span><b>{{ $rp($tunaiDiterima) }}</b></div>
          <div class="lp-laci-baris"><span>Kembalian diberikan</span><b>− {{ $rp($kembalian) }}</b></div>
          <div class="lp-laci-baris total"><span>Tunai bersih seharusnya ada</span><b>{{ $rp($perMetode[0]['nominal']) }}</b></div>
        </div>
      </div>
    </div>

    <!-- ===================== MENU TERLARIS ===================== -->
    <div class="col-12 col-xl-6">
      <div class="km-card h-100">
        <h2 class="km-card-title mb-3"><i class="bi bi-fire"></i> Menu Terlaris</h2>
        @forelse ($terlaris as $i => $menu)
          <div class="lp-rank">
            <span class="lp-rank-no">{{ $i + 1 }}</span>
            <div class="lp-rank-info">
              <b>{{ $menu['nama'] }}</b>
              <div class="lp-bar"><i style="width: {{ round(($menu['qty'] / $qtyMax) * 100) }}%"></i></div>
            </div>
            <strong>{{ number_format($menu['qty'], 0, ',', '.') }}<small> porsi</small></strong>
          </div>
        @empty
          <div class="lp-kosong"><i class="bi bi-inbox"></i> Belum ada penjualan di periode ini.</div>
        @endforelse

        @if ($terbesar)
          <div class="lp-sorot">
            <i class="bi bi-trophy-fill"></i>
            <div>
              <small>Transaksi terbesar</small>
              <b>{{ $rp($terbesar->total) }}</b>
              <span>{{ $terbesar->no_transaksi }} • {{ $terbesar->created_at->translatedFormat('d M, H:i') }}</span>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <!-- ===================== JAM TERSIBUK ===================== -->
    <div class="col-12 {{ count($tren) ? 'col-xl-6' : '' }}">
      <div class="km-card h-100">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
          <h2 class="km-card-title mb-0"><i class="bi bi-clock-fill"></i> Jam Tersibuk</h2>
          @if ($jamTersibuk !== null)
            <span class="km-sub">Puncak jam {{ sprintf('%02d.00', $jamTersibuk) }} • {{ $puncakJam }} transaksi</span>
          @endif
        </div>
        @if ($jamMulai !== null)
          <div class="lp-chart">
            @for ($jam = $jamMulai; $jam <= $jamAkhir; $jam++)
              <div class="lp-col {{ $jam === $jamTersibuk ? 'puncak' : '' }}" title="{{ sprintf('%02d.00', $jam) }}: {{ $perJam[$jam] }} transaksi">
                <span class="lp-val">{{ $perJam[$jam] ?: '' }}</span>
                <i style="height: {{ $puncakJam > 0 ? max(4, round(($perJam[$jam] / $puncakJam) * 100)) : 4 }}%"></i>
                <em>{{ sprintf('%02d', $jam) }}</em>
              </div>
            @endfor
          </div>
        @else
          <div class="lp-kosong"><i class="bi bi-inbox"></i> Belum ada transaksi di periode ini.</div>
        @endif
      </div>
    </div>

    <!-- ===================== TREN HARIAN ===================== -->
    @if (count($tren))
      <div class="col-12 col-xl-6">
        <div class="km-card h-100">
          <h2 class="km-card-title mb-3"><i class="bi bi-bar-chart-line-fill"></i> Omzet per Hari</h2>
          <div class="lp-chart">
            @foreach ($tren as $hari)
              <div class="lp-col" title="{{ $hari['label'] }}: {{ $rp($hari['total']) }} ({{ $hari['jumlah'] }} transaksi)">
                <span class="lp-val">{{ $hari['total'] > 0 ? number_format($hari['total'] / 1000, 0, ',', '.') . 'k' : '' }}</span>
                <i style="height: {{ max(4, round(($hari['total'] / $trenMax) * 100)) }}%"></i>
                <em>{{ \Illuminate\Support\Str::before($hari['label'], ' ') }}</em>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    @endif
  </div>

</div>
@endsection

@push('scripts')
  <script>
    function cetakRekap() {
      document.body.classList.add('lp-printing');
      window.print();
    }
    window.addEventListener('afterprint', function () {
      document.body.classList.remove('lp-printing');
    });
  </script>
@endpush
