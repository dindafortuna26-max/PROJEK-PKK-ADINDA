@extends('layouts.admin')

@section('title', 'Cek Stok - ' . config('app.name', 'Cimol Bojot AA'))

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/kasir-modern.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/cimol-plus.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/kasir-polish.css') }}?v={{ @filemtime(public_path('assets_dashboard/css/kasir-polish.css')) }}">
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/bahan-operasional.css') }}?v={{ @filemtime(public_path('assets_dashboard/css/bahan-operasional.css')) }}">
@endpush

@php
  // Label & kelas tampilan per status (dipakai bareng tab Menu dan tab Topping & Bumbu)
  $labelStatus = ['aman' => 'Aman', 'menipis' => 'Menipis', 'habis' => 'Habis', 'belum_dikelola' => 'Belum Dikelola'];
  $kelasPill = ['aman' => 'ok', 'menipis' => 'warn', 'habis' => 'bad', 'belum_dikelola' => 'neutral'];

  // Lebar bar stok: cuma bantuan visual. Penuh kalau stok >= 3x batas minimum.
  $lebarBar = function ($stok, $minimum, $status) {
    if ($status === 'habis') return 4;
    if ($status === 'belum_dikelola') return 0;
    $acuan = max((int) $minimum * 3, 1);
    return max(8, min(100, (int) round(($stok / $acuan) * 100)));
  };
@endphp

@php
  // Ringkasan 5 pergerakan stok terakhir (hanya untuk tampilan detail, read-only).
  $riwayatStok = function ($relasi) {
    return $relasi->latest('id')->limit(5)->get()->map(function ($r) {
      return [
        'tgl' => $r->tanggal ? $r->tanggal->translatedFormat('d M Y') : '-',
        'jenis' => $r->jenis,
        'jumlah' => (int) $r->jumlah,
        'ket' => $r->keterangan,
      ];
    })->values()->all();
  };

  $detailMenu = function ($item) use ($labelStatus, $lebarBar, $riwayatStok) {
    $menu = $item->menu;

    return [
      'tipe' => 'menu',
      'nama' => $menu->nama,
      'foto' => $menu->gambar ? asset('storage/' . $menu->gambar) : asset('assets_dashboard/images/logo3.png'),
      'label' => $menu->kategori->nama ?? 'Tanpa kategori',
      'deskripsi' => $menu->deskripsi,
      'status' => $item->status,
      'statusLabel' => $labelStatus[$item->status],
      'stok' => (int) $item->stok,
      'satuan' => 'porsi',
      'minimum' => (int) $menu->stok_minimum,
      'persen' => $lebarBar($item->stok, $menu->stok_minimum, $item->status),
      'terakhir' => $item->terakhir_masuk
        ? $item->terakhir_masuk->tanggal->translatedFormat('d M Y') . ' (+' . $item->terakhir_masuk->jumlah . ')'
        : null,
      'harga' => $menu->ukuranAktif->map(fn ($u) => ['nama' => $u->nama, 'harga' => (int) $u->pivot->harga])->values()->all(),
      'riwayat' => $riwayatStok($menu->pergerakanStok()),
    ];
  };

  $detailTopping = function ($item) use ($labelStatus, $lebarBar, $riwayatStok) {
    $topping = $item->topping;

    return [
      'tipe' => 'topping',
      'nama' => $topping->nama,
      'foto' => $topping->gambar_url,
      'label' => $topping->jenis_label,
      'ikon' => $topping->jenis === 'saus' ? 'bi-droplet-fill' : 'bi-stars',
      'deskripsi' => null,
      'status' => $item->status,
      'statusLabel' => $labelStatus[$item->status],
      'stok' => (int) $item->stok,
      'satuan' => 'stok',
      'minimum' => (int) $topping->stok_minimum,
      'persen' => $lebarBar($item->stok, $topping->stok_minimum, $item->status),
      'terakhir' => $item->terakhir_masuk
        ? $item->terakhir_masuk->tanggal->translatedFormat('d M Y') . ' (+' . $item->terakhir_masuk->jumlah . ')'
        : null,
      'hargaTambahan' => (int) $topping->harga_tambahan,
      'riwayat' => $riwayatStok($topping->pergerakanStok()),
    ];
  };

  $detailBahan = function ($item) use ($labelStatus) {
    $b = $item->bahan;
    $dikelola = $item->status !== 'belum_dikelola';

    $riwayat = $b->pergerakanStok()->latest('id')->limit(5)->get()->map(function ($r) {
      return [
        'tgl' => $r->tanggal ? $r->tanggal->translatedFormat('d M Y') : '-',
        'jenis' => $r->jenis,
        'jumlah' => (int) $r->sesi,
        'unit' => 'sesi',
        'label' => $r->jenis === 'masuk' ? 'Stok masuk' : ($r->transaksi_id ? 'Terpakai otomatis' : 'Koreksi stok'),
        'ket' => $r->keterangan,
      ];
    })->values()->all();

    return [
      'tipe' => 'bahan',
      'nama' => $b->nama,
      'foto' => $b->gambar_url,
      'label' => 'Bahan Operasional',
      'ikon' => $b->ikon,
      'deskripsi' => 'Stok berkurang otomatis 1 sesi goreng tiap transaksi selesai. Kasir tidak perlu mengurangi manual.',
      'status' => $item->status,
      'statusLabel' => $labelStatus[$item->status],
      'stok' => $dikelola ? (float) $b->stok_saat_ini : 0,
      'satuan' => $b->satuan,
      'persen' => $dikelola ? ($item->status === 'habis' ? 4 : (int) $b->persen_berjalan) : 0,
      'tiles' => [
        ['Sisa sesi goreng', $dikelola ? number_format($item->stok, 0, ',', '.') . ' kali' : '-'],
        ['Batas menipis', (int) $b->stok_minimum . ' sesi'],
        ['Setara', '1 ' . $b->satuan . ' = ' . $b->sesi_per_satuan . ' sesi'],
        ['Stok masuk terakhir', $item->terakhir_masuk
          ? $item->terakhir_masuk->tanggal->translatedFormat('d M Y') . ' (+' . ($item->terakhir_masuk->jumlah ?: $item->terakhir_masuk->sesi) . ' ' . ($item->terakhir_masuk->jumlah ? $b->satuan : 'sesi') . ')'
          : 'Belum ada'],
      ],
      'riwayat' => $riwayat,
    ];
  };
@endphp

@section('content')
<div class="km">

  <div class="hero kp-hero">
    <div>
      <span class="hero-eyebrow"><i class="bi bi-boxes"></i> Kasir</span>
      <h1>Cek Stok</h1>
      <p>Lihat sisa stok menu, bumbu, dan topping. Stok minyak goreng &amp; gas berkurang otomatis tiap transaksi, jadi tinggal dipantau di tab Minyak &amp; Gas. Mau tambah stok? Minta Admin ya.</p>
    </div>
    <div class="kp-hero-steps">
      <span><b>{{ number_format($ringkasan['aman'], 0, ',', '.') }}</b> Aman</span>
      <span><b>{{ number_format($ringkasan['menipis'], 0, ',', '.') }}</b> Menipis</span>
      <span><b>{{ number_format($ringkasan['habis'], 0, ',', '.') }}</b> Habis</span>
    </div>
  </div>

  <!-- ===================== KARTU RINGKASAN ===================== -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="km-card km-stat tone-ok">
        <span class="km-stat-icon"><i class="bi bi-check-circle-fill"></i></span>
        <div>
          <div class="km-stat-label">Stok Aman</div>
          <div class="km-stat-value">{{ number_format($ringkasan['aman'], 0, ',', '.') }}</div>
          <div class="km-stat-note">total kuantitas barang</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="km-card km-stat tone-warn">
        <span class="km-stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></span>
        <div>
          <div class="km-stat-label">Stok Menipis</div>
          <div class="km-stat-value">{{ number_format($ringkasan['menipis'], 0, ',', '.') }}</div>
          <div class="km-stat-note">segera minta restok ke Admin</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="km-card km-stat tone-bad">
        <span class="km-stat-icon"><i class="bi bi-x-circle-fill"></i></span>
        <div>
          <div class="km-stat-label">Stok Habis</div>
          <div class="km-stat-value">{{ number_format($ringkasan['habis'], 0, ',', '.') }}</div>
          <div class="km-stat-note">produk tidak bisa dijual</div>
        </div>
      </div>
    </div>
  </div>

  <div class="km-card km-card-flush">

    <!-- Tab + Cari + Filter status -->
    <div class="stok-toolbar">
      <div class="km-seg" role="tablist" aria-label="Tab Cek Stok">
        <button type="button" id="tab-btn-data-stok" class="active" onclick="tampilkanTabStok('data-stok')">
          <i class="bi bi-box-seam"></i><span>Menu</span>
        </button>
        <button type="button" id="tab-btn-topping" onclick="tampilkanTabStok('topping')">
          <i class="bi bi-droplet-fill"></i><span>Bumbu &amp; Topping</span>
        </button>
        <button type="button" id="tab-btn-bahan" onclick="tampilkanTabStok('bahan')">
          <i class="bi bi-fire"></i><span>Minyak &amp; Gas</span>
          @if ($perhatianBahan > 0)
            <span class="km-pill bad" style="padding:.1rem .5rem; margin-left:.25rem;">{{ $perhatianBahan }}</span>
          @endif
        </button>
      </div>

      <form action="{{ route('kasir.stok.index') }}" method="GET" class="d-flex flex-wrap gap-2 flex-grow-1 align-items-center">
        <div class="km-search">
          <i class="bi bi-search"></i>
          <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari nama barang...">
        </div>
        @if (request('cari'))
          <a href="{{ route('kasir.stok.index') }}" class="km-btn km-btn-ghost km-btn-sm">
            <i class="bi bi-x-lg"></i><span>Reset</span>
          </a>
        @endif
      </form>
    </div>

    <div class="px-3 pt-3" id="filter-wrap">
      <div class="km-chips" id="filter-status">
        <button type="button" class="km-chip active" data-status="" onclick="filterStatus(this, '')">Semua</button>
        <button type="button" class="km-chip" data-status="aman" onclick="filterStatus(this, 'aman')">Aman</button>
        <button type="button" class="km-chip" data-status="menipis" onclick="filterStatus(this, 'menipis')">Menipis</button>
        <button type="button" class="km-chip" data-status="habis" onclick="filterStatus(this, 'habis')">Habis</button>
      </div>
    </div>

    <!-- ===================== TAB: MENU (read-only) ===================== -->
    <div id="tab-pane-data-stok">
      <div class="stok-grid">
        @forelse ($dataStok as $item)
          <div class="stok-card st-{{ $item->status }}" data-status="{{ $item->status }}" data-detail="{{ json_encode($detailMenu($item), JSON_UNESCAPED_UNICODE) }}" role="button" tabindex="0" title="Lihat detail" onclick="bukaDetailStok(this)" onkeydown="if (event.key === 'Enter') { bukaDetailStok(this); }">
            <div class="stok-top">
              <img class="stok-foto"
                src="{{ $item->menu->gambar ? asset('storage/' . $item->menu->gambar) : asset('assets_dashboard/images/logo3.png') }}"
                alt="{{ $item->menu->nama }}" loading="lazy">
              <div class="flex-grow-1" style="min-width:0;">
                <p class="stok-nama">{{ $item->menu->nama }}</p>
                <span class="stok-kat">{{ $item->menu->kategori->nama ?? 'Tanpa kategori' }}</span>
              </div>
              <span class="km-pill {{ $kelasPill[$item->status] }}">{{ $labelStatus[$item->status] }}</span>
            </div>

            <div>
              <div class="stok-mid mb-2">
                <div class="stok-angka">{{ number_format($item->stok, 0, ',', '.') }}<small>porsi</small></div>
                <span class="stok-kat">Batas minimum: <b>{{ number_format($item->menu->stok_minimum, 0, ',', '.') }}</b></span>
              </div>
              <div class="stok-meter"><i style="width: {{ $lebarBar($item->stok, $item->menu->stok_minimum, $item->status) }}%"></i></div>
            </div>

            <div class="stok-foot">
              <span>Stok masuk terakhir</span>
              @if ($item->terakhir_masuk)
                <b>{{ $item->terakhir_masuk->tanggal->translatedFormat('d M Y') }} (+{{ $item->terakhir_masuk->jumlah }})</b>
              @else
                <span>Belum ada</span>
              @endif
            </div>
          </div>
        @empty
          <div class="stok-empty" style="grid-column: 1 / -1;">
            <i class="bi bi-inbox"></i>
            {{ request('cari') ? 'Tidak ada menu yang cocok dengan pencarian.' : 'Belum ada menu.' }}
          </div>
        @endforelse
      </div>

      <div class="stok-empty d-none" data-kosong-filter>
        <i class="bi bi-funnel"></i> Tidak ada barang dengan status ini.
      </div>

      <div class="stok-foot-bar">
        <span>Menampilkan {{ $dataStok->count() }} dari {{ $dataStok->total() }} menu</span>
        {{ $dataStok->links() }}
      </div>
    </div>

    <!-- ===================== TAB: BUMBU & TOPPING (read-only) ===================== -->
    <div id="tab-pane-topping" class="d-none">
      <div class="stok-grid">
        @forelse ($dataStokTopping as $item)
          @php $adalahBumbu = $item->topping->jenis === 'saus'; @endphp
          <div class="stok-card st-{{ $item->status }}" data-status="{{ $item->status }}" data-detail="{{ json_encode($detailTopping($item), JSON_UNESCAPED_UNICODE) }}" role="button" tabindex="0" title="Lihat detail" onclick="bukaDetailStok(this)" onkeydown="if (event.key === 'Enter') { bukaDetailStok(this); }">
            <div class="stok-top">
              @include('partials.thumb', ['src' => $item->topping->gambar_url, 'icon' => $adalahBumbu ? 'bi-droplet-fill' : 'bi-stars', 'size' => 56, 'alt' => $item->topping->nama])
              <div class="flex-grow-1" style="min-width:0;">
                <p class="stok-nama">{{ $item->topping->nama }}</p>
                <span class="stok-jenis {{ $adalahBumbu ? 'bumbu' : 'topping' }}">{{ $item->topping->jenis_label }}</span>
              </div>
              <span class="km-pill {{ $kelasPill[$item->status] }}">{{ $labelStatus[$item->status] }}</span>
            </div>

            <div>
              <div class="stok-mid mb-2">
                <div class="stok-angka">{{ number_format($item->stok, 0, ',', '.') }}<small>stok</small></div>
                <span class="stok-kat">Batas minimum: <b>{{ number_format($item->topping->stok_minimum, 0, ',', '.') }}</b></span>
              </div>
              <div class="stok-meter"><i style="width: {{ $lebarBar($item->stok, $item->topping->stok_minimum, $item->status) }}%"></i></div>
            </div>

            <div class="stok-foot">
              <span>Stok masuk terakhir</span>
              @if ($item->terakhir_masuk)
                <b>{{ $item->terakhir_masuk->tanggal->translatedFormat('d M Y') }} (+{{ $item->terakhir_masuk->jumlah }})</b>
              @else
                <span>Belum ada</span>
              @endif
            </div>
          </div>
        @empty
          <div class="stok-empty" style="grid-column: 1 / -1;">
            <i class="bi bi-inbox"></i>
            {{ request('cari') ? 'Tidak ada bumbu/topping yang cocok dengan pencarian.' : 'Belum ada bumbu atau topping.' }}
          </div>
        @endforelse
      </div>

      <div class="stok-empty d-none" data-kosong-filter>
        <i class="bi bi-funnel"></i> Tidak ada barang dengan status ini.
      </div>

      <div class="stok-foot-bar">
        <span>Menampilkan {{ $dataStokTopping->count() }} dari {{ $dataStokTopping->total() }} item</span>
        {{ $dataStokTopping->links() }}
      </div>
    </div>


    <!-- ===================== TAB: BAHAN OPERASIONAL (minyak goreng, gas) ===================== -->
    <div id="tab-pane-bahan" class="d-none">

      @if ($perhatianBahan > 0)
        <div class="bo-banner">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span>{{ $perhatianBahan }} bahan menipis atau habis. Kabari Admin supaya segera restock.</span>
        </div>
      @endif

      <div class="bo-grid">
        @forelse ($dataStokBahan as $item)
          @php
            $b = $item->bahan;
            $status = $item->status;
            $dikelola = $status !== 'belum_dikelola';
          @endphp
          <div class="bo-card bo-klik st-{{ $status }}" data-detail="{{ json_encode($detailBahan($item), JSON_UNESCAPED_UNICODE) }}" role="button" tabindex="0" title="Lihat detail" onclick="bukaDetailStok(this)" onkeydown="if (event.key === 'Enter') { bukaDetailStok(this); }">
            <div class="bo-card-top">
              @include('partials.thumb', ['src' => $b->gambar_url, 'icon' => $b->ikon, 'size' => 56, 'alt' => $b->nama])
              <div>
                <p class="bo-nama">{{ $b->nama }}</p>
                <span class="bo-sub">1 {{ $b->satuan }} = {{ $b->sesi_per_satuan }} kali goreng (transaksi)</span>
              </div>
              <span class="bo-pill {{ $status }}">{{ \App\Models\BahanOperasional::labelStatus($status) }}</span>
            </div>

            @if ($dikelola)
              <div>
                <div class="bo-meter-label">
                  <span>{{ $b->satuan }} yang sedang dipakai: <b>{{ $b->sesi_berjalan }}</b> dari {{ $b->sesi_per_satuan }} sesi</span>
                  <span>{{ $b->persen_berjalan }}%</span>
                </div>
                <div class="bo-meter"><i style="width: {{ $b->persen_berjalan }}%"></i></div>
              </div>
            @endif

            <div class="bo-tiles">
              <div class="bo-tile"><span>Sisa stok</span><b>{{ $dikelola ? $b->labelStok() : '-' }}</b></div>
              <div class="bo-tile"><span>Sisa sesi goreng</span><b>{{ $dikelola ? number_format($item->stok, 0, ',', '.') . ' kali' : '-' }}</b></div>
            </div>

            @if ($status === 'habis')
              <div class="bo-alert bad"><i class="bi bi-x-octagon-fill"></i><span>{{ $b->nama }} sudah habis. Segera kabari Admin untuk restock.</span></div>
            @elseif ($status === 'menipis')
              <div class="bo-alert warn"><i class="bi bi-exclamation-triangle-fill"></i><span>Tinggal {{ $item->stok }} sesi goreng ({{ $b->labelStok() }}). Minta Admin restock sebelum habis.</span></div>
            @elseif ($status === 'belum_dikelola')
              <div class="bo-alert warn"><i class="bi bi-info-circle-fill"></i><span>Belum ada stok. Minta Admin menambahkan stok dulu.</span></div>
            @else
              <div class="bo-alert ok"><i class="bi bi-check-circle-fill"></i><span>Aman. Stok berkurang otomatis tiap transaksi selesai.</span></div>
            @endif

            <span class="bo-lihat"><i class="bi bi-info-circle"></i> Ketuk untuk lihat detail</span>
          </div>
        @empty
          <div class="bo-empty">
            <i class="bi bi-fire"></i>
            {{ request('cari') ? 'Tidak ada bahan operasional yang cocok dengan pencarian.' : 'Belum ada bahan operasional. Minta Admin menambahkan minyak goreng dan gas.' }}
          </div>
        @endforelse
      </div>
    </div>
  </div>

</div>

  <div class="sd-overlay" id="sd-overlay" aria-hidden="true">
    <div class="sd-sheet" role="dialog" aria-modal="true" aria-labelledby="sd-nama">
      <button type="button" class="sd-close" onclick="tutupDetailStok()" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
      <div class="sd-hero" id="sd-hero"></div>
      <div class="sd-body" id="sd-body"></div>
      <div class="sd-foot">
        <button type="button" class="sd-btn" onclick="tutupDetailStok()">Tutup</button>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    let statusAktif = '';

    function tampilkanTabStok(tab) {
      const panes = {
        'data-stok': document.getElementById('tab-pane-data-stok'),
        'topping': document.getElementById('tab-pane-topping'),
        'bahan': document.getElementById('tab-pane-bahan'),
      };
      const btns = {
        'data-stok': document.getElementById('tab-btn-data-stok'),
        'topping': document.getElementById('tab-btn-topping'),
        'bahan': document.getElementById('tab-btn-bahan'),
      };

      if (!panes[tab]) tab = 'data-stok';

      Object.keys(panes).forEach((key) => {
        const aktif = key === tab;
        panes[key].classList.toggle('d-none', !aktif);
        btns[key].classList.toggle('active', aktif);
      });

      // Filter status (Aman/Menipis/Habis) hanya untuk Menu & Bumbu/Topping.
      const filterWrap = document.getElementById('filter-wrap');
      if (filterWrap) filterWrap.classList.toggle('d-none', tab === 'bahan');

      localStorage.setItem('tabCekStokAktif', tab);
      terapkanFilterStatus();
    }

    // Filter status jalan di sisi browser untuk kartu yang sedang tampil di halaman ini.
    function filterStatus(btn, status) {
      statusAktif = status;
      document.querySelectorAll('#filter-status .km-chip').forEach((el) => el.classList.remove('active'));
      btn.classList.add('active');
      terapkanFilterStatus();
    }

    function terapkanFilterStatus() {
      ['tab-pane-data-stok', 'tab-pane-topping'].forEach((idPane) => {
        const pane = document.getElementById(idPane);
        let adaYangTampil = false;
        let adaKartu = false;

        pane.querySelectorAll('.stok-card').forEach((kartu) => {
          adaKartu = true;
          const tampil = !statusAktif || kartu.dataset.status === statusAktif;
          kartu.classList.toggle('d-none', !tampil);
          if (tampil) adaYangTampil = true;
        });

        const pesanKosong = pane.querySelector('[data-kosong-filter]');
        if (pesanKosong) pesanKosong.classList.toggle('d-none', !adaKartu || adaYangTampil);
      });
    }

    document.addEventListener('DOMContentLoaded', function () {
      const tabTersimpan = localStorage.getItem('tabCekStokAktif');
      if (tabTersimpan && tabTersimpan !== 'data-stok') {
        tampilkanTabStok(tabTersimpan);
      }
    });
  </script>
@endpush

@push('scripts')
  <script>
    // ===== Detail stok (read-only): klik kartu menu / topping =====
    function escStok(t) {
      const d = document.createElement('div');
      d.textContent = t == null ? '' : String(t);
      return d.innerHTML;
    }
    function rupiahStok(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); }

    function bukaDetailStok(el) {
      let d;
      try { d = JSON.parse(el.dataset.detail); } catch (e) { return; }

      const tone = { aman: 'ok', menipis: 'warn', habis: 'bad', belum_dikelola: 'neutral' }[d.status] || 'neutral';
      const foto = d.foto
        ? '<img class="sd-foto" src="' + escStok(d.foto) + '" alt="" onerror="this.style.display=\'none\'">'
        : '<span class="sd-foto sd-foto-ikon"><i class="bi ' + escStok(d.ikon || 'bi-box-seam') + '"></i></span>';

      document.getElementById('sd-hero').className = 'sd-hero tone-' + tone;
      document.getElementById('sd-hero').innerHTML =
        foto +
        '<div class="sd-hero-teks">' +
          '<span class="sd-chip">' + escStok(d.label) + '</span>' +
          '<h3 id="sd-nama">' + escStok(d.nama) + '</h3>' +
          '<span class="sd-status ' + tone + '">' + escStok(d.statusLabel) + '</span>' +
        '</div>';

      // Kartu kecil di bawah angka stok. Bahan operasional punya isi sendiri (sesi goreng, setara, dll).
      const tilesHtml = d.tiles
        ? d.tiles.map(function (t) { return '<div class="sd-tile"><span>' + escStok(t[0]) + '</span><b>' + escStok(t[1]) + '</b></div>'; }).join('')
        : '<div class="sd-tile"><span>Batas minimum</span><b>' + Number(d.minimum).toLocaleString('id-ID') + '</b></div>' +
          '<div class="sd-tile"><span>Stok masuk terakhir</span><b>' + (d.terakhir ? escStok(d.terakhir) : 'Belum ada') + '</b></div>';

      let html =
        '<div class="sd-stok">' +
          '<div><div class="sd-label">Sisa stok</div>' +
          '<div class="sd-angka">' + Number(d.stok).toLocaleString('id-ID') + '<small>' + escStok(d.satuan) + '</small></div></div>' +
          '<div class="sd-meter ' + tone + '"><i style="width:' + Number(d.persen || 0) + '%"></i></div>' +
        '</div>' +
        '<div class="sd-tiles">' + tilesHtml + '</div>';

      if (d.deskripsi) {
        html += '<div class="sd-section"><div class="sd-label">Deskripsi</div><p class="sd-desk">' + escStok(d.deskripsi) + '</p></div>';
      }

      if (d.tipe === 'menu' && d.harga && d.harga.length) {
        html += '<div class="sd-section"><div class="sd-label">Harga per ukuran</div><div class="sd-harga">' +
          d.harga.map(function (u) { return '<span><em>' + escStok(u.nama) + '</em><b>' + rupiahStok(u.harga) + '</b></span>'; }).join('') +
          '</div></div>';
      }
      if (d.tipe === 'topping') {
        html += '<div class="sd-section"><div class="sd-label">Harga tambahan</div><div class="sd-harga"><span><em>Per porsi</em><b>' +
          (d.hargaTambahan > 0 ? '+ ' + rupiahStok(d.hargaTambahan) : 'Gratis') + '</b></span></div></div>';
      }

      html += '<div class="sd-section"><div class="sd-label">Pergerakan stok terakhir</div>';
      if (d.riwayat && d.riwayat.length) {
        html += '<ul class="sd-riwayat">' + d.riwayat.map(function (r) {
          const masuk = r.jenis === 'masuk';
          return '<li><span class="sd-rik ' + (masuk ? 'in' : 'out') + '"><i class="bi ' + (masuk ? 'bi-arrow-down-left' : 'bi-arrow-up-right') + '"></i></span>' +
            '<div><b>' + escStok(r.label || (masuk ? 'Stok masuk' : 'Terjual / keluar')) + '</b><small>' + escStok(r.tgl) + (r.ket ? ' • ' + escStok(r.ket) : '') + '</small></div>' +
            '<strong class="' + (masuk ? 'in' : 'out') + '">' + (masuk ? '+' : '-') + Number(r.jumlah).toLocaleString('id-ID') + (r.unit ? ' ' + escStok(r.unit) : '') + '</strong></li>';
        }).join('') + '</ul>';
      } else {
        html += '<p class="sd-desk">Belum ada pergerakan stok.</p>';
      }
      html += '</div>';

      document.getElementById('sd-body').innerHTML = html;
      const ov = document.getElementById('sd-overlay');
      ov.classList.add('show');
      ov.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function tutupDetailStok() {
      const ov = document.getElementById('sd-overlay');
      ov.classList.remove('show');
      ov.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }
    document.getElementById('sd-overlay').addEventListener('click', function (e) {
      if (e.target === this) tutupDetailStok();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') tutupDetailStok();
    });
  </script>
@endpush
