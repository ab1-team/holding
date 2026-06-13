<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        $currentTenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        $isVendorPanel = (bool) request()->attributes->get('is_vendor_panel');

        return view('auth.login', [
            'currentTenant' => $currentTenant,
            'isVendorPanel' => $isVendorPanel,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! \Illuminate\Support\Facades\Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.',
            ]);
        }

        // Subdomain guard — strict: tenant user hanya di subdomain tenant-nya.
        $currentTenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        $isVendorPanel = (bool) $request->attributes->get('is_vendor_panel');
        $baseDomain = config('app.domain_base');

        if ($user->isSuperadmin() && ! $isVendorPanel) {
            throw ValidationException::withMessages([
                'email' => "Akun vendor hanya dapat login di admin.{$baseDomain}.",
            ]);
        }

        if (! $user->isSuperadmin()) {
            // tenant_owner / tenant_staff harus di subdomain tenant-nya
            if ($isVendorPanel || $currentTenant === null || $currentTenant->id !== $user->tenant_id) {
                $correctHost = $user->tenant
                    ? $user->tenant->getSubdomainUrl()
                    : null;
                $msg = $correctHost
                    ? "Akun ini milik tenant lain. Silakan login di {$correctHost}."
                    : 'Akun Anda belum terikat ke tenant manapun. Hubungi administrator.';
                throw ValidationException::withMessages(['email' => $msg]);
            }
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        ActivityLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'action' => 'login',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'metadata' => [
                'remember' => $request->boolean('remember'),
                'subdomain' => $isVendorPanel ? 'admin' : ($currentTenant?->slug),
            ],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        $request->session()->regenerateToken();

        return redirect()->intended($this->redirectPathFor($user));
    }

    private function redirectPathFor($user): string
    {
        return match ($user->role) {
            'superadmin' => route('admin.dashboard'),
            'tenant_owner', 'tenant_staff' => route('tenant.home'),
            default => route('login'),
        };
    }
}
