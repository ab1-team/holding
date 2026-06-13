<?php

namespace App\Models;

use Database\Factories\TenantApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tenant_id', 'application_id', 'label', 'instance_url', 'api_secret', 'is_active', 'activated_at', 'expired_at', 'notes'])]
class TenantApplication extends Model
{
    /** @use HasFactory<TenantApplicationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'activated_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function reportCaches(): HasMany
    {
        return $this->hasMany(ReportCache::class);
    }

    public function isExpired(): bool
    {
        return $this->expired_at !== null && $this->expired_at->isPast();
    }

    /**
     * License yang perlu diperhatikan: aktif + akan/sudah expired dalam window hari.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\HasMany  $query
     */
    public function scopeExpiringWithin($query, int $days = 30)
    {
        return $query->where('is_active', true)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now()->addDays($days));
    }
}
