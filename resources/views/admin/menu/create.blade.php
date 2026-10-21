@extends('layouts.admin')

@section('title', 'Tambah Menu - ' . config('app.name', 'Cimol Bojot AA'))

@section('content')
<div class="mo-page">

  <div class="mo-hero">
    <div>
      <span class="mo-hero-kicker"><i class="bi bi-plus-circle-fill"></i> Menu Baru</span>
      <h1>Tambah Menu</h1>
      <p>Isi detail menu baru untuk {{ config('app.name', 'Cimol Bojot AA') }}.</p>
    </div>
    <a href="{{ route('admin.menu.index') }}" class="mo-btn-glass">
      <i class="bi bi-arrow-left"></i>
      <span>Kembali</span>
    </a>
  </div>

  @include('admin.menu._form', [
      'menu' => null,
      'action' => route('admin.menu.store'),
      'submitText' => 'Simpan Menu',
  ])

</div>
@endsection
