{{-- Kartu satu jenis (bumbu / topping). Variabel: $list, $labelNama, $ikon, $teksKosong --}}
<div class="tp-grid">
  @forelse ($list as $topping)
    <article class="tp-card {{ $topping->is_active ? '' : 'is-off' }}">
      <div class="tp-photo">
        <i class="bi {{ $ikon }}"></i>
        @if ($topping->gambar_url)
          <img src="{{ $topping->gambar_url }}" alt="{{ $topping->nama }}" loading="lazy" onerror="this.remove()">
        @endif
        <span class="tp-badge">{{ $topping->jenis_label }}</span>
        <span class="tp-status {{ $topping->is_active ? 'on' : 'off' }}">{{ $topping->is_active ? 'Aktif' : 'Nonaktif' }}</span>
      </div>

      <div class="tp-body">
        <h3 class="tp-nama">{{ $topping->nama }}</h3>
        <div class="tp-meta">
          @if ($topping->harga_tambahan > 0)
            <span class="tp-harga">+ Rp {{ number_format($topping->harga_tambahan, 0, ',', '.') }}</span>
          @else
            <span class="tp-harga gratis">Gratis</span>
          @endif
          <span class="tp-min"><i class="bi bi-bell"></i> Menipis ≤ {{ $topping->stok_minimum }}</span>
        </div>
      </div>

      <div class="tp-actions">
        <button type="button" class="tp-act edit" data-bs-toggle="modal" data-bs-target="#modal-edit-topping-{{ $topping->id }}">
          <i class="bi bi-pencil-fill"></i><span>Edit</span>
        </button>
        <form action="{{ route('admin.topping.toggle-status', $topping) }}" method="POST">
          @csrf
          @method('PATCH')
          <button type="submit" class="tp-act" title="{{ $topping->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
            <i class="bi {{ $topping->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
          </button>
        </form>
        <form action="{{ route('admin.topping.destroy', $topping) }}" method="POST"
          onsubmit="return confirm('Hapus &quot;{{ $topping->nama }}&quot; permanen? Yang sudah punya riwayat stok/transaksi tidak bisa dihapus (nonaktifkan saja).')">
          @csrf
          @method('DELETE')
          <button type="submit" class="tp-act danger" title="Hapus"><i class="bi bi-trash"></i></button>
        </form>
      </div>
    </article>
  @empty
    <div class="tp-empty">
      <i class="bi {{ $ikon }}"></i>
      {{ $teksKosong }}
    </div>
  @endforelse
</div>
