<?php

namespace App\Providers;

use App\Support\NotifikasiStok;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Paksa zona waktu WIB. Tanpa ini Laravel memakai UTC (default config),
        // sehingga jam transaksi tampil mundur 7 jam dari jam sebenarnya.
        config(['app.timezone' => 'Asia/Jakarta']);
        date_default_timezone_set('Asia/Jakarta');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tema admin (assets_dashboard) pakai Bootstrap, jadi pagination
        // Laravel juga dirender dengan markup Bootstrap 5, bukan Tailwind.
        Paginator::useBootstrapFive();

        // Nama hari & bulan (translatedFormat) tampil berbahasa Indonesia.
        \Carbon\Carbon::setLocale('id');

        // Lonceng notifikasi di navbar (layout admin dipakai admin & kasir):
        // isinya peringatan stok menipis/habis yang asli dari database.
        View::composer('layouts.admin', function ($view) {
            $view->with('notifStok', auth()->check() ? NotifikasiStok::ambil() : collect());
        });
    }
}
