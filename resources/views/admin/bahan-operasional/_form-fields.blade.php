{{--
  Isian form Tambah/Edit Bahan Operasional.
  Variabel: $bahan (null = tambah baru), $aktif (true kalau modal ini yang
  barusan gagal validasi, supaya old() dan pesan error dipakai).
--}}
@php
  $isEdit = ! empty($bahan);
  $pakaiOld = $aktif ?? false;
  $val = function ($field, $default = '') use ($bahan, $isEdit, $pakaiOld) {
      if ($pakaiOld) {
          return old($field, $default);
      }
      return $isEdit ? ($bahan->{$field} ?? $default) : $default;
  };
@endphp

<div class="row g-3">
  <div class="col-12">
    <label class="form-label-custom">Foto Bahan <span class="bo-opsional">(opsional)</span></label>
    <div class="bo-foto" data-bo-foto>
      <span class="bo-foto-preview">
        <i class="bi {{ $isEdit ? $bahan->ikon : 'bi-image' }} bo-foto-ikon {{ $isEdit && $bahan->gambar_url ? 'd-none' : '' }}"></i>
        <img src="{{ $isEdit ? $bahan->gambar_url : '' }}" alt="" class="bo-foto-img {{ $isEdit && $bahan->gambar_url ? '' : 'd-none' }}"
          data-awal="{{ $isEdit ? $bahan->gambar_url : '' }}">
      </span>
      <div class="bo-foto-aksi">
        <label class="btn-custom btn-custom-sm btn-custom-outline-secondary mb-0" style="cursor:pointer;">
          <i class="bi bi-camera-fill"></i>
          <span data-bo-foto-teks>{{ $isEdit && $bahan->gambar_url ? 'Ganti Foto' : 'Tambah Foto' }}</span>
          <input type="file" name="gambar" accept="image/png,image/jpeg,image/webp" class="d-none" data-bo-foto-input>
        </label>
        @if ($isEdit && $bahan->gambar_url)
          <label class="bo-foto-hapus">
            <input type="checkbox" name="hapus_gambar" value="1" data-bo-foto-hapus>
            <span>Hapus foto</span>
          </label>
        @endif
        <span class="form-feedback-custom">JPG, PNG, atau WEBP. Maksimal 2 MB.</span>
        <span class="form-feedback-custom invalid-custom d-none" data-bo-foto-error></span>
        @if ($pakaiOld)
          @error('gambar')
            <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
          @enderror
        @endif
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <label class="form-label-custom">Nama Bahan</label>
    <input type="text" name="nama" value="{{ $val('nama') }}"
      class="form-control-custom {{ $pakaiOld && $errors->has('nama') ? 'is-invalid-custom' : '' }}"
      placeholder="contoh: Minyak Goreng / Gas">
    @if ($pakaiOld)
      @error('nama')
        <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
      @enderror
    @endif
  </div>

  <div class="col-md-4">
    <label class="form-label-custom">Satuan Stok</label>
    <input type="text" name="satuan" value="{{ $val('satuan', 'liter') }}"
      class="form-control-custom {{ $pakaiOld && $errors->has('satuan') ? 'is-invalid-custom' : '' }}"
      placeholder="liter / tabung">
    @if ($pakaiOld)
      @error('satuan')
        <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
      @enderror
    @endif
  </div>

  <div class="col-md-6">
    <label class="form-label-custom">Setara Berapa Sesi Goreng</label>
    <div class="input-prefix">
      <input type="number" name="sesi_per_satuan" min="1" value="{{ $val('sesi_per_satuan', 10) }}"
        class="form-control-custom {{ $pakaiOld && $errors->has('sesi_per_satuan') ? 'is-invalid-custom' : '' }}">
      <span class="input-prefix-text">sesi / satuan</span>
    </div>
    <span class="form-feedback-custom">1 sesi = 1 transaksi. Minyak: 1 liter = 10 sesi. Gas: 1 tabung 3 kg = 20 sesi.</span>
    @if ($pakaiOld)
      @error('sesi_per_satuan')
        <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
      @enderror
    @endif
  </div>

  <div class="col-md-6">
    <label class="form-label-custom">Batas Menipis</label>
    <div class="input-prefix">
      <input type="number" name="stok_minimum" min="0" value="{{ $val('stok_minimum', 3) }}"
        class="form-control-custom {{ $pakaiOld && $errors->has('stok_minimum') ? 'is-invalid-custom' : '' }}">
      <span class="input-prefix-text">sesi</span>
    </div>
    <span class="form-feedback-custom">Sisa sesi ≤ angka ini ditandai "Menipis" dan muncul di notifikasi.</span>
    @if ($pakaiOld)
      @error('stok_minimum')
        <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
      @enderror
    @endif
  </div>
</div>
