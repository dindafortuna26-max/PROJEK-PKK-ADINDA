{{--
  Isian form Tambah/Edit Bumbu & Topping.
  Variabel: $topping (null = tambah baru), $prefix (id unik), $aktif (true kalau
  modal ini yang barusan gagal validasi, supaya old() dan error dipakai).
--}}
@php
  $isEdit = ! empty($topping);
  $pakaiOld = $aktif ?? false;
  $val = function ($field, $default = '') use ($topping, $isEdit, $pakaiOld) {
      if ($pakaiOld) {
          return old($field, $default);
      }
      return $isEdit ? ($topping->{$field} ?? $default) : $default;
  };
  $jenisNilai = $val('jenis', 'saus');
@endphp

<div class="mb-3">
  @include('partials.foto-upload', [
      'id' => $prefix . '-foto',
      'name' => 'gambar',
      'src' => $isEdit ? $topping->gambar_url : null,
      'icon' => 'bi-image',
      'hapus' => $isEdit ? 'hapus_gambar' : null,
      'shape' => 'square',
      'hint' => 'Foto bumbu/topping (JPG/PNG, maksimal 2MB).',
      'error' => $pakaiOld ? $errors->first('gambar') : null,
  ])
</div>

<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label-custom">Jenis</label>
    <select name="jenis" class="form-select-custom w-100 {{ $pakaiOld && $errors->has('jenis') ? 'is-invalid-custom' : '' }}">
      <option value="saus" {{ $jenisNilai === 'saus' ? 'selected' : '' }}>Bumbu</option>
      <option value="bubuk_tabur" {{ $jenisNilai === 'bubuk_tabur' ? 'selected' : '' }}>Topping</option>
    </select>
    @if ($pakaiOld)
      @error('jenis')
        <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
      @enderror
    @endif
  </div>

  <div class="col-md-6">
    <label class="form-label-custom">Nama</label>
    <input type="text" name="nama" value="{{ $val('nama') }}"
      class="form-control-custom {{ $pakaiOld && $errors->has('nama') ? 'is-invalid-custom' : '' }}"
      placeholder="contoh: Asin Pedas">
    @if ($pakaiOld)
      @error('nama')
        <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
      @enderror
    @endif
  </div>

  <div class="col-md-6">
    <label class="form-label-custom">Harga Tambahan</label>
    <div class="input-prefix">
      <span class="input-prefix-text">Rp</span>
      <input type="number" name="harga_tambahan" min="0" value="{{ $val('harga_tambahan', 0) }}"
        class="form-control-custom {{ $pakaiOld && $errors->has('harga_tambahan') ? 'is-invalid-custom' : '' }}" placeholder="0">
    </div>
    <span class="form-feedback-custom">Isi 0 kalau gratis.</span>
    @if ($pakaiOld)
      @error('harga_tambahan')
        <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
      @enderror
    @endif
  </div>

  <div class="col-md-6">
    <label class="form-label-custom">Batas Stok Menipis</label>
    <input type="number" name="stok_minimum" min="0" value="{{ $val('stok_minimum', 5) }}"
      class="form-control-custom {{ $pakaiOld && $errors->has('stok_minimum') ? 'is-invalid-custom' : '' }}">
    <span class="form-feedback-custom">Stok ≤ angka ini ditandai "Menipis".</span>
  </div>
</div>
