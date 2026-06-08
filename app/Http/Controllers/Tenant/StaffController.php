<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant;
        $staff = $tenant->users()->orderBy('name')->get();

        return view('tenant.staff.index', [
            'tenant' => $tenant,
            'staff' => $staff,
        ]);
    }

    public function create(Request $request): View
    {
        return view('tenant.staff.create', [
            'tenant' => $request->user()->tenant,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
        ]);

        $initialPassword = Str::random(10);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($initialPassword),
            'role' => 'tenant_staff',
            'is_active' => true,
        ]);

        return redirect()
            ->route('tenant.staff.index')
            ->with('status', "Staff {$user->name} berhasil diundang.")
            ->with('initial_password', $initialPassword);
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorizeStaffAccess($request, $user);

        return view('tenant.staff.edit', [
            'tenant' => $request->user()->tenant,
            'staff' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeStaffAccess($request, $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'is_active' => ['boolean'],
        ]);

        $user->update($data);

        return redirect()
            ->route('tenant.staff.index')
            ->with('status', "Staff {$user->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeStaffAccess($request, $user);

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('tenant.staff.index')
            ->with('status', "Staff {$name} berhasil dihapus.");
    }

    private function authorizeStaffAccess(Request $request, User $user): void
    {
        $tenant = $request->user()->tenant;
        abort_unless($user->tenant_id === $tenant->id, 403, 'Staff bukan bagian dari tenant Anda.');
        abort_if($user->isSuperadmin() || $user->isTenantOwner(), 403, 'Tidak dapat mengelola user dengan role tersebut.');
    }
}
