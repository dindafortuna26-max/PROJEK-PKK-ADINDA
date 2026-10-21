{{--
  Isian form Tambah/Edit Kasir.
  Variabel: $kasir (null = tambah baru), $prefix (id unik), $aktif (true kalau
  modal ini yang barusan gagal validasi -> pakai old() & tampilkan error),
  $denganPassword (true hanya untuk form Tambah).
--}}
@php
  $isEdit = ! empty($kasir);
  $pakaiOld = $aktif ?? false;
  $val = function ($field, $default = '') use ($kasir, $isEdit, $pakaiOld) {
      if ($pakaiOld) {
          return old($field, $default);
      }
      return $isEdit ? ($kasir->{$field} ?? $default) : $default;
  };
  $tglLahir = $pakaiOld
      ? old('tanggal_lahir')
      : ($isEdit && $kasir->tanggal_lahir ? $kasir->tanggal_lahir->toDateString() : '');
  $jk = $val('jenis_kelamin', '');
@endphp

<div class="mb-4">
  <label class="form-label-custom">Foto Profil</label>
  @include('partials.foto-upload', [
      'id' => $prefix . '-foto',
      'name' => 'foto',
      'src' => ($isEdit && $kasir->punyaFoto()) ? $kasir->avatar_url : null,
      'icon' => 'bi-person-fill',
      'hapus' => $isEdit ? 'hapus_foto' : null,
      'shape' => 'circle',
      'hint' => 'JPG/PNG, maksimal 2MB (opsional).',
      'error' => $pakaiOld ? $errors->first('foto') : null,
  ])
</div>

<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label-custom">Nama Kasir</label>
    <input type="text" name="name" value="{{ $val('name') }}"
      class="form-control-custom {{ $pakaiOld && $errors->has('name') ? 'is-invalid-custom' : '' }}"
      placeholder="contoh: Rani Putri">
    @if ($pakaiOld)
      @error('name')
        <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
      @enderror
    @endif
  </div>

  <div class="col-md-6">
    <label class="form-label-custom">Email</label>
    <input type="email" name="email" value="{{ $val('email') }}"
      class="form-control-custom {{ $pakaiOld && $errors->has('email') ? 'is-invalid-custom' : '' }}"
      placeholder="contoh: rani@gmail.com">
    <span class="form-feedback-custom">Dipakai kasir untuk login.</span>
    @if ($pakaiOld)
      @error('email')
        <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
      @enderror
    @endif
  </div>

  <div class="col-md-6">
    <label class="form-label-custom">No. HP</label>
    <input type="text" name="no_hp" inputmode="tel" value="{{ $val('no_hp') }}"
      class="form-control-custom {{ $pakaiOld && $errors->has('no_hp') ? 'is-invalid-custom' : '' }}"
      placeholder="contoh: 0812 3456 7890">
    @if ($pakaiOld)
      @error('no_hp')
        <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
      @enderror
    @endif
  </div>

  <div class="col-md-6">
    <label class="form-label-custom">Tanggal Lahir</label>
    <input type="date" name="tanggal_lahir" max="{{ now()->subDay()->toDateString() }}" value="{{ $tglLahir }}"
      class="form-control-custom {{ $pakaiOld && $errors->has('tanggal_lahir') ? 'is-invalid-custom' : '' }}">
    @if ($pakaiOld)
      @error('tanggal_lahir')
        <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
      @enderror
    @endif
  </div>

  <div class="col-12">
    <label class="form-label-custom">Jenis Kelamin</label>
    <div class="radio-cards">
      <label class="radio-card">
        <input type="radio" name="jenis_kelamin" value="L" {{ $jk === 'L' ? 'checked' : '' }}>
        <span class="radio-card-body"><i class="bi bi-gender-male"></i> Laki-laki</span>
      </label>
      <label class="radio-card">
        <input type="radio" name="jenis_kelamin" value="P" {{ $jk === 'P' ? 'checked' : '' }}>
        <span class="radio-card-body"><i class="bi bi-gender-female"></i> Perempuan</span>
      </label>
    </div>
  </div>

  <div class="col-12">
    <label class="form-label-custom">Alamat</label>
    <textarea name="alamat" rows="2" class="form-control-custom" placeholder="Alamat (opsional)">{{ $val('alamat') }}</textarea>
  </div>

  @if (! empty($denganPassword))
    <div class="col-md-6">
      <label class="form-label-custom">Password</label>
      <input type="password" name="password" autocomplete="new-password"
        class="form-control-custom {{ $pakaiOld && $errors->has('password') ? 'is-invalid-custom' : '' }}"
        placeholder="Minimal 6 karakter">
      @if ($pakaiOld)
        @error('password')
          <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
        @enderror
      @endif
    </div>

    <div class="col-md-6">
      <label class="form-label-custom">Konfirmasi Password</label>
      <input type="password" name="password_confirmation" autocomplete="new-password" class="form-control-custom" placeholder="Ulangi password">
    </div>
  @endif
</div>
