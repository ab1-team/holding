<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'superadmin',
            'is_active' => true,
            'last_login_at' => null,
        ];
    }

    public function forTenant(int $tenantId, string $role = 'tenant_staff'): static
    {
        return $this->state(fn () => [
            'tenant_id' => $tenantId,
            'role' => $role,
        ]);
    }
}
