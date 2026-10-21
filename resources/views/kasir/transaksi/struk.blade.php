<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Struk {{ $transaksi->no_transaksi }}</title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: 'Courier New', Courier, monospace;
      background: #F4EDE9;
      margin: 0;
      padding: 20px 12px 40px;
      color: #111;
      font-size: 13px;
    }

    /* Kertas struk */
    .struk {
      width: 302px; /* ± 80mm */
      margin: 0 auto;
      background: #fff;
      padding: 18px 16px 22px;
      border-radius: 6px;
      box-shadow: 0 10px 30px rgba(60, 12, 12, .15);
    }

    .center { text-align: center; }
    .logo { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; display: block; margin: 0 auto 6px; }
    .toko-nama { font-size: 17px; font-weight: 700; letter-spacing: .02em; }
    .toko-sub { font-size: 11px; margin-top: 2px; color: #444; }

    hr { border: none; border-top: 1px dashed #555; margin: 10px 0; }
    hr.tebal { border-top: 2px solid #111; }

    .baris { display: flex; justify-content: space-between; gap: 10px; padding: 1px 0; }
    .baris span:last-child { text-align: right; }

    table { width: 100%; border-collapse: collapse; }
    td { padding: 2px 0; vertical-align: top; }
    .text-end { text-align: right; white-space: nowrap; }
    .item-nama { font-weight: 700; padding-top: 6px; }
    .kecil { font-size: 11px; color: #333; }

    .total-row td { font-weight: 700; font-size: 15px; padding-top: 4px; }
    .footer-note { margin-top: 12px; font-size: 11px; line-height: 1.5; }

    /* Bar tombol di atas struk (tidak ikut tercetak) */
    .btn-print-bar { width: 302px; margin: 0 auto 14px; display: flex; gap: 8px; }
    .btn-print-bar button {
      flex: 1; border: none; border-radius: 999px; padding: 10px 14px; font-size: 13px; font-weight: 700;
      font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; cursor: pointer;
      background: #8C1F1F; color: #fff; box-shadow: 0 6px 14px rgba(140, 31, 31, .3);
    }
    .btn-print-bar button:hover { background: #B23A3A; }
    .btn-print-bar button.secondary { background: #fff; color: #8C1F1F; box-shadow: none; border: 1.5px solid #E7D5CE; }

    @page { size: 80mm auto; margin: 0; }
    @media print {
      body { background: #fff; padding: 0; }
      .btn-print-bar { display: none; }
      .struk { box-shadow: none; border-radius: 0; width: 100%; padding: 8px 10px; }
    }
  </style>
</head>
<body>

  <div class="btn-print-bar">
    <button type="button" onclick="window.print()">🖨️ Cetak Sekarang</button>
    <button type="button" class="secondary" onclick="window.close()">Tutup Tab</button>
  </div>

  <div class="struk">

    <div class="center">
      <img class="logo" src="{{ asset('assets_dashboard/images/logo3.png') }}" alt="" onerror="this.remove()">
      <div class="toko-nama">Cimol Bojot AA</div>
      <div class="toko-sub">Struk Transaksi</div>
    </div>

    <hr>

    <div class="baris"><span>No</span><span>{{ $transaksi->no_transaksi }}</span></div>
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
        <tr>
          <td>{{ $item->jumlah }} x Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
          <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if ($item->level_pedas !== 'tidak_pedas' || !empty($item->pelengkap))
          <tr>
            <td colspan="2" class="kecil">
              {{ $item->level_pedas !== 'tidak_pedas' ? str_replace('_', ' ', ucfirst($item->level_pedas)) : '' }}
              {{ !empty($item->pelengkap) ? implode(', ', $item->pelengkap) : '' }}
            </td>
          </tr>
        @endif
        @foreach ($item->opsi as $opsi)
          <tr>
            <td class="kecil">+ {{ $opsi->nama_topping }}</td>
            <td class="text-end kecil">Rp {{ number_format($opsi->harga_tambahan, 0, ',', '.') }}</td>
          </tr>
        @endforeach
      @empty
        <tr><td colspan="2">Tidak ada rincian item.</td></tr>
      @endforelse
    </table>

    <hr class="tebal">

    <table>
      <tr class="total-row">
        <td>TOTAL</td>
        <td class="text-end">Rp {{ number_format($transaksi->total, 0, ',', '.') }}</td>
      </tr>
      <tr>
        <td>Metode Bayar</td>
        <td class="text-end">{{ $transaksi->metode_bayar_label }}</td>
      </tr>
      <tr>
        <td>Kembalian</td>
        <td class="text-end">Rp 0</td>
      </tr>
    </table>

    <hr>

    <div class="center footer-note">
      Terima kasih sudah berbelanja di<br><b>Cimol Bojot AA</b>!<br>
      Ditunggu pesanan berikutnya 🙌
    </div>

  </div>

  <script>
    window.onload = function () {
      window.print();
    };
  </script>
</body>
</html>
