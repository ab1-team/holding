<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            SuperadminSeeder::class,
            ApplicationSeeder::class,
        ]);
    }
}
