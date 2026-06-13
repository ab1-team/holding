<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantCrudTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::factory()->create(['role' => 'superadmin']);
    }

    public function test_index_lists_tenants_with_counts(): void
    {
        $admin = $this->superadmin();
        $t1 = Tenant::factory()->create(['name' => 'PT Maju']);
        $t2 = Tenant::factory()->create(['name' => 'CV Sentosa']);
        User::factory()->forTenant($t1->id, 'tenant_owner')->create();

        $this->actingAs($admin)
            ->get(route('admin.tenants.index'))
            ->assertOk()
            ->assertSee('PT Maju')
            ->assertSee('CV Sentosa')
            ->assertSee('1');
    }

    public function test_search_filters_by_name_slug_or_email(): void
    {
        $admin = $this->superadmin();
        Tenant::factory()->create(['name' => 'PT Maju', 'email' => 'maju@x.test']);
        Tenant::factory()->create(['name' => 'CV Sentosa', 'email' => 's@x.test']);

        $this->actingAs($admin)
            ->get(route('admin.tenants.index', ['search' => 'maju']))
            ->assertSee('PT Maju')
            ->assertDontSee('CV Sentosa');
    }

    public function test_create_form_is_accessible(): void
    {
        $this->actingAs($this->superadmin())
            ->get(route('admin.tenants.create'))
            ->assertOk()
            ->assertSee('Tambah Tenant');
    }

    public function test_store_creates_tenant_with_auto_slug(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)->post(route('admin.tenants.store'), [
            'name' => 'PT Maju Jaya',
            'email' => 'maju@jaya.test',
            'phone' => '08123456789',
        ])->assertRedirect();

        $tenant = Tenant::where('name', 'PT Maju Jaya')->first();
        $this->assertNotNull($tenant);
        $this->assertSame('pt-maju-jaya', $tenant->slug);
    }

    public function test_store_uses_provided_slug_when_present(): void
    {
        $this->actingAs($this->superadmin())->post(route('admin.tenants.store'), [
            'name' => 'Apapun',
            'slug' => 'kustom-123',
            'email' => 'a@b.test',
            'phone' => '08',
        ])->assertRedirect();

        $this->assertDatabaseHas('tenants', ['slug' => 'kustom-123']);
    }

    public function test_store_rejects_reserved_slug(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)->post(route('admin.tenants.store'), [
            'name' => 'Test',
            'slug' => 'admin',
            'email' => 'a@b.test',
            'phone' => '08',
        ])->assertSessionHasErrors('slug');

        $this->assertDatabaseMissing('tenants', ['slug' => 'admin']);
    }

    public function test_update_persists_domain_field(): void
    {
        $admin = $this->superadmin();
        $tenant = Tenant::factory()->create();

        $this->actingAs($admin)->put(route('admin.tenants.update', $tenant), [
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'domain' => 'kantan-bumdesma.net',
            'email' => $tenant->email,
            'phone' => $tenant->phone,
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertSame('kantan-bumdesma.net', $tenant->fresh()->domain);
    }

    public function test_update_can_clear_domain(): void
    {
        $admin = $this->superadmin();
        $tenant = Tenant::factory()->create(['domain' => 'old.example.com']);

        $this->actingAs($admin)->put(route('admin.tenants.update', $tenant), [
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'domain' => '',
            'email' => $tenant->email,
            'phone' => $tenant->phone,
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertNull($tenant->fresh()->domain);
    }

    public function test_store_ensures_unique_slug_with_suffix(): void
    {
        $admin = $this->superadmin();
        Tenant::factory()->create(['name' => 'PT Sama', 'slug' => 'pt-sama']);

        $this->actingAs($admin)->post(route('admin.tenants.store'), [
            'name' => 'PT Sama',
            'email' => 'sama2@b.test',
            'phone' => '08',
        ])->assertRedirect();

        $this->assertDatabaseHas('tenants', ['slug' => 'pt-sama-1']);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->superadmin())
            ->from(route('admin.tenants.create'))
            ->post(route('admin.tenants.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'phone']);
    }

    public function test_show_displays_tenant_with_users_and_apps(): void
    {
        $admin = $this->superadmin();
        $tenant = Tenant::factory()->create(['name' => 'PT Maju']);
        User::factory()->forTenant($tenant->id, 'tenant_owner')->create(['name' => 'Pak Bos']);
        User::factory()->forTenant($tenant->id, 'tenant_staff')->create(['name' => 'Bu Ani']);

        $this->actingAs($admin)
            ->get(route('admin.tenants.show', $tenant))
            ->assertOk()
            ->assertSee('PT Maju')
            ->assertSee('Pak Bos')
            ->assertSee('Bu Ani')
            ->assertSee('Pemilik')
            ->assertSee('Staff');
    }

    public function test_update_modifies_tenant(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Lama']);
        $admin = $this->superadmin();

        $this->actingAs($admin)
            ->put(route('admin.tenants.update', $tenant), [
                'name' => 'Baru',
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'is_active' => 0,
            ])
            ->assertRedirect(route('admin.tenants.show', $tenant));

        $tenant->refresh();
        $this->assertSame('Baru', $tenant->name);
        $this->assertFalse($tenant->is_active);
    }

    public function test_update_rejects_duplicate_slug_from_other_tenant(): void
    {
        $a = Tenant::factory()->create(['slug' => 'a']);
        $b = Tenant::factory()->create(['slug' => 'b']);
        $admin = $this->superadmin();

        $this->actingAs($admin)
            ->put(route('admin.tenants.update', $b), [
                'name' => $b->name,
                'slug' => 'a',
                'email' => $b->email,
                'phone' => $b->phone,
            ])
            ->assertSessionHasErrors('slug');
    }

    public function test_destroy_orphans_users_and_removes_apps(): void
    {
        $admin = $this->superadmin();
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant->id, 'tenant_owner')->create();

        $this->actingAs($admin)
            ->delete(route('admin.tenants.destroy', $tenant))
            ->assertRedirect(route('admin.tenants.index'));

        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
        // users.tenant_id → nullOnDelete: user jadi orphan (tenant_id null), bukan terhapus
        $this->assertDatabaseHas('users', ['id' => $user->id, 'tenant_id' => null]);
    }

    public function test_tenant_user_cannot_access_tenants_crud(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant->id, 'tenant_owner')->create();

        $this->actingAs($user)
            ->get(route('admin.tenants.index'))
            ->assertForbidden();
    }
}
