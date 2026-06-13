<?php

namespace App\Models;

use App\Support\ReservedSlug;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'domain', 'email', 'phone', 'address', 'logo_path', 'is_active'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Tenant $tenant) {
            if ($tenant->slug !== null && $tenant->slug !== '' && ReservedSlug::isReserved($tenant->slug)) {
                throw new \InvalidArgumentException("Slug '{$tenant->slug}' tidak tersedia (reserved untuk sistem).");
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tenantApplications(): HasMany
    {
        return $this->hasMany(TenantApplication::class);
    }

    public function getDomainBase(): string
    {
        return config('app.domain_base', 'holding.test');
    }

    public function getSubdomainUrl(): string
    {
        return "{$this->slug}.{$this->getDomainBase()}";
    }

    /**
     * Identifier yang dikirim sebagai X-Holding-Tenant ke subsidiary.
     * Prioritas: domain (kalau diisi) → slug.
     */
    public function getHoldingTenantId(): string
    {
        return $this->domain ?: $this->slug;
    }
}
