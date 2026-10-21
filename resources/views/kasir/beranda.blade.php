@extends('layouts.admin')

@section('title', 'Beranda Kasir - ' . config('app.name', 'Cimol Bojot AA'))

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/kasir-modern.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/cimol-plus.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/kasir-polish.css') }}?v={{ @filemtime(public_path('assets_dashboard/css/kasir-polish.css')) }}">
@endpush

@php
  $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
  $maksGrafik = max(1, (int) $grafik->max('total'));
  $totalSplit = max(1, $ringkasan['omzet']);
  $jam = now()->format('H');
  $salam = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
@endphp

@section('content')
<div class="km">

  <!-- ===================== HERO ===================== -->
  <section class="db-hero">
    <div class="db-hero-in">
      <div>
        <div class="db-date" id="hero-tanggal">Hari ini</div>
        <h1><span id="hero-salam">{{ $salam }}</span>, {{ \Illuminate\Support\Str::of($kasir->name)->explode(' ')->first() }}!</h1>
        <p>
          @if ($ringkasan['jumlah'] > 0)
            Hari ini kamu sudah melayani {{ $ringkasan['jumlah'] }} transaksi dengan omzet {{ $rp($ringkasan['omzet']) }}. Lanjut semangat!
          @else
            Belum ada transaksi hari ini. Cek stok dulu, lalu layani pembeli pertamamu.
          @endif
        </p>
        <div class="db-hero-actions">
          <a href="{{ route('kasir.transaksi.index') }}" class="km-btn km-btn-gold">
            <i class="bi bi-cart-plus-fill"></i><span>Mulai Transaksi</span>
          </a>
          <a href="{{ route('kasir.stok.index') }}" class="db-btn-glass"><i class="bi bi-boxes"></i><span>Cek Stok</span></a>
        </div>
      </div>
      <div class="db-clock">
        <b id="hero-jam">--:--</b>
        <small>Waktu setempat</small>
      </div>
    </div>
  </section>

  <!-- ===================== STATISTIK HARI INI ===================== -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
      <div class="db-stat is-primary">
        <div class="db-stat-top"><span class="db-stat-label">Omzet Hari Ini</span><span class="db-stat-ico"><i class="bi bi-wallet2"></i></span></div>
        <div class="db-stat-val">{{ $rp($ringkasan['omzet']) }}</div>
        <div class="db-stat-note">dari transaksi yang kamu layani</div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="db-stat">
        <div class="db-stat-top"><span class="db-stat-label">Transaksi</span><span class="db-stat-ico"><i class="bi bi-receipt"></i></span></div>
        <div class="db-stat-val">{{ number_format($ringkasan['jumlah'], 0, ',', '.') }}</div>
        <div class="db-stat-note">rata-rata {{ $rp($ringkasan['rata_rata']) }} / transaksi</div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="db-stat">
        <div class="db-stat-top"><span class="db-stat-label">Porsi Terjual</span><span class="db-stat-ico"><i class="bi bi-bag-heart-fill"></i></span></div>
        <div class="db-stat-val">{{ number_format($ringkasan['porsi'], 0, ',', '.') }}</div>
        <div class="db-stat-note">total porsi hari ini</div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="db-stat">
        <div class="db-stat-top"><span class="db-stat-label">Stok Perlu Perhatian</span><span class="db-stat-ico"><i class="bi bi-exclamation-triangle-fill"></i></span></div>
        <div class="db-stat-val">{{ $peringatan->count() }}</div>
        <div class="db-stat-note">{{ $peringatan->where('status', 'habis')->count() }} habis, {{ $peringatan->where('status', 'menipis')->count() }} menipis</div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <!-- Grafik 7 hari -->
    <div class="col-lg-7">
      <div class="km-card h-100">
        <div class="d-flex align-items-center justify-content-between">
          <h2 class="km-card-title"><i class="bi bi-bar-chart-line-fill"></i> Omzet 7 Hari Terakhir</h2>
          <span class="km-sub">{{ $rp($grafik->sum('total')) }}</span>
        </div>
        <div class="db-chart">
          @foreach ($grafik as $hari)
            <div class="db-bar {{ $hari['hari_ini'] ? 'is-today' : '' }}">
              <div class="db-bar-fill" style="height: {{ max(4, round($hari['total'] / $maksGrafik * 100)) }}%">
                <span class="db-bar-tip">{{ $rp($hari['total']) }}</span>
              </div>
              <span class="db-bar-lbl">{{ $hari['hari_ini'] ? 'Hari ini' : $hari['label'] }}</span>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <!-- Pecahan pembayaran -->
    <div class="col-lg-5">
      <div class="km-card h-100">
        <h2 class="km-card-title"><i class="bi bi-credit-card-2-front-fill"></i> Metode Bayar Hari Ini</h2>
        <div class="db-split" aria-hidden="true">
          <i class="c-cash" style="width: {{ $ringkasan['cash'] / $totalSplit * 100 }}%"></i>
          <i class="c-qris" style="width: {{ $ringkasan['qris'] / $totalSplit * 100 }}%"></i>
          <i class="c-pay" style="width: {{ $ringkasan['paylater'] / $totalSplit * 100 }}%"></i>
        </div>
        <div class="db-pay-row"><img src="{{ asset('assets_dashboard/images/pay/cash.svg') }}" alt=""><b>Cash</b><span>{{ $rp($ringkasan['cash']) }}</span></div>
        <div class="db-pay-row"><img src="{{ asset('assets_dashboard/images/pay/qris.svg') }}" alt=""><b>QRIS</b><span>{{ $rp($ringkasan['qris']) }}</span></div>
        <div class="db-pay-row"><img src="{{ asset('assets_dashboard/images/pay/paylater.svg') }}" alt=""><b>PayLater</b><span>{{ $rp($ringkasan['paylater']) }}</span></div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <!-- Terlaris -->
    <div class="col-lg-4">
      <div class="km-card h-100">
        <h2 class="km-card-title mb-2"><i class="bi bi-fire"></i> Terlaris Hari Ini</h2>
        @forelse ($terlaris as $i => $menu)
          <div class="db-rank">
            <span class="db-rank-no">{{ $i + 1 }}</span>
            <span class="db-rank-nama">{{ $menu->nama_menu }}</span>
            <span class="db-rank-val">{{ $menu->total_porsi }} porsi</span>
          </div>
        @empty
          <div class="db-empty"><i class="bi bi-emoji-smile"></i>Belum ada penjualan hari ini.</div>
        @endforelse
      </div>
    </div>

    <!-- Peringatan stok -->
    <div class="col-lg-4">
      <div class="km-card h-100">
        <h2 class="km-card-title mb-3"><i class="bi bi-bell-fill"></i> Peringatan Stok</h2>
        @forelse ($peringatan->take(5) as $p)
          <div class="db-alert {{ $p['status'] }}">
            <i class="bi {{ $p['status'] === 'habis' ? 'bi-x-circle-fill' : 'bi-exclamation-triangle-fill' }}"></i>
            <b>{{ $p['nama'] }} <span class="fw-normal opacity-75">· {{ $p['jenis'] }}</span></b>
            <small>{{ $p['status'] === 'habis' ? 'Habis' : 'Sisa ' . $p['stok'] }}</small>
          </div>
        @empty
          <div class="db-empty"><i class="bi bi-check2-circle"></i>Semua stok aman.</div>
        @endforelse
        @if ($peringatan->count() > 5)
          <a href="{{ route('kasir.stok.index') }}" class="km-sub d-inline-block mt-1">+{{ $peringatan->count() - 5 }} lainnya, lihat di Cek Stok</a>
        @endif
      </div>
    </div>

    <!-- Transaksi terakhir -->
    <div class="col-lg-4">
      <div class="km-card h-100">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <h2 class="km-card-title"><i class="bi bi-clock-history"></i> Transaksi Terakhir</h2>
          <a href="{{ route('kasir.riwayat-transaksi.index') }}" class="km-sub">Semua</a>
        </div>
        @forelse ($terakhir as $trx)
          <a href="{{ route('kasir.riwayat-transaksi.index', ['semua' => 1, 'cari' => $trx->no_transaksi]) }}" class="db-trx">
            <img src="{{ $trx->metode_bayar_logo }}" alt="{{ $trx->metode_bayar_label }}">
            <div>
              <div class="db-trx-no">{{ $trx->no_transaksi }}</div>
              <div class="db-trx-sub">{{ $trx->created_at->translatedFormat('d M, H:i') }} · {{ $trx->detail_count }} item</div>
            </div>
            <span class="db-trx-tot">{{ $rp($trx->total) }}</span>
          </a>
        @empty
          <div class="db-empty"><i class="bi bi-receipt"></i>Belum ada transaksi.</div>
        @endforelse
      </div>
    </div>
  </div>

  <!-- ===================== JALAN PINTAS ===================== -->
  <div class="row g-3">
    <div class="col-md-4">
      <a href="{{ route('kasir.transaksi.index') }}" class="db-quick">
        <span class="db-quick-ico"><i class="bi bi-cart-check-fill"></i></span>
        <div><b>Transaksi</b><span>Buat pesanan baru</span></div><i class="bi bi-chevron-right"></i>
      </a>
    </div>
    <div class="col-md-4">
      <a href="{{ route('kasir.riwayat-transaksi.index') }}" class="db-quick">
        <span class="db-quick-ico"><i class="bi bi-receipt"></i></span>
        <div><b>Riwayat Transaksi</b><span>Cocokkan saat closing</span></div><i class="bi bi-chevron-right"></i>
      </a>
    </div>
    <div class="col-md-4">
      <a href="{{ route('profile.edit') }}" class="db-quick">
        <span class="db-quick-ico"><i class="bi bi-person-circle"></i></span>
        <div><b>Profil Saya</b><span>Data diri &amp; password</span></div><i class="bi bi-chevron-right"></i>
      </a>
    </div>
  </div>

</div>
@endsection

@push('scripts')
  <script>
    (function () {
      var elTanggal = document.getElementById('hero-tanggal');
      var elJam = document.getElementById('hero-jam');
      var elSalam = document.getElementById('hero-salam');

      function perbarui() {
        var sekarang = new Date();
        if (elTanggal) {
          elTanggal.textContent = sekarang.toLocaleDateString('id-ID', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
          });
        }
        // Salam mengikuti jam di perangkat kasir (bukan jam server),
        // jadi selalu cocok dengan jam yang tampil di sebelahnya.
        if (elSalam) {
          var j = sekarang.getHours();
          elSalam.textContent = j < 11 ? 'Selamat pagi' : (j < 15 ? 'Selamat siang' : (j < 18 ? 'Selamat sore' : 'Selamat malam'));
        }
        if (elJam) {
          elJam.textContent = sekarang.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':');
        }
      }

      perbarui();
      setInterval(perbarui, 15000);
    })();
  </script>
@endpush
