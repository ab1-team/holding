<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tenant::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $tenants = $query->withCount('users', 'tenantApplications')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.tenants.index', [
            'tenants' => $tenants,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.tenants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['name']);

        $tenant = Tenant::create($data);

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('status', "Tenant {$tenant->name} berhasil ditambahkan.");
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load(['users', 'tenantApplications.application']);

        return view('admin.tenants.show', [
            'tenant' => $tenant,
        ]);
    }

    public function edit(Tenant $tenant): View
    {
        return view('admin.tenants.edit', [
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $this->validateData($request, $tenant->id);
        $tenant->update($data);

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('status', "Tenant {$tenant->name} berhasil diperbarui.");
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $name = $tenant->name;
        $tenant->delete();

        return redirect()
            ->route('admin.tenants.index')
            ->with('status', "Tenant {$name} berhasil dihapus.");
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $slugRule = ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', Rule::notIn(\App\Support\ReservedSlug::all())];
        $slugRule[] = $ignoreId
            ? 'unique:tenants,slug,' . $ignoreId
            : 'unique:tenants,slug';

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => $slugRule,
            'domain' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
    }

    private function uniqueSlug(?string $slug, string $fallbackName): string
    {
        $base = $slug ?: Str::slug($fallbackName);
        $candidate = $base;
        $i = 1;

        while (Tenant::where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $i++;
        }

        return $candidate;
    }
}
