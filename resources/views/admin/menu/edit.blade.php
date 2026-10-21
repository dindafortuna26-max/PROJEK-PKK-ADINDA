@extends('layouts.admin')

@section('title', 'Edit Menu - ' . config('app.name', 'Cimol Bojot AA'))

@section('content')
<div class="mo-page">

  <div class="mo-hero">
    <div>
      <span class="mo-hero-kicker"><i class="bi bi-pencil-fill"></i> Edit Menu</span>
      <h1>{{ $menu->nama }}</h1>
      <p>Perbarui foto dan detail menu ini.</p>
    </div>
    <a href="{{ route('admin.menu.index') }}" class="mo-btn-glass">
      <i class="bi bi-arrow-left"></i>
      <span>Kembali</span>
    </a>
  </div>

  @include('admin.menu._form', [
      'menu' => $menu,
      'action' => route('admin.menu.update', $menu),
      'submitText' => 'Simpan Perubahan',
  ])

</div>
@endsection
