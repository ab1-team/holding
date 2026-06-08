<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPERADMIN_EMAIL', 'admin@holding.local');
        $password = env('SUPERADMIN_PASSWORD', 'password');

        User::updateOrCreate(
            ['email' => $email],
            [
                'tenant_id' => null,
                'name' => 'Super Admin',
                'password' => Hash::make($password),
                'role' => 'superadmin',
                'is_active' => true,
                'last_login_at' => null,
            ]
        );

        $this->command->info("Superadmin ready: {$email}");
    }
}
