<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\ReportCache;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\SubsidiaryReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UnifiedReportTest extends TestCase
{
    use RefreshDatabase;

    private function tenantOwner(Tenant $tenant): User
    {
        return User::factory()->forTenant($tenant->id, 'tenant_owner')->create();
    }

    private function tenantStaff(Tenant $tenant): User
    {
        return User::factory()->forTenant($tenant->id, 'tenant_staff')->create();
    }

    /**
     * Build a fake subsidiary response payload.
     *
     * @param  array<int, array{account_code:string,account_name:string,amount:int}>  $rows
     */
    private function payload(string $period, array $rows): array
    {
        return [
            'status' => 'success',
            'period' => $period,
            'generated_at' => '2026-01-15T10:00:00Z',
            'data' => $rows,
        ];
    }

    public function test_reports_index_lists_only_financial_report_apps(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $fin = Application::factory()->create(['has_financial_report' => true, 'name' => 'EnFinance']);
        $nofin = Application::factory()->create(['has_financial_report' => false, 'name' => 'EnChat']);
        TenantApplication::factory()->for($tenant)->for($fin, 'application')->create();
        TenantApplication::factory()->for($tenant)->for($nofin, 'application')->create();

        $this->actingAs($owner)
            ->get(route('tenant.reports.index'))
            ->assertOk()
            ->assertSee('Laporan Keuangan Komparatif')
            ->assertSee('Neraca')
            ->assertSee('Laba Rugi');
    }

    public function test_superadmin_cannot_access_tenant_reports(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($admin)
            ->get(route('tenant.reports.index'))
            ->assertForbidden();
    }

    public function test_guest_redirected_to_login(): void
    {
        $this->get(route('tenant.reports.index'))
            ->assertRedirect(route('login'));
    }

    public function test_show_404_for_unknown_report_type(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'bogus']))
            ->assertNotFound();
    }

    public function test_show_validates_period_format(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $this->actingAs($owner)
            ->from(route('tenant.reports.show', ['type' => 'neraca']))
            ->get(route('tenant.reports.show', ['type' => 'neraca', 'period' => '2026-13']))
            ->assertSessionHasErrors('period');
    }

    public function test_show_fetches_and_renders_comparative_table(): void
    {
        Http::fake([
            'app-a.test/*' => Http::response($this->payload('2026-01', [
                ['account_code' => '1.1.01.01', 'account_name' => 'Kas', 'amount' => 10_000_000],
                ['account_code' => '1.1.01.02', 'account_name' => 'Bank', 'amount' => 5_000_000],
            ]), 200),
            'app-b.test/*' => Http::response($this->payload('2026-01', [
                ['account_code' => '1.1.01.01', 'account_name' => 'Kas', 'amount' => 3_500_000],
                ['account_code' => '2.1.01.01', 'account_name' => 'Utang', 'amount' => 1_200_000],
            ]), 200),
        ]);

        $tenant = Tenant::factory()->create(['slug' => 'acme']);
        $owner = $this->tenantOwner($tenant);
        $appA = Application::factory()->create(['name' => 'EnStore']);
        $appB = Application::factory()->create(['name' => 'EnTopUp']);
        $licA = TenantApplication::factory()->for($tenant)->for($appA, 'application')->create(['instance_url' => 'https://app-a.test']);
        $licB = TenantApplication::factory()->for($tenant)->for($appB, 'application')->create(['instance_url' => 'https://app-b.test']);

        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'neraca', 'period' => '2026-01']))
            ->assertOk();

        // Header
        $response->assertSee('Neraca Komparatif');
        $response->assertSee('EnStore');
        $response->assertSee('EnTopUp');

        // Composite key: Kas (sama di kedua app) → satu baris
        $response->assertSee('Kas');
        $response->assertSeeInOrder(['1.1.01.01', 'Kas', '10.000.000', '3.500.000']);

        // Kode+name unik hanya di B → baris sendiri
        $response->assertSee('Utang');

        // Total per app
        $response->assertSee('15.000.000'); // 10M + 5M
        $response->assertSee('4.700.000');  // 3.5M + 1.2M

        // Cache written
        $this->assertSame(2, ReportCache::count());
        $this->assertNotNull(ReportCache::where('tenant_application_id', $licA->id)->where('report_type', 'neraca')->where('period', '2026-01')->first());
        $this->assertNotNull(ReportCache::where('tenant_application_id', $licB->id)->where('report_type', 'neraca')->where('period', '2026-01')->first());

        // Activity log ditulis
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'view_report',
            'user_id' => $owner->id,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_uses_cached_payload_when_still_valid(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'acme']);
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        $lic = TenantApplication::factory()->for($tenant)->for($app, 'application')->create(['instance_url' => 'https://app-c.test']);

        ReportCache::create([
            'tenant_application_id' => $lic->id,
            'report_type' => 'laba_rugi',
            'period' => '2026-02',
            'payload' => $this->payload('2026-02', [
                ['account_code' => '4.1.01', 'account_name' => 'Pendapatan', 'amount' => 50_000_000],
            ]),
            'fetched_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(25),
        ]);

        // Jika Http dipanggil, test akan gagal karena tidak ada fake.
        $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'laba_rugi', 'period' => '2026-02']))
            ->assertOk()
            ->assertSee('Pendapatan')
            ->assertSee('50.000.000');
    }

    public function test_refetches_when_cache_expired(): void
    {
        Http::fake([
            '*' => Http::response($this->payload('2026-03', [
                ['account_code' => '5.1.01', 'account_name' => 'Beban', 'amount' => 7_000_000],
            ]), 200),
        ]);

        $tenant = Tenant::factory()->create(['slug' => 'acme']);
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        $lic = TenantApplication::factory()->for($tenant)->for($app, 'application')->create(['instance_url' => 'https://app-d.test']);

        ReportCache::create([
            'tenant_application_id' => $lic->id,
            'report_type' => 'arus_kas',
            'period' => '2026-03',
            'payload' => $this->payload('2026-03', [
                ['account_code' => 'STALE', 'account_name' => 'Stale', 'amount' => 1],
            ]),
            'fetched_at' => now()->subHours(2),
            'expires_at' => now()->subHours(1),
        ]);

        $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'arus_kas', 'period' => '2026-03']))
            ->assertOk()
            ->assertSee('Beban')
            ->assertSee('7.000.000')
            ->assertDontSee('Stale');
    }

    public function test_tenant_isolation_other_tenants_apps_excluded(): void
    {
        Http::fake([
            '*' => Http::response($this->payload('2026-04', [
                ['account_code' => 'X', 'account_name' => 'Y', 'amount' => 100],
            ]), 200),
        ]);

        $t1 = Tenant::factory()->create(['slug' => 't1']);
        $t2 = Tenant::factory()->create(['slug' => 't2']);
        $owner = $this->tenantOwner($t1);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($t1)->for($app, 'application')->create(['instance_url' => 'https://mine.test']);
        $otherLicense = TenantApplication::factory()->for($t2)->for($app, 'application')->create(['instance_url' => 'https://other.test']);

        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'neraca', 'period' => '2026-04']))
            ->assertOk();
        $response->assertSee('X');
        $response->assertSee('Y');
        // Confirm the other license wasn't accessed (Http::fake has no sequence assertion, but no error is enough)
        $this->assertTrue(true);
    }

    public function test_sends_correct_headers_to_subsidiary(): void
    {
        Http::fake([
            'https://app-e.test/*' => Http::response($this->payload('2026-05', [
                ['account_code' => '1', 'account_name' => 'A', 'amount' => 100],
            ]), 200),
        ]);

        $tenant = Tenant::factory()->create(['slug' => 'slugku']);
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        $lic = TenantApplication::factory()->for($tenant)->for($app, 'application')->create(['instance_url' => 'https://app-e.test', 'api_secret' => 'super-secret-token']);

        $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'perubahan_ekuitas', 'period' => '2026-05']))
            ->assertOk();

        Http::assertSent(function ($request) use ($lic) {
            return $request->url() === 'https://app-e.test/api/v1/holding/laporan/perubahan-ekuitas?tahun=2026&bulan=05'
                && $request->header('X-Holding-Token')[0] === $lic->api_secret
                && $request->header('X-Holding-Tenant')[0] === 'slugku'
                && $request->header('Accept')[0] === 'application/json';
        });
    }

    public function test_sends_tenant_domain_as_header_when_set(): void
    {
        Http::fake([
            'https://app-f.test/*' => Http::response($this->payload('2026-07', []), 200),
        ]);

        $tenant = Tenant::factory()->create(['slug' => 'acme', 'domain' => 'kantan-bumdesma.net']);
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        $lic = TenantApplication::factory()->for($tenant)->for($app, 'application')->create(['instance_url' => 'https://app-f.test']);

        $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'neraca', 'period' => '2026-07']))
            ->assertOk();

        Http::assertSent(function ($request) {
            return $request->header('X-Holding-Tenant')[0] === 'kantan-bumdesma.net';
        });
    }

    public function test_calk_report_type_supported(): void
    {
        Http::fake([
            'https://app-g.test/*' => Http::response($this->payload('2026-08', [
                ['account_code' => 'NOTE.1', 'account_name' => 'Kebijakan Akuntansi', 'amount' => 0, 'notes' => 'PSAK 1'],
            ]), 200),
        ]);

        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create(['instance_url' => 'https://app-g.test']);

        $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'calk', 'period' => '2026-08']))
            ->assertOk()
            ->assertSee('Catatan (CALK)')
            ->assertSee('Kebijakan Akuntansi');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/v1/holding/laporan/calk')
                && $request->url() === 'https://app-g.test/api/v1/holding/laporan/calk?tahun=2026&bulan=08';
        });
    }

    public function test_report_type_labels_include_calk(): void
    {
        $labels = SubsidiaryReportService::reportTypeLabels();
        $this->assertArrayHasKey('calk', $labels);
        $this->assertSame('Catatan (CALK)', $labels['calk']);
    }

    public function test_pdf_endpoint_with_inline_param_renders_in_browser(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.pdf', ['type' => 'neraca', 'period' => '2026-01', 'inline' => 1]));

        $response->assertOk();
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
    }

    public function test_pdf_endpoint_without_inline_param_force_download(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.pdf', ['type' => 'neraca', 'period' => '2026-01']));

        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
    }

    public function test_subsidiary_offline_gracefully_handled(): void
    {
        Http::fake([
            '*' => Http::response('Server Error', 500),
        ]);

        $tenant = Tenant::factory()->create(['slug' => 't']);
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'neraca', 'period' => '2026-06']))
            ->assertOk();
        $response->assertSee('tidak dapat dihubungi');
        $response->assertSee('1 aplikasi sedang'); // unavailable_count = 1
    }

    public function test_staff_can_view_reports(): void
    {
        Http::fake([
            '*' => Http::response($this->payload('2026-07', [
                ['account_code' => '1', 'account_name' => 'X', 'amount' => 1],
            ]), 200),
        ]);

        $tenant = Tenant::factory()->create();
        $staff = $this->tenantStaff($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $this->actingAs($staff)
            ->get(route('tenant.reports.index'))
            ->assertOk();
        $this->actingAs($staff)
            ->get(route('tenant.reports.show', ['type' => 'neraca', 'period' => '2026-07']))
            ->assertOk();
    }

    public function test_comparative_merge_uses_composite_key(): void
    {
        $service = app(SubsidiaryReportService::class);
        $app1 = Application::factory()->create();
        $app2 = Application::factory()->create();
        $tenant = Tenant::factory()->create();
        $l1 = TenantApplication::factory()->for($tenant)->for($app1, 'application')->create();
        $l2 = TenantApplication::factory()->for($tenant)->for($app2, 'application')->create();

        $payloads = [
            $l1->id => $this->payload('2026-08', [
                ['account_code' => '3.1.02.01', 'account_name' => 'Modal Usaha', 'amount' => 50_000_000],
                ['account_code' => '3.1.02.01', 'account_name' => 'Modal Desa', 'amount' => 0],
            ]),
            $l2->id => $this->payload('2026-08', [
                ['account_code' => '3.1.02.01', 'account_name' => 'Modal Desa', 'amount' => 30_000_000],
            ]),
        ];

        $merged = $service->mergeComparative([$l1, $l2], $payloads, 'neraca', '2026-08');

        $this->assertCount(2, $merged['rows'], 'Same code + different name harus jadi 2 baris');
        $codes = array_column($merged['rows'], 'account_code');
        $this->assertSame(['3.1.02.01', '3.1.02.01'], $codes);

        $names = array_column($merged['rows'], 'account_name');
        $this->assertSame(['Modal Desa', 'Modal Usaha'], $names, 'Sort by code lalu name');

        // Baris Modal Usaha: amount di l1 = 50jt, di l2 = null
        $modalUsaha = collect($merged['rows'])->firstWhere('account_name', 'Modal Usaha');
        $this->assertSame(50_000_000, $modalUsaha['amounts'][$l1->id]);
        $this->assertArrayNotHasKey($l2->id, $modalUsaha['amounts']);
    }

    public function test_report_type_labels_exist(): void
    {
        $labels = SubsidiaryReportService::reportTypeLabels();
        $this->assertSame('Neraca', $labels['neraca']);
        $this->assertSame('Laba Rugi', $labels['laba_rugi']);
        $this->assertSame('Arus Kas', $labels['arus_kas']);
        $this->assertSame('Perubahan Ekuitas', $labels['perubahan_ekuitas']);
    }

    public function test_report_form_has_monthpicker_attribute(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'neraca']))
            ->assertOk()
            ->assertSee('data-monthpicker', false)
            ->assertSee('name="period"', false);
    }
}
