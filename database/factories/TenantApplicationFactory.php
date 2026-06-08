<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TenantApplication>
 */
class TenantApplicationFactory extends Factory
{
    protected $model = TenantApplication::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'application_id' => Application::factory(),
            'label' => null,
            'instance_url' => 'https://' . fake()->domainName(),
            'api_secret' => Str::random(40),
            'is_active' => true,
            'activated_at' => now(),
            'expired_at' => now()->addYear(),
            'notes' => null,
        ];
    }
}
