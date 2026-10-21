@extends('layouts.admin')

@section('title', 'Riwayat Transaksi - ' . config('app.name', 'Cimol Bojot AA'))

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/cimol-plus.css') }}">
  <style>
    .rt .rt-avatar-img { width: 34px; height: 34px; flex-shrink: 0; border-radius: 12px; object-fit: cover; background: #f3e6e0; }
    .rt .rt-detail-kasir { display: inline-flex; align-items: center; gap: .5rem; }
    .rt .rt-detail-kasir img { width: 26px; height: 26px; border-radius: 9px; object-fit: cover; }
  </style>
@endpush

@section('content')
<div class="ap rt">

  <!-- START: Page Header -->
  <div class="ap-hero">
    <div>
      <h1>Riwayat Transaksi</h1>
      <p>Semua transaksi kasir tercatat dalam satu tempat. Klik baris untuk melihat rincian dan struk.</p>
    </div>
    <a href="{{ route('admin.riwayat-transaksi.export', request()->only(['cari', 'kasir_id', 'tanggal', 'metode_bayar'])) }}"
      class="ap-btn" title="Unduh CSV sesuai filter yang sedang aktif">
      <i class="bi bi-filetype-csv"></i> Export CSV
    </a>
  </div>

  @if (isset($ringkasan))
    <div class="rt-ringkasan">
      <div class="rt-rk"><span class="rt-rk-ico"><i class="bi bi-receipt"></i></span><div><small>Transaksi selesai</small><b>{{ number_format($ringkasan['jumlah'], 0, ',', '.') }}</b></div></div>
      <div class="rt-rk ok"><span class="rt-rk-ico"><i class="bi bi-cash-coin"></i></span><div><small>Total omzet</small><b>Rp {{ number_format($ringkasan['omzet'], 0, ',', '.') }}</b></div></div>
      <div class="rt-rk"><span class="rt-rk-ico"><i class="bi bi-calculator"></i></span><div><small>Rata-rata / transaksi</small><b>Rp {{ number_format($ringkasan['rata_rata'], 0, ',', '.') }}</b></div></div>
      <div class="rt-rk"><span class="rt-rk-ico"><i class="bi bi-wallet2"></i></span><div><small>Tunai</small><b>Rp {{ number_format($ringkasan['tunai'], 0, ',', '.') }}</b></div></div>
      <div class="rt-rk"><span class="rt-rk-ico"><i class="bi bi-qr-code-scan"></i></span><div><small>Non-tunai</small><b>Rp {{ number_format($ringkasan['non_tunai'], 0, ',', '.') }}</b></div></div>
      @if ($ringkasan['dibatalkan'] > 0)
        <div class="rt-rk bad"><span class="rt-rk-ico"><i class="bi bi-x-octagon"></i></span><div><small>Tidak selesai</small><b>{{ number_format($ringkasan['dibatalkan'], 0, ',', '.') }}</b></div></div>
      @endif
    </div>
  @endif
  <!-- END: Page Header -->

  @php
    $adaFilter = request()->anyFilled(['cari', 'kasir_id', 'tanggal', 'metode_bayar']);
    $namaKasirDipilih = request('kasir_id') ? optional(collect($daftarKasir)->firstWhere('id', (int) request('kasir_id')))->name : null;
    $labelMetode = ['cash' => 'Cash', 'qris' => 'QRIS', 'paylater' => 'PayLater'];
  @endphp

  <!-- START: Filter Card -->
  <div class="rt-filter">
    <div class="rt-filter-title"><i class="bi bi-funnel-fill"></i> Filter Transaksi</div>

    <form action="{{ route('admin.riwayat-transaksi.index') }}" method="GET" class="row g-3 align-items-end">
      <div class="col-12 col-lg-4">
        <label class="form-label-custom">Cari Transaksi</label>
        <div class="table-search-box w-100">
          <i class="bi bi-search table-search-icon"></i>
          <input
            type="text"
            name="cari"
            value="{{ request('cari') }}"
            class="table-search-input"
            placeholder="No transaksi / nama kasir..."
          >
        </div>
      </div>

      <div class="col-6 col-lg-2">
        <label class="form-label-custom">Kasir</label>
        <select name="kasir_id" class="form-select-custom w-100">
          <option value="">Semua Kasir</option>
          @foreach ($daftarKasir as $kasir)
            <option value="{{ $kasir->id }}" {{ (string) request('kasir_id') === (string) $kasir->id ? 'selected' : '' }}>
              {{ $kasir->name }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-6 col-lg-2">
        <label class="form-label-custom">Tanggal</label>
        <input
          type="text"
          name="tanggal"
          id="filter-tanggal"
          value="{{ request('tanggal') }}"
          class="form-control-custom w-100"
          placeholder="Pilih tanggal"
          autocomplete="off"
        >
      </div>

      <div class="col-6 col-lg-2">
        <label class="form-label-custom">Metode Bayar</label>
        <select name="metode_bayar" class="form-select-custom w-100">
          <option value="">Semua Metode</option>
          <option value="cash" {{ request('metode_bayar') === 'cash' ? 'selected' : '' }}>Cash</option>
          <option value="qris" {{ request('metode_bayar') === 'qris' ? 'selected' : '' }}>QRIS</option>
          <option value="paylater" {{ request('metode_bayar') === 'paylater' ? 'selected' : '' }}>PayLater</option>
        </select>
      </div>

      <div class="col-6 col-lg-2">
        <div class="d-flex gap-2">
          <button type="submit" class="btn-custom btn-custom-primary flex-grow-1 justify-content-center">
            <i class="bi bi-sliders"></i>
            <span>Terapkan</span>
          </button>

          @if ($adaFilter)
            <a href="{{ route('admin.riwayat-transaksi.index') }}" class="rt-reset" title="Reset filter">
              <i class="bi bi-arrow-counterclockwise"></i>
            </a>
          @endif
        </div>
      </div>
    </form>

    @if ($adaFilter)
      <div class="rt-active">
        @if (request('cari'))
          <span class="rt-active-chip"><i class="bi bi-search"></i> "{{ request('cari') }}"</span>
        @endif
        @if ($namaKasirDipilih)
          <span class="rt-active-chip"><i class="bi bi-person-fill"></i> {{ $namaKasirDipilih }}</span>
        @endif
        @if (request('tanggal'))
          <span class="rt-active-chip"><i class="bi bi-calendar-event"></i> {{ request('tanggal') }}</span>
        @endif
        @if (request('metode_bayar'))
          <span class="rt-active-chip"><i class="bi bi-wallet2"></i> {{ $labelMetode[request('metode_bayar')] ?? request('metode_bayar') }}</span>
        @endif
      </div>
    @endif
  </div>
  <!-- END: Filter Card -->

  <div class="row g-4 align-items-start">

    <!-- START: Tabel Riwayat Transaksi -->
    <div class="col-12 col-xl-8">
      <div class="rt-card">
        <div class="table-responsive">
          <table class="rt-table">
            <thead>
              <tr>
                <th>No</th>
                <th>No Transaksi</th>
                <th>Kasir</th>
                <th>Tanggal &amp; Waktu</th>
                <th>Total</th>
                <th>Metode Bayar</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($transaksis as $index => $trx)
                @php
                  $namaKasir = $trx->kasir->name ?? '-';
                @endphp
                <tr class="baris-transaksi" data-id="{{ $trx->id }}">
                  <td class="rt-col-no" data-label="No">{{ $transaksis->firstItem() + $index }}</td>
                  <td data-label="No Transaksi"><span class="rt-invoice">{{ $trx->no_transaksi }}</span></td>
                  <td data-label="Kasir">
                    <div class="rt-cashier">
                      @if ($trx->kasir)
                        <img src="{{ $trx->kasir->avatar_url }}" alt="{{ $namaKasir }}" class="rt-avatar-img">
                      @else
                        <span class="rt-avatar">?</span>
                      @endif
                      <span>{{ $namaKasir }}</span>
                    </div>
                  </td>
                  <td data-label="Waktu">
                    <div class="rt-date">{{ $trx->created_at->translatedFormat('d M Y') }}</div>
                    <div class="rt-time">{{ $trx->created_at->format('H:i') }} WIB</div>
                  </td>
                  <td data-label="Total"><span class="rt-amount">Rp {{ number_format($trx->total, 0, ',', '.') }}</span></td>
                  <td data-label="Metode">
                    <span class="pay-chip"><img src="{{ $trx->metode_bayar_logo }}" alt="">{{ $trx->metode_bayar_label }}</span>
                  </td>
                  <td data-label="Status">
                    <span class="badge-table {{ $trx->status_badge_class }}">{{ $trx->status_label }}</span>
                  </td>
                  <td data-label="Aksi">
                    <button type="button" class="rt-eye btn-lihat-detail" data-id="{{ $trx->id }}" title="Lihat detail">
                      <i class="bi bi-eye"></i>
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="rt-empty">
                    <i class="bi bi-receipt-cutoff"></i>
                    <b>Belum ada transaksi</b>
                    Tidak ada transaksi yang cocok dengan filter ini.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="rt-foot">
          <span class="table-pagination-info">
            Menampilkan {{ $transaksis->count() }} dari {{ $transaksis->total() }} transaksi
          </span>
          {{ $transaksis->links() }}
        </div>
      </div>
    </div>
    <!-- END: Tabel Riwayat Transaksi -->

    <!-- START: Panel Detail Transaksi -->
    <div class="col-12 col-xl-4">
      <div class="rt-sticky">
        <div class="card" id="panel-detail-kosong">
          <i class="bi bi-receipt-cutoff empty-state-icon"></i>
          <p>Pilih salah satu transaksi di tabel untuk melihat detailnya di sini.</p>
        </div>

        <div id="loading-detail" class="card d-none align-items-center justify-content-center">
          <div class="spinner-border" role="status" style="color: var(--brand-forest-medium) !important;">
            <span class="visually-hidden">Memuat...</span>
          </div>
        </div>

        <div id="panel-detail-isi"></div>
      </div>
    </div>
    <!-- END: Panel Detail Transaksi -->

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
    });

    (function () {
      const kosong = document.getElementById('panel-detail-kosong');
      const loading = document.getElementById('loading-detail');
      const isi = document.getElementById('panel-detail-isi');
      const detailUrlBase = @json(route('admin.riwayat-transaksi.show', ['transaksi' => '__ID__']));

      function tandaiBarisAktif(id) {
        document.querySelectorAll('.baris-transaksi').forEach(function (tr) {
          tr.classList.toggle('is-selected', tr.dataset.id === String(id));
        });
      }

      function muatDetail(id) {
        tandaiBarisAktif(id);
        kosong.classList.add('d-none');
        isi.innerHTML = '';
        loading.classList.remove('d-none');

        fetch(detailUrlBase.replace('__ID__', id), {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
          .then(function (res) {
            if (!res.ok) throw new Error('Gagal memuat detail transaksi.');
            return res.text();
          })
          .then(function (html) {
            loading.classList.add('d-none');
            isi.innerHTML = html;
          })
          .catch(function () {
            loading.classList.add('d-none');
            isi.innerHTML = '<div class="card"><p class="mb-0 text-danger">Gagal memuat detail transaksi. Coba lagi.</p></div>';
          });
      }

      document.querySelectorAll('.baris-transaksi').forEach(function (tr) {
        tr.addEventListener('click', function () {
          muatDetail(tr.dataset.id);
        });
      });

      document.querySelectorAll('.btn-lihat-detail').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.stopPropagation();
          muatDetail(btn.dataset.id);
        });
      });

      // Tombol "Tutup" & "Cetak Struk" ada di dalam HTML yang ditempel
      // secara dinamis lewat fetch, jadi listenernya harus delegasi ke
      // parent yang sudah ada dari awal (bukan attach langsung).
      isi.addEventListener('click', function (e) {
        const btnTutup = e.target.closest('#btn-tutup-detail');
        if (btnTutup) {
          isi.innerHTML = '';
          kosong.classList.remove('d-none');
          tandaiBarisAktif(null);
          return;
        }

        const btnCetak = e.target.closest('.btn-cetak-struk');
        if (btnCetak) {
          window.open(btnCetak.dataset.strukUrl, '_blank');
          return;
        }

        const btnPdf = e.target.closest('.btn-simpan-pdf');
        if (btnPdf) {
          simpanPdf(btnPdf);
        }
      });

      let jsPdfPromise = null;
      function muatJsPdf() {
        if (window.jspdf) return Promise.resolve();
        if (jsPdfPromise) return jsPdfPromise;

        jsPdfPromise = new Promise(function (resolve, reject) {
          const script = document.createElement('script');
          script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
          script.onload = resolve;
          script.onerror = reject;
          document.head.appendChild(script);
        });

        return jsPdfPromise;
      }

      function simpanPdf(btn) {
        const dataEl = document.getElementById('data-transaksi-pdf');
        if (!dataEl) return;

        const trx = JSON.parse(dataEl.textContent);
        const teksAsli = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyiapkan...';

        muatJsPdf()
          .then(function () {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ unit: 'mm', format: [80, 160 + trx.items.length * 6] });
            let y = 10;

            doc.setFontSize(12);
            doc.setFont(undefined, 'bold');
            doc.text('Cimol Bojot AA', 40, y, { align: 'center' });
            y += 5;
            doc.setFontSize(8);
            doc.setFont(undefined, 'normal');
            doc.text('Struk Transaksi', 40, y, { align: 'center' });
            y += 6;
            doc.text('--------------------------------', 40, y, { align: 'center' });
            y += 5;

            doc.text('No Transaksi : ' + trx.no_transaksi, 5, y); y += 4;
            doc.text('Tanggal      : ' + trx.tanggal, 5, y); y += 4;
            doc.text('Kasir        : ' + trx.kasir, 5, y); y += 4;
            doc.text('Metode Bayar : ' + trx.metode_bayar, 5, y); y += 4;
            doc.text('Status       : ' + trx.status, 5, y); y += 5;
            doc.text('--------------------------------', 40, y, { align: 'center' });
            y += 5;

            trx.items.forEach(function (item) {
              doc.text(item.nama, 5, y); y += 4;
              doc.text(item.jumlah + ' x ' + item.harga, 5, y);
              doc.text(item.subtotal, 75, y, { align: 'right' });
              y += 5;
            });

            doc.text('--------------------------------', 40, y, { align: 'center' });
            y += 5;
            doc.setFont(undefined, 'bold');
            doc.text('TOTAL', 5, y);
            doc.text(trx.total, 75, y, { align: 'right' });
            y += 5;

            doc.setFont(undefined, 'normal');
            if (trx.uang_diterima) {
              doc.text('Uang Diterima', 5, y);
              doc.text(trx.uang_diterima, 75, y, { align: 'right' });
              y += 4;
            }
            doc.text('Kembalian', 5, y);
            doc.text(trx.kembalian, 75, y, { align: 'right' });
            y += 8;

            doc.setFont(undefined, 'normal');
            doc.setFontSize(7);
            doc.text('Terima kasih sudah berbelanja!', 40, y, { align: 'center' });

            doc.save(trx.no_transaksi + '.pdf');
          })
          .catch(function () {
            alert('Gagal membuat PDF. Pastikan koneksi internet aktif lalu coba lagi.');
          })
          .finally(function () {
            btn.disabled = false;
            btn.innerHTML = teksAsli;
          });
      }
    })();
  </script>
@endpush
