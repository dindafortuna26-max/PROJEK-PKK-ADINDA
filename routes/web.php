<?php

use App\Http\Controllers\Admin\BahanOperasionalController;
use App\Http\Controllers\Admin\KasirController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MenuUkuranController;
use App\Http\Controllers\Admin\RiwayatTransaksiController;
use App\Http\Controllers\Admin\StokController;
use App\Http\Controllers\Admin\ToppingController;
use App\Http\Controllers\BahanOperasionalFotoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Kasir\BerandaController;
use App\Http\Controllers\Kasir\LaporanController as KasirLaporanController;
use App\Http\Controllers\Kasir\RiwayatTransaksiController as KasirRiwayatTransaksiController;
use App\Http\Controllers\Kasir\StokController as KasirStokController;
use App\Http\Controllers\Kasir\TransaksiController as KasirTransaksiController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

// Dashboard (omzet, grafik, dll) khusus Admin — makanya pakai middleware
// 'admin' juga, bukan cuma 'auth'. Kasir yang coba akses langsung ditolak
// (403) supaya data omzet toko nggak kelihat oleh akun kasir.
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('dashboard');

// Halaman "rumah" buat Kasir setelah login (belum ada Dashboard di role
// Kasir sesuai struktur fitur — ini cuma sapaan + jalan pintas ke
// Transaksi/Cek Stok/Riwayat Transaksi begitu modul-modul itu jadi).
Route::get('/beranda', [BerandaController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('kasir.home');

// Modul Transaksi milik Kasir (input penjualan): pilih menu, ukuran,
// saus, bubuk tabur, sampai metode bayar. Tidak dibatasi middleware
// 'admin' supaya kasir bisa akses, tapi controller-nya sendiri boleh
// diakses admin juga (mis. buat uji coba) — sama pola dengan /beranda.
Route::middleware(['auth', 'verified'])->prefix('transaksi')->name('kasir.transaksi.')->group(function () {
    Route::get('/', [KasirTransaksiController::class, 'index'])->name('index');
    Route::post('/', [KasirTransaksiController::class, 'store'])->name('store');
    Route::get('/{transaksi}/struk', [KasirTransaksiController::class, 'struk'])->name('struk');
});

// Halaman "Cek Stok" milik Kasir — READ ONLY (cuma lihat sisa stok menu
// & topping/bumbu, tidak ada tombol tambah/edit/hapus sama sekali).
// Sengaja dibuat controller & route sendiri (bukan pakai Admin\StokController)
// supaya tidak ada satupun aksi tulis yang kebuka buat role kasir.
Route::get('/stok', [KasirStokController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('kasir.stok.index');

// Halaman "Riwayat Transaksi" milik Kasir — READ ONLY & MILIK SENDIRI.
// Beda dari punya Admin (RiwayatTransaksiController di atas) yang bisa
// lihat lintas kasir: di sini query-nya dikunci ke user_id = kasir yang
// login (lihat controller), jadi tidak perlu middleware 'admin'.
Route::middleware(['auth', 'verified'])->prefix('riwayat-transaksi')->name('kasir.riwayat-transaksi.')->group(function () {
    Route::get('/', [KasirRiwayatTransaksiController::class, 'index'])->name('index');
    Route::get('/{transaksi}', [KasirRiwayatTransaksiController::class, 'show'])->name('show');
});

// Laporan Saya (Kasir) — rekap penjualan milik kasir yang login, READ ONLY.
// Query dikunci ke user_id di controller, jadi tidak butuh middleware 'admin'.
Route::get('/laporan-saya', [KasirLaporanController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('kasir.laporan.index');

// Foto bahan operasional (minyak goreng, gas) disajikan lewat route ini
// supaya tidak bergantung pada storage:link. Admin & kasir sama-sama boleh lihat.
Route::get('/bahan-operasional/{bahanOperasional}/foto', BahanOperasionalFotoController::class)
    ->middleware('auth')
    ->name('bahan-operasional.foto');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Semua route di bawah ini khusus Admin (middleware 'admin' cek role === 'admin').
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::get('/menu/create', [MenuController::class, 'create'])->name('menu.create');
    Route::post('/menu', [MenuController::class, 'store'])->name('menu.store');
    Route::get('/menu/{menu}/edit', [MenuController::class, 'edit'])->name('menu.edit');
    Route::put('/menu/{menu}', [MenuController::class, 'update'])->name('menu.update');
    Route::patch('/menu/{menu}/toggle-status', [MenuController::class, 'toggleStatus'])->name('menu.toggle-status');

    Route::get('/menu/{menu}/ukuran', [MenuUkuranController::class, 'edit'])->name('menu.ukuran.edit');
    Route::put('/menu/{menu}/ukuran', [MenuUkuranController::class, 'update'])->name('menu.ukuran.update');

    Route::get('/topping', [ToppingController::class, 'index'])->name('topping.index');
    Route::post('/topping', [ToppingController::class, 'store'])->name('topping.store');
    Route::put('/topping/{topping}', [ToppingController::class, 'update'])->name('topping.update');
    Route::patch('/topping/{topping}/toggle-status', [ToppingController::class, 'toggleStatus'])->name('topping.toggle-status');
    Route::delete('/topping/{topping}', [ToppingController::class, 'destroy'])->name('topping.destroy');

    Route::get('/bahan-operasional', [BahanOperasionalController::class, 'index'])->name('bahan-operasional.index');
    Route::post('/bahan-operasional', [BahanOperasionalController::class, 'store'])->name('bahan-operasional.store');
    Route::put('/bahan-operasional/{bahanOperasional}', [BahanOperasionalController::class, 'update'])->name('bahan-operasional.update');
    Route::patch('/bahan-operasional/{bahanOperasional}/toggle-status', [BahanOperasionalController::class, 'toggleStatus'])->name('bahan-operasional.toggle-status');
    Route::delete('/bahan-operasional/{bahanOperasional}', [BahanOperasionalController::class, 'destroy'])->name('bahan-operasional.destroy');

    Route::get('/kasir', [KasirController::class, 'index'])->name('kasir.index');
    Route::post('/kasir', [KasirController::class, 'store'])->name('kasir.store');
    Route::put('/kasir/{kasir}', [KasirController::class, 'update'])->name('kasir.update');
    Route::patch('/kasir/{kasir}/toggle-status', [KasirController::class, 'toggleStatus'])->name('kasir.toggle-status');
    Route::patch('/kasir/{kasir}/reset-password', [KasirController::class, 'resetPassword'])->name('kasir.reset-password');
    Route::delete('/kasir/{kasir}', [KasirController::class, 'destroy'])->name('kasir.destroy');

    Route::get('/stok', [StokController::class, 'index'])->name('stok.index');
    Route::post('/stok', [StokController::class, 'storeMasuk'])->name('stok.store');
    Route::patch('/stok/{menu}/koreksi', [StokController::class, 'koreksi'])->name('stok.koreksi');
    Route::delete('/stok/{menu}/riwayat', [StokController::class, 'hapusRiwayat'])->name('stok.hapus-riwayat');
    Route::delete('/stok/riwayat/{pergerakanStok}', [StokController::class, 'hapusEntriMasuk'])->name('stok.hapus-entri-masuk');

    Route::post('/stok/topping', [StokController::class, 'storeMasukTopping'])->name('stok.topping.store');
    Route::patch('/stok/topping/{topping}/koreksi', [StokController::class, 'koreksiTopping'])->name('stok.topping.koreksi');
    Route::delete('/stok/topping/{topping}/riwayat', [StokController::class, 'hapusRiwayatTopping'])->name('stok.topping.hapus-riwayat');
    Route::delete('/stok/topping/riwayat/{pergerakanStokTopping}', [StokController::class, 'hapusEntriMasukTopping'])->name('stok.topping.hapus-entri-masuk');

    Route::post('/stok/bahan', [StokController::class, 'storeMasukBahan'])->name('stok.bahan.store');
    Route::patch('/stok/bahan/{bahanOperasional}/koreksi', [StokController::class, 'koreksiBahan'])->name('stok.bahan.koreksi');
    Route::delete('/stok/bahan/{bahanOperasional}/riwayat', [StokController::class, 'hapusRiwayatBahan'])->name('stok.bahan.hapus-riwayat');
    Route::delete('/stok/bahan/riwayat/{pergerakanStokBahanOperasional}', [StokController::class, 'hapusEntriMasukBahan'])->name('stok.bahan.hapus-entri-masuk');

    Route::get('/riwayat-transaksi', [RiwayatTransaksiController::class, 'index'])->name('riwayat-transaksi.index');
    Route::get('/riwayat-transaksi/export', [RiwayatTransaksiController::class, 'export'])->name('riwayat-transaksi.export');
    Route::get('/riwayat-transaksi/{transaksi}/struk', [RiwayatTransaksiController::class, 'struk'])->name('riwayat-transaksi.struk');
    Route::get('/riwayat-transaksi/{transaksi}', [RiwayatTransaksiController::class, 'show'])->name('riwayat-transaksi.show');

    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
});

require __DIR__.'/auth.php';
