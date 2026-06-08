<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\TenantApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantAccessController extends Controller
{
    public function redirect(Request $request, TenantApplication $license): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user->isSuperadmin() || $license->tenant_id === $user->tenant_id,
            403,
            'Anda tidak memiliki akses ke aplikasi ini.'
        );

        abort_unless($license->is_active, 403, 'Lisensi aplikasi ini sedang nonaktif.');
        abort_if($license->isExpired(), 403, 'Lisensi aplikasi ini sudah kedaluwarsa.');

        ActivityLog::create([
            'tenant_id' => $license->tenant_id,
            'user_id' => $user->id,
            'action' => 'access_app',
            'subject_type' => TenantApplication::class,
            'subject_id' => $license->id,
            'metadata' => [
                'application' => $license->application->name,
                'instance_url' => $license->instance_url,
            ],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return redirect()->away($license->instance_url);
    }
}
