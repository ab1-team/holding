<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_root_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_login_form_is_accessible_to_guest(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Holding App')
            ->assertSee('Email')
            ->assertSee('Kata Sandi');
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/login')->assertRedirect();
    }

    public function test_login_with_wrong_password_fails(): void
    {
        User::factory()->create(['email' => 'admin@holding.local', 'password' => Hash::make('correct')]);

        $this->from('/login')->post('/login', [
            'email' => 'admin@holding.local',
            'password' => 'wrong',
        ])->assertRedirect('/login')
          ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_superadmin_login_redirects_to_admin_dashboard(): void
    {
        User::factory()->create([
            'email' => 'admin@holding.local',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@holding.local',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
        $this->assertNotNull(User::first()->last_login_at);
    }

    public function test_tenant_owner_login_redirects_to_tenant_home(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->forTenant($tenant->id, 'tenant_owner')->create([
            'email' => 'owner@tenant.test',
            'password' => Hash::make('password'),
        ]);

        session()->forget('url.intended');
        request()->server->set('HTTP_HOST', $tenant->slug . '.holding.test');
        \Illuminate\Support\Facades\URL::forceRootUrl('http://' . $tenant->slug . '.holding.test');

        $resp = $this->post('/login', [
            'email' => 'owner@tenant.test',
            'password' => 'password',
        ]);

        $loc = $resp->headers->get('Location');
        $this->assertSame(
            'http://' . $tenant->slug . '.holding.test/app',
            $loc,
            "status={$resp->getStatusCode()} loc={$loc} auth=" . (auth()->check() ? 'y' : 'n')
        );
    }

    public function test_tenant_owner_cannot_login_on_wrong_subdomain(): void
    {
        $acme = Tenant::factory()->create(['slug' => 'acme']);
        $beta = Tenant::factory()->create(['slug' => 'beta']);
        User::factory()->forTenant($acme->id, 'tenant_owner')->create([
            'email' => 'owner@acme.test',
            'password' => Hash::make('password'),
        ]);

        session()->forget('url.intended');
        request()->server->set('HTTP_HOST', 'beta.holding.test');
        \Illuminate\Support\Facades\URL::forceRootUrl('http://beta.holding.test');

        $this->post('/login', [
            'email' => 'owner@acme.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_superadmin_cannot_login_on_tenant_subdomain(): void
    {
        $acme = Tenant::factory()->create(['slug' => 'acme']);
        User::factory()->create([
            'email' => 'admin@holding.local',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'superadmin',
        ]);

        session()->forget('url.intended');
        request()->server->set('HTTP_HOST', 'acme.holding.test');
        \Illuminate\Support\Facades\URL::forceRootUrl('http://acme.holding.test');

        $this->post('/login', [
            'email' => 'admin@holding.local',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }

    public function test_tenant_user_cannot_access_admin_dashboard(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant->id, 'tenant_owner')->create();

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_superadmin_can_access_admin_dashboard_and_sees_stats(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        Tenant::factory()->count(3)->create();
        Application::factory()->count(2)->create();

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Dashboard Vendor')
            ->assertSee('Total Tenant')
            ->assertSee('3');
    }

    public function test_tenant_resolves_via_full_domain_not_just_slug(): void
    {
        $tenant = Tenant::factory()->create([
            'slug' => 'satu-desa-mandiri',
            'domain' => 'acme.holding.test',
        ]);
        $owner = User::factory()->forTenant($tenant->id, 'tenant_owner')->create();

        // Akses via domain (full host), bukan slug.
        $this->withServerVariables(['HTTP_HOST' => 'acme.holding.test'])
            ->actingAs($owner)
            ->get('/app')
            ->assertOk()
            ->assertSee($tenant->name);
    }

    public function test_tenant_resolves_via_subdomain_slug(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'acme']);
        $owner = User::factory()->forTenant($tenant->id, 'tenant_owner')->create();

        $this->withServerVariables(['HTTP_HOST' => 'acme.holding.test'])
            ->actingAs($owner)
            ->get('/app')
            ->assertOk()
            ->assertSee($tenant->name);
    }

    public function test_tenant_home_lists_their_active_applications(): void
    {
        $app = Application::factory()->create(['name' => 'EnStore']);
        $tenant = Tenant::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();
        $owner = User::factory()->forTenant($tenant->id, 'tenant_owner')->create();

        $this->actingAs($owner)
            ->get('/app')
            ->assertOk()
            ->assertSee('EnStore')
            ->assertSee($tenant->name);
    }

    public function test_logout_invalidates_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
