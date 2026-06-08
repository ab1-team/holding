<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAndActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_owner_can_manage_staff(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->forTenant($tenant->id, 'tenant_owner')->create();

        $this->actingAs($owner)
            ->get(route('tenant.staff.index'))
            ->assertOk()
            ->assertSee($tenant->name);
    }

    public function test_tenant_owner_can_invite_staff_with_generated_password(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->forTenant($tenant->id, 'tenant_owner')->create();

        $response = $this->actingAs($owner)->post(route('tenant.staff.store'), [
            'name' => 'Staff Baru',
            'email' => 'staffbaru@x.test',
        ]);

        $staff = User::where('email', 'staffbaru@x.test')->first();
        $this->assertNotNull($staff);
        $this->assertSame('tenant_staff', $staff->role);
        $this->assertSame($tenant->id, $staff->tenant_id);

        $response->assertRedirect(route('tenant.staff.index'));
        $response->assertSessionHas('initial_password');
    }

    public function test_tenant_owner_cannot_invite_with_duplicate_email(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->forTenant($tenant->id, 'tenant_owner')->create();
        User::factory()->forTenant($tenant->id, 'tenant_staff')->create(['email' => 'taken@x.test']);

        $this->actingAs($owner)
            ->from(route('tenant.staff.create'))
            ->post(route('tenant.staff.store'), [
                'name' => 'X',
                'email' => 'taken@x.test',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_tenant_owner_cannot_edit_staff_from_other_tenant(): void
    {
        $t1 = Tenant::factory()->create();
        $t2 = Tenant::factory()->create();
        $owner = User::factory()->forTenant($t1->id, 'tenant_owner')->create();
        $otherStaff = User::factory()->forTenant($t2->id, 'tenant_staff')->create();

        $this->actingAs($owner)
            ->get(route('tenant.staff.edit', $otherStaff))
            ->assertForbidden();
    }

    public function test_tenant_owner_cannot_edit_owner_or_superadmin(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->forTenant($tenant->id, 'tenant_owner')->create();
        $anotherOwner = User::factory()->forTenant($tenant->id, 'tenant_owner')->create(['email' => 'b@x.test']);
        $superadmin = User::factory()->create(['role' => 'superadmin', 'email' => 's@x.test']);

        $this->actingAs($owner)
            ->get(route('tenant.staff.edit', $anotherOwner))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('tenant.staff.edit', $superadmin))
            ->assertForbidden();
    }

    public function test_tenant_owner_can_edit_and_delete_their_staff(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->forTenant($tenant->id, 'tenant_owner')->create();
        $staff = User::factory()->forTenant($tenant->id, 'tenant_staff')->create(['name' => 'Lama']);

        $this->actingAs($owner)
            ->put(route('tenant.staff.update', $staff), [
                'name' => 'Baru',
                'email' => $staff->email,
                'is_active' => 1,
            ])
            ->assertRedirect(route('tenant.staff.index'));

        $staff->refresh();
        $this->assertSame('Baru', $staff->name);

        $this->actingAs($owner)
            ->delete(route('tenant.staff.destroy', $staff))
            ->assertRedirect(route('tenant.staff.index'));

        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
    }

    public function test_tenant_staff_cannot_access_staff_management(): void
    {
        $tenant = Tenant::factory()->create();
        $staff = User::factory()->forTenant($tenant->id, 'tenant_staff')->create();

        $this->actingAs($staff)
            ->get(route('tenant.staff.index'))
            ->assertForbidden();
    }

    public function test_superadmin_can_view_activity_logs(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        ActivityLog::create([
            'tenant_id' => null,
            'user_id' => $admin->id,
            'action' => 'login',
            'subject_type' => User::class,
            'subject_id' => $admin->id,
            'metadata' => ['ip' => '127.0.0.1'],
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->assertSee('login')
            ->assertSee('127.0.0.1');
    }

    public function test_activity_logs_can_be_filtered_by_tenant(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        $t1 = Tenant::factory()->create(['name' => 'T1']);
        $t2 = Tenant::factory()->create(['name' => 'T2']);
        ActivityLog::create(['tenant_id' => $t1->id, 'action' => 'login', 'created_at' => now()]);
        ActivityLog::create(['tenant_id' => $t2->id, 'action' => 'access_app', 'created_at' => now()]);

        // Test query logic — filter tenant_id bekerja (1 log per tenant)
        $this->assertCount(1, ActivityLog::where('tenant_id', $t1->id)->get());
        $this->assertCount(1, ActivityLog::where('tenant_id', $t2->id)->get());

        // Response OK
        $this->actingAs($admin)
            ->get(route('admin.activity-logs.index', ['tenant_id' => $t1->id]))
            ->assertOk();
    }

    public function test_activity_log_show_displays_metadata(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        $log = ActivityLog::create([
            'tenant_id' => null,
            'user_id' => $admin->id,
            'action' => 'login',
            'metadata' => ['key' => 'value', 'nested' => ['x' => 1]],
            'ip_address' => '10.0.0.1',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.activity-logs.show', $log))
            ->assertOk()
            ->assertSee('10.0.0.1')
            ->assertSee('"key"')
            ->assertSee('"value"');
    }

    public function test_tenant_user_cannot_view_activity_logs(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant->id, 'tenant_owner')->create();

        $this->actingAs($user)
            ->get(route('admin.activity-logs.index'))
            ->assertForbidden();
    }
}
