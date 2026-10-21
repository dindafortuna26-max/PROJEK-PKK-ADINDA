{{--
  Komponen upload foto dengan preview langsung (dipakai di Profil, Data
  Kasir, dan Master Topping & Bumbu).

  Parameter:
    id        (wajib)  id unik input file
    name      (opsional, default 'foto') nama field file
    src       URL foto saat ini (null kalau belum ada)
    icon      ikon placeholder (default bi-camera)
    hapus     nama field checkbox "hapus foto" (null = tidak ada opsi hapus)
    shape     'circle' (default) atau 'square'
    hint      teks bantuan
    error     pesan error validasi (opsional)
--}}
@php
  $fuName = $name ?? 'foto';
  $fuShape = $shape ?? 'circle';
  $fuIcon = $icon ?? 'bi-camera';
  $fuHint = $hint ?? 'JPG/PNG, maksimal 2MB.';
@endphp
<div class="foto-upload {{ ! empty($src) ? 'has-img' : '' }}" data-foto-upload>
  <div class="foto-upload-preview shape-{{ $fuShape }}">
    <i class="bi {{ $fuIcon }} foto-upload-placeholder"></i>
    <img src="{{ $src ?? '' }}" alt="Preview foto" class="foto-upload-img" onerror="this.closest('[data-foto-upload]').classList.remove('has-img')">
  </div>
  <div class="foto-upload-body">
    <label for="{{ $id }}" class="btn-custom btn-custom-sm btn-custom-outline-secondary mb-0">
      <i class="bi bi-upload"></i>
      <span>{{ ! empty($src) ? 'Ganti Foto' : 'Pilih Foto' }}</span>
    </label>
    <input type="file" id="{{ $id }}" name="{{ $fuName }}" accept="image/*" class="d-none" data-foto-input>
    @if (! empty($hapus) && ! empty($src))
      <label class="foto-upload-hapus">
        <input type="checkbox" name="{{ $hapus }}" value="1" data-foto-hapus>
        <span>Hapus foto</span>
      </label>
    @endif
    <span class="foto-upload-hint">{{ $fuHint }}</span>
    @if (! empty($error))
      <span class="form-feedback-custom invalid-custom">{{ $error }}</span>
    @endif
  </div>
</div>
