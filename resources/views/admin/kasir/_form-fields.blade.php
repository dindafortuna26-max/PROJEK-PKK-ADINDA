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
  $salah = fn ($f) => $pakaiOld && $errors->has($f) ? 'kp-invalid' : '';
@endphp

<!-- ============ FOTO ============ -->
<section class="kp-sec">
  <h6 class="kp-sec-title">Foto profil</h6>
  <p class="kp-sec-sub">Opsional. Kalau kosong, akan dipakai inisial nama.</p>
  @include('partials.foto-upload', [
      'id' => $prefix . '-foto',
      'name' => 'foto',
      'src' => ($isEdit && $kasir->punyaFoto()) ? $kasir->avatar_url : null,
      'icon' => 'bi-person-fill',
      'hapus' => $isEdit ? 'hapus_foto' : null,
      'shape' => 'circle',
      'hint' => 'JPG/PNG, maksimal 2MB.',
      'error' => $pakaiOld ? $errors->first('foto') : null,
  ])
</section>

<!-- ============ DATA DIRI ============ -->
<section class="kp-sec">
  <h6 class="kp-sec-title">Data diri</h6>
  <p class="kp-sec-sub">Nama dan email dipakai kasir untuk masuk ke aplikasi.</p>

  <div class="kp-cols">
    <div>
      <label class="kp-label" for="{{ $prefix }}-name">Nama kasir</label>
      <div class="kp-field">
        <i class="bi bi-person lead"></i>
        <input type="text" id="{{ $prefix }}-name" name="name" value="{{ $val('name') }}" class="kp-input {{ $salah('name') }}" placeholder="contoh: Rani Putri">
      </div>
      @if ($pakaiOld)
        @error('name') <span class="kp-err">{{ $message }}</span> @enderror
      @endif
    </div>

    <div>
      <label class="kp-label" for="{{ $prefix }}-email">Email</label>
      <div class="kp-field">
        <i class="bi bi-envelope lead"></i>
        <input type="email" id="{{ $prefix }}-email" name="email" value="{{ $val('email') }}" class="kp-input {{ $salah('email') }}" placeholder="contoh: rani@gmail.com">
      </div>
      @if ($pakaiOld)
        @error('email') <span class="kp-err">{{ $message }}</span> @enderror
      @endif
    </div>

    <div>
      <label class="kp-label" for="{{ $prefix }}-hp">No. HP</label>
      <div class="kp-field">
        <i class="bi bi-telephone lead"></i>
        <input type="text" id="{{ $prefix }}-hp" name="no_hp" inputmode="numeric" maxlength="13" pattern="[0-9]*" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,13)" value="{{ $val('no_hp') }}" class="kp-input {{ $salah('no_hp') }}" placeholder="contoh: 081234567890">
      </div>
      @if ($pakaiOld)
        @error('no_hp') <span class="kp-err">{{ $message }}</span> @enderror
      @endif
    </div>

    <div>
      <label class="kp-label" for="{{ $prefix }}-tgl">Tanggal lahir</label>
      <div class="kp-field">
        <i class="bi bi-cake2 lead"></i>
        <input type="date" id="{{ $prefix }}-tgl" name="tanggal_lahir" max="{{ now()->subDay()->toDateString() }}" value="{{ $tglLahir }}" class="kp-input {{ $salah('tanggal_lahir') }}">
      </div>
      @if ($pakaiOld)
        @error('tanggal_lahir') <span class="kp-err">{{ $message }}</span> @enderror
      @endif
    </div>

    <div class="span2">
      <span class="kp-label">Jenis kelamin</span>
      <div class="kp-choice">
        <label>
          <input type="radio" name="jenis_kelamin" value="L" {{ $jk === 'L' ? 'checked' : '' }}>
          <span><i class="bi bi-gender-male"></i> Laki-laki</span>
        </label>
        <label>
          <input type="radio" name="jenis_kelamin" value="P" {{ $jk === 'P' ? 'checked' : '' }}>
          <span><i class="bi bi-gender-female"></i> Perempuan</span>
        </label>
      </div>
      @if ($pakaiOld)
        @error('jenis_kelamin') <span class="kp-err">{{ $message }}</span> @enderror
      @endif
    </div>

    <div class="span2">
      <label class="kp-label" for="{{ $prefix }}-alamat">Alamat</label>
      <div class="kp-field is-area">
        <i class="bi bi-geo-alt lead"></i>
        <textarea id="{{ $prefix }}-alamat" name="alamat" rows="2" class="kp-input {{ $salah('alamat') }}" placeholder="Alamat (opsional)">{{ $val('alamat') }}</textarea>
      </div>
      @if ($pakaiOld)
        @error('alamat') <span class="kp-err">{{ $message }}</span> @enderror
      @endif
    </div>
  </div>
</section>

<!-- ============ PASSWORD (khusus tambah) ============ -->
@if (! empty($denganPassword))
  <section class="kp-sec">
    <h6 class="kp-sec-title">Password awal</h6>
    <p class="kp-sec-sub">Kasir bisa menggantinya sendiri nanti lewat menu Profil.</p>

    <div class="kp-cols">
      <div>
        <label class="kp-label" for="{{ $prefix }}-pw">Password</label>
        <div class="kp-field has-eye">
          <i class="bi bi-lock lead"></i>
          <input type="password" id="{{ $prefix }}-pw" name="password" autocomplete="new-password" class="kp-input {{ $salah('password') }}" placeholder="Minimal 6 karakter, ada angka/simbol">
          <button type="button" class="kp-eye" data-kp-eye aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
        </div>
        @if ($pakaiOld)
          @error('password') <span class="kp-err">{{ $message }}</span> @enderror
        @endif
      </div>

      <div>
        <label class="kp-label" for="{{ $prefix }}-pw2">Ulangi password</label>
        <div class="kp-field has-eye">
          <i class="bi bi-shield-lock lead"></i>
          <input type="password" id="{{ $prefix }}-pw2" name="password_confirmation" autocomplete="new-password" class="kp-input" placeholder="Ketik ulang password">
          <button type="button" class="kp-eye" data-kp-eye aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
        </div>
      </div>
    </div>
  </section>
@endif
