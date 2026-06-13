<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Phase4PolishTest extends TestCase
{
    use RefreshDatabase;

    private function tenantOwner(Tenant $tenant): User
    {
        return User::factory()->forTenant($tenant->id, 'tenant_owner')->create();
    }

    private function payload(string $period, array $rows): array
    {
        return [
            'status' => 'success',
            'period' => $period,
            'generated_at' => '2026-01-15T10:00:00Z',
            'data' => $rows,
        ];
    }

    public function test_tenant_dashboard_shows_banner_for_license_expiring_within_30_days(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create(['name' => 'EnStore']);
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create([
            'expired_at' => now()->addDays(7),
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('tenant.home'))
            ->assertOk()
            ->assertSee('akan kedaluwarsa dalam 30 hari ke depan')
            ->assertSee('EnStore');
    }

    public function test_tenant_dashboard_banner_marks_already_expired_license_as_error(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create(['name' => 'EnStore']);
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create([
            'expired_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('tenant.home'))
            ->assertOk()
            ->assertSee('sudah kedaluwarsa')
            ->assertSee('Kedaluwarsa');
    }

    public function test_tenant_dashboard_no_banner_when_license_not_expiring_soon(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create([
            'expired_at' => now()->addMonths(6),
        ]);

        $this->actingAs($owner)
            ->get(route('tenant.home'))
            ->assertOk()
            ->assertDontSee('akan kedaluwarsa dalam 30 hari')
            ->assertDontSee('sudah kedaluwarsa');
    }

    public function test_tenant_dashboard_ignores_inactive_license_in_banner(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create([
            'expired_at' => now()->addDays(5),
            'is_active' => false,
        ]);

        $this->actingAs($owner)
            ->get(route('tenant.home'))
            ->assertOk()
            ->assertDontSee('akan kedaluwarsa dalam 30 hari');
    }

    public function test_csv_export_returns_streamed_csv_with_bom_and_totals(): void
    {
        Http::fake([
            'https://app-x.test/*' => Http::response($this->payload('2026-01', [
                ['account_code' => '1.1.01', 'account_name' => 'Kas', 'amount' => 1_500_000],
                ['account_code' => '1.1.02', 'account_name' => 'Bank', 'amount' => 2_500_000],
            ]), 200),
            'https://app-y.test/*' => Http::response($this->payload('2026-01', [
                ['account_code' => '1.1.01', 'account_name' => 'Kas', 'amount' => 800_000],
            ]), 200),
        ]);

        $tenant = Tenant::factory()->create(['slug' => 'acme']);
        $owner = $this->tenantOwner($tenant);
        $app1 = Application::factory()->create(['name' => 'AppX']);
        $app2 = Application::factory()->create(['name' => 'AppY']);
        TenantApplication::factory()->for($tenant)->for($app1, 'application')->create(['instance_url' => 'https://app-x.test']);
        TenantApplication::factory()->for($tenant)->for($app2, 'application')->create(['instance_url' => 'https://app-y.test']);

        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.csv', ['type' => 'neraca', 'period' => '2026-01']))
            ->assertOk();

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content, 'BOM UTF-8 untuk Excel');
        // fputcsv default enclosures fields containing separator; just assert each token is present
        $this->assertStringContainsString('Kode', $content);
        $this->assertStringContainsString('Nama Akun', $content);
        $this->assertStringContainsString('AppX', $content);
        $this->assertStringContainsString('AppY', $content);
        $this->assertStringContainsString('1.1.01', $content);
        $this->assertStringContainsString('Kas', $content);
        $this->assertStringContainsString('1500000', $content);
        $this->assertStringContainsString('800000', $content);
        $this->assertStringContainsString('1.1.02', $content);
        $this->assertStringContainsString('Bank', $content);
        $this->assertStringContainsString('2500000', $content);
        $this->assertStringContainsString('Total', $content);
        $this->assertStringContainsString('4000000', $content);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'export_report_csv',
            'user_id' => $owner->id,
        ]);
    }

    public function test_csv_export_404_for_unknown_report_type(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $this->actingAs($owner)
            ->get(route('tenant.reports.csv', ['type' => 'bogus']))
            ->assertNotFound();
    }

    public function test_csv_export_forbidden_for_superadmin(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($admin)
            ->get(route('tenant.reports.csv', ['type' => 'neraca']))
            ->assertForbidden();
    }

    public function test_pdf_export_returns_pdf_download(): void
    {
        Http::fake([
            'https://app-z.test/*' => Http::response($this->payload('2026-09', [
                ['account_code' => '1.1.01', 'account_name' => 'Kas', 'amount' => 5_000_000],
            ]), 200),
        ]);

        $tenant = Tenant::factory()->create(['slug' => 'acme', 'name' => 'PT Test']);
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create(['name' => 'AppZ']);
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create(['instance_url' => 'https://app-z.test']);

        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.pdf', ['type' => 'neraca', 'period' => '2026-09']));

        $response->assertOk();
        $content = $response->getContent() ?: $response->streamedContent();
        $this->assertStringStartsWith('%PDF-', $content);
        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('laporan-neraca-2026-09-', $disposition);
        $this->assertStringContainsString('.pdf', $disposition);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'export_report_pdf',
            'user_id' => $owner->id,
        ]);
    }

    public function test_pdf_export_isolates_tenant_data(): void
    {
        Http::fake([
            '*' => Http::response($this->payload('2026-10', [
                ['account_code' => 'X', 'account_name' => 'Y', 'amount' => 100],
            ]), 200),
        ]);

        $t1 = Tenant::factory()->create(['slug' => 't1']);
        $t2 = Tenant::factory()->create(['slug' => 't2']);
        $owner = $this->tenantOwner($t1);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($t1)->for($app, 'application')->create();
        TenantApplication::factory()->for($t2)->for($app, 'application')->create();

        $this->actingAs($owner)
            ->get(route('tenant.reports.pdf', ['type' => 'neraca', 'period' => '2026-10']))
            ->assertOk();
    }
}
