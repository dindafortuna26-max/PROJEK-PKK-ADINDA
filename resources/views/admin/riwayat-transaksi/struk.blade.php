<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Struk {{ $transaksi->no_transaksi }}</title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: 'Courier New', Courier, monospace;
      width: 300px;
      margin: 16px auto;
      color: #111;
      font-size: 13px;
    }
    .center { text-align: center; }
    .toko-nama { font-size: 16px; font-weight: 700; margin-bottom: 2px; }
    .toko-sub { font-size: 11px; margin-bottom: 10px; }
    hr { border: none; border-top: 1px dashed #444; margin: 8px 0; }
    .baris { display: flex; justify-content: space-between; gap: 8px; }
    .item-nama { flex: 1; }
    table { width: 100%; border-collapse: collapse; margin: 6px 0; }
    td { padding: 2px 0; vertical-align: top; }
    .text-end { text-align: right; }
    .total-row { font-weight: 700; font-size: 14px; }
    .footer-note { margin-top: 14px; font-size: 11px; }
    .btn-print-bar { text-align: center; margin-bottom: 14px; }
    .btn-print-bar button {
      background-color: #8C1F1F; color: #fff; border: none; border-radius: 6px;
      padding: 8px 18px; font-size: 13px; cursor: pointer;
    }
    @media print {
      .btn-print-bar { display: none; }
      body { margin: 0 auto; }
    }
  </style>
</head>
<body>

  <div class="btn-print-bar">
    <button type="button" onclick="window.print()">🖨️ Cetak Sekarang</button>
  </div>

  <div class="center">
    <div class="toko-nama">Cimol Bojot AA</div>
    <div class="toko-sub">Struk Transaksi</div>
  </div>

  <hr>

  <div class="baris"><span>No Transaksi</span><span>{{ $transaksi->no_transaksi }}</span></div>
  <div class="baris"><span>Tanggal</span><span>{{ $transaksi->created_at->translatedFormat('d M Y H:i') }}</span></div>
  <div class="baris"><span>Kasir</span><span>{{ $transaksi->kasir->name ?? '-' }}</span></div>
  <div class="baris"><span>Status</span><span>{{ $transaksi->status_label }}</span></div>

  <hr>

  <table>
    @forelse ($transaksi->detail as $item)
      <tr>
        <td colspan="2" class="item-nama">
          {{ $item->nama_menu }}{{ $item->nama_ukuran ? ' (' . $item->nama_ukuran . ')' : '' }}
        </td>
      </tr>
      @if ($item->level_pedas && $item->level_pedas !== 'tidak_pedas')
        <tr>
          <td colspan="2" style="font-size: 11px; color: #444; padding-top:0;">
            Pedas: {{ str_replace('_', ' ', ucfirst($item->level_pedas)) }}
          </td>
        </tr>
      @endif
      @if ($item->opsi->isNotEmpty())
        <tr>
          <td colspan="2" style="font-size: 11px; color: #444; padding-top:0;">
            + {{ $item->opsi->pluck('nama_topping')->implode(', ') }}
          </td>
        </tr>
      @endif
      <tr>
        <td>{{ $item->jumlah }} x Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
        <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
      </tr>
      @if ($item->total_opsi > 0)
        <tr>
          <td style="font-size: 11px;">Tambahan topping</td>
          <td class="text-end" style="font-size: 11px;">Rp {{ number_format($item->total_opsi, 0, ',', '.') }}</td>
        </tr>
      @endif
    @empty
      <tr><td colspan="2">Tidak ada rincian item.</td></tr>
    @endforelse
  </table>

  <hr>

  <table>
    <tr class="total-row">
      <td>TOTAL</td>
      <td class="text-end">Rp {{ number_format($transaksi->total, 0, ',', '.') }}</td>
    </tr>
    <tr>
      <td>Metode Bayar</td>
      <td class="text-end">{{ $transaksi->metode_bayar_label }}</td>
    </tr>
    @if ($transaksi->ada_rincian_tunai)
      <tr>
        <td>Uang Diterima</td>
        <td class="text-end">Rp {{ number_format($transaksi->uang_diterima, 0, ',', '.') }}</td>
      </tr>
    @endif
    <tr>
      <td>Kembalian</td>
      <td class="text-end">Rp {{ number_format($transaksi->kembalian ?? 0, 0, ',', '.') }}</td>
    </tr>
  </table>

  <hr>

  <div class="center footer-note">
    Terima kasih sudah berbelanja di<br>Cimol Bojot AA!
  </div>

  <script>
    window.onload = function () {
      window.print();
    };
  </script>
</body>
</html>
