@php
  $dataPdf = [
    'no_transaksi' => $transaksi->no_transaksi,
    'tanggal' => $transaksi->created_at->translatedFormat('d M Y H:i'),
    'kasir' => $transaksi->kasir->name ?? '-',
    'metode_bayar' => $transaksi->metode_bayar_label,
    'status' => $transaksi->status_label,
    'total' => 'Rp ' . number_format($transaksi->total, 0, ',', '.'),
    'uang_diterima' => $transaksi->ada_rincian_tunai ? 'Rp ' . number_format($transaksi->uang_diterima, 0, ',', '.') : null,
    'kembalian' => 'Rp ' . number_format($transaksi->kembalian ?? 0, 0, ',', '.'),
    'items' => $transaksi->detail->map(function ($item) {
      return [
        'nama' => $item->nama_menu . ($item->nama_ukuran ? ' (' . $item->nama_ukuran . ')' : '') . ($item->opsi->isNotEmpty() ? ' + ' . $item->opsi->pluck('nama_topping')->implode(', ') : ''),
        'jumlah' => $item->jumlah,
        'harga' => 'Rp ' . number_format($item->harga_satuan, 0, ',', '.'),
        'subtotal' => 'Rp ' . number_format($item->subtotal_dengan_opsi, 0, ',', '.'),
      ];
    })->values(),
  ];
@endphp

<div class="rt-detail">

  <div class="rt-detail-top">
    <div>
      <small>Detail Transaksi</small>
      <h4>{{ $transaksi->no_transaksi }}</h4>
    </div>
    <button type="button" id="btn-tutup-detail" class="rt-close" aria-label="Tutup">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <div class="rt-meta">
    <div>
      <div class="k">Tanggal &amp; Waktu</div>
      <div class="v">{{ $transaksi->created_at->translatedFormat('d M Y, H:i') }}</div>
    </div>
    <div>
      <div class="k">Status</div>
      <div class="v"><span class="badge-table {{ $transaksi->status_badge_class }}">{{ $transaksi->status_label }}</span></div>
    </div>
    <div>
      <div class="k">Kasir</div>
      <div class="v">
        <span class="rt-detail-kasir">
          @if ($transaksi->kasir)
            <img src="{{ $transaksi->kasir->avatar_url }}" alt="">
          @endif
          {{ $transaksi->kasir->name ?? '-' }}
        </span>
      </div>
    </div>
    <div>
      <div class="k">Metode Bayar</div>
      <div class="v"><img src="{{ $transaksi->metode_bayar_logo }}" alt="">{{ $transaksi->metode_bayar_label }}</div>
    </div>
  </div>

  <div class="rt-items">
    @forelse ($transaksi->detail as $item)
      @php
        $bumbu = $item->opsi->where('jenis_topping', 'saus')->pluck('nama_topping');
        $topping = $item->opsi->where('jenis_topping', 'bubuk_tabur')->pluck('nama_topping');
      @endphp
      <div class="rt-item">
        <span class="rt-qty">{{ $item->jumlah }}x</span>
        <div class="rt-info">
          <div class="rt-nama">{{ $item->nama_menu }}</div>
          <div class="rt-harga">@ Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</div>

          <div class="rt-tags">
            @if ($item->nama_ukuran)
              <span class="rt-tag">{{ $item->nama_ukuran }}</span>
            @endif
            @if ($item->level_pedas !== 'tidak_pedas')
              <span class="rt-tag pedas"><i class="bi bi-fire"></i> {{ str_replace('_', ' ', ucfirst($item->level_pedas)) }}</span>
            @endif
            @foreach ($bumbu as $nama)
              <span class="rt-tag bumbu"><i class="bi bi-droplet-fill"></i> {{ $nama }}</span>
            @endforeach
            @foreach ($topping as $nama)
              <span class="rt-tag topping"><i class="bi bi-stars"></i> {{ $nama }}</span>
            @endforeach
            @if (! empty($item->pelengkap))
              <span class="rt-tag">{{ implode(', ', $item->pelengkap) }}</span>
            @endif
          </div>
        </div>
        <div class="rt-sub">Rp {{ number_format($item->subtotal_dengan_opsi, 0, ',', '.') }}</div>
      </div>
    @empty
      <div class="text-center py-4" style="color: var(--mo-muted); font-size: .85rem;">
        Tidak ada rincian item untuk transaksi ini.
      </div>
    @endforelse
  </div>

  <div class="rt-sum">
    <div class="rt-sum-row">
      <span>Jumlah Item</span>
      <span>{{ $transaksi->detail->sum('jumlah') }} porsi</span>
    </div>
    <div class="rt-sum-row">
      <span>Metode Bayar</span>
      <span>{{ $transaksi->metode_bayar_label }}</span>
    </div>
    <div class="rt-sum-row total">
      <span>Total</span>
      <span>Rp {{ number_format($transaksi->total, 0, ',', '.') }}</span>
    </div>
    @if ($transaksi->ada_rincian_tunai)
      <div class="rt-sum-row">
        <span>Uang Diterima</span>
        <span>Rp {{ number_format($transaksi->uang_diterima, 0, ',', '.') }}</span>
      </div>
    @endif
    <div class="rt-sum-row">
      <span>Kembalian</span>
      <span>Rp {{ number_format($transaksi->kembalian ?? 0, 0, ',', '.') }}</span>
    </div>
  </div>

  <div class="rt-actions">
    <button type="button" class="btn-custom btn-custom-outline-secondary btn-cetak-struk"
      data-struk-url="{{ route('admin.riwayat-transaksi.struk', $transaksi) }}">
      <i class="bi bi-printer"></i>
      <span>Cetak Struk</span>
    </button>
    <button type="button" class="btn-custom btn-custom-primary btn-simpan-pdf">
      <i class="bi bi-file-earmark-arrow-down"></i>
      <span>Simpan PDF</span>
    </button>
  </div>
</div>

<script type="application/json" id="data-transaksi-pdf">{!! json_encode($dataPdf) !!}</script>
