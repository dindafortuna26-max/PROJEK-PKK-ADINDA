@extends('layouts.admin')

@section('title', 'Daftar Menu - ' . config('app.name', 'Cimol Bojot AA'))

@section('content')

  <!-- ===================== HERO ===================== -->
  <div class="hero">
    <div>
      <span class="hero-eyebrow"><i class="bi bi-egg-fried"></i> Master Data</span>
      <h1>Daftar Menu</h1>
      <p>Atur menu {{ config('app.name', 'Cimol Bojot AA') }}: foto, kategori, ukuran, harga, dan status tampil di kasir.</p>
    </div>
    <a href="{{ route('admin.menu.create') }}" class="hero-btn">
      <i class="bi bi-plus-lg"></i>
      <span>Tambah Menu</span>
    </a>
  </div>

  @if (session('success'))
    <div class="alert alert-success mb-4" role="alert">{{ session('success') }}</div>
  @endif

  <!-- ===================== RINGKASAN ===================== -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="mstat">
        <span class="mstat-ico"><i class="bi bi-grid-3x3-gap-fill"></i></span>
        <div><div class="mstat-label">Total Menu</div><div class="mstat-value">{{ $ringkasan['total'] }}</div></div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="mstat ok">
        <span class="mstat-ico"><i class="bi bi-check-circle-fill"></i></span>
        <div><div class="mstat-label">Aktif</div><div class="mstat-value">{{ $ringkasan['aktif'] }}</div></div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="mstat bad">
        <span class="mstat-ico"><i class="bi bi-pause-circle-fill"></i></span>
        <div><div class="mstat-label">Nonaktif</div><div class="mstat-value">{{ $ringkasan['nonaktif'] }}</div></div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="mstat gold">
        <span class="mstat-ico"><i class="bi bi-tags-fill"></i></span>
        <div><div class="mstat-label">Kategori</div><div class="mstat-value">{{ $ringkasan['kategori'] }}</div></div>
      </div>
    </div>
  </div>

  <!-- ===================== TOOLBAR: CARI + KATEGORI ===================== -->
  <form action="{{ route('admin.menu.index') }}" method="GET" class="toolbar">
    @if (request('kategori_id'))
      <input type="hidden" name="kategori_id" value="{{ request('kategori_id') }}">
    @endif

    <div class="toolbar-search">
      <i class="bi bi-search"></i>
      <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari nama menu...">
    </div>

    <div class="chips">
      <a href="{{ route('admin.menu.index', request()->only('cari')) }}" class="chip {{ request('kategori_id') ? '' : 'active' }}">
        <i class="bi bi-collection"></i> Semua
      </a>
      @foreach ($kategoris as $kategori)
        <a href="{{ route('admin.menu.index', request()->only('cari') + ['kategori_id' => $kategori->id]) }}"
          class="chip {{ (string) request('kategori_id') === (string) $kategori->id ? 'active' : '' }}">
          {{ $kategori->nama }}
        </a>
      @endforeach
      @if (request('cari') || request('kategori_id'))
        <a href="{{ route('admin.menu.index') }}" class="chip"><i class="bi bi-x-lg"></i> Reset</a>
      @endif
    </div>
  </form>

  <!-- ===================== KARTU MENU ===================== -->
  <div class="card-grid">
    @forelse ($menus as $menu)
      <div class="mn-card {{ $menu->is_active ? '' : 'is-off' }}">
        <div class="mn-photo" @if ($menu->gambar) style="--photo: url('{{ asset('storage/' . $menu->gambar) }}')" @endif>
          <img
            src="{{ $menu->gambar ? asset('storage/' . $menu->gambar) : asset('assets_dashboard/images/logo3.png') }}"
            alt="{{ $menu->nama }}"
            class="{{ $menu->gambar ? '' : 'is-logo' }}"
            loading="lazy"
            onerror="this.classList.add('is-logo'); this.src='{{ asset('assets_dashboard/images/logo3.png') }}'; this.onerror=null;"
          >
          <span class="mn-kat">{{ $menu->kategori->nama ?? 'Tanpa kategori' }}</span>
          <span class="mn-status {{ $menu->is_active ? '' : 'off' }}">{{ $menu->is_active ? 'Aktif' : 'Nonaktif' }}</span>
        </div>

        <div class="mn-body">
          <h3 class="mn-nama">{{ $menu->nama }}</h3>
          <p class="mn-desk">{{ \Illuminate\Support\Str::limit($menu->deskripsi, 70) ?: 'Belum ada deskripsi.' }}</p>
          <div class="mn-harga">{{ $menu->harga_label }}</div>
          <div class="mn-sizes">
            @forelse ($menu->ukuranAktif as $ukuran)
              <span class="mn-size">{{ $ukuran->nama }}</span>
            @empty
              <span class="mn-size none">Ukuran belum diatur</span>
            @endforelse
          </div>
        </div>

        <div class="mn-actions">
          <a href="{{ route('admin.menu.edit', $menu) }}" class="act primary">
            <i class="bi bi-pencil-fill"></i><span>Edit</span>
          </a>

          <a href="{{ route('admin.menu.ukuran.edit', $menu) }}" class="act icon" title="Atur ukuran &amp; harga">
            <i class="bi bi-sliders"></i>
          </a>

          <form action="{{ route('admin.menu.toggle-status', $menu) }}" method="POST" class="m-0"
            data-confirm="{{ $menu->is_active ? 'Nonaktifkan' : 'Aktifkan' }} menu ini?"
            data-confirm-text="{{ $menu->is_active ? $menu->nama . ' tidak akan muncul di halaman kasir.' : $menu->nama . ' akan tampil kembali di halaman kasir.' }}"
            data-confirm-yes="{{ $menu->is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan' }}"
            data-confirm-tone="{{ $menu->is_active ? 'warn' : 'ok' }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="act icon {{ $menu->is_active ? 'warn' : 'good' }}" title="{{ $menu->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
              <i class="bi {{ $menu->is_active ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' }}"></i>
            </button>
          </form>
        </div>
      </div>
    @empty
      <div class="empty-state">
        <i class="bi bi-egg-fried"></i>
        <b>{{ request('cari') || request('kategori_id') ? 'Menu tidak ditemukan' : 'Belum ada menu' }}</b>
        {{ request('cari') || request('kategori_id') ? 'Coba ubah kata kunci atau kategori.' : 'Klik "Tambah Menu" untuk mulai menambahkan.' }}
      </div>
    @endforelse
  </div>

  <div class="foot-bar">
    <span>Menampilkan {{ $menus->count() }} dari {{ $menus->total() }} menu</span>
    {{ $menus->links() }}
  </div>

@endsection
