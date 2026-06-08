<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function superadmin(Request $request): View
    {
        $stats = [
            'tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('is_active', true)->count(),
            'applications' => Application::count(),
            'tenant_applications' => TenantApplication::where('is_active', true)->count(),
            'users' => User::count(),
            'tenant_users' => User::whereNotNull('tenant_id')->count(),
        ];

        $recentTenants = Tenant::latest()->limit(5)->get();

        return view('dashboards.superadmin', [
            'stats' => $stats,
            'recentTenants' => $recentTenants,
            'user' => $request->user(),
        ]);
    }

    public function tenant(Request $request): View
    {
        $user = $request->user();
        $tenant = $user->tenant;

        $applications = $tenant
            ? $tenant->tenantApplications()->with('application')->where('is_active', true)->get()
            : collect();

        return view('dashboards.tenant', [
            'user' => $user,
            'tenant' => $tenant,
            'applications' => $applications,
        ]);
    }
}
