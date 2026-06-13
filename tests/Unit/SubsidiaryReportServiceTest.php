<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Services\SubsidiaryReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubsidiaryReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubsidiaryReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SubsidiaryReportService();
    }

    public function test_row_key_is_composite(): void
    {
        $this->assertSame('1.1.01||Kas', $this->service->rowKey('1.1.01', 'Kas'));
        $this->assertSame('1.1.01||Kas di Bank', $this->service->rowKey('1.1.01', 'Kas di Bank'));
        // Same code, different name → different key
        $this->assertNotSame(
            $this->service->rowKey('3.1.02.01', 'Modal Usaha'),
            $this->service->rowKey('3.1.02.01', 'Modal Desa'),
        );
    }

    public function test_report_types_and_labels(): void
    {
        $types = SubsidiaryReportService::reportTypes();
        $this->assertSame(['neraca', 'laba_rugi', 'arus_kas', 'perubahan_ekuitas', 'calk'], $types);

        $labels = SubsidiaryReportService::reportTypeLabels();
        $this->assertCount(5, $labels);
        $this->assertSame('Neraca', $labels['neraca']);
        $this->assertSame('Laba Rugi', $labels['laba_rugi']);
        $this->assertSame('Catatan (CALK)', $labels['calk']);
    }

    public function test_build_query_for_period(): void
    {
        $this->assertSame('tahun=2026&bulan=01', SubsidiaryReportService::buildQuery('2026-01'));
        $this->assertSame('tahun=2026&bulan=12', SubsidiaryReportService::buildQuery('2026-12'));
    }

    public function test_build_query_with_hari_and_semester(): void
    {
        $q = SubsidiaryReportService::buildQuery('2026-12', hari: 31, semester: 1);
        $this->assertStringContainsString('tahun=2026', $q);
        $this->assertStringContainsString('bulan=12', $q);
        $this->assertStringContainsString('hari=31', $q);
        $this->assertStringContainsString('semester=1', $q);
    }

    public function test_merge_aggregates_totals_per_license(): void
    {
        $tenant = Tenant::factory()->create();
        $app1 = Application::factory()->create();
        $app2 = Application::factory()->create();
        $l1 = TenantApplication::factory()->for($tenant)->for($app1, 'application')->create();
        $l2 = TenantApplication::factory()->for($tenant)->for($app2, 'application')->create();

        $payloads = [
            $l1->id => [
                'status' => 'success',
                'period' => '2026-01',
                'generated_at' => null,
                'data' => [
                    ['account_code' => '1', 'account_name' => 'A', 'amount' => 1000],
                    ['account_code' => '2', 'account_name' => 'B', 'amount' => 2500],
                ],
            ],
            $l2->id => [
                'status' => 'success',
                'period' => '2026-01',
                'generated_at' => null,
                'data' => [
                    ['account_code' => '1', 'account_name' => 'A', 'amount' => 500],
                ],
            ],
        ];

        $merged = $this->service->mergeComparative([$l1, $l2], $payloads, 'neraca', '2026-01');

        $this->assertCount(2, $merged['rows']);
        $this->assertCount(2, $merged['columns']);

        $col1 = collect($merged['columns'])->firstWhere('license_id', $l1->id);
        $col2 = collect($merged['columns'])->firstWhere('license_id', $l2->id);
        $this->assertSame(3500, $col1['total']);
        $this->assertSame(500, $col2['total']);
        $this->assertTrue($col1['available']);
        $this->assertTrue($col2['available']);
    }

    public function test_merge_handles_unavailable_payloads(): void
    {
        $tenant = Tenant::factory()->create();
        $app1 = Application::factory()->create();
        $app2 = Application::factory()->create();
        $l1 = TenantApplication::factory()->for($tenant)->for($app1, 'application')->create();
        $l2 = TenantApplication::factory()->for($tenant)->for($app2, 'application')->create();

        $merged = $this->service->mergeComparative(
            [$l1, $l2],
            [$l1->id => null, $l2->id => null],
            'neraca',
            '2026-01',
        );

        $this->assertSame([], $merged['rows']);
        foreach ($merged['columns'] as $col) {
            $this->assertSame(0, $col['total']);
            $this->assertFalse($col['available']);
        }
    }

    public function test_merge_sorts_empty_codes_last(): void
    {
        $tenant = Tenant::factory()->create();
        $app = Application::factory()->create();
        $l = TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $merged = $this->service->mergeComparative([$l], [
            $l->id => [
                'status' => 'success',
                'period' => '2026-01',
                'generated_at' => null,
                'data' => [
                    ['account_code' => '', 'account_name' => 'Tanpa Kode', 'amount' => 100],
                    ['account_code' => '1', 'account_name' => 'Pertama', 'amount' => 200],
                    ['account_code' => '2', 'account_name' => 'Kedua', 'amount' => 300],
                ],
            ],
        ], 'neraca', '2026-01');

        $names = array_column($merged['rows'], 'account_name');
        $this->assertSame(['Pertama', 'Kedua', 'Tanpa Kode'], $names);
    }
}
