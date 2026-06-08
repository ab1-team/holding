<?php

namespace Database\Seeders;

use App\Models\Application;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $apps = [
            [
                'name' => 'EnStore',
                'slug' => 'enstore',
                'description' => 'Aplikasi toko online / e-commerce multi-outlet',
                'base_url' => 'https://enstore.example.com',
                'has_financial_report' => true,
            ],
            [
                'name' => 'EnTopUp',
                'slug' => 'entopup',
                'description' => 'Aplikasi penjualan pulsa, paket data, dan PPOB',
                'base_url' => 'https://entopup.example.com',
                'has_financial_report' => true,
            ],
            [
                'name' => 'SI DBM',
                'slug' => 'si-dbm',
                'description' => 'Sistem Informasi Dana Bantuan Masyarakat (Desa)',
                'base_url' => 'https://sidbm.example.com',
                'has_financial_report' => true,
            ],
            [
                'name' => 'EnKas',
                'slug' => 'enkas',
                'description' => 'Pembukuan kas sederhana untuk UMKM',
                'base_url' => 'https://enkas.example.com',
                'has_financial_report' => false,
            ],
        ];

        foreach ($apps as $data) {
            Application::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'icon_path' => null,
                    'api_token_key' => \Illuminate\Support\Str::random(32),
                    'is_active' => true,
                ])
            );
        }

        $this->command->info('Applications seeded: ' . count($apps));
    }
}
