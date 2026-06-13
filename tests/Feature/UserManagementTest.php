<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::factory()->create(['role' => 'superadmin']);
    }

    public function test_index_lists_all_users_with_filters(): void
    {
        $admin = $this->superadmin();
        $t = Tenant::factory()->create();
        User::factory()->create(['name' => 'Budi', 'role' => 'tenant_owner']);
        User::factory()->forTenant($t->id, 'tenant_staff')->create(['name' => 'Siti']);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Budi')
            ->assertSee('Siti');

        // Smart-table tidak filter by role; role filter hanya via search bar UI.
        // Asersi hanya bahwa halaman OK & tetap menampilkan semua user (tanpa filter).
        $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Siti']))
            ->assertSee('Siti')
            ->assertDontSee('Budi');
    }

    public function test_create_form_is_accessible(): void
    {
        $this->actingAs($this->superadmin())
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Tambah Pengguna');
    }

    public function test_store_creates_user_with_generated_password(): void
    {
        $admin = $this->superadmin();
        $tenant = Tenant::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Andi',
            'email' => 'andi@x.test',
            'role' => 'tenant_owner',
            'tenant_id' => $tenant->id,
            'is_active' => 1,
        ]);

        $user = User::where('email', 'andi@x.test')->first();
        $this->assertNotNull($user);
        $this->assertSame('tenant_owner', $user->role);
        $this->assertSame($tenant->id, $user->tenant_id);

        $response->assertRedirect(route('admin.users.show', $user));
        $response->assertSessionHas('initial_password');
    }

    public function test_store_creates_superadmin_with_null_tenant(): void
    {
        $admin = $this->superadmin();
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Vendor',
            'email' => 'vendor@x.test',
            'role' => 'superadmin',
            'is_active' => 1,
        ])->assertRedirect();

        $u = User::where('email', 'vendor@x.test')->first();
        $this->assertNull($u->tenant_id);
    }

    public function test_store_rejects_tenant_required_for_non_superadmin(): void
    {
        $this->actingAs($this->superadmin())
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'X',
                'email' => 'x@x.test',
                'role' => 'tenant_owner',
            ])
            ->assertSessionHasErrors('tenant_id');
    }

    public function test_store_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@x.test']);
        $this->actingAs($this->superadmin())
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'X',
                'email' => 'taken@x.test',
                'role' => 'superadmin',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_show_displays_user(): void
    {
        $user = User::factory()->create(['role' => 'superadmin', 'name' => 'Pak Admin']);
        $this->actingAs($this->superadmin())
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Pak Admin')
            ->assertSee('Vendor Admin');
    }

    public function test_update_modifies_user(): void
    {
        $user = User::factory()->create(['name' => 'Lama']);
        $admin = $this->superadmin();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Baru',
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => 0,
            ])
            ->assertRedirect(route('admin.users.show', $user));

        $user->refresh();
        $this->assertSame('Baru', $user->name);
        $this->assertFalse($user->is_active);
    }

    public function test_destroy_removes_user(): void
    {
        $user = User::factory()->create(['role' => 'tenant_staff', 'tenant_id' => Tenant::factory()]);
        $this->actingAs($this->superadmin())
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_destroy_blocks_superadmin_deletion(): void
    {
        $admin = $this->superadmin();
        $another = User::factory()->create(['role' => 'superadmin', 'email' => 'second@x.test']);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $another))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('users', ['id' => $another->id]);
    }

    public function test_tenant_user_cannot_access_users_crud(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant->id, 'tenant_owner')->create();

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }
}
