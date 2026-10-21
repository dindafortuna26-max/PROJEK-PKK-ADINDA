{{--
  Kartu ringkasan satu kelompok stok (Menu / Topping & Bumbu / Bahan Operasional).
  Variabel: $r (array aman, menipis, habis, total), $labelTotal, $satuanTotal, $satuanHabis
--}}
<div class="row g-3 mb-4 px-3 pt-3">
  <div class="col-md-6 col-xl-3">
    <div class="card card-stat h-100 mb-0">
      <div class="stat-icon-row">
        <span class="stat-icon-badge"><i class="bi bi-box-seam-fill"></i></span>
        <span class="stat-label">{{ $labelTotal }}</span>
      </div>
      <div class="stat-value">{{ number_format($r['total'], 0, ',', '.') }}</div>
      <p class="stat-note">{{ $satuanTotal }}</p>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card card-stat h-100 mb-0">
      <div class="stat-icon-row">
        <span class="stat-icon-badge"><i class="bi bi-check-circle-fill"></i></span>
        <span class="stat-label">Stok Aman</span>
      </div>
      <div class="stat-value">{{ number_format($r['aman'], 0, ',', '.') }}</div>
      <p class="stat-note">{{ $satuanTotal }}</p>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card card-stat h-100 mb-0">
      <div class="stat-icon-row">
        <span class="stat-icon-badge"><i class="bi bi-exclamation-triangle-fill"></i></span>
        <span class="stat-label">Stok Menipis</span>
      </div>
      <div class="stat-value">{{ number_format($r['menipis'], 0, ',', '.') }}</div>
      <p class="stat-note">{{ $satuanTotal }}</p>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="card card-stat h-100 mb-0">
      <div class="stat-icon-row">
        <span class="stat-icon-badge"><i class="bi bi-x-circle-fill"></i></span>
        <span class="stat-label">Stok Habis</span>
      </div>
      <div class="stat-value">{{ number_format($r['habis'], 0, ',', '.') }}</div>
      <p class="stat-note">{{ $satuanHabis }}</p>
    </div>
  </div>
</div>
