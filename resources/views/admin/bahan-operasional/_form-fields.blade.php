{{--
  Isian form Tambah/Edit Bahan Operasional.
  Variabel:
    $bahan      : null = tambah baru
    $aktif      : true kalau modal ini yang barusan gagal validasi (pakai old() + pesan error)
    $ukuranList : daftar ukuran aktif (Kecil / Sedang / Besar) untuk jenis Kemasan

  Ada dua jenis bahan:
    - habis_pakai : minyak goreng, gas, dst (diberi manual ke kasir)
    - kemasan     : cup / box per ukuran (berkurang otomatis tiap transaksi)
  Bagian yang khusus kemasan ditampilkan/disembunyikan oleh JS di index.blade.php.
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
  $err = fn ($field) => $pakaiOld && $errors->has($field);

  $jenis = $val('jenis', 'habis_pakai') ?: 'habis_pakai';
  $isKemasan = $jenis === 'kemasan';
  $ukuranList = $ukuranList ?? collect();
@endphp

<div class="row g-3" data-bo-form data-jenis="{{ $jenis }}">

  {{-- ===== 1. Jenis bahan ===== --}}
  <div class="col-12">
    <label class="form-label-custom">Jenis Bahan</label>
    <div class="bo-jenis">
      <label class="bo-jenis-opsi">
        <input type="radio" name="jenis" value="habis_pakai" {{ ! $isKemasan ? 'checked' : '' }}>
        <span class="bo-jenis-kartu">
          <i class="bi bi-droplet-half"></i>
          <b>Bahan Habis-Pakai</b>
          <small>Minyak goreng, gas, dll. Diberi manual ke kasir.</small>
        </span>
      </label>
      <label class="bo-jenis-opsi">
        <input type="radio" name="jenis" value="kemasan" {{ $isKemasan ? 'checked' : '' }}>
        <span class="bo-jenis-kartu">
          <i class="bi bi-box-seam"></i>
          <b>Kemasan</b>
          <small>Cup, box, plastik. Berkurang otomatis tiap transaksi.</small>
        </span>
      </label>
    </div>
    @if ($err('jenis'))
      <span class="form-feedback-custom invalid-custom">{{ $errors->first('jenis') }}</span>
    @endif
  </div>

  {{-- ===== 2. Foto ===== --}}
  <div class="col-12">
    <label class="form-label-custom">
      <span data-t-habis_pakai="Foto Bahan" data-t-kemasan="Foto Kemasan">{{ $isKemasan ? 'Foto Kemasan' : 'Foto Bahan' }}</span>
      <span class="bo-opsional">(opsional)</span>
    </label>
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
        <span class="form-feedback-custom bo-hanya-kemasan {{ $isKemasan ? '' : 'd-none' }}" data-bo-hanya="kemasan">
          Foto kemasan ini tampil di halaman kasir pada pilihan ukuran.
        </span>
        <span class="form-feedback-custom invalid-custom d-none" data-bo-foto-error></span>
        @if ($err('gambar'))
          <span class="form-feedback-custom invalid-custom">{{ $errors->first('gambar') }}</span>
        @endif
      </div>
    </div>
  </div>

  {{-- ===== 3. Nama + satuan ===== --}}
  <div class="col-md-8">
    <label class="form-label-custom">Nama</label>
    <input type="text" name="nama" value="{{ $val('nama') }}"
      class="form-control-custom {{ $err('nama') ? 'is-invalid-custom' : '' }}"
      placeholder="{{ $isKemasan ? 'contoh: Cup Cimol Kecil / Box Besar' : 'contoh: Minyak Goreng / Gas' }}"
      data-p-habis_pakai="contoh: Minyak Goreng / Gas"
      data-p-kemasan="contoh: Cup Cimol Kecil / Box Besar">
    @if ($err('nama'))
      <span class="form-feedback-custom invalid-custom">{{ $errors->first('nama') }}</span>
    @endif
  </div>

  <div class="col-md-4">
    <label class="form-label-custom">Satuan Stok</label>
    <input type="text" name="satuan" value="{{ $val('satuan', $isKemasan ? 'pcs' : 'liter') }}" list="bo-satuan-saran" autocomplete="off"
      class="form-control-custom {{ $err('satuan') ? 'is-invalid-custom' : '' }}"
      placeholder="liter / tabung / pcs" data-bo-satuan>
    @if ($err('satuan'))
      <span class="form-feedback-custom invalid-custom">{{ $errors->first('satuan') }}</span>
    @endif
  </div>

  {{-- ===== 4. Khusus kemasan: dipakai untuk ukuran apa ===== --}}
  <div class="col-12 bo-hanya-kemasan {{ $isKemasan ? '' : 'd-none' }}" data-bo-hanya="kemasan">
    <div class="bo-kemasan-box">
      <div class="bo-kemasan-judul"><i class="bi bi-magic"></i> Pemakaian Otomatis per Transaksi</div>

      @if ($ukuranList->isEmpty())
        <div class="bo-alert warn mb-0">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span>Belum ada ukuran aktif. Tambahkan dulu ukuran (Kecil / Sedang / Besar) di Master Menu → Ukuran.</span>
        </div>
      @else
        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label-custom">Dipakai untuk Ukuran</label>
            <select name="ukuran_id" class="form-select-custom w-100 {{ $err('ukuran_id') ? 'is-invalid-custom' : '' }}">
              <option value="">Pilih ukuran...</option>
              @foreach ($ukuranList as $ukuran)
                <option value="{{ $ukuran->id }}" {{ (string) $val('ukuran_id', '') === (string) $ukuran->id ? 'selected' : '' }}>{{ $ukuran->nama }}</option>
              @endforeach
            </select>
            @if ($err('ukuran_id'))
              <span class="form-feedback-custom invalid-custom">{{ $errors->first('ukuran_id') }}</span>
            @endif
          </div>
          <div class="col-md-5">
            <label class="form-label-custom">Terpakai per Porsi</label>
            <div class="bo-suffix">
              <input type="number" name="pakai_per_porsi" min="1" max="20" value="{{ $val('pakai_per_porsi', 1) ?: 1 }}"
                class="form-control-custom {{ $err('pakai_per_porsi') ? 'is-invalid-custom' : '' }}">
              <span class="bo-suffix-teks" data-bo-satuan-teks>pcs</span>
            </div>
            @if ($err('pakai_per_porsi'))
              <span class="form-feedback-custom invalid-custom">{{ $errors->first('pakai_per_porsi') }}</span>
            @endif
          </div>
        </div>
        <p class="bo-kemasan-bantu">
          Tiap 1 porsi menu dengan ukuran ini terjual di kasir, stok kemasan kasir itu berkurang otomatis.
          Contoh: <b>Cup Kecil</b> → ukuran <b>Kecil</b>, 1 pcs per porsi.
        </p>

        <div class="row g-3 mt-0">
          <div class="col-12">
            <label class="form-label-custom">Batas Menipis</label>
            <div class="bo-suffix">
              <input type="number" name="stok_minimum" min="0" max="2000" value="{{ $val('stok_minimum', 10) }}"
                class="form-control-custom {{ $err('stok_minimum') ? 'is-invalid-custom' : '' }}">
              <span class="bo-suffix-teks" data-bo-satuan-teks>pcs</span>
            </div>
            <span class="form-feedback-custom">Kalau sisa stok kemasan kasir sama atau di bawah angka ini, statusnya jadi <b>Menipis</b> di Cek Stok kasir (sama seperti batas minimum menu).</span>
            @if ($err('stok_minimum'))
              <span class="form-feedback-custom invalid-custom">{{ $errors->first('stok_minimum') }}</span>
            @endif
          </div>
        </div>
      @endif
    </div>
  </div>

  {{-- ===== 5. Jatah harian ===== --}}
  <div class="col-12">
    <label class="form-label-custom">Jatah Harian per Kasir</label>
    <div class="bo-suffix">
      <input type="number" name="jatah_harian" min="0" value="{{ $val('jatah_harian', 0) }}"
        class="form-control-custom {{ $err('jatah_harian') ? 'is-invalid-custom' : '' }}">
      <span class="bo-suffix-teks" data-bo-satuan-teks>{{ $val('satuan', $isKemasan ? 'pcs' : 'liter') ?: 'satuan' }}</span>
    </div>
    <span class="form-feedback-custom"
      data-t-habis_pakai="Contoh: 5 untuk minyak goreng (5 liter), 1 untuk gas (1 tabung). Dipakai tombol &quot;Bagikan Jatah Harian&quot; dan jadi isian awal saat memberi jatah. Isi 0 kalau tidak ada jatah tetap."
      data-t-kemasan="Contoh: 100 untuk cup (100 pcs per kasir per hari). Dipakai tombol &quot;Bagikan Jatah Harian&quot; dan jadi isian awal saat memberi jatah. Isi 0 kalau tidak ada jatah tetap."
      data-bo-jatah-bantu>{{ $isKemasan
        ? 'Contoh: 100 untuk cup (100 pcs per kasir per hari). Dipakai tombol "Bagikan Jatah Harian" dan jadi isian awal saat memberi jatah. Isi 0 kalau tidak ada jatah tetap.'
        : 'Contoh: 5 untuk minyak goreng (5 liter), 1 untuk gas (1 tabung). Dipakai tombol "Bagikan Jatah Harian" dan jadi isian awal saat memberi jatah. Isi 0 kalau tidak ada jatah tetap.' }}</span>
    @if ($err('jatah_harian'))
      <span class="form-feedback-custom invalid-custom">{{ $errors->first('jatah_harian') }}</span>
    @endif
  </div>
</div>
