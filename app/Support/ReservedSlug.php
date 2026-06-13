<?php

namespace App\Support;

class ReservedSlug
{
    /**
     * Subdomain reserved — tidak boleh dipakai sebagai slug tenant.
     * Digunakan untuk route/admin panel atau protokol standar.
     */
    public const ALL = [
        'www',
        'api',
        'app',
        'admin',
        'mail',
        'static',
        'cdn',
        'dashboard',
        'vendor',
        'super',
        'root',
        'support',
        'docs',
        'status',
        'auth',
        'login',
        'logout',
        'register',
    ];

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return self::ALL;
    }

    public static function isReserved(string $slug): bool
    {
        return in_array(strtolower($slug), self::ALL, true);
    }
}
