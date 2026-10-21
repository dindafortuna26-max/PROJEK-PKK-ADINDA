<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Blokir akses kalau user yang login bukan admin.
     * Kasir yang coba akses URL admin secara langsung akan
     * di-redirect balik ke dashboard-nya sendiri (403 kalau mau lebih tegas).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->role !== 'admin') {
            abort(403, 'Halaman ini khusus untuk Admin.');
        }

        return $next($request);
    }
}
