{{--
  Thumbnail foto kecil dengan placeholder ikon otomatis kalau belum ada
  foto / fotonya gagal dimuat.
  Pemakaian:
    @include('partials.thumb', ['src' => $topping->gambar_url, 'icon' => 'bi-droplet-fill', 'size' => 44, 'alt' => $topping->nama])
--}}
@php
  $thumbSize = $size ?? 44;
  $thumbIcon = $icon ?? 'bi-image';
@endphp
<span class="thumb-box" style="--thumb-size: {{ $thumbSize }}px;">
  <i class="bi {{ $thumbIcon }} thumb-box-icon"></i>
  @if (! empty($src))
    <img src="{{ $src }}" alt="{{ $alt ?? '' }}" class="thumb-box-img" loading="lazy" onerror="this.remove()">
  @endif
</span>
