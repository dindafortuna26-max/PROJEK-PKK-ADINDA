@extends('layouts.admin')

@section('title', 'Transaksi - ' . config('app.name', 'Cimol Bojot AA'))

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/kasir-modern.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/cimol-plus.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/kasir-polish.css') }}?v={{ @filemtime(public_path('assets_dashboard/css/kasir-polish.css')) }}">
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/kasir-level.css') }}?v={{ @filemtime(public_path('assets_dashboard/css/kasir-level.css')) }}">
  <style>
    .km .cash-box { background: #fff; border: 1.5px solid var(--km-line); border-radius: 14px; padding: .8rem; margin-bottom: .9rem; }
    .km .cash-input-wrap { display: flex; align-items: center; gap: .5rem; border: 1.5px solid var(--km-line); border-radius: 12px; padding: .1rem .75rem; background: var(--km-cream); }
    .km .cash-input-wrap:focus-within { border-color: var(--km-red); }
    .km .cash-input-wrap span { font-weight: 800; color: var(--km-muted); }
    .km .cash-input-wrap input { border: 0; outline: 0; background: transparent; width: 100%; font-weight: 800; font-size: 1.1rem; padding: .55rem 0; color: var(--km-ink); }
    .km .cash-quick { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .6rem; }
    .km .cash-quick button { border: 1.5px solid var(--km-line); background: #fff; border-radius: 999px; padding: .28rem .7rem; font-size: .76rem; font-weight: 700; color: var(--km-ink); cursor: pointer; }
    .km .cash-quick button:hover { border-color: var(--km-red); color: var(--km-red); }
    .km .cash-kembalian { display: flex; justify-content: space-between; align-items: baseline; margin-top: .75rem; padding-top: .7rem; border-top: 1px dashed var(--km-line); }
    .km .cash-kembalian span:first-child { font-size: .85rem; font-weight: 700; color: var(--km-muted); }
    .km .cash-kembalian span:last-child { font-size: 1.25rem; font-weight: 800; color: var(--km-ok); }
    .km .cash-kembalian.kurang span:last-child { color: var(--km-bad); font-size: 1rem; }
  </style>
@endpush

@section('content')
<div class="km">

  <div class="hero kp-hero">
    <div>
      <span class="hero-eyebrow"><i class="bi bi-cart-check-fill"></i> Kasir</span>
      <h1>Transaksi Baru</h1>
      <p>Pilih menu, atur ukuran, bumbu &amp; topping, lalu selesaikan pembayaran.</p>
    </div>
    <div class="kp-hero-steps">
      <span><b>1</b> Pilih menu</span>
      <span><b>2</b> Atur pesanan</span>
      <span><b>3</b> Bayar</span>
    </div>
  </div>

  @if (session('success'))
    <div class="alert alert-success mb-4" role="alert">{{ session('success') }}</div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger mb-4" role="alert">
      @foreach ($errors->all() as $pesan)
        <div>{{ $pesan }}</div>
      @endforeach
    </div>
  @endif

  <div class="row g-4 align-items-start">

    <!-- ===================== KOLOM KIRI: GRID MENU ===================== -->
    <div class="col-lg-8">
      <div class="km-card">

        <div class="d-flex flex-wrap gap-3 mb-3">
          <div class="km-search">
            <i class="bi bi-search"></i>
            <input type="text" id="input-cari-menu" placeholder="Cari menu favorit pembeli..." autocomplete="off">
          </div>
        </div>

        <div class="km-chips mb-4" id="filter-kategori">
          <button type="button" class="km-chip active filter-kategori-btn" data-kategori-id="" onclick="pilihKategori(this, '')">
            <i class="bi bi-grid-fill"></i> Semua
          </button>
          @foreach ($kategoris as $kategori)
            <button type="button" class="km-chip filter-kategori-btn" data-kategori-id="{{ $kategori->id }}" onclick="pilihKategori(this, '{{ $kategori->id }}')">
              {{ $kategori->nama }}
            </button>
          @endforeach
        </div>

        <div class="row g-3" id="grid-menu">
          @forelse ($menus as $menu)
            @php $statusStok = $menu->status_stok; @endphp
            <div class="col-6 col-md-4 col-xl-3 menu-card-col"
              data-kategori-id="{{ $menu->kategori_id }}"
              data-nama="{{ \Illuminate\Support\Str::lower($menu->nama) }}">
              <div class="pos-menu-card {{ $statusStok === 'habis' ? 'is-habis' : '' }}" onclick="bukaModalMenu({{ $menu->id }})">
                <div class="pos-menu-img">
                  <img src="{{ $menu->gambar ? asset('storage/' . $menu->gambar) : asset('assets_dashboard/images/logo3.png') }}" alt="{{ $menu->nama }}" loading="lazy">
                  @if ($menu->kategori)
                    <span class="pos-menu-cat">{{ $menu->kategori->nama }}</span>
                  @endif
                  @if ($statusStok === 'habis')
                    <span class="pos-menu-flag habis">Stok Habis</span>
                  @elseif ($statusStok === 'menipis')
                    <span class="pos-menu-flag menipis">Hampir habis</span>
                  @endif
                  <button type="button" class="pos-menu-add" title="Tambah {{ $menu->nama }}"
                    onclick="event.stopPropagation(); bukaModalMenu({{ $menu->id }})">
                    <i class="bi bi-plus-lg"></i>
                  </button>
                </div>
                <div class="pos-menu-body">
                  <p class="pos-menu-nama">{{ $menu->nama }}</p>
                  <p class="pos-menu-harga">{{ $menu->harga_label }}</p>
                </div>
              </div>
            </div>
          @empty
            <div class="col-12 stok-empty">
              <i class="bi bi-inbox"></i>
              Belum ada menu aktif. Tambahkan dulu lewat halaman Master Menu.
            </div>
          @endforelse
        </div>

        <div id="grid-menu-kosong" class="stok-empty d-none">
          <i class="bi bi-search"></i>
          Tidak ada menu yang cocok dengan pencarian atau filter.
        </div>

      </div>
    </div>

    <!-- ===================== KOLOM KANAN: KERANJANG ===================== -->
    <div class="col-lg-4">
      <div class="km-card pos-cart" id="pos-cart">
        <div class="pos-cart-head">
          <h2 class="km-card-title">
            <i class="bi bi-bag-check-fill"></i> Keranjang
            <span class="pos-cart-count" id="cart-count">0</span>
          </h2>
          <button type="button" class="pos-cart-clear d-none" id="btn-kosongkan" onclick="kosongkanKeranjang()">
            <i class="bi bi-trash3"></i> Kosongkan
          </button>
        </div>

        <div class="pos-cart-body">
          <div id="cart-empty-msg" class="pos-cart-empty">
            <i class="bi bi-cart3"></i>
            Keranjang masih kosong.<br>Klik menu di sebelah kiri untuk menambahkan.
          </div>
          <div id="cart-list"></div>
        </div>

        <div class="pos-cart-foot">
          <div class="pos-total">
            <span>Total Bayar</span>
            <span id="cart-total">Rp 0</span>
          </div>

          <span class="pos-pay-label">Metode Bayar</span>
          <div class="pos-pay-grid">
            <button type="button" class="pos-pay metode-bayar-btn active" data-metode="cash" onclick="pilihMetodeBayar(this, 'cash')">
              <span class="pay-tick"><i class="bi bi-check-lg"></i></span>
              <img class="pay-logo" src="{{ asset('assets_dashboard/images/pay/cash.svg') }}" alt="">
              <span>Cash</span>
            </button>
            <button type="button" class="pos-pay metode-bayar-btn" data-metode="qris" onclick="pilihMetodeBayar(this, 'qris')">
              <span class="pay-tick"><i class="bi bi-check-lg"></i></span>
              <img class="pay-logo" src="{{ asset('assets_dashboard/images/pay/qris.svg') }}" alt="">
              <span>QRIS</span>
            </button>
            <button type="button" class="pos-pay metode-bayar-btn" data-metode="paylater" onclick="pilihMetodeBayar(this, 'paylater')">
              <span class="pay-tick"><i class="bi bi-check-lg"></i></span>
              <img class="pay-logo" src="{{ asset('assets_dashboard/images/pay/paylater.svg') }}" alt="">
              <span>PayLater</span>
            </button>
          </div>

          <!-- Khusus Cash: input uang diterima & kembalian otomatis -->
          <div class="cash-box" id="cash-box">
            <span class="pos-pay-label">Uang Diterima</span>
            <div class="cash-input-wrap">
              <span>Rp</span>
              <input type="text" id="input-uang-tampil" inputmode="numeric" autocomplete="off" placeholder="0" oninput="ubahUangDiterima(this.value)">
            </div>
            <div class="cash-quick" id="cash-quick"></div>
            <div class="cash-kembalian" id="cash-kembalian">
              <span id="cash-kembalian-label">Kembalian</span>
              <span id="cash-kembalian-nilai">Rp 0</span>
            </div>
          </div>

          <form id="form-checkout" action="{{ route('kasir.transaksi.store') }}" method="POST" onsubmit="return sebelumSubmitCheckout()">
            @csrf
            <input type="hidden" name="metode_bayar" id="input-metode-bayar" value="cash">
            <input type="hidden" name="uang_diterima" id="input-uang-diterima" value="">
            <div id="hidden-items-container"></div>
            <button type="submit" class="km-btn km-btn-block" id="btn-checkout" disabled>
              <i class="bi bi-check-circle-fill"></i>
              <span>Selesaikan Transaksi</span>
            </button>
          </form>
        </div>
      </div>
    </div>

  </div>

  <!-- Bar ringkas keranjang khusus layar HP -->
  <div class="pos-mobile-bar" id="pos-mobile-bar">
    <div>
      <b id="mobile-total">Rp 0</b>
      <small id="mobile-jumlah">0 item</small>
    </div>
    <button type="button" onclick="document.getElementById('pos-cart').scrollIntoView({ behavior: 'smooth', block: 'start' })">
      Lihat Keranjang <i class="bi bi-arrow-down-short"></i>
    </button>
  </div>

  <!-- ===================== MODAL: DETAIL ITEM ===================== -->
  <div class="modal fade pos-modal" id="modal-item-menu" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <button type="button" class="pos-modal-close" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>

        <div class="pos-modal-body">
          <!-- Kiri: foto + info menu -->
          <aside class="pos-modal-hero">
            <div class="pos-modal-img"><img id="modal-item-gambar" src="" alt=""></div>
            <div>
              <h5 class="pos-modal-nama" id="modal-item-nama">Nama Menu</h5>
              <p class="pos-modal-desc" id="modal-item-deskripsi"></p>
            </div>
            <div class="pos-modal-harga">
              <small>Harga per porsi</small>
              <span id="modal-item-harga-dasar">Rp 0</span>
            </div>
          </aside>

          <!-- Kanan: pilihan -->
          <section class="pos-modal-options">

            <div class="opt-section">
              <div class="opt-head">
                <h6><i class="bi bi-rulers"></i> Pilih Ukuran</h6>
                <span class="opt-note wajib">Wajib</span>
              </div>
              <div id="modal-ukuran-list" class="opt-size-row"></div>
            </div>

            <div class="opt-section">
              <div class="opt-head">
                <h6><i class="bi bi-fire"></i> Level Pedas</h6>
                <span class="opt-note" id="modal-level-note">Opsional</span>
              </div>
              <div id="modal-level-list" class="lv-grid"></div>
              <div id="modal-level-banner" class="lv-banner"></div>
            </div>

            <div class="opt-section">
              <div class="opt-head">
                <h6><i class="bi bi-droplet-fill"></i> Pilih Bumbu</h6>
                <span class="opt-note" id="modal-saus-note">Opsional · boleh lebih dari 1</span>
              </div>
              <div id="modal-saus-list" class="opt-grid"></div>
            </div>

            <div class="opt-section">
              <div class="opt-head">
                <h6><i class="bi bi-stars"></i> Pilih Topping</h6>
                <span class="opt-note" id="modal-bubuk-note">Opsional · boleh lebih dari 1</span>
              </div>
              <div id="modal-bubuk-list" class="opt-grid"></div>
            </div>

          </section>
        </div>

        <div class="pos-modal-foot">
          <div class="pos-stepper lg">
            <button type="button" onclick="ubahJumlahModal(-1)" aria-label="Kurangi"><i class="bi bi-dash-lg"></i></button>
            <span id="modal-jumlah">1</span>
            <button type="button" onclick="ubahJumlahModal(1)" aria-label="Tambah"><i class="bi bi-plus-lg"></i></button>
          </div>
          <button type="button" class="pos-add-btn" id="btn-tambah-keranjang" onclick="tambahKeKeranjang()">
            <i class="bi bi-bag-plus-fill"></i>
            <span>Tambah ke Keranjang</span>
            <span class="harga" id="modal-item-harga-preview">Rp 0</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="km-toast-wrap" id="km-toast-wrap"></div>

  {{-- Dialog konfirmasi (pengganti confirm() bawaan browser) --}}
  <div class="kc-overlay" id="kc-overlay" aria-hidden="true">
    <div class="kc-box" role="alertdialog" aria-modal="true" aria-labelledby="kc-judul" aria-describedby="kc-pesan">
      <div class="kc-icon" id="kc-icon"><i class="bi bi-question-lg"></i></div>
      <h3 class="kc-judul" id="kc-judul"></h3>
      <p class="kc-pesan" id="kc-pesan"></p>
      <div class="kc-aksi">
        <button type="button" class="kc-btn kc-tidak" id="kc-tidak">Tidak</button>
        <button type="button" class="kc-btn kc-ya" id="kc-ya">Ya</button>
      </div>
    </div>
  </div>

  <script type="application/json" id="data-menu">{!! json_encode($menuDataJson) !!}</script>
  <script type="application/json" id="data-saus">{!! json_encode($sausDataJson) !!}</script>
  <script type="application/json" id="data-bubuk">{!! json_encode($bubukDataJson) !!}</script>

</div>
@endsection

@push('scripts')
  <script>
    const DATA_MENU = JSON.parse(document.getElementById('data-menu').textContent || '[]');
    const DATA_SAUS = JSON.parse(document.getElementById('data-saus').textContent || '[]');
    const DATA_BUBUK = JSON.parse(document.getElementById('data-bubuk').textContent || '[]');

    let cart = [];
    let metodeBayarTerpilih = 'cash';
    let totalKeranjang = 0;
    let uangDiterima = 0;

    // ---- State modal item yang lagi dibuka ----
    let modalMenu = null;
    let modalUkuranId = null;
    let modalSausIds = [];
    let modalLevel = 'tidak_pedas';
    let modalBubukIds = [];
    let modalJumlah = 1;

    function formatRupiah(angka) {
      return 'Rp ' + Number(angka || 0).toLocaleString('id-ID');
    }

    function esc(teks) {
      const div = document.createElement('div');
      div.textContent = teks == null ? '' : String(teks);
      return div.innerHTML;
    }

    function tampilToast(pesan, tipe, posisi) {
      const tengah = posisi === 'tengah';
      const wrap = tengah ? document.body : document.getElementById('km-toast-wrap');
      const el = document.createElement('div');
      el.className = 'km-toast ' + (tipe || 'ok') + (tengah ? ' km-toast-tengah' : '');
      el.innerHTML = '<i class="bi ' + (tipe === 'warn' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill') + '"></i><span></span>';
      el.querySelector('span').textContent = pesan;
      wrap.appendChild(el);
      setTimeout(function () {
        el.style.transition = 'opacity .3s';
        el.style.opacity = '0';
        setTimeout(function () { el.remove(); }, 300);
      }, 2200);
    }

    // ===================== FILTER GRID MENU =====================
    let kategoriAktif = '';

    function pilihKategori(btn, kategoriId) {
      kategoriAktif = kategoriId;
      document.querySelectorAll('.filter-kategori-btn').forEach((el) => el.classList.remove('active'));
      btn.classList.add('active');
      terapkanFilterGrid();
    }

    function terapkanFilterGrid() {
      const kata = (document.getElementById('input-cari-menu').value || '').toLowerCase().trim();
      let adaYangTampil = false;

      document.querySelectorAll('.menu-card-col').forEach((col) => {
        const cocokKategori = !kategoriAktif || col.dataset.kategoriId === kategoriAktif;
        const cocokNama = !kata || col.dataset.nama.includes(kata);
        const tampil = cocokKategori && cocokNama;
        col.classList.toggle('d-none', !tampil);
        if (tampil) adaYangTampil = true;
      });

      document.getElementById('grid-menu-kosong').classList.toggle('d-none', adaYangTampil);
    }

    document.getElementById('input-cari-menu').addEventListener('input', terapkanFilterGrid);

    // ===================== MODAL ITEM MENU =====================
    function bukaModalMenu(menuId) {
      const menu = DATA_MENU.find((m) => m.id === menuId);
      if (!menu) return;
      if (menu.habis) {
        tampilToast('Stok "' + menu.nama + '" sedang habis.', 'warn');
        return;
      }
      if (!menu.ukurans.length) {
        tampilToast('"' + menu.nama + '" belum punya ukuran & harga. Atur dulu lewat Master Menu.', 'warn');
        return;
      }

      modalMenu = menu;
      modalUkuranId = menu.ukurans[0].id;
      modalSausIds = [];
      modalLevel = 'tidak_pedas';
      modalBubukIds = [];
      modalJumlah = 1;

      document.getElementById('modal-item-nama').textContent = menu.nama;
      document.getElementById('modal-item-deskripsi').textContent = menu.deskripsi || '';
      document.getElementById('modal-item-gambar').src = menu.gambar;
      document.getElementById('modal-item-gambar').alt = menu.nama;
      document.getElementById('modal-jumlah').textContent = '1';

      renderUkuranPills();
      renderLevelCards();
      renderSausPills();
      renderBubukPills();
      updateHargaPreview();

      bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-item-menu')).show();
    }

    // Kartu opsi bumbu/topping: foto di atas, nama & harga di bawah,
    // centang di pojok kalau kepilih. Kalau belum ada foto -> ikon.
    function buatOptionCard({ nama, hargaText, terpilih, disabled, onClick, foto, ikon }) {
      const card = document.createElement('div');
      card.className = 'opt-card' + (terpilih ? ' selected' : '') + (disabled ? ' disabled' : '');
      card.innerHTML = `
        <div class="opt-photo"><i class="bi ${ikon}"></i></div>
        <span class="opt-check"><i class="bi bi-check-lg"></i></span>
        ${disabled ? '<span class="opt-habis">Habis</span>' : ''}
        <div class="opt-info">
          <span class="opt-nama"></span>
          <span class="opt-harga"></span>
        </div>
      `;
      card.querySelector('.opt-nama').textContent = nama;
      card.querySelector('.opt-harga').textContent = hargaText;

      if (foto) {
        const img = document.createElement('img');
        img.alt = '';
        img.loading = 'lazy';
        img.onerror = () => img.remove();
        img.src = foto;
        card.querySelector('.opt-photo').appendChild(img);
      }
      if (!disabled) card.onclick = onClick;
      return card;
    }

    function renderUkuranPills() {
      const wrap = document.getElementById('modal-ukuran-list');
      wrap.innerHTML = '';
      modalMenu.ukurans.forEach((ukuran) => {
        const tombol = document.createElement('button');
        tombol.type = 'button';
        tombol.className = 'opt-size' + (ukuran.id === modalUkuranId ? ' selected' : '');
        tombol.innerHTML = '<b></b><span></span>';
        tombol.querySelector('b').textContent = ukuran.nama;
        tombol.querySelector('span').textContent = formatRupiah(ukuran.harga);
        tombol.onclick = () => { modalUkuranId = ukuran.id; renderUkuranPills(); updateHargaPreview(); };
        wrap.appendChild(tombol);
      });
    }

    // ---- Level pedas: Tanpa / Level 1 / Level 2 / Level 3 ----
    const LEVEL_PEDAS = [
      { kode: 'tidak_pedas', kelas: 'lv-0', nama: 'Tidak Pedas', sub: 'Aman', api: 0, pesan: 'Tanpa level pedas — rasa original.' },
      { kode: 'level_1',     kelas: 'lv-1', nama: 'Level 1',     sub: 'Santai', api: 1, pesan: 'Pedas santai, hangat di lidah.' },
      { kode: 'level_2',     kelas: 'lv-2', nama: 'Level 2',     sub: 'Mantap', api: 2, pesan: 'Pedas mantap, keringat mulai keluar.' },
      { kode: 'level_3',     kelas: 'lv-3', nama: 'Level 3',     sub: 'Gila!',  api: 3, pesan: 'Pedas gila! Siapkan air minum dulu.' },
    ];

    function namaLevel(kode) {
      return (LEVEL_PEDAS.find((l) => l.kode === kode) || LEVEL_PEDAS[0]).nama;
    }

    function renderLevelCards() {
      const wrap = document.getElementById('modal-level-list');
      const note = document.getElementById('modal-level-note');
      const banner = document.getElementById('modal-level-banner');
      wrap.innerHTML = '';

      LEVEL_PEDAS.forEach((lv) => {
        const aktif = modalLevel === lv.kode;
        const card = document.createElement('div');
        card.className = 'lv-card ' + lv.kelas + (aktif ? ' selected' : '');
        card.setAttribute('role', 'button');
        card.setAttribute('tabindex', '0');
        const api = lv.api === 0
          ? '<i class="bi bi-slash-circle"></i>'
          : '<i class="bi bi-fire"></i>'.repeat(lv.api);
        card.innerHTML = `
          <span class="lv-check"><i class="bi bi-check-lg"></i></span>
          <span class="lv-icon">${api}</span>
          <span class="lv-name"></span>
          <span class="lv-sub"></span>
          <span class="lv-meter"><i></i><i></i><i></i></span>
        `;
        card.querySelector('.lv-name').textContent = lv.nama;
        card.querySelector('.lv-sub').textContent = lv.sub;
        const pilih = () => { modalLevel = lv.kode; renderLevelCards(); };
        card.onclick = pilih;
        card.onkeydown = (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pilih(); } };
        wrap.appendChild(card);
      });

      const lvAktif = LEVEL_PEDAS.find((l) => l.kode === modalLevel);
      note.textContent = lvAktif.api ? lvAktif.nama + ' dipilih' : 'Opsional';
      note.classList.toggle('terpilih', lvAktif.api > 0);
      banner.className = 'lv-banner' + (lvAktif.api ? ' b' + lvAktif.api : '');
      banner.innerHTML = '<i class="bi ' + (lvAktif.api ? 'bi-fire' : 'bi-emoji-smile') + '"></i><span></span>';
      banner.querySelector('span').textContent = lvAktif.pesan;
    }

    function renderSausPills() {
      const wrap = document.getElementById('modal-saus-list');
      const note = document.getElementById('modal-saus-note');
      wrap.innerHTML = '';
      note.textContent = modalSausIds.length ? modalSausIds.length + ' dipilih' : 'Opsional · boleh lebih dari 1';
      note.classList.toggle('terpilih', modalSausIds.length > 0);

      if (!DATA_SAUS.length) {
        wrap.innerHTML = '<div class="opt-empty" style="grid-column: 1 / -1;">Belum ada bumbu di master data.</div>';
        return;
      }
      DATA_SAUS.forEach((saus) => {
        const terpilih = modalSausIds.includes(saus.id);
        wrap.appendChild(buatOptionCard({
          nama: saus.nama,
          foto: saus.foto,
          ikon: 'bi-droplet-fill',
          hargaText: saus.harga_tambahan > 0 ? '+' + formatRupiah(saus.harga_tambahan) : 'Gratis',
          terpilih,
          disabled: saus.habis,
          onClick: () => {
            // Klik = pilih, klik lagi = batal. Bumbu boleh lebih dari satu.
            if (terpilih) {
              modalSausIds = modalSausIds.filter((id) => id !== saus.id);
            } else {
              modalSausIds.push(saus.id);
            }
            renderSausPills();
            updateHargaPreview();
          },
        }));
      });
    }

    function renderBubukPills() {
      const wrap = document.getElementById('modal-bubuk-list');
      const note = document.getElementById('modal-bubuk-note');
      wrap.innerHTML = '';
      note.textContent = modalBubukIds.length ? modalBubukIds.length + ' dipilih' : 'Opsional · boleh lebih dari 1';
      note.classList.toggle('terpilih', modalBubukIds.length > 0);

      if (!DATA_BUBUK.length) {
        wrap.innerHTML = '<div class="opt-empty" style="grid-column: 1 / -1;">Belum ada topping di master data.</div>';
        return;
      }
      DATA_BUBUK.forEach((bubuk) => {
        const terpilih = modalBubukIds.includes(bubuk.id);
        wrap.appendChild(buatOptionCard({
          nama: bubuk.nama,
          foto: bubuk.foto,
          ikon: 'bi-stars',
          hargaText: bubuk.harga_tambahan > 0 ? '+' + formatRupiah(bubuk.harga_tambahan) : 'Gratis',
          terpilih,
          disabled: bubuk.habis,
          onClick: () => {
            if (terpilih) {
              modalBubukIds = modalBubukIds.filter((id) => id !== bubuk.id);
            } else {
              modalBubukIds.push(bubuk.id);
            }
            renderBubukPills();
            updateHargaPreview();
          },
        }));
      });
    }

    function hitungHargaSatuanModal() {
      let harga = modalMenu.ukurans.find((u) => u.id === modalUkuranId)?.harga || 0;
      modalSausIds.forEach((id) => {
        harga += DATA_SAUS.find((s) => s.id === id)?.harga_tambahan || 0;
      });
      modalBubukIds.forEach((id) => {
        harga += DATA_BUBUK.find((b) => b.id === id)?.harga_tambahan || 0;
      });
      return harga;
    }

    function updateHargaPreview() {
      const hargaSatuan = hitungHargaSatuanModal();
      document.getElementById('modal-item-harga-dasar').textContent = formatRupiah(hargaSatuan);
      document.getElementById('modal-item-harga-preview').textContent = formatRupiah(hargaSatuan * modalJumlah);
    }

    function ubahJumlahModal(delta) {
      modalJumlah = Math.max(1, modalJumlah + delta);
      document.getElementById('modal-jumlah').textContent = modalJumlah;
      updateHargaPreview();
    }

    function tambahKeKeranjang() {
      const ukuran = modalMenu.ukurans.find((u) => u.id === modalUkuranId);
      const sausTerpilih = DATA_SAUS.filter((s) => modalSausIds.includes(s.id));
      const bubukTerpilih = DATA_BUBUK.filter((b) => modalBubukIds.includes(b.id));

      const hargaSatuan = hitungHargaSatuanModal();

      // Kalau kombinasi menu+ukuran+bumbu+topping persis sama sudah ada
      // di keranjang, tambahkan jumlahnya saja daripada bikin baris baru.
      const kunciKombinasi = JSON.stringify({
        menuId: modalMenu.id, ukuranId: modalUkuranId, sausIds: [...modalSausIds].sort(),
        levelPedas: modalLevel,
        bubukIds: [...modalBubukIds].sort(),
      });

      const existing = cart.find((item) => item._kunci === kunciKombinasi);
      if (existing) {
        existing.jumlah += modalJumlah;
      } else {
        cart.push({
          _kunci: kunciKombinasi,
          menuId: modalMenu.id,
          nama: modalMenu.nama,
          gambar: modalMenu.gambar,
          ukuranId: modalUkuranId,
          namaUkuran: ukuran.nama,
          sausIds: [...modalSausIds],
          namaSaus: sausTerpilih.map((s) => s.nama),
          fotoSaus: sausTerpilih.map((s) => s.foto),
          levelPedas: modalLevel,
          bubukIds: [...modalBubukIds],
          namaBubuk: bubukTerpilih.map((b) => b.nama),
          fotoBubuk: bubukTerpilih.map((b) => b.foto),
          hargaSatuan: hargaSatuan,
          jumlah: modalJumlah,
        });
      }

      modalBolehTutup = true;
      bootstrap.Modal.getInstance(document.getElementById('modal-item-menu'))?.hide();
      renderCart();
      tampilToast(modalJumlah + 'x ' + modalMenu.nama + ' masuk keranjang');
    }

    // ===================== KERANJANG =====================
    // Batal beli: menutup popup pilih menu (X / Esc / klik luar) tanpa menekan
    // "Tambah ke keranjang" dianggap membatalkan pembelian menu itu -> tanya dulu.
    let modalBolehTutup = false;
    (function () {
      const elModal = document.getElementById('modal-item-menu');
      elModal.addEventListener('show.bs.modal', function () { modalBolehTutup = false; });
      elModal.addEventListener('hide.bs.modal', function (e) {
        if (modalBolehTutup) return;
        e.preventDefault();
        if (document.getElementById('kc-overlay').classList.contains('show')) return;
        const namaMenu = modalMenu ? modalMenu.nama : 'menu ini';
        konfirmasiAksi({
          ikon: 'bi-bag-x',
          judul: 'Batal beli menu ini?',
          pesan: 'Pilihan untuk ' + namaMenu + ' belum masuk keranjang dan akan dibatalkan.',
          tombolYa: 'Ya, batal beli',
          tombolTidak: 'Tidak, lanjut pilih',
          pesanTidak: 'Tidak jadi, silakan lanjut memilih',
          onYa: function () {
            modalBolehTutup = true;
            bootstrap.Modal.getInstance(elModal)?.hide();
            tampilToast('Pembelian ' + namaMenu + ' dibatalkan', 'ok', 'tengah');
          },
        });
      });
    })();

    // Dialog konfirmasi Ya / Tidak (dipakai untuk kosongkan keranjang & batalkan menu)
    let kcOnYa = null;
    let kcPesanTidak = '';
    function konfirmasiAksi(opsi) {
      const ov = document.getElementById('kc-overlay');
      kcOnYa = opsi.onYa;
      kcPesanTidak = opsi.pesanTidak || '';
      document.getElementById('kc-judul').textContent = opsi.judul;
      document.getElementById('kc-pesan').textContent = opsi.pesan;
      document.getElementById('kc-ya').textContent = opsi.tombolYa || 'Ya';
      document.getElementById('kc-tidak').textContent = opsi.tombolTidak || 'Tidak';
      document.getElementById('kc-icon').querySelector('i').className = 'bi ' + (opsi.ikon || 'bi-question-lg');
      const modalTerbuka = document.querySelector('#modal-item-menu.show');
      (modalTerbuka || document.body).appendChild(ov);
      ov.classList.add('show');
      ov.setAttribute('aria-hidden', 'false');
      document.getElementById('kc-tidak').focus();
    }
    function tutupKonfirmasi(karenaTidak) {
      const ov = document.getElementById('kc-overlay');
      const pesanTidak = kcPesanTidak;
      ov.classList.remove('show');
      ov.setAttribute('aria-hidden', 'true');
      kcOnYa = null;
      kcPesanTidak = '';
      if (karenaTidak === true && pesanTidak) tampilToast(pesanTidak, 'warn', 'tengah');
    }
    document.getElementById('kc-ya').addEventListener('click', function () {
      const aksi = kcOnYa;
      tutupKonfirmasi();
      if (aksi) aksi();
    });
    document.getElementById('kc-tidak').addEventListener('click', function () { tutupKonfirmasi(true); });
    document.getElementById('kc-overlay').addEventListener('click', function (e) {
      if (e.target === this) tutupKonfirmasi(true);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && document.getElementById('kc-overlay').classList.contains('show')) tutupKonfirmasi(true);
    });

    function ubahJumlahCart(index, delta) {
      // Jumlah turun sampai 0 = membatalkan menu itu, jadi tanyakan dulu.
      if (cart[index].jumlah + delta <= 0) {
        hapusDariKeranjang(index);
        return;
      }
      cart[index].jumlah += delta;
      renderCart();
    }

    function hapusDariKeranjang(index) {
      const item = cart[index];
      if (!item) return;
      konfirmasiAksi({
        ikon: 'bi-x-circle',
        judul: 'Batalkan menu ini?',
        pesan: item.nama + ' (' + item.namaUkuran + ') x' + item.jumlah + ' akan dihapus dari keranjang.',
        tombolYa: 'Ya, batalkan',
        tombolTidak: 'Tidak',
        pesanTidak: 'Tidak jadi, menu tetap ada di keranjang',
        onYa: function () {
          const posisi = cart.indexOf(item);
          if (posisi === -1) return;
          cart.splice(posisi, 1);
          renderCart();
          tampilToast(item.nama + ' sudah dibatalkan dari keranjang', 'ok', 'tengah');
        },
      });
    }

    function kosongkanKeranjang() {
      if (!cart.length) return;
      konfirmasiAksi({
        ikon: 'bi-trash3',
        judul: 'Kosongkan keranjang?',
        pesan: 'Semua ' + cart.length + ' menu di keranjang akan dihapus.',
        tombolYa: 'Ya, kosongkan',
        tombolTidak: 'Tidak',
        pesanTidak: 'Tidak jadi, keranjang tetap seperti semula',
        onYa: function () {
          cart = [];
          renderCart();
          tampilToast('Keranjang sudah dikosongkan', 'ok', 'tengah');
        },
      });
    }

    function renderCart() {
      const list = document.getElementById('cart-list');
      const emptyMsg = document.getElementById('cart-empty-msg');
      list.innerHTML = '';

      const adaItem = cart.length > 0;
      emptyMsg.classList.toggle('d-none', adaItem);
      document.getElementById('btn-kosongkan').classList.toggle('d-none', !adaItem);

      let total = 0;
      let jumlahItem = 0;

      cart.forEach((item, index) => {
        const subtotal = item.hargaSatuan * item.jumlah;
        total += subtotal;
        jumlahItem += item.jumlah;

        // Tag opsi: kalau ada foto bumbu/topping tampil sebagai bulatan kecil, kalau tidak pakai ikon.
        const penanda = (foto, ikon) => foto
          ? '<img class="pos-tag-foto" src="' + esc(foto) + '" alt="" onerror="this.remove()">'
          : '<i class="bi ' + ikon + '"></i>';
        const tags = ['<span class="pos-tag">' + esc(item.namaUkuran) + '</span>'];
        if (item.levelPedas && item.levelPedas !== 'tidak_pedas') {
          const angka = item.levelPedas.slice(-1);
          tags.push('<span class="pos-tag pedas p' + angka + '"><i class="bi bi-fire"></i> ' + esc(namaLevel(item.levelPedas)) + '</span>');
        }
        item.namaSaus.forEach((n, i) => tags.push('<span class="pos-tag bumbu">' + penanda((item.fotoSaus || [])[i], 'bi-droplet-fill') + ' ' + esc(n) + '</span>'));
        item.namaBubuk.forEach((n, i) => tags.push('<span class="pos-tag topping">' + penanda((item.fotoBubuk || [])[i], 'bi-stars') + ' ' + esc(n) + '</span>'));

        const row = document.createElement('div');
        row.className = 'pos-cart-item';
        row.innerHTML = `
          <img src="${esc(item.gambar)}" alt="">
          <div class="pos-cart-info">
            <p class="pos-cart-nama">${esc(item.nama)}</p>
            <div class="pos-cart-tags">${tags.join('')}</div>
            <div class="pos-cart-row">
              <div class="pos-stepper">
                <button type="button" onclick="ubahJumlahCart(${index}, -1)" aria-label="Kurangi"><i class="bi bi-dash"></i></button>
                <span>${item.jumlah}</span>
                <button type="button" onclick="ubahJumlahCart(${index}, 1)" aria-label="Tambah"><i class="bi bi-plus"></i></button>
              </div>
              <span class="pos-cart-price">${formatRupiah(subtotal)}</span>
            </div>
          </div>
          <button type="button" class="pos-cart-del" onclick="hapusDariKeranjang(${index})" aria-label="Hapus"><i class="bi bi-x-lg"></i></button>
        `;
        list.appendChild(row);
      });

      totalKeranjang = total;
      document.getElementById('cart-total').textContent = formatRupiah(total);
      document.getElementById('cart-count').textContent = jumlahItem;

      // Bar ringkas di HP
      document.getElementById('mobile-total').textContent = formatRupiah(total);
      document.getElementById('mobile-jumlah').textContent = jumlahItem + ' item';
      document.getElementById('pos-mobile-bar').classList.toggle('has-items', adaItem);

      bangunHiddenInputs();
      perbaruiPembayaran();
    }

    function bangunHiddenInputs() {
      const container = document.getElementById('hidden-items-container');
      container.innerHTML = '';

      cart.forEach((item, index) => {
        const tambah = (nama, nilai) => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = nama;
          input.value = nilai;
          container.appendChild(input);
        };

        tambah(`items[${index}][menu_id]`, item.menuId);
        tambah(`items[${index}][ukuran_id]`, item.ukuranId);
        tambah(`items[${index}][jumlah]`, item.jumlah);
        tambah(`items[${index}][level_pedas]`, item.levelPedas || 'tidak_pedas');
        item.sausIds.forEach((sausId) => {
          tambah(`items[${index}][saus_ids][]`, sausId);
        });
        item.bubukIds.forEach((bubukId) => {
          tambah(`items[${index}][bubuk_ids][]`, bubukId);
        });
      });
    }

    function sebelumSubmitCheckout() {
      if (!cart.length) {
        tampilToast('Keranjang masih kosong.', 'warn');
        return false;
      }
      if (metodeBayarTerpilih === 'cash' && uangDiterima < totalKeranjang) {
        tampilToast('Uang diterima kurang dari total belanja.', 'warn');
        return false;
      }
      document.getElementById('btn-checkout').disabled = true;
      document.getElementById('btn-checkout').innerHTML = '<i class="bi bi-hourglass-split"></i><span>Menyimpan...</span>';
      return true;
    }

    // ===================== METODE BAYAR =====================
    function pilihMetodeBayar(btn, metode) {
      metodeBayarTerpilih = metode;
      document.getElementById('input-metode-bayar').value = metode;
      document.querySelectorAll('.metode-bayar-btn').forEach((el) => el.classList.remove('active'));
      btn.classList.add('active');
      perbaruiPembayaran();
    }

    // ===================== UANG DITERIMA & KEMBALIAN (CASH) =====================
    function ubahUangDiterima(teks) {
      uangDiterima = parseInt(String(teks).replace(/\D/g, ''), 10) || 0;
      const input = document.getElementById('input-uang-tampil');
      input.value = uangDiterima > 0 ? uangDiterima.toLocaleString('id-ID') : '';
      perbaruiPembayaran();
    }

    function setUangDiterima(nominal) {
      ubahUangDiterima(String(nominal));
    }

    // Tombol nominal cepat: "Uang Pas" + pecahan uang yang paling
    // dekat di atas total (dibulatkan ke 5rb/10rb/20rb/50rb/100rb).
    function bangunTombolCepat() {
      const wrap = document.getElementById('cash-quick');
      wrap.innerHTML = '';
      if (totalKeranjang <= 0) return;

      const opsi = [totalKeranjang];
      [5000, 10000, 20000, 50000, 100000].forEach((pecahan) => {
        const bulat = Math.ceil(totalKeranjang / pecahan) * pecahan;
        if (!opsi.includes(bulat)) opsi.push(bulat);
      });

      opsi.slice(0, 5).forEach((nominal, i) => {
        const tombol = document.createElement('button');
        tombol.type = 'button';
        tombol.textContent = i === 0 ? 'Uang Pas' : formatRupiah(nominal);
        tombol.onclick = () => setUangDiterima(nominal);
        wrap.appendChild(tombol);
      });
    }

    function perbaruiPembayaran() {
      const tunai = metodeBayarTerpilih === 'cash';
      const box = document.getElementById('cash-box');
      const adaItem = cart.length > 0;
      box.classList.toggle('d-none', !tunai);

      const kembalianBox = document.getElementById('cash-kembalian');
      const label = document.getElementById('cash-kembalian-label');
      const nilai = document.getElementById('cash-kembalian-nilai');
      const selisih = uangDiterima - totalKeranjang;

      if (tunai) {
        bangunTombolCepat();
        kembalianBox.classList.toggle('kurang', selisih < 0);
        if (uangDiterima <= 0 || !adaItem) {
          label.textContent = 'Kembalian';
          nilai.textContent = 'Rp 0';
        } else if (selisih < 0) {
          label.textContent = 'Uang kurang';
          nilai.textContent = formatRupiah(Math.abs(selisih));
        } else {
          label.textContent = 'Kembalian';
          nilai.textContent = formatRupiah(selisih);
        }
      }

      // Nominal dikirim ke server hanya kalau bayar tunai.
      document.getElementById('input-uang-diterima').value = tunai ? uangDiterima : '';

      // Tombol checkout aktif kalau keranjang ada isi, dan (untuk cash)
      // uang yang diterima sudah cukup.
      document.getElementById('btn-checkout').disabled = !adaItem || (tunai && uangDiterima < totalKeranjang);
    }

    renderCart();
  </script>
@endpush
