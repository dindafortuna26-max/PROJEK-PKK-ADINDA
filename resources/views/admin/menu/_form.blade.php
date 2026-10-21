{{--
  Form Tambah / Edit Menu (dipakai bersama oleh create.blade.php & edit.blade.php).
  Variabel:
    $menu       -> null saat tambah, model Menu saat edit
    $kategoris  -> daftar kategori
    $action     -> url tujuan form
    $submitText -> tulisan tombol simpan
--}}
@php
  $isEdit = ! empty($menu);
  $fotoLama = $isEdit && $menu->gambar ? asset('storage/' . $menu->gambar) : null;
  $nilaiDeskripsi = old('deskripsi', $isEdit ? $menu->deskripsi : '');
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="mo-form" id="form-menu" novalidate>
  @csrf
  @if ($isEdit)
    @method('PUT')
  @endif

  <div class="mo-card">
    <div class="mo-card-head">
      <i class="bi {{ $isEdit ? 'bi-pencil-fill' : 'bi-box-seam-fill' }}"></i>
      <div>
        <b>Informasi Menu</b>
        <span>{{ $isEdit ? 'Ubah foto dan detail menu di bawah ini.' : 'Foto yang menarik bikin menu lebih laku.' }}</span>
      </div>
    </div>

    <div class="mo-card-body">
      <div class="mo-grid">

        <!-- ============ Kolom kiri: foto ============ -->
        <div>
          <label class="mo-label"><i class="bi bi-image"></i> Foto Menu</label>

          <label class="mo-drop {{ $fotoLama ? 'has-img' : '' }} {{ $errors->has('gambar') ? 'is-invalid' : '' }}" id="mo-drop">
            <span class="mo-drop-empty">
              <i class="bi bi-cloud-arrow-up-fill"></i>
              <b>Klik atau seret foto ke sini</b>
              <span>JPG / PNG, maksimal 2MB</span>
            </span>
            <img id="preview-gambar" src="{{ $fotoLama ?? '' }}" alt="Preview foto menu">
            <span class="mo-drop-change"><i class="bi bi-arrow-repeat"></i> Ganti foto</span>
            <input type="file" name="gambar" id="input-gambar" accept="image/*">
          </label>

          <span class="mo-hint" id="nama-file">
            {{ $isEdit ? 'Kosongkan kalau tidak mau ganti foto.' : 'Foto boleh diisi nanti.' }}
          </span>
          @error('gambar')
            <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
          @enderror
        </div>

        <!-- ============ Kolom kanan: detail ============ -->
        <div>
          <div class="mo-field">
            <label class="mo-label" for="nama"><i class="bi bi-tag-fill"></i> Nama Menu <span class="mo-req">*</span></label>
            <input
              type="text"
              id="nama"
              name="nama"
              value="{{ old('nama', $isEdit ? $menu->nama : '') }}"
              class="form-control-custom w-100 {{ $errors->has('nama') ? 'is-invalid-custom' : '' }}"
              placeholder="contoh: Cimol Bojot Original"
              autocomplete="off"
            >
            @error('nama')
              <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
            @enderror
          </div>

          <div class="mo-row-2">
            <div class="mo-field">
              <label class="mo-label" for="kategori_id"><i class="bi bi-grid-fill"></i> Kategori</label>
              <select id="kategori_id" name="kategori_id" class="form-select-custom w-100">
                <option value="">Tanpa Kategori</option>
                @foreach ($kategoris as $kategori)
                  <option value="{{ $kategori->id }}" {{ (string) old('kategori_id', $isEdit ? $menu->kategori_id : '') === (string) $kategori->id ? 'selected' : '' }}>
                    {{ $kategori->nama }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="mo-field">
              <label class="mo-label" for="stok_minimum"><i class="bi bi-exclamation-triangle-fill"></i> Stok Minimum</label>
              <input
                type="number"
                id="stok_minimum"
                name="stok_minimum"
                value="{{ old('stok_minimum', $isEdit ? $menu->stok_minimum : 5) }}"
                min="0"
                class="form-control-custom w-100 {{ $errors->has('stok_minimum') ? 'is-invalid-custom' : '' }}"
              >
              @error('stok_minimum')
                <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
              @enderror
            </div>
          </div>
          <span class="mo-hint" style="margin-top:-.6rem; margin-bottom:1.25rem;">
            Kalau stok menyentuh angka minimum, statusnya jadi "Menipis" di halaman Manajemen Stok.
          </span>

          <div class="mo-field">
            <label class="mo-label" for="deskripsi">
              <i class="bi bi-card-text"></i> Deskripsi
              <span class="mo-hint mo-counter" id="hitung-deskripsi"></span>
            </label>
            <textarea
              id="deskripsi"
              name="deskripsi"
              rows="4"
              class="form-control-custom w-100 {{ $errors->has('deskripsi') ? 'is-invalid-custom' : '' }}"
              placeholder="Ceritakan sedikit tentang menu ini..."
            >{{ $nilaiDeskripsi }}</textarea>
            @error('deskripsi')
              <span class="form-feedback-custom invalid-custom">{{ $message }}</span>
            @enderror
          </div>
        </div>

      </div>

      @unless ($isEdit)
        <div class="mo-note" role="note">
          <i class="bi bi-info-circle-fill"></i>
          <div>
            Harga belum diatur di sini. Setelah menu tersimpan, klik <strong>"Atur Ukuran &amp; Harga"</strong>
            di Daftar Menu untuk mengisi harga per ukuran.
          </div>
        </div>
      @endunless
    </div>

    <div class="mo-actions">
      <a href="{{ route('admin.menu.index') }}" class="btn-custom btn-custom-outline-secondary">
        <span>Batal</span>
      </a>
      <button type="submit" class="btn-custom btn-custom-primary" id="btn-simpan-menu">
        <i class="bi bi-check-lg"></i>
        <span>{{ $submitText }}</span>
      </button>
    </div>
  </div>
</form>

@push('scripts')
  <script>
    (function () {
      var drop = document.getElementById('mo-drop');
      var input = document.getElementById('input-gambar');
      var img = document.getElementById('preview-gambar');
      var info = document.getElementById('nama-file');
      var form = document.getElementById('form-menu');
      var tombol = document.getElementById('btn-simpan-menu');
      var desk = document.getElementById('deskripsi');
      var hitung = document.getElementById('hitung-deskripsi');
      if (!drop || !input) return;

      function tampilkan(file) {
        if (!file) return;
        if (file.type && file.type.indexOf('image/') !== 0) {
          alert('File yang dipilih harus berupa gambar.');
          input.value = '';
          return;
        }
        if (file.size > 2 * 1024 * 1024) {
          alert('Ukuran foto maksimal 2MB. Pilih foto yang lebih kecil ya.');
          input.value = '';
          return;
        }
        img.src = URL.createObjectURL(file);
        drop.classList.add('has-img');
        drop.classList.remove('is-invalid');
        info.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
      }

      input.addEventListener('change', function () { tampilkan(input.files && input.files[0]); });

      ['dragenter', 'dragover'].forEach(function (nama) {
        drop.addEventListener(nama, function (e) { e.preventDefault(); drop.classList.add('is-drag'); });
      });
      ['dragleave', 'drop'].forEach(function (nama) {
        drop.addEventListener(nama, function () { drop.classList.remove('is-drag'); });
      });
      drop.addEventListener('drop', function (e) {
        e.preventDefault();
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
          input.files = e.dataTransfer.files;
          tampilkan(input.files[0]);
        }
      });

      // Hitung karakter deskripsi
      function updateHitung() {
        if (desk && hitung) hitung.textContent = desk.value.length + ' karakter';
      }
      if (desk) { desk.addEventListener('input', updateHitung); updateHitung(); }

      // Kalau halaman dipulihkan lewat tombol Back, tombol simpan aktif lagi
      window.addEventListener('pageshow', function () {
        if (tombol && tombol.disabled) {
          tombol.disabled = false;
          tombol.innerHTML = '<i class="bi bi-check-lg"></i><span>' + @json($submitText) + '</span>';
        }
      });

      // Cegah klik ganda saat menyimpan
      if (form && tombol) {
        form.addEventListener('submit', function () {
          if (tombol.disabled) return;
          setTimeout(function () {
            tombol.disabled = true;
            tombol.innerHTML = '<span class="spinner-border spinner-border-sm"></span><span>Menyimpan...</span>';
          }, 0);
        });
      }
    })();
  </script>
@endpush
