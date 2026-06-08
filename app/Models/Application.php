<?php

namespace App\Models;

use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'icon_path', 'base_url', 'api_token_key', 'has_financial_report', 'is_active'])]
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'has_financial_report' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenantApplications(): HasMany
    {
        return $this->hasMany(TenantApplication::class);
    }
}
