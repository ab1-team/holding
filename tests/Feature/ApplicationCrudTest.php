<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApplicationCrudTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::factory()->create(['role' => 'superadmin']);
    }

    public function test_index_lists_applications_with_search(): void
    {
        $admin = $this->superadmin();
        Application::factory()->create(['name' => 'EnStore', 'slug' => 'enstore']);
        Application::factory()->create(['name' => 'EnTopUp', 'slug' => 'entopup']);

        $this->actingAs($admin)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee('EnStore')
            ->assertSee('EnTopUp');

        $this->actingAs($admin)
            ->get(route('admin.applications.index', ['search' => 'enstore']))
            ->assertOk()
            ->assertSee('EnStore')
            ->assertDontSee('EnTopUp');
    }

    public function test_create_form_is_accessible(): void
    {
        $this->actingAs($this->superadmin())
            ->get(route('admin.applications.create'))
            ->assertOk()
            ->assertSee('Tambah Aplikasi');
    }

    public function test_store_creates_application_with_auto_token(): void
    {
        $admin = $this->superadmin();

        $response = $this->actingAs($admin)->post(route('admin.applications.store'), [
            'name' => 'EnKas',
            'slug' => 'enkas',
            'description' => 'Pembukuan kas UMKM',
            'base_url' => 'https://enkas.test',
            'has_financial_report' => 1,
            'is_active' => 1,
        ]);

        $app = Application::where('slug', 'enkas')->first();
        $this->assertNotNull($app);
        $this->assertNotEmpty($app->api_token_key);
        $this->assertSame(32, strlen($app->api_token_key));

        $response->assertRedirect(route('admin.applications.show', $app));
    }

    public function test_store_validates_unique_slug(): void
    {
        Application::factory()->create(['slug' => 'enstore']);

        $this->actingAs($this->superadmin())
            ->from(route('admin.applications.create'))
            ->post(route('admin.applications.store'), [
                'name' => 'Lain',
                'slug' => 'enstore',
                'base_url' => 'https://x.test',
            ])
            ->assertRedirect(route('admin.applications.create'))
            ->assertSessionHasErrors('slug');
    }

    public function test_store_validates_slug_format(): void
    {
        $this->actingAs($this->superadmin())
            ->from(route('admin.applications.create'))
            ->post(route('admin.applications.store'), [
                'name' => 'X',
                'slug' => 'Tidak Valid!',
                'base_url' => 'https://x.test',
            ])
            ->assertSessionHasErrors('slug');
    }

    public function test_show_displays_token_key(): void
    {
        $app = Application::factory()->create(['api_token_key' => 'secret-key-123']);

        $this->actingAs($this->superadmin())
            ->get(route('admin.applications.show', $app))
            ->assertOk()
            ->assertSee('secret-key-123');
    }

    public function test_update_modifies_application(): void
    {
        $app = Application::factory()->create(['name' => 'Lama']);
        $admin = $this->superadmin();

        $this->actingAs($admin)
            ->put(route('admin.applications.update', $app), [
                'name' => 'Baru',
                'slug' => $app->slug,
                'base_url' => $app->base_url,
                'has_financial_report' => 0,
                'is_active' => 0,
            ])
            ->assertRedirect(route('admin.applications.show', $app));

        $app->refresh();
        $this->assertSame('Baru', $app->name);
        $this->assertFalse($app->has_financial_report);
        $this->assertFalse($app->is_active);
    }

    public function test_update_allows_same_slug(): void
    {
        $app = Application::factory()->create(['slug' => 'enstore']);
        $admin = $this->superadmin();

        $this->actingAs($admin)
            ->put(route('admin.applications.update', $app), [
                'name' => 'EnStore v2',
                'slug' => 'enstore',
                'base_url' => $app->base_url,
            ])
            ->assertRedirect(route('admin.applications.show', $app));
    }

    public function test_update_rejects_duplicate_slug_from_other_app(): void
    {
        $a = Application::factory()->create(['slug' => 'enstore']);
        $b = Application::factory()->create(['slug' => 'entopup']);
        $admin = $this->superadmin();

        $this->actingAs($admin)
            ->put(route('admin.applications.update', $b), [
                'name' => $b->name,
                'slug' => 'enstore',
                'base_url' => $b->base_url,
            ])
            ->assertSessionHasErrors('slug');
    }

    public function test_destroy_deletes_application(): void
    {
        $app = Application::factory()->create();
        $admin = $this->superadmin();

        $this->actingAs($admin)
            ->delete(route('admin.applications.destroy', $app))
            ->assertRedirect(route('admin.applications.index'));

        $this->assertDatabaseMissing('applications', ['id' => $app->id]);
    }

    public function test_tenant_user_cannot_access_applications_crud(): void
    {
        $tenant = \App\Models\Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant->id, 'tenant_owner')->create();

        $this->actingAs($user)
            ->get(route('admin.applications.index'))
            ->assertForbidden();
    }
}
