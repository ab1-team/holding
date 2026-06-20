<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Services\SubsidiaryReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantApplicationController extends Controller
{
    public function __construct(private readonly SubsidiaryReportService $service)
    {
    }

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

    /**
     * Test koneksi dari holding ke subsidiary: hit endpoint laporan dengan token saat ini,
     * return JSON diagnostic (status, http code, latency, URL, header masked).
     *
     * Superadmin only. Tidak boleh di-spam (saran future: rate limit per license).
     */
    public function testConnection(Request $request, Tenant $tenant, TenantApplication $license): JsonResponse
    {
        abort_unless($license->tenant_id === $tenant->id, 404);

        $startedAt = microtime(true);
        $result = $this->service->fetchLive($license, 'neraca', now()->format('Y-m'));
        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

        ActivityLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'action' => 'test_license_connection',
            'subject_type' => TenantApplication::class,
            'subject_id' => $license->id,
            'metadata' => [
                'application' => $license->application->name,
                'result' => $result['status'] ?? 'error',
                'reason' => $result['reason'] ?? null,
                'http_code' => $result['http_code'] ?? null,
                'latency_ms' => $latencyMs,
            ],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        $secret = $license->api_secret;
        $maskedSecret = strlen($secret) >= 12
            ? substr($secret, 0, 8) . '...' . substr($secret, -4)
            : str_repeat('*', strlen($secret));

        return response()->json([
            'status' => $result['status'] ?? 'error',
            'reason' => $result['status'] === 'success' ? 'ok' : ($result['reason'] ?? 'unknown'),
            'http_code' => $result['status'] === 'success' ? 200 : ($result['http_code'] ?? null),
            'message' => $result['message'] ?? ($result['status'] === 'success' ? 'Subsidiary merespons dengan baik.' : 'Tidak diketahui.'),
            'latency_ms' => $latencyMs,
            'url' => rtrim($license->instance_url, '/') . SubsidiaryReportService::ENDPOINTS['neraca'] . '?' . SubsidiaryReportService::buildQuery(now()->format('Y-m')),
            'sent_headers' => [
                'X-Holding-Token' => $maskedSecret,
                'X-Holding-Tenant' => $this->service->resolveTenantHeader($license),
            ],
        ]);
    }

    /**
     * Hit endpoint subsidiary langsung (tanpa adapter/cache) dan return raw response
     * untuk debugging. TIDAK untuk production — expose token + struktur internal subsidiary.
     *
     * Pakai: verifikasi "apakah subsidiary beneran punya data untuk periode X?".
     * Output: http_code, latency, raw body (parsed), headers.
     */
    public function debugFetch(Request $request, Tenant $tenant, TenantApplication $license, string $type, string $period): JsonResponse
    {
        abort_unless($license->tenant_id === $tenant->id, 404);
        abort_unless(
            in_array($type, SubsidiaryReportService::reportTypes(), true),
            400,
            'Tipe laporan tidak dikenal.'
        );
        abort_unless(
            preg_match('/^\d{4}-(0[1-9]|1[0-2])(-(0[1-9]|[12]\d|3[01]))?$/', $period),
            400,
            'Format periode: YYYY-MM atau YYYY-MM-DD.'
        );

        $url = rtrim($license->instance_url, '/')
            . SubsidiaryReportService::ENDPOINTS[$type]
            . '?' . SubsidiaryReportService::buildQuery($period);

        $startedAt = microtime(true);
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Holding-Token' => $license->api_secret,
                    'X-Holding-Tenant' => $this->service->resolveTenantHeader($license),
                    'Accept' => 'application/json',
                ])
                ->get($url);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'ok' => false,
                'reason' => 'unreachable',
                'message' => $e->getMessage(),
                'url' => $url,
            ]);
        }
        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

        $body = $response->json();
        $isArray = is_array($body);
        $dataCount = $isArray ? (is_array($body['data'] ?? null) ? count($body['data']) : null) : null;

        return response()->json([
            'ok' => $response->successful(),
            'http_code' => $response->status(),
            'latency_ms' => $latencyMs,
            'url' => $url,
            'response_top_level_keys' => $isArray ? array_keys($body) : null,
            'is_success_field' => $isArray ? ($body['success'] ?? null) : null,
            'is_status_field' => $isArray ? ($body['status'] ?? null) : null,
            'tgl_kondisi' => $isArray ? ($body['tgl_kondisi'] ?? null) : null,
            'data_type' => $isArray ? gettype($body['data'] ?? null) : null,
            'data_count' => $dataCount,
            'data_first_row_keys' => $isArray && is_array($body['data'] ?? null) && isset($body['data'][0]) && is_array($body['data'][0])
                ? array_keys($body['data'][0])
                : null,
            'raw_body_excerpt' => $isArray ? $this->truncateForDebug($body) : null,
        ]);
    }

    private function truncateForDebug(array $body, int $maxKeys = 2, int $maxDepth = 3, int $depth = 0): array
    {
        if ($depth >= $maxDepth) {
            return ['__truncated__' => '...'];
        }
        $out = [];
        $keys = array_slice(array_keys($body), 0, $maxKeys);
        foreach ($keys as $k) {
            $v = $body[$k];
            if (is_array($v)) {
                $out[$k] = $this->truncateForDebug($v, $maxKeys, $maxDepth, $depth + 1);
            } else {
                $out[$k] = is_string($v) && strlen($v) > 200 ? substr($v, 0, 200) . '...' : $v;
            }
        }
        return $out;
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
