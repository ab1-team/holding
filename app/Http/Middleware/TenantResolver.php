<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\ReservedSlug;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantResolver
{
    /**
     * Resolve tenant dari subdomain host.
     *
     * - `admin.{base}` → vendor panel (no tenant)
     * - `{slug}.{base}` → tenant panel (lookup by slug)
     * - `{base}` (apex) atau IP/host lain → redirect ke admin subdomain
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $base = config('app.domain_base');

        // Local dev/test fallback — kalau host tidak sesuai base, treat as admin (apex).
        if (! $base || $host === $base || $host === '127.0.0.1' || $host === 'localhost') {
            app()->instance('current_tenant', null);
            $request->attributes->set('is_vendor_panel', true);

            return $next($request);
        }

        $sub = $this->extractSubdomain($host, $base);

        if ($sub === null) {
            return redirect()->away('http://admin.' . $base . $request->getRequestUri());
        }

        if ($sub === 'admin') {
            app()->instance('current_tenant', null);
            $request->attributes->set('is_vendor_panel', true);

            return $next($request);
        }

        if (ReservedSlug::isReserved($sub)) {
            abort(404, 'Subdomain tidak tersedia.');
        }

        $tenant = Tenant::where('slug', $sub)->first();
        if (! $tenant) {
            abort(404, "Tenant '{$sub}' tidak ditemukan.");
        }

        app()->instance('current_tenant', $tenant);
        $request->attributes->set('current_tenant', $tenant);
        $request->attributes->set('is_vendor_panel', false);

        return $next($request);
    }

    private function extractSubdomain(string $host, string $base): ?string
    {
        $base = strtolower($base);
        $host = strtolower($host);

        if ($host === $base) {
            return null;
        }

        $suffix = '.' . $base;
        if (! str_ends_with($host, $suffix)) {
            return null;
        }

        $sub = substr($host, 0, -strlen($suffix));
        if ($sub === '' || str_contains($sub, '.')) {
            return null;
        }

        return $sub;
    }
}
