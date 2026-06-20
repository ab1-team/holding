<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\SubsidiaryReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LicenseConnectionTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::factory()->create(['role' => 'superadmin']);
    }

    private function makeLicense(Tenant $tenant, string $url = 'https://app.test', string $secret = 'tok1234567890123456789012345678901234567890'): TenantApplication
    {
        $app = Application::factory()->create();

        return TenantApplication::factory()
            ->for($tenant)
            ->for($app, 'application')
            ->create(['instance_url' => $url, 'api_secret' => $secret]);
    }

    public function test_superadmin_can_test_connection_and_get_diagnostic(): void
    {
        Http::fake([
            'app.test/*' => Http::response([
                'status' => 'success',
                'period' => '2026-06',
                'data' => [],
            ], 200),
        ]);

        $tenant = Tenant::factory()->create();
        $license = $this->makeLicense($tenant);

        $response = $this->actingAs($this->superadmin())
            ->postJson(route('admin.tenants.licenses.test-connection', [$tenant, $license]))
            ->assertOk();

        $response->assertJson(['status' => 'success', 'http_code' => 200]);
        $response->assertJsonStructure(['url', 'sent_headers', 'latency_ms', 'message']);

        // Header masked: 8 char + '...' + 4 char
        $token = $license->api_secret;
        $expectedMasked = substr($token, 0, 8) . '...' . substr($token, -4);
        $this->assertSame($expectedMasked, $response->json('sent_headers.X-Holding-Token'));
    }

    public function test_test_connection_returns_token_rejected_for_401(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Token tidak valid.'], 401)]);

        $tenant = Tenant::factory()->create();
        $license = $this->makeLicense($tenant);

        $response = $this->actingAs($this->superadmin())
            ->postJson(route('admin.tenants.licenses.test-connection', [$tenant, $license]))
            ->assertOk();

        $response->assertJson([
            'status' => 'error',
            'reason' => 'token_rejected',
            'http_code' => 401,
        ]);
    }

    public function test_test_connection_returns_validation_error_for_422(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Invalid period format.'], 422)]);

        $tenant = Tenant::factory()->create();
        $license = $this->makeLicense($tenant);

        $response = $this->actingAs($this->superadmin())
            ->postJson(route('admin.tenants.licenses.test-connection', [$tenant, $license]))
            ->assertOk();

        $response->assertJson([
            'status' => 'error',
            'reason' => 'validation_error',
            'http_code' => 422,
        ]);
    }

    public function test_test_connection_uses_instance_url_host_as_tenant_header(): void
    {
        Http::fake([
            'app.test/*' => Http::response(['status' => 'success', 'data' => []], 200),
        ]);

        $tenant = Tenant::factory()->create(['slug' => 'my-tenant', 'domain' => 'wrong.example.com']);
        $license = $this->makeLicense($tenant, 'https://app.test');

        $response = $this->actingAs($this->superadmin())
            ->postJson(route('admin.tenants.licenses.test-connection', [$tenant, $license]))
            ->assertOk();

        // Host dari instance_url, BUKAN tenant.domain atau tenant.slug.
        $this->assertSame('app.test', $response->json('sent_headers.X-Holding-Tenant'));
    }

    public function test_test_connection_returns_server_error_for_500(): void
    {
        Http::fake(['*' => Http::response('Server Error', 500)]);

        $tenant = Tenant::factory()->create();
        $license = $this->makeLicense($tenant);

        $response = $this->actingAs($this->superadmin())
            ->postJson(route('admin.tenants.licenses.test-connection', [$tenant, $license]))
            ->assertOk();

        $response->assertJson([
            'status' => 'error',
            'reason' => 'server_error',
            'http_code' => 500,
        ]);
    }

    public function test_test_connection_returns_unreachable_on_connection_exception(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: timeout');
        });

        $tenant = Tenant::factory()->create();
        $license = $this->makeLicense($tenant);

        $response = $this->actingAs($this->superadmin())
            ->postJson(route('admin.tenants.licenses.test-connection', [$tenant, $license]))
            ->assertOk();

        $response->assertJson([
            'status' => 'error',
            'reason' => 'unreachable',
            'http_code' => null,
        ]);
    }

    public function test_test_connection_logs_activity(): void
    {
        Http::fake(['*' => Http::response('Server Error', 500)]);

        $tenant = Tenant::factory()->create();
        $license = $this->makeLicense($tenant);
        $admin = $this->superadmin();

        $this->actingAs($admin)
            ->postJson(route('admin.tenants.licenses.test-connection', [$tenant, $license]))
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'test_license_connection',
            'subject_id' => $license->id,
            'subject_type' => TenantApplication::class,
            'user_id' => $admin->id,
        ]);
    }

    public function test_non_superadmin_cannot_test_connection(): void
    {
        $tenant = Tenant::factory()->create();
        $license = $this->makeLicense($tenant);
        $owner = User::factory()->forTenant($tenant->id, 'tenant_owner')->create();

        $this->actingAs($owner)
            ->postJson(route('admin.tenants.licenses.test-connection', [$tenant, $license]))
            ->assertForbidden();
    }

    public function test_test_connection_rejects_license_from_other_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $license = $this->makeLicense($tenantA);

        $this->actingAs($this->superadmin())
            ->postJson(route('admin.tenants.licenses.test-connection', [$tenantB, $license]))
            ->assertNotFound();
    }

    public function test_test_connection_url_uses_neraca_endpoint_with_current_period(): void
    {
        Http::fake([
            'app.test/*' => Http::response(['status' => 'success', 'data' => []], 200),
        ]);

        $tenant = Tenant::factory()->create();
        $license = $this->makeLicense($tenant);

        $response = $this->actingAs($this->superadmin())
            ->postJson(route('admin.tenants.licenses.test-connection', [$tenant, $license]))
            ->assertOk();

        $expected = 'https://app.test' . SubsidiaryReportService::ENDPOINTS['neraca']
            . '?' . SubsidiaryReportService::buildQuery(now()->format('Y-m'));
        $this->assertSame($expected, $response->json('url'));
    }
}
