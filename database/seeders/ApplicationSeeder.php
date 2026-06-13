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
                'name' => 'SIDBM',
                'slug' => 'sidbm',
                'description' => 'Sistem Informasi Dana Bantuan Masyarakat (Desa)',
                'base_url' => 'https://app.sidbm.net',
                'has_financial_report' => true,
            ],
            [
                'name' => 'SIUPK',
                'slug' => 'siupk',
                'description' => 'Sistem Informasi Unit Pengelola Kegiatan',
                'base_url' => 'https://app.siupk.com',
                'has_financial_report' => true,
            ],
            [
                'name' => 'LKM',
                'slug' => 'lkm',
                'description' => 'Sistem Informasi Lembaga Keuangan Mikro',
                'base_url' => 'https://app.silkm.net',
                'has_financial_report' => true,
            ]
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
