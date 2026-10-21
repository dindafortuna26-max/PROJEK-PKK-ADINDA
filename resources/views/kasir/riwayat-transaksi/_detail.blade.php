<div class="rk-receipt">

  <div class="rk-r-top">
    <div>
      <small>Struk Transaksi</small>
      <h4>{{ $transaksi->no_transaksi }}</h4>
    </div>
    <button type="button" id="btn-tutup-detail" class="rk-r-close" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
  </div>

  <div class="rk-r-meta">
    <div>
      <div class="k">Tanggal &amp; Waktu</div>
      <div class="v">{{ $transaksi->created_at->translatedFormat('d M Y, H:i') }}</div>
    </div>
    <div>
      <div class="k">Status</div>
      <div class="v"><span class="rk-status {{ $transaksi->status === 'selesai' ? '' : 'bad' }}">{{ $transaksi->status_label }}</span></div>
    </div>
    <div>
      <div class="k">Metode Bayar</div>
      <div class="v"><span class="rk-r-pay"><img src="{{ $transaksi->metode_bayar_logo }}" alt="">{{ $transaksi->metode_bayar_label }}</span></div>
    </div>
    <div>
      <div class="k">Jumlah Item</div>
      <div class="v">{{ $transaksi->detail->sum('jumlah') }} porsi</div>
    </div>
  </div>

  <div class="rk-r-items">
    @forelse ($transaksi->detail as $item)
      @php
        $bumbu = $item->opsi->where('jenis_topping', 'saus')->pluck('nama_topping');
        $topping = $item->opsi->where('jenis_topping', 'bubuk_tabur')->pluck('nama_topping');
        $catatan = [];
        if ($item->level_pedas !== 'tidak_pedas') {
          $catatan[] = str_replace('_', ' ', ucfirst($item->level_pedas));
        }
        if (! empty($item->pelengkap)) {
          $catatan[] = implode(', ', $item->pelengkap);
        }
      @endphp
      <div class="rk-r-item">
        <span class="rk-r-qty">{{ $item->jumlah }}x</span>
        <div>
          <div class="rk-r-nama">{{ $item->nama_menu }}</div>
          <div class="rk-tags">
            @if ($item->nama_ukuran)
              <span class="rk-tag">{{ $item->nama_ukuran }}</span>
            @endif
            @foreach ($bumbu as $nama)
              <span class="rk-tag"><i class="bi bi-droplet-fill"></i> {{ $nama }}</span>
            @endforeach
            @foreach ($topping as $nama)
              <span class="rk-tag"><i class="bi bi-stars"></i> {{ $nama }}</span>
            @endforeach
          </div>
          @if (! empty($catatan))
            <div class="rk-r-note">{{ implode(' · ', $catatan) }}</div>
          @endif
        </div>
        <div class="rk-r-sub">Rp {{ number_format($item->subtotal_dengan_opsi, 0, ',', '.') }}</div>
      </div>
    @empty
      <div class="rk-empty" style="border:0; padding: 1.5rem 0;">Tidak ada rincian item untuk transaksi ini.</div>
    @endforelse
  </div>

  <div class="rk-r-total">
    <span>Total</span>
    <span>Rp {{ number_format($transaksi->total, 0, ',', '.') }}</span>
  </div>

  @if ($transaksi->ada_rincian_tunai)
    <div class="rk-r-cash"><span>Uang Diterima</span><b>Rp {{ number_format($transaksi->uang_diterima, 0, ',', '.') }}</b></div>
    <div class="rk-r-cash"><span>Kembalian</span><b>Rp {{ number_format($transaksi->kembalian ?? 0, 0, ',', '.') }}</b></div>
  @endif

  <div class="rk-r-actions">
    <button type="button" class="rk-btn btn-cetak-struk" data-struk-url="{{ route('kasir.transaksi.struk', $transaksi) }}">
      <i class="bi bi-printer-fill"></i>
      <span>Cetak Struk</span>
    </button>
  </div>
</div>
