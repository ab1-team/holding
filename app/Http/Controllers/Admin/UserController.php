<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->with('tenant');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
        ]);
    }

    public function create(): View
    {
        $tenants = Tenant::where('is_active', true)->orderBy('name')->get();

        return view('admin.users.create', [
            'tenants' => $tenants,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $initialPassword = Str::random(10);

        $user = User::create([
            'tenant_id' => $data['tenant_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($initialPassword),
            'role' => $data['role'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', "Pengguna {$user->name} berhasil dibuat.")
            ->with('initial_password', $initialPassword);
    }

    public function show(User $user): View
    {
        $user->load('tenant');

        return view('admin.users.show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user): View
    {
        $tenants = Tenant::where('is_active', true)->orderBy('name')->get();

        return view('admin.users.edit', [
            'user' => $user,
            'tenants' => $tenants,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validateData($request, $user);
        $updateData = [
            'tenant_id' => $data['tenant_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => $data['is_active'] ?? true,
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', "Pengguna {$user->name} berhasil diperbarui.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->isSuperadmin()) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['error' => 'Superadmin tidak dapat dihapus. Nonaktifkan akun sebagai gantinya.']);
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Pengguna {$name} berhasil dihapus.");
    }

    private function validateData(Request $request, ?User $user = null): array
    {
        $emailRule = ['required', 'email', 'max:255'];
        $emailRule[] = Rule::unique('users', 'email')->ignore($user?->id);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRule,
            'role' => ['required', Rule::in(['superadmin', 'tenant_owner', 'tenant_staff'])],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'is_active' => ['boolean'],
        ];

        if (! $user) {
            // password di-handle terpisah saat create
        } else {
            $rules['password'] = ['nullable', 'string', 'min:8'];
        }

        $data = $request->validate($rules);

        // Konsistensi role ↔ tenant_id
        if (($data['role'] ?? null) === 'superadmin') {
            $data['tenant_id'] = null;
        } elseif (! in_array($data['role'] ?? null, ['tenant_owner', 'tenant_staff'], true)) {
            $data['tenant_id'] = null;
        } elseif (empty($data['tenant_id'])) {
            $request->validate(['tenant_id' => ['required']]);
        }

        return $data;
    }
}
