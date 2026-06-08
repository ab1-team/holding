<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = ActivityLog::with(['user', 'tenant'])->latest('created_at');

        if ($tenantId = $request->query('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        $logs = $query->paginate(30)->withQueryString();

        $tenants = Tenant::orderBy('name')->get(['id', 'name']);
        $actions = ActivityLog::distinct()->pluck('action');

        return view('admin.activity_logs.index', [
            'logs' => $logs,
            'tenants' => $tenants,
            'actions' => $actions,
            'filters' => [
                'tenant_id' => $tenantId,
                'action' => $action,
                'user_id' => $userId,
            ],
        ]);
    }

    public function show(ActivityLog $log): View
    {
        $log->load(['user', 'tenant']);

        return view('admin.activity_logs.show', [
            'log' => $log,
        ]);
    }
}
