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
     * Resolve tenant dari host.
     *
     * - `admin.{base}` → vendor panel (no tenant)
     * - `{slug}.{base}` → tenant panel (lookup by slug)
     * - Custom `domain` (full host) → tenant panel (lookup by domain)
     * - `{base}` (apex) atau IP/host lain → redirect ke admin subdomain
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $base = strtolower((string) config('app.domain_base'));

        // Local dev/test fallback — kalau host tidak sesuai base, treat as admin (apex).
        if (! $base || $host === $base || $host === '127.0.0.1' || $host === 'localhost') {
            app()->instance('current_tenant', null);
            $request->attributes->set('is_vendor_panel', true);

            return $next($request);
        }

        // Apex → redirect ke admin.
        if ($host === $base) {
            return redirect()->away('http://admin.' . $base . $request->getRequestUri());
        }

        // Reserved admin subdomain → vendor panel.
        if ($host === 'admin.' . $base) {
            app()->instance('current_tenant', null);
            $request->attributes->set('is_vendor_panel', true);

            return $next($request);
        }

        // Reserved subdomain lain → 404.
        $sub = $this->extractSubdomain($host, $base);
        if ($sub !== null && ReservedSlug::isReserved($sub)) {
            abort(404, 'Subdomain tidak tersedia.');
        }

        // Resolve tenant:
        // 1. Full host match ke `domain` field (custom domain, mis. tenantku.com)
        // 2. Subdomain match ke `slug` (default, mis. acme.holding.test)
        $tenant = Tenant::where('domain', $host)
            ->orWhere('slug', $sub)
            ->first();

        if (! $tenant) {
            abort(404, "Tenant '{$host}' tidak ditemukan.");
        }

        app()->instance('current_tenant', $tenant);
        $request->attributes->set('current_tenant', $tenant);
        $request->attributes->set('is_vendor_panel', false);

        return $next($request);
    }

    /**
     * Extract subdomain dari host. Return null kalau host bukan subdomain dari base.
     * Contoh: extractSubdomain('acme.holding.test', 'holding.test') = 'acme'.
     */
    private function extractSubdomain(string $host, string $base): ?string
    {
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
