<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LicenseAndAccessTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::factory()->create(['role' => 'superadmin']);
    }

    public function test_create_license_form_lists_unassigned_applications_only(): void
    {
        $admin = $this->superadmin();
        $tenant = Tenant::factory()->create();
        $enstore = Application::factory()->create(['name' => 'EnStore']);
        $entopup = Application::factory()->create(['name' => 'EnTopUp']);
        TenantApplication::factory()->for($tenant)->for($enstore, 'application')->create();

        $this->actingAs($admin)
            ->get(route('admin.tenants.licenses.create', $tenant))
            ->assertOk()
            ->assertSee('EnTopUp')
            ->assertDontSee('EnStore');
    }

    public function test_store_creates_license_with_auto_secret(): void
    {
        $admin = $this->superadmin();
        $tenant = Tenant::factory()->create();
        $app = Application::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.tenants.licenses.store', $tenant), [
            'application_id' => $app->id,
            'instance_url' => 'https://tenant-app.test',
            'is_active' => 1,
        ]);

        $license = TenantApplication::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($license);
        $this->assertSame(40, strlen($license->api_secret));
        $this->assertNotNull($license->activated_at);

        $response->assertRedirect(route('admin.tenants.show', $tenant));
        $response->assertSessionHas('new_api_secret', $license->api_secret);
    }

    public function test_store_rejects_duplicate_assignment(): void
    {
        $admin = $this->superadmin();
        $tenant = Tenant::factory()->create();
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $this->actingAs($admin)
            ->from(route('admin.tenants.licenses.create', $tenant))
            ->post(route('admin.tenants.licenses.store', $tenant), [
                'application_id' => $app->id,
                'instance_url' => 'https://x.test',
            ])
            ->assertSessionHasErrors('application_id');
    }

    public function test_store_rejects_expired_before_activated(): void
    {
        $admin = $this->superadmin();
        $tenant = Tenant::factory()->create();
        $app = Application::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.tenants.licenses.create', $tenant))
            ->post(route('admin.tenants.licenses.store', $tenant), [
                'application_id' => $app->id,
                'instance_url' => 'https://x.test',
                'activated_at' => '2024-06-01',
                'expired_at' => '2024-01-01',
            ])
            ->assertSessionHasErrors('expired_at');
    }

    public function test_update_modifies_license(): void
    {
        $admin = $this->superadmin();
        $tenant = Tenant::factory()->create();
        $app = Application::factory()->create();
        $license = TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $this->actingAs($admin)
            ->put(route('admin.licenses.update', $license), [
                'application_id' => $app->id,
                'instance_url' => 'https://updated.test',
                'is_active' => 0,
                'expired_at' => '2026-12-31',
            ])
            ->assertRedirect(route('admin.tenants.show', $tenant));

        $license->refresh();
        $this->assertSame('https://updated.test', $license->instance_url);
        $this->assertFalse($license->is_active);
    }

    public function test_destroy_revokes_license(): void
    {
        $admin = $this->superadmin();
        $tenant = Tenant::factory()->create();
        $app = Application::factory()->create();
        $license = TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $this->actingAs($admin)
            ->delete(route('admin.licenses.destroy', $license))
            ->assertRedirect(route('admin.tenants.show', $tenant));

        $this->assertDatabaseMissing('tenant_applications', ['id' => $license->id]);
    }

    public function test_tenant_user_can_quick_access_their_apps(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant->id, 'tenant_owner')->create();
        $app = Application::factory()->create();
        $license = TenantApplication::factory()->for($tenant)->for($app, 'application')->create([
            'instance_url' => 'https://target.test/path',
        ]);

        $response = $this->actingAs($user)->get(route('tenant.access', $license));

        $response->assertRedirect('https://target.test/path');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'access_app',
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'subject_id' => $license->id,
        ]);
    }

    public function test_tenant_user_cannot_access_other_tenants_apps(): void
    {
        $t1 = Tenant::factory()->create();
        $t2 = Tenant::factory()->create();
        $user = User::factory()->forTenant($t1->id, 'tenant_owner')->create();
        $app = Application::factory()->create();
        $otherLicense = TenantApplication::factory()->for($t2)->for($app, 'application')->create();

        $this->actingAs($user)
            ->get(route('tenant.access', $otherLicense))
            ->assertForbidden();
    }

    public function test_cannot_access_inactive_or_expired_license(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant->id, 'tenant_owner')->create();
        $inactiveApp = Application::factory()->create();
        $expiredApp = Application::factory()->create();
        $inactive = TenantApplication::factory()->for($tenant)->for($inactiveApp, 'application')->create(['is_active' => false]);
        $expired = TenantApplication::factory()->for($tenant)->for($expiredApp, 'application')->create(['expired_at' => now()->subDay()]);

        $this->actingAs($user)->get(route('tenant.access', $inactive))->assertForbidden();
        $this->actingAs($user)->get(route('tenant.access', $expired))->assertForbidden();
    }

    public function test_login_writes_activity_log(): void
    {
        User::factory()->create(['email' => 'admin@holding.local', 'password' => bcrypt('password'), 'role' => 'superadmin']);

        $this->post('/login', [
            'email' => 'admin@holding.local',
            'password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'login',
            'subject_type' => User::class,
        ]);
    }
}
