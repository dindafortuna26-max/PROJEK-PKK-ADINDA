<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Cimol Bojot AA' . ' - Panel Utama')</title>

  <!-- Favicon (ganti file ini juga kalau mau branding penuh) -->
  <link rel="icon" type="image/png" href="{{ asset('assets_dashboard/images/favicon.ico') }}">

  <!-- Local Third-Party Libraries CSS -->
  <link rel="stylesheet" href="{{ asset('assets_dashboard/libs/bootstrap/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_dashboard/libs/bootstrap-icons/bootstrap-icons.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_dashboard/libs/apexcharts/apexcharts.css') }}">
  <link rel="stylesheet" href="{{ asset('assets_dashboard/libs/flatpickr/flatpickr.min.css') }}">

  <!-- Main Stylesheet (public/assets_dashboard/css/main.css) -->
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/main.css') }}">

  <!-- Tema Cimol Bojot AA: hanya override variabel warna + beberapa
       komponen baru. main.css di atas TIDAK diubah/dihapus. -->
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/cimol-theme.css') }}">

  <!-- Lapisan tampilan modern (modal, form, riwayat transaksi). File terpisah,
       tidak mengubah CSS lain. -->
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/cimol-modern.css') }}">

  <!-- Polish: perbaikan modal (tombol Batal/Simpan) + desain terbaru -->
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/cimol-polish.css') }}?v={{ @filemtime(public_path('assets_dashboard/css/cimol-polish.css')) }}">


  <!-- Notifikasi toast (berhasil tambah / edit / hapus) - tampil di tengah layar -->
  <style>
    .cb-toast-wrap {
      position: fixed;
      inset: 0;
      z-index: 2100;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 14px;
      padding: 16px;
      pointer-events: none;
      transition: background .3s ease;
    }
    .cb-toast-wrap:not(:empty) { background: rgba(43, 20, 20, .32); }
    .cb-toast {
      --cb-accent: #1F9D55;
      --cb-accent-soft: #E3F6EB;
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      gap: 10px;
      width: min(400px, 100%);
      padding: 30px 28px 34px;
      background: #fff;
      border: 1px solid var(--border-light, #F1E1DA);
      border-top: 6px solid var(--cb-accent);
      border-radius: 22px;
      box-shadow: 0 24px 60px rgba(92, 18, 18, .28), 0 4px 12px rgba(0, 0, 0, .08);
      overflow: hidden;
      pointer-events: auto;
      opacity: 0;
      transform: translateY(18px) scale(.9);
      animation: cbToastIn .4s cubic-bezier(.2, .9, .3, 1.25) forwards;
    }
    .cb-toast.cb-hide { animation: cbToastOut .25s ease forwards; }
    .cb-toast.cb-danger  { --cb-accent: #C62828; --cb-accent-soft: #FDE7E7; }
    .cb-toast.cb-warning { --cb-accent: #E08A00; --cb-accent-soft: #FFF2D9; }
    .cb-toast-icon {
      width: 68px;
      height: 68px;
      display: grid;
      place-items: center;
      border-radius: 50%;
      font-size: 2.2rem;
      color: var(--cb-accent);
      background: var(--cb-accent-soft);
    }
    .cb-toast-body { min-width: 0; }
    .cb-toast-title {
      margin: 0;
      font-size: 1.2rem;
      font-weight: 800;
      line-height: 1.3;
      color: var(--text-main, #2B1414);
    }
    .cb-toast-text {
      margin: 6px 0 0;
      font-size: .9rem;
      line-height: 1.45;
      color: var(--text-muted-green, #8C7370);
      word-break: break-word;
    }
    .cb-toast-close {
      position: absolute;
      top: 12px;
      right: 12px;
      width: 30px;
      height: 30px;
      display: grid;
      place-items: center;
      border: 0;
      border-radius: 9px;
      background: transparent;
      color: #A08F8B;
      font-size: .85rem;
      cursor: pointer;
      transition: background .15s, color .15s;
    }
    .cb-toast-close:hover { background: #F6ECE8; color: #5C1212; }
    .cb-toast-bar {
      position: absolute;
      left: 0;
      bottom: 0;
      height: 5px;
      width: 100%;
      background: var(--cb-accent);
      opacity: .4;
      transform-origin: left;
      animation: cbToastBar var(--cb-dur, 4500ms) linear forwards;
    }
    .cb-toast:hover .cb-toast-bar { animation-play-state: paused; }
    @keyframes cbToastIn  { to { opacity: 1; transform: translateY(0) scale(1); } }
    @keyframes cbToastOut { to { opacity: 0; transform: translateY(-10px) scale(.94); } }
    @keyframes cbToastBar { from { transform: scaleX(1); } to { transform: scaleX(0); } }
    @media (prefers-reduced-motion: reduce) {
      .cb-toast, .cb-toast.cb-hide { animation-duration: .01s; }
      .cb-toast-bar { display: none; }
    }
  </style>

  <!-- Slot CSS Tambahan -->
  @stack('styles')

  <!-- Final polish (admin & kasir): dimuat PALING AKHIR supaya menang atas CSS halaman -->
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/cimol-final.css') }}?v={{ @filemtime(public_path('assets_dashboard/css/cimol-final.css')) }}">

  <!-- Desain premium (paling akhir; hapus baris ini untuk kembali ke tampilan lama) -->
  <link rel="stylesheet" href="{{ asset('assets_dashboard/css/cimol-premium.css') }}?v={{ @filemtime(public_path('assets_dashboard/css/cimol-premium.css')) }}">
</head>

<body>

  <!-- ==========================================
       START: Sidebar Component
       ========================================== -->
  <div class="sidebar-wrapper" id="sidebar">
    <!-- Brand Logo -->
    <a href="{{ auth()->user()->role === 'admin' ? route('dashboard') : route('kasir.home') }}" class="sidebar-brand">
      {{--
        LOGO MUDAH DIGANTI:
        Tinggal timpa file public/assets_dashboard/images/logo.png
        dengan logo toko (disarankan gambar persegi, mis. 200x200px).
        Tidak perlu sentuh kode ini sama sekali.
        Kalau file logo belum ada / gagal dimuat, otomatis muncul
        inisial sebagai pengganti sementara (tidak akan tampil ikon
        gambar rusak).
      --}}
      <span class="sidebar-brand-logo">
        <img src="{{ asset('assets_dashboard/images/logo3.png') }}"
          alt="Logo {{ 'Cimol Bojot AA' }}"
          class="sidebar-brand-logo-img"
          onerror="this.closest('.sidebar-brand-logo').classList.add('logo-fallback'); this.remove();">
        <span class="sidebar-brand-logo-fallback">
          {{ strtoupper(substr('Cimol Bojot AA', 0, 2)) }}
        </span>
      </span>
      <span class="sidebar-brand-text">{{ 'Cimol Bojot AA' }}</span>
    </a>

    <!-- Navigation Menu -->
    <!--
      SIDEBAR BEDA PER ROLE:
      Admin & Kasir login ke aplikasi yang sama, tapi menu yang boleh
      mereka lihat beda (sesuai struktur fitur). Makanya seluruh menu
      di bawah dibungkus kondisi role admin/kasir.
      Kasir TIDAK dikasih lihat Dashboard (data omzet toko), Master
      Menu, Data Kasir, Manajemen Stok punya Admin, atau Laporan.
    -->
    <div class="flex-grow-1 overflow-y-auto">

      @if (auth()->user()->role === 'admin')

        <!-- ============ MENU ADMIN ============ -->

        <!-- Group 1: Dashboard Utama -->
        <div class="sidebar-menu-section mt-3">
          <ul class="sidebar-menu-list">
            <li class="sidebar-menu-item">
              <a href="{{ route('dashboard') }}"
                class="sidebar-menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                id="menu-overview" title="Dashboard">
                <i class="bi bi-grid-fill"></i>
                <span>Dashboard</span>
              </a>
            </li>
          </ul>
        </div>

        <!-- Group 2: Master -->
        <div class="sidebar-menu-section">
          <div class="sidebar-menu-title">MASTER</div>
          <ul class="sidebar-menu-list">
            <li class="sidebar-menu-item">
              <a href="{{ route('admin.menu.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.menu.*') ? 'active' : '' }}" id="menu-master-menu" title="Master Menu">
                <i class="bi bi-box-seam-fill"></i>
                <span>Master Menu</span>
              </a>
            </li>
            <li class="sidebar-menu-item">
              <a href="{{ route('admin.topping.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.topping.*') ? 'active' : '' }}" id="menu-master-topping" title="Topping & Bumbu">
                <i class="bi bi-droplet-fill"></i>
                <span>Topping & Bumbu</span>
              </a>
            </li>
            <li class="sidebar-menu-item">
              <a href="{{ route('admin.bahan-operasional.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.bahan-operasional.*') ? 'active' : '' }}" id="menu-master-bahan" title="Bahan Operasional">
                <i class="bi bi-fire"></i>
                <span>Bahan Operasional</span>
              </a>
            </li>
            <li class="sidebar-menu-item">
              <a href="{{ route('admin.kasir.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.kasir.*') ? 'active' : '' }}" id="menu-data-kasir" title="Data Kasir">
                <i class="bi bi-person-badge-fill"></i>
                <span>Data Kasir</span>
              </a>
            </li>
          </ul>
        </div>

        <!-- Group 3: Manajemen Stok -->
        <div class="sidebar-menu-section">
          <div class="sidebar-menu-title">MANAJEMEN STOK</div>
          <ul class="sidebar-menu-list">
            <li class="sidebar-menu-item">
              <a href="{{ route('admin.stok.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.stok.*') ? 'active' : '' }}" id="menu-stok" title="Manajemen Stok">
                <i class="bi bi-boxes"></i>
                <span>Manajemen Stok</span>
              </a>
            </li>
          </ul>
        </div>

        <!-- Group 4: Transaksi -->
        <div class="sidebar-menu-section">
          <div class="sidebar-menu-title">TRANSAKSI</div>
          <ul class="sidebar-menu-list">
            <li class="sidebar-menu-item">
              <a href="{{ route('admin.riwayat-transaksi.index') }}"
                class="sidebar-menu-link {{ request()->routeIs('admin.riwayat-transaksi.*') ? 'active' : '' }}"
                id="menu-transaksi" title="Riwayat Transaksi">
                <i class="bi bi-receipt"></i>
                <span>Riwayat Transaksi</span>
              </a>
            </li>
          </ul>
        </div>

        <!-- Group 5: Laporan -->
        <div class="sidebar-menu-section">
          <div class="sidebar-menu-title">LAPORAN</div>
          <ul class="sidebar-menu-list">
            <li class="sidebar-menu-item">
              <a href="{{ route('admin.laporan.index') }}"
                class="sidebar-menu-link {{ request()->routeIs('admin.laporan.*') ? 'active' : '' }}"
                id="menu-laporan" title="Laporan">
                <i class="bi bi-file-earmark-bar-graph-fill"></i>
                <span>Laporan</span>
              </a>
            </li>
          </ul>
        </div>

      @else

        <!-- ============ MENU KASIR ============ -->

        <div class="sidebar-menu-section mt-3">
          <ul class="sidebar-menu-list">
            <li class="sidebar-menu-item">
              <a href="{{ route('kasir.home') }}"
                class="sidebar-menu-link {{ request()->routeIs('kasir.home') ? 'active' : '' }}"
                id="menu-kasir-beranda" title="Beranda">
                <i class="bi bi-grid-fill"></i>
                <span>Beranda</span>
              </a>
            </li>
          </ul>
        </div>

        <div class="sidebar-menu-section">
          <div class="sidebar-menu-title">TRANSAKSI</div>
          <ul class="sidebar-menu-list">
            <li class="sidebar-menu-item">
              <a href="{{ route('kasir.transaksi.index') }}"
                class="sidebar-menu-link {{ request()->routeIs('kasir.transaksi.*') ? 'active' : '' }}"
                id="menu-kasir-transaksi" title="Transaksi">
                <i class="bi bi-cart-check-fill"></i>
                <span>Transaksi</span>
              </a>
            </li>
            <li class="sidebar-menu-item">
              <a href="{{ route('kasir.stok.index') }}"
                class="sidebar-menu-link {{ request()->routeIs('kasir.stok.*') ? 'active' : '' }}"
                id="menu-kasir-stok" title="Cek Stok">
                <i class="bi bi-boxes"></i>
                <span>Cek Stok</span>
              </a>
            </li>
            <li class="sidebar-menu-item">
              <a href="{{ route('kasir.riwayat-transaksi.index') }}"
                class="sidebar-menu-link {{ request()->routeIs('kasir.riwayat-transaksi.*') ? 'active' : '' }}"
                id="menu-kasir-riwayat" title="Riwayat Transaksi">
                <i class="bi bi-receipt"></i>
                <span>Riwayat Transaksi</span>
              </a>
            </li>
            <li class="sidebar-menu-item">
              <a href="{{ route('kasir.laporan.index') }}"
                class="sidebar-menu-link {{ request()->routeIs('kasir.laporan.*') ? 'active' : '' }}"
                id="menu-kasir-laporan" title="Laporan Saya">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Laporan Saya</span>
              </a>
            </li>
          </ul>
        </div>

        <div class="sidebar-menu-section">
          <div class="sidebar-menu-title">AKUN</div>
          <ul class="sidebar-menu-list">
            <li class="sidebar-menu-item">
              <a href="{{ route('profile.edit') }}" class="sidebar-menu-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}" id="menu-kasir-profil" title="Profil">
                <i class="bi bi-person-circle"></i>
                <span>Profil</span>
              </a>
            </li>
          </ul>
        </div>

      @endif

    </div>

    <!-- Sidebar Profile Card Bottom -->
    <a href="{{ route('profile.edit') }}" class="text-decoration-none">
      <div class="sidebar-profile">
        <img src="{{ auth()->user()->avatar_url ?? asset('assets_dashboard/images/Go Youn-Jung.png') }}"
          alt="{{ auth()->user()->name ?? 'Administrator' }}" class="sidebar-profile-img"
          onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMjgiIGhlaWdodD0iMTI4Ij48cmVjdCB3aWR0aD0iMTI4IiBoZWlnaHQ9IjEyOCIgZmlsbD0iIzhDMUYxRiIvPjwvc3ZnPg=='">
        <div class="sidebar-profile-info">
          <div class="sidebar-profile-name">{{ auth()->user()->name ?? 'Admin Cimol Bojot AA' }}</div>
          <div class="sidebar-profile-email">{{ auth()->user()->email ?? 'admin@gmail.com' }}</div>
        </div>
      </div>
    </a>
  </div>
  <!-- END: Sidebar Component -->


  <!-- ==========================================
       START: Main Content Area
       ========================================== -->
  <div class="main-wrapper">

    <!-- START: Top Navbar Component -->
    <header class="navbar-custom">
      <div class="navbar-left">
        <button class="btn-desktop-toggle d-none d-xl-flex align-items-center justify-content-center me-3"
          id="desktop-sidebar-toggle" aria-label="Minimize Sidebar">
          <i class="bi bi-chevron-bar-left"></i>
        </button>
        <button class="sidebar-toggle-btn me-2" id="sidebar-toggle" aria-label="Toggle Navigation">
          <i class="bi bi-list"></i>
        </button>

        <div class="navbar-greeting">
          <span class="navbar-greeting-icon"><i class="bi bi-file-earmark-text-fill"></i></span>
          <div style="min-width: 0;">
            <div class="navbar-greeting-title">Halo, {{ auth()->user()->name ?? 'Admin' }} 👋</div>
            <div class="navbar-greeting-sub">Selamat datang di aplikasi {{ 'Cimol Bojot AA' }}</div>
          </div>
        </div>
      </div>

      <div class="navbar-search-wrapper">
        <input type="text" class="navbar-search-input" placeholder="Cari fitur, mis. stok, transaksi, laporan..." id="main-search" autocomplete="off">
        <button class="navbar-search-btn" aria-label="Search">
          <i class="bi bi-search"></i>
        </button>
      </div>

      <div class="navbar-actions">
        <button class="navbar-action-btn me-1" aria-label="Toggle Fullscreen" id="btn-fullscreen">
          <i class="bi bi-arrows-fullscreen"></i>
        </button>

        <div class="dropdown">
          <button class="navbar-action-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
            aria-expanded="false" id="btn-notifications" data-bs-auto-close="outside">
            <i class="bi bi-bell"></i>
            @if (($notifStok ?? collect())->count() > 0)
              <span class="navbar-action-badge"></span>
            @endif
          </button>
          <div class="dropdown-menu dropdown-menu-end dropdown-menu-notification p-0"
            aria-labelledby="btn-notifications">
            <div class="notification-header">
              <h6 class="notification-title">Pemberitahuan Stok</h6>
              <span class="notif-count-pill">{{ ($notifStok ?? collect())->count() }}</span>
            </div>
            <div class="notification-list">
              @forelse (($notifStok ?? collect()) as $notif)
                <a href="{{ auth()->user()->role === 'admin' ? route('admin.stok.index') : route('kasir.stok.index') }}" class="notification-item">
                  <div class="notification-icon {{ $notif['status'] === 'habis' ? 'bg-danger text-white' : 'bg-warning text-dark' }}">
                    <i class="bi {{ $notif['status'] === 'habis' ? 'bi-x-circle' : 'bi-exclamation-triangle' }}"></i>
                  </div>
                  <div class="notification-content">
                    <p class="notification-text">
                      <strong>{{ $notif['nama'] }}</strong>
                      {{ $notif['pesan'] ?? ($notif['status'] === 'habis' ? 'sudah habis' : 'tersisa ' . $notif['stok']) }}
                    </p>
                    <span class="notification-time">{{ $notif['tipe'] }}</span>
                  </div>
                </a>
              @empty
                <div class="notification-empty">
                  <i class="bi bi-check2-circle"></i>
                  <span>Semua stok aman. Tidak ada pemberitahuan.</span>
                </div>
              @endforelse
            </div>
          </div>
        </div>

        <div class="dropdown ms-2">
          <button class="navbar-profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
            aria-expanded="false" id="profile-dropdown">
            <img src="{{ auth()->user()->avatar_url ?? asset('assets_dashboard/images/Go Youn-Jung.png') }}" alt="Profile Image"
              class="navbar-profile-img"
              onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMjgiIGhlaWdodD0iMTI4Ij48cmVjdCB3aWR0aD0iMTI4IiBoZWlnaHQ9IjEyOCIgZmlsbD0iIzhDMUYxRiIvPjwvc3ZnPg=='">
            <span class="navbar-profile-name d-none d-md-inline">{{ auth()->user()->name ?? 'Admin' }}</span>
            <i class="bi bi-chevron-down navbar-profile-caret"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-profile" aria-labelledby="profile-dropdown">
            <li class="dropdown-header">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Kasir' }}</li>
            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person"></i> Profil Saya</a></li>
            @if (auth()->user()->role === 'admin')
             
            @endif
            <li>
              <hr class="dropdown-divider">
            </li>
            <li>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item text-danger">
                  <i class="bi bi-box-arrow-right"></i> Keluar (Logout)
                </button>
              </form>
            </li>
          </ul>
        </div>
      </div>
    </header>
    <!-- END: Top Navbar Component -->

    <!-- ==========================================
         CONTENT AREA (Tampilan dari dashboard.blade.php)
         ========================================== -->
    @yield('content')

    <!-- START: Footer Component -->
    <footer class="footer-custom">
      <div class="footer-left">
        <span class="footer-logo">
          <i class="bi bi-shop text-warning"></i> {{ 'Cimol Bojot AA' }} POS
        </span>
        <span class="footer-separator">|</span>
        <span class="footer-copy">&copy; {{ date('Y') }} System Management</span>
      </div>
      <div class="footer-right">
        <ul class="footer-links">
          <li><a href="{{ route('profile.edit') }}" class="footer-link">Profil Saya</a></li>
          <li><span class="footer-link">Sistem Online <span class="status-dot"></span></span></li>
        </ul>
      </div>
    </footer>
    <!-- END: Footer Component -->

  </div>
  <!-- END: Main Content Area -->

  <!-- Local Third-Party Libraries JavaScript -->
  <script src="{{ asset('assets_dashboard/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets_dashboard/libs/apexcharts/apexcharts.min.js') }}"></script>
  <script src="{{ asset('assets_dashboard/libs/flatpickr/flatpickr.min.js') }}"></script>

  <!-- Slot Script Tambahan dari Halaman Child -->
  <script>
    // Pencarian fitur di navbar: ketik nama menu sidebar lalu Enter / klik
    // ikon cari, langsung pindah ke halaman yang cocok.
    (function () {
      var input = document.getElementById('main-search');
      if (!input) return;
      var tombol = input.parentElement.querySelector('.navbar-search-btn');

      function cari() {
        var kata = input.value.trim().toLowerCase();
        if (!kata) return;
        var tautan = document.querySelectorAll('.sidebar-menu-link');
        for (var i = 0; i < tautan.length; i++) {
          var teks = (tautan[i].textContent || '').trim().toLowerCase();
          if (teks.indexOf(kata) !== -1) {
            window.location.href = tautan[i].href;
            return;
          }
        }
        input.classList.add('search-not-found');
        input.setAttribute('title', 'Fitur tidak ditemukan');
        setTimeout(function () { input.classList.remove('search-not-found'); }, 1200);
      }

      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); cari(); }
      });
      if (tombol) tombol.addEventListener('click', cari);
    })();
  </script>

  <script>
    // Preview foto langsung saat memilih file (komponen partials.foto-upload).
    document.querySelectorAll('[data-foto-upload]').forEach(function (box) {
      var input = box.querySelector('[data-foto-input]');
      var img = box.querySelector('.foto-upload-img');
      var hapus = box.querySelector('[data-foto-hapus]');
      if (!input || !img) return;

      input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
          alert('Ukuran foto maksimal 2MB. Pilih foto yang lebih kecil ya.');
          input.value = '';
          return;
        }
        if (file.type && file.type.indexOf('image/') !== 0) {
          alert('File yang dipilih harus berupa gambar.');
          input.value = '';
          return;
        }

        img.src = URL.createObjectURL(file);
        box.classList.add('has-img');
        if (hapus) hapus.checked = false;
      });

      if (hapus) {
        hapus.addEventListener('change', function () {
          if (hapus.checked) {
            input.value = '';
            box.classList.remove('has-img');
          } else if (img.getAttribute('src')) {
            box.classList.add('has-img');
          }
        });
      }
    });
  </script>

  <!-- ==========================================
       Notifikasi toast global
       Dipakai semua halaman admin & kasir (karena semua extends layout ini).
       Membaca session('success') / session('error') dari controller, jadi
       tidak perlu ubah controller maupun view lain.
       ========================================== -->
  <div class="cb-toast-wrap" id="cb-toast-wrap" aria-live="polite" aria-atomic="true"></div>

  <script>
    (function () {
      var KUNCI = 'cimolAksiTerakhir';
      var flash = {
        success: @json(session('success')),
        error: @json(session('error')),
        validasi: @json($errors->any())
      };

      /* ---------- 1) Tangkap jenis aksi saat form dikirim ---------- */
      function tebakAksi(form) {
        var method = (form.getAttribute('method') || 'GET').toUpperCase();
        if (method !== 'POST') return null;              // form pencarian/filter (GET) diabaikan

        var spoof = form.querySelector('input[name="_method"]');
        if (spoof && spoof.value) method = spoof.value.toUpperCase();

        var url = (form.getAttribute('action') || '').toLowerCase();
        if (url.indexOf('/logout') !== -1 || url.indexOf('/login') !== -1) return null;

        if (method === 'DELETE' || url.indexOf('hapus') !== -1 || url.indexOf('destroy') !== -1) return 'hapus';
        if (url.indexOf('toggle-status') !== -1) return 'status';
        if (url.indexOf('reset-password') !== -1) return 'password';
        if (url.indexOf('koreksi') !== -1) return 'koreksi';
        if (url.indexOf('/stok/bahan/') !== -1 && url.indexOf('/ganti') !== -1) return 'ganti';
        if (url.indexOf('/stok/bahan/') !== -1 && url.indexOf('/goreng') !== -1) return 'goreng';
        if (url.indexOf('/transaksi') !== -1 && method === 'POST') return 'transaksi';
        if (method === 'PUT' || method === 'PATCH') return 'edit';
        return 'tambah';
      }

      document.addEventListener('submit', function (e) {
        if (e.defaultPrevented) return;                  // mis. dibatalkan lewat confirm()
        var aksi = tebakAksi(e.target);
        if (!aksi) return;
        try { sessionStorage.setItem(KUNCI, JSON.stringify({ aksi: aksi, t: Date.now() })); } catch (err) {}
      });

      /* ---------- 2) Teks notifikasi ---------- */
      var JUDUL = {
        tambah:    'Data berhasil ditambahkan',
        edit:      'Data berhasil diedit',
        hapus:     'Data berhasil dihapus',
        status:    'Status berhasil diperbarui',
        password:  'Password berhasil direset',
        koreksi:   'Stok berhasil dikoreksi',
        transaksi: 'Transaksi berhasil disimpan',
        ganti:     'Konfirmasi tersimpan',
        goreng:    'Hitungan goreng bertambah'
      };
      var JUDUL_GAGAL = {
        tambah: 'Data gagal ditambahkan',
        edit:   'Data gagal diedit',
        hapus:  'Data gagal dihapus'
      };

      function bacaAksi() {
        try {
          var mentah = sessionStorage.getItem(KUNCI);
          sessionStorage.removeItem(KUNCI);
          if (!mentah) return null;
          var d = JSON.parse(mentah);
          return (Date.now() - d.t < 60000) ? d.aksi : null;   // hanya berlaku untuk redirect barusan
        } catch (err) { return null; }
      }

      /* ---------- 3) Tampilkan toast ---------- */
      var wrap = document.getElementById('cb-toast-wrap');

      function esc(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
      }

      function tutup(el) {
        if (!el || el.classList.contains('cb-hide')) return;
        el.classList.add('cb-hide');
        setTimeout(function () { el.remove(); }, 300);
      }

      function toast(tipe, judul, teks, durasi) {
        durasi = durasi || 4500;
        var ikon = { success: 'bi-check-circle-fill', danger: 'bi-x-octagon-fill', warning: 'bi-exclamation-triangle-fill' }[tipe];
        var el = document.createElement('div');
        el.className = 'cb-toast cb-' + (tipe === 'success' ? 'success' : tipe);
        el.setAttribute('role', tipe === 'success' ? 'status' : 'alert');
        el.style.setProperty('--cb-dur', durasi + 'ms');
        el.innerHTML =
          '<div class="cb-toast-icon"><i class="bi ' + ikon + '"></i></div>' +
          '<div class="cb-toast-body">' +
            '<p class="cb-toast-title">' + esc(judul) + '</p>' +
            (teks ? '<p class="cb-toast-text">' + esc(teks) + '</p>' : '') +
          '</div>' +
          '<button type="button" class="cb-toast-close" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>' +
          '<span class="cb-toast-bar"></span>';

        var timer;
        function mulai() { timer = setTimeout(function () { tutup(el); }, durasi); }
        el.addEventListener('mouseenter', function () { clearTimeout(timer); });
        el.addEventListener('mouseleave', mulai);
        el.querySelector('.cb-toast-close').addEventListener('click', function () { clearTimeout(timer); tutup(el); });

        wrap.appendChild(el);
        mulai();
      }
      window.cimolToast = toast;   // bisa dipanggil manual: cimolToast('success', 'Judul', 'Keterangan')

      /* Sembunyikan alert bawaan halaman yang isinya sama dengan toast (biar tidak dobel) */
      function sembunyikanAlertLama(pesan) {
        if (!pesan) return;
        var target = pesan.replace(/\s+/g, ' ').trim();
        document.querySelectorAll('.main-wrapper .alert').forEach(function (a) {
          if (a.closest('.cb-toast-wrap')) return;
          var isi = (a.textContent || '').replace(/\s+/g, ' ').trim();
          if (isi === target) a.style.display = 'none';
        });
      }

      function sudahMenyebutAksi(pesan, aksi) {
        var p = (pesan || '').toLowerCase();
        var kata = { tambah: /tambah|dibuat|disimpan/, edit: /edit|ubah|diperbarui|update/, hapus: /hapus/,
                     status: /status|aktif|nonaktif/, password: /password|sandi/, koreksi: /koreksi/, transaksi: /transaksi/ }[aksi];
        return kata ? kata.test(p) : false;
      }

      function jalankan() {
        var aksi = bacaAksi();

        if (flash.success) {
          var judul = JUDUL[aksi];
          var teks = flash.success;
          if (!judul) { judul = 'Berhasil'; }
          else if (sudahMenyebutAksi(flash.success, aksi)) { teks = ''; }   // pesan controller sudah jelas, tidak perlu diulang
          toast('success', judul, teks);
          sembunyikanAlertLama(flash.success);
        }

        if (flash.error) {
          toast('danger', JUDUL_GAGAL[aksi] || 'Terjadi kesalahan', flash.error, 6500);
          sembunyikanAlertLama(flash.error);
        } else if (flash.validasi && aksi) {
          toast('warning', JUDUL_GAGAL[aksi] || 'Data belum tersimpan', 'Periksa kembali isian form yang ditandai merah.', 6000);
        }
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', jalankan);
      } else {
        jalankan();
      }
    })();
  </script>


  <!-- ==========================================
       Dialog konfirmasi modern (pengganti confirm() bawaan browser).
       Pakai di form: data-confirm="Judul" data-confirm-text="Keterangan"
       data-confirm-yes="Ya, Hapus" data-confirm-tone="danger|warn|ok"
       ========================================== -->
  <div class="modal fade cf-modal" id="cf-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content">
        <div class="cf-ico" id="cf-ico"><i class="bi bi-trash3-fill"></i></div>
        <h5 id="cf-judul">Yakin?</h5>
        <p id="cf-teks"></p>
        <div class="cf-actions">
          <button type="button" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="cf-ya" id="cf-ya">Ya, Lanjutkan</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function () {
      var modalEl = document.getElementById('cf-modal');
      if (!modalEl || !window.bootstrap) return;
      var modal = new bootstrap.Modal(modalEl);
      var formTertunda = null;
      var IKON = { danger: 'bi-trash3-fill', warn: 'bi-exclamation-triangle-fill', ok: 'bi-check2-circle' };

      document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.hasAttribute || !form.hasAttribute('data-confirm')) return;
        if (form.dataset.confirmed === '1') { form.dataset.confirmed = ''; return; }
        e.preventDefault();
        formTertunda = form;

        var tone = form.getAttribute('data-confirm-tone') || 'danger';
        var ico = document.getElementById('cf-ico');
        ico.className = 'cf-ico ' + (tone === 'danger' ? '' : tone);
        ico.innerHTML = '<i class="bi ' + (IKON[tone] || IKON.danger) + '"></i>';
        document.getElementById('cf-judul').textContent = form.getAttribute('data-confirm');
        document.getElementById('cf-teks').textContent = form.getAttribute('data-confirm-text') || '';
        var ya = document.getElementById('cf-ya');
        ya.textContent = form.getAttribute('data-confirm-yes') || 'Ya, Lanjutkan';
        ya.classList.toggle('is-ok', tone === 'ok');
        modal.show();
      }, true);

      document.getElementById('cf-ya').addEventListener('click', function () {
        if (!formTertunda) return;
        var form = formTertunda;
        formTertunda = null;
        modal.hide();
        form.dataset.confirmed = '1';
        // requestSubmit() memicu event submit lagi, jadi toast notifikasi tetap tahu jenis aksinya.
        if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
      });
    })();
  </script>

  @stack('scripts')
</body>

</html>