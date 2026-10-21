@extends('layouts.admin')

@section('title', 'Riwayat Transaksi - ' . config('app.name', 'Cimol Bojot AA'))

@section('content')
@php
  $bawaan = request()->only(['cari', 'metode_bayar']);
  $kemarin = today()->subDay()->toDateString();
  $isHariIni = ! $tampilkanSemua && $tanggal === today()->toDateString();
  $isKemarin = ! $tampilkanSemua && $tanggal === $kemarin;
  $labelPeriode = $tampilkanSemua ? 'Semua tanggal' : \Carbon\Carbon::parse($tanggal)->translatedFormat('l, d F Y');
@endphp

  <!-- ===================== HERO ===================== -->
  <div class="hero">
    <div>
      <span class="hero-eyebrow"><i class="bi bi-receipt-cutoff"></i> Riwayat Transaksi</span>
      <h1>Transaksi Saya</h1>
      <p>Semua transaksi yang kamu input sendiri. Pas buat cross-check saat closing kasir.</p>
      <div class="rk-hero-meta">
        <span class="rk-hero-pill"><i class="bi bi-calendar3"></i> {{ $labelPeriode }}</span>
        <span class="rk-hero-pill"><i class="bi bi-receipt"></i> {{ number_format($ringkasan['jumlah_transaksi'], 0, ',', '.') }} transaksi selesai</span>
      </div>
    </div>
    <div class="rk-hero-total">
      <small>Total Omzet</small>
      <strong>Rp {{ number_format($ringkasan['total_omzet'], 0, ',', '.') }}</strong>
      <span>dari transaksi berstatus selesai</span>
    </div>
  </div>

  <!-- ===================== RINGKASAN ===================== -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
      <div class="mstat">
        <span class="mstat-ico"><i class="bi bi-receipt"></i></span>
        <div>
          <div class="mstat-label">Jumlah Transaksi</div>
          <div class="mstat-value">{{ number_format($ringkasan['jumlah_transaksi'], 0, ',', '.') }}</div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4">
      <div class="mstat gold">
        <span class="mstat-ico"><i class="bi bi-wallet2"></i></span>
        <div>
          <div class="mstat-label">Total Cash</div>
          <div class="mstat-value">Rp {{ number_format($ringkasan['total_cash'], 0, ',', '.') }}</div>
          <div class="text-muted" style="font-size:.74rem;">cocokkan ke laci kasir</div>
        </div>
      </div>
    </div>
    <div class="col-sm-12 col-xl-4">
      <div class="mstat ok">
        <span class="mstat-ico"><i class="bi bi-qr-code-scan"></i></span>
        <div>
          <div class="mstat-label">Total Non-Tunai</div>
          <div class="mstat-value">Rp {{ number_format($ringkasan['total_non_tunai'], 0, ',', '.') }}</div>
          <div class="text-muted" style="font-size:.74rem;">QRIS + PayLater</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ===================== FILTER ===================== -->
  <div class="toolbar rk-toolbar">
    <div class="chips">
      <a class="chip {{ $isHariIni ? 'active' : '' }}" href="{{ route('kasir.riwayat-transaksi.index', $bawaan) }}"><i class="bi bi-sun"></i> Hari ini</a>
      <a class="chip {{ $isKemarin ? 'active' : '' }}" href="{{ route('kasir.riwayat-transaksi.index', $bawaan + ['tanggal' => $kemarin]) }}"><i class="bi bi-clock-history"></i> Kemarin</a>
      <a class="chip {{ $tampilkanSemua ? 'active' : '' }}" href="{{ route('kasir.riwayat-transaksi.index', $bawaan + ['semua' => 1]) }}"><i class="bi bi-calendar3"></i> Semua tanggal</a>
    </div>

    <form action="{{ route('kasir.riwayat-transaksi.index') }}" method="GET" class="rk-filter">
      @if ($tampilkanSemua)
        <input type="hidden" name="semua" value="1">
      @endif

      <div>
        <label>Cari Transaksi</label>
        <div class="rk-search">
          <i class="bi bi-search"></i>
          <input type="text" name="cari" value="{{ request('cari') }}" class="rk-input" placeholder="Cari no transaksi...">
        </div>
      </div>

      <div>
        <label>Tanggal</label>
        <input type="text" name="tanggal" id="filter-tanggal" value="{{ $tanggal }}" class="rk-input"
          placeholder="Pilih tanggal" autocomplete="off" {{ $tampilkanSemua ? 'disabled' : '' }}>
      </div>

      <div>
        <label>Metode Bayar</label>
        <select name="metode_bayar" class="rk-input">
          <option value="">Semua Metode</option>
          <option value="cash" {{ request('metode_bayar') === 'cash' ? 'selected' : '' }}>Cash</option>
          <option value="qris" {{ request('metode_bayar') === 'qris' ? 'selected' : '' }}>QRIS</option>
          <option value="paylater" {{ request('metode_bayar') === 'paylater' ? 'selected' : '' }}>PayLater</option>
        </select>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="act primary" style="height:46px;"><i class="bi bi-funnel-fill"></i><span>Terapkan</span></button>
        @if (request()->anyFilled(['cari', 'metode_bayar']) || $tampilkanSemua || (request()->filled('tanggal') && request('tanggal') !== today()->toDateString()))
          <a href="{{ route('kasir.riwayat-transaksi.index') }}" class="act icon" style="height:46px;width:46px;" title="Reset ke hari ini">
            <i class="bi bi-arrow-counterclockwise"></i>
          </a>
        @endif
      </div>
    </form>
  </div>

  <div class="row g-4 align-items-start">

    <!-- ===================== DAFTAR TRANSAKSI ===================== -->
    <div class="col-12 col-xl-7">
      @php
        $grupTanggal = $transaksis->getCollection()->groupBy(fn ($t) => $t->created_at->toDateString());
      @endphp
      <div class="rk-list">
        @forelse ($grupTanggal as $tgl => $daftar)
          @if ($tampilkanSemua)
            <div class="rk-group">
              <div><i class="bi bi-calendar3 me-1"></i> {{ \Carbon\Carbon::parse($tgl)->translatedFormat('l, d F Y') }}</div>
              <span>{{ $daftar->count() }} transaksi · <b>Rp {{ number_format($daftar->where('status', 'selesai')->sum('total'), 0, ',', '.') }}</b></span>
            </div>
          @endif

          @foreach ($daftar as $trx)
            <div class="rk-row {{ $trx->status === 'selesai' ? '' : 'batal' }}" data-id="{{ $trx->id }}">
              <span class="rk-ico"><img src="{{ $trx->metode_bayar_logo }}" alt="{{ $trx->metode_bayar_label }}"></span>

              <div>
                <div class="rk-no">{{ $trx->no_transaksi }}</div>
                <div class="rk-time"><i class="bi bi-clock me-1"></i>{{ $trx->created_at->translatedFormat('d M Y, H:i') }}</div>
                <div class="rk-tags">
                  <span class="rk-tag"><i class="bi bi-wallet2"></i> {{ $trx->metode_bayar_label }}</span>
                  @if ($trx->jumlah_item)
                    <span class="rk-tag"><i class="bi bi-bag"></i> {{ $trx->jumlah_item }} porsi</span>
                  @endif
                </div>
              </div>

              <div class="rk-right">
                <div class="rk-total">Rp {{ number_format($trx->total, 0, ',', '.') }}</div>
                <span class="rk-status rk-col-status {{ $trx->status === 'selesai' ? '' : 'bad' }}">{{ $trx->status_label }}</span>
              </div>

              <button type="button" class="rk-eye btn-lihat-detail" data-id="{{ $trx->id }}" title="Lihat detail">
                <i class="bi bi-eye-fill"></i>
              </button>
            </div>
          @endforeach
        @empty
          <div class="rk-empty">
            <i class="bi bi-receipt-cutoff"></i>
            <b>Belum ada transaksi</b>
            {{ $tampilkanSemua ? 'Tidak ada transaksi yang cocok dengan filter ini.' : 'Kamu belum punya transaksi di tanggal ini.' }}
          </div>
        @endforelse
      </div>

      <div class="foot-bar">
        <span>Menampilkan {{ $transaksis->count() }} dari {{ $transaksis->total() }} transaksi</span>
        {{ $transaksis->links() }}
      </div>
    </div>

    <!-- ===================== PANEL DETAIL (STRUK) ===================== -->
    <div class="col-12 col-xl-5">
      <div class="rk-panel">
        <div class="rk-panel-empty" id="panel-detail-kosong">
          <i class="bi bi-receipt-cutoff"></i>
          <div class="fw-bold mb-1" style="color: var(--pl-ink);">Belum ada transaksi dipilih</div>
          <div style="font-size:.85rem;">Klik salah satu transaksi untuk melihat struk rinciannya di sini.</div>
        </div>

        <div id="loading-detail" class="rk-panel-empty d-none" style="border-style: solid;">
          <div class="spinner-border" role="status" style="color: var(--pl-red);">
            <span class="visually-hidden">Memuat...</span>
          </div>
        </div>

        <div id="panel-detail-isi"></div>
      </div>
    </div>

  </div>
@endsection

@push('scripts')
  <script>
    flatpickr('#filter-tanggal', {
      dateFormat: 'Y-m-d',
      altInput: true,
      altFormat: 'j M Y',
      allowInput: true,
      defaultDate: @json($tanggal),
    });

    (function () {
      const kosong = document.getElementById('panel-detail-kosong');
      const loading = document.getElementById('loading-detail');
      const isi = document.getElementById('panel-detail-isi');
      const detailUrlBase = @json(route('kasir.riwayat-transaksi.show', ['transaksi' => '__ID__']));

      function tandaiBarisAktif(id) {
        document.querySelectorAll('.rk-row').forEach(function (baris) {
          baris.classList.toggle('is-selected', baris.dataset.id === String(id));
        });
      }

      function muatDetail(id) {
        tandaiBarisAktif(id);
        kosong.classList.add('d-none');
        isi.innerHTML = '';
        loading.classList.remove('d-none');

        fetch(detailUrlBase.replace('__ID__', id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(function (res) {
            if (!res.ok) throw new Error('Gagal memuat detail transaksi.');
            return res.text();
          })
          .then(function (html) {
            loading.classList.add('d-none');
            isi.innerHTML = html;
            if (window.innerWidth < 1200) {
              isi.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
          })
          .catch(function () {
            loading.classList.add('d-none');
            isi.innerHTML = '<div class="rk-panel-empty" style="border-style: solid;"><i class="bi bi-exclamation-triangle"></i>Gagal memuat detail transaksi. Coba lagi.</div>';
          });
      }

      document.querySelectorAll('.rk-row').forEach(function (baris) {
        baris.addEventListener('click', function () { muatDetail(baris.dataset.id); });
      });

      document.querySelectorAll('.btn-lihat-detail').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.stopPropagation();
          muatDetail(btn.dataset.id);
        });
      });

      isi.addEventListener('click', function (e) {
        if (e.target.closest('#btn-tutup-detail')) {
          isi.innerHTML = '';
          kosong.classList.remove('d-none');
          tandaiBarisAktif(null);
          return;
        }
        const btnCetak = e.target.closest('.btn-cetak-struk');
        if (btnCetak) {
          window.open(btnCetak.dataset.strukUrl, '_blank');
        }
      });
    })();
  </script>
@endpush
