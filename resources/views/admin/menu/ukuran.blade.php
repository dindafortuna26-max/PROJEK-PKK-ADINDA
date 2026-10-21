@extends('layouts.admin')

@section('title', 'Atur Ukuran & Harga - ' . config('app.name', 'Cimol Bojot AA'))

@section('content')

  <div class="page-header">
    <div>
      <h1 class="page-title">Atur Ukuran &amp; Harga</h1>
      <p class="page-subtitle">Menu: <strong>{{ $menu->nama }}</strong></p>
    </div>
    <a href="{{ route('admin.menu.index') }}" class="btn-custom btn-custom-outline-secondary">
      <i class="bi bi-arrow-left"></i>
      <span>Kembali</span>
    </a>
  </div>

  <div class="table-card-custom">
    <form action="{{ route('admin.menu.ukuran.update', $menu) }}" method="POST" class="p-4">
      @csrf
      @method('PUT')

      <p class="table-user-sub mb-4">
        Aktifkan ukuran yang tersedia untuk menu ini dan isi harga jualnya.
        Ukuran yang dikosongkan harganya tidak akan muncul di halaman Transaksi kasir.
      </p>

      @foreach ($ukurans as $ukuran)
        @php
          $pivot = $menuUkurans->get($ukuran->id);
        @endphp
        <div class="d-flex align-items-center gap-3 py-3" style="border-bottom: 1px solid rgba(11,19,15,0.06);">
          <div class="form-switch-custom mb-0" style="min-width: 160px;">
            <input
              type="checkbox"
              name="aktif[{{ $ukuran->id }}]"
              value="1"
              class="form-switch-input-custom"
              {{ old('aktif.' . $ukuran->id, $pivot?->is_active ?? true) ? 'checked' : '' }}
            >
            <span class="table-product-name mb-0">{{ $ukuran->nama }}</span>
          </div>

          <div class="input-group-custom" style="max-width: 220px;">
            <span class="input-group-text-custom">Rp</span>
            <input
              type="number"
              name="harga[{{ $ukuran->id }}]"
              value="{{ old('harga.' . $ukuran->id, $pivot?->harga) }}"
              min="0"
              placeholder="Belum diatur"
              class="form-control-custom"
            >
          </div>
        </div>
        @error('harga.' . $ukuran->id)
          <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
        @enderror
      @endforeach

      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn-custom btn-custom-primary">
          <i class="bi bi-check-lg"></i>
          <span>Simpan Ukuran &amp; Harga</span>
        </button>
        <a href="{{ route('admin.menu.index') }}" class="btn-custom btn-custom-outline-secondary">
          <span>Batal</span>
        </a>
      </div>
    </form>
  </div>

@endsection
