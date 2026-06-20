<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Paksa browser untuk tidak cache halaman tertentu.
 *
 * Dipakai untuk halaman login + halaman yang di-redirect setelah auth.
 * Tanpa ini, browser bisa serve HTML login dari cache dengan CSRF token
 * yang sudah expired → POST menghasilkan 419 (Page Expired).
 */
class NoCacheForAuthenticatedRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
