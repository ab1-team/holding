<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantApplicationController extends Controller
{
    public function create(Tenant $tenant): View
    {
        $assigned = $tenant->tenantApplications()->pluck('application_id')->all();
        $availableApplications = Application::where('is_active', true)
            ->whereNotIn('id', $assigned)
            ->orderBy('name')
            ->get();

        return view('admin.tenant_applications.create', [
            'tenant' => $tenant,
            'availableApplications' => $availableApplications,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $this->validateData($request);

        $license = $tenant->tenantApplications()->create(array_merge($data, [
            'api_secret' => Str::random(40),
            'activated_at' => now(),
        ]));

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('status', "Lisensi {$license->application->name} berhasil ditambahkan untuk {$tenant->name}.")
            ->with('new_api_secret', $license->api_secret);
    }

    public function edit(Tenant $tenant, TenantApplication $license): View
    {
        return view('admin.tenant_applications.edit', [
            'tenant' => $tenant,
            'license' => $license,
        ]);
    }

    public function update(Request $request, Tenant $tenant, TenantApplication $license): RedirectResponse
    {
        $data = $this->validateData($request, $license);
        $license->update($data);

        return redirect()
            ->route('admin.tenants.show', $license->tenant)
            ->with('status', "Lisensi {$license->application->name} berhasil diperbarui.");
    }

    public function destroy(Tenant $tenant, TenantApplication $license): RedirectResponse
    {
        $tenantModel = $license->tenant;
        $appName = $license->application->name;
        $license->delete();

        return redirect()
            ->route('admin.tenants.show', $tenantModel)
            ->with('status', "Lisensi {$appName} untuk {$tenantModel->name} berhasil dicabut.");
    }

    /**
     * Regenerate api_secret untuk license. Secret lama langsung invalid —
     * subsidiary yang masih pakai secret lama akan ditolak (401/403).
     */
    public function regenerateSecret(Request $request, Tenant $tenant, TenantApplication $license): RedirectResponse
    {
        abort_unless($license->tenant_id === $tenant->id, 404);

        $oldSecret = $license->api_secret;
        $newSecret = Str::random(40);

        $license->forceFill(['api_secret' => $newSecret])->save();

        ActivityLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'action' => 'regenerate_api_secret',
            'subject_type' => TenantApplication::class,
            'subject_id' => $license->id,
            'metadata' => [
                'application' => $license->application->name,
                'old_secret_prefix' => substr($oldSecret, 0, 8) . '...',
            ],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('status', "API Secret untuk {$license->application->name} berhasil di-regenerate. Secret lama langsung tidak valid.")
            ->with('new_api_secret', $newSecret);
    }

    private function validateData(Request $request, ?TenantApplication $license = null): array
    {
        $tenantId = $license?->tenant_id ?? $request->route('tenant');

        $applicationRule = [
            'required',
            'exists:applications,id',
            Rule::unique('tenant_applications', 'application_id')
                ->where('tenant_id', $tenantId)
                ->ignore($license?->id),
        ];

        return $request->validate([
            'application_id' => $applicationRule,
            'label' => ['nullable', 'string', 'max:255'],
            'instance_url' => ['required', 'url', 'max:255'],
            'is_active' => ['boolean'],
            'activated_at' => ['nullable', 'date'],
            'expired_at' => ['nullable', 'date', 'after:activated_at'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
