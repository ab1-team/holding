<?php

namespace App\Services;

use App\Models\ReportCache;
use App\Models\TenantApplication;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubsidiaryReportService
{
    /**
     * TTL cache (menit). Default 30 menit sesuai plan.
     */
    public const CACHE_TTL_MINUTES = 30;

    /**
     * HTTP timeout (detik) ke subsidiary.
     */
    public const HTTP_TIMEOUT_SECONDS = 15;

    /**
     * Retry attempts saat subsidiary gagal.
     */
    public const HTTP_RETRY_TIMES = 2;

    /**
     * Endpoint map per report type (sesuai API Contract v2 di .guide).
     *
     * @var array<string, string>
     */
    public const ENDPOINTS = [
        'neraca' => '/api/v1/holding/laporan/neraca',
        'laba_rugi' => '/api/v1/holding/laporan/laba-rugi',
        'arus_kas' => '/api/v1/holding/laporan/arus-kas',
        'perubahan_ekuitas' => '/api/v1/holding/laporan/perubahan-ekuitas',
        'calk' => '/api/v1/holding/laporan/calk',
    ];

    /**
     * Valid report types.
     *
     * @return array<int, string>
     */
    public static function reportTypes(): array
    {
        return array_keys(self::ENDPOINTS);
    }

    /**
     * Label Indonesian untuk UI.
     *
     * @return array<string, string>
     */
    public static function reportTypeLabels(): array
    {
        return [
            'neraca' => 'Neraca',
            'laba_rugi' => 'Laba Rugi',
            'arus_kas' => 'Arus Kas',
            'perubahan_ekuitas' => 'Perubahan Ekuitas',
            'calk' => 'Catatan (CALK)',
        ];
    }

    /**
     * Build query string untuk endpoint report.
     * Format: ?tahun=YYYY&bulan=MM[&hari=DD][&semester=1|2]
     */
    public static function buildQuery(string $period, ?int $hari = null, ?int $semester = null): string
    {
        [$year, $month] = array_pad(explode('-', $period, 2), 2, null);
        $params = [
            'tahun' => $year,
            'bulan' => $month,
        ];
        if ($hari !== null) {
            $params['hari'] = (string) $hari;
        }
        if ($semester !== null) {
            $params['semester'] = (string) $semester;
        }
        return http_build_query($params);
    }

    /**
     * Fetch report dari subsidiary (HTTP). Tidak pakai cache.
     *
     * @return array{status:string,period:string,generated_at:?string,data:array<int,array<string,mixed>>}|null
     */
    public function fetchLive(TenantApplication $license, string $reportType, string $period): ?array
    {
        if (! isset(self::ENDPOINTS[$reportType])) {
            throw new \InvalidArgumentException("Tipe laporan tidak dikenal: {$reportType}");
        }

        $url = rtrim($license->instance_url, '/') . self::ENDPOINTS[$reportType] . '?' . self::buildQuery($period);

        try {
            $response = Http::timeout(self::HTTP_TIMEOUT_SECONDS)
                ->retry(self::HTTP_RETRY_TIMES, 250, throw: false)
                ->withHeaders([
                    'X-Holding-Token' => $license->api_secret,
                    'X-Holding-Tenant' => $license->tenant->getHoldingTenantId(),
                    'Accept' => 'application/json',
                ])
                ->get($url);
        } catch (ConnectionException $e) {
            Log::warning('Subsidiary unreachable', [
                'license_id' => $license->id,
                'report_type' => $reportType,
                'period' => $period,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        if (! $response->successful()) {
            Log::warning('Subsidiary returned non-2xx', [
                'license_id' => $license->id,
                'status' => $response->status(),
            ]);
            return null;
        }

        $body = $response->json();
        if (! is_array($body) || ($body['status'] ?? null) !== 'success' || ! is_array($body['data'] ?? null)) {
            Log::warning('Subsidiary returned malformed payload', [
                'license_id' => $license->id,
            ]);
            return null;
        }

        return [
            'status' => 'success',
            'period' => (string) ($body['period'] ?? $period),
            'generated_at' => $body['generated_at'] ?? null,
            'data' => array_values(array_map(fn ($row) => [
                'account_code' => (string) ($row['account_code'] ?? ''),
                'account_name' => (string) ($row['account_name'] ?? ''),
                'amount' => is_numeric($row['amount'] ?? null) ? (int) $row['amount'] : 0,
                'notes' => $row['notes'] ?? null,
            ], $body['data'])),
        ];
    }

    /**
     * Ambil report dengan cache (read-through). Jika cache masih valid → pakai cache.
     * Jika expired / tidak ada → fetch dari subsidiary, simpan ke cache, return.
     *
     * @return array{status:string,period:string,generated_at:?string,data:array<int,array<string,mixed>>}|null
     */
    public function get(TenantApplication $license, string $reportType, string $period): ?array
    {
        $cache = ReportCache::where('tenant_application_id', $license->id)
            ->where('report_type', $reportType)
            ->where('period', $period)
            ->first();

        if ($cache && ! $cache->isExpired()) {
            return $cache->payload;
        }

        $payload = $this->fetchLive($license, $reportType, $period);
        if ($payload === null) {
            // fallback ke cache basi (lebih baik daripada kosong total)
            return $cache?->payload;
        }

        ReportCache::updateOrCreate(
            [
                'tenant_application_id' => $license->id,
                'report_type' => $reportType,
                'period' => $period,
            ],
            [
                'payload' => $payload,
                'fetched_at' => now(),
                'expires_at' => now()->addMinutes(self::CACHE_TTL_MINUTES),
            ],
        );

        return $payload;
    }

    /**
     * Gabungkan payload banyak license jadi satu struktur komparatif.
     * Baris digabung pakai composite key (account_code + account_name).
     *
     * Input: ['license_id' => payload|null, ...]
     * Output: rows + per-license amount map + totals per app.
     *
     * @param  array<int, TenantApplication>  $licenses
     * @param  array<int, array{status:string,period:string,generated_at:?string,data:array<int,array<string,mixed>>}|null>  $payloads  Indexed by license.id
     * @return array{rows:array<int,array{account_code:string,account_name:string,amounts:array<int,int>,notes:?string}>,columns:array<int,array{license_id:int,label:string,application:string,total:int}>}
     */
    public function mergeComparative(array $licenses, array $payloads, string $reportType, string $period): array
    {
        $columns = [];
        $byKey = [];

        foreach ($licenses as $license) {
            $payload = $payloads[$license->id] ?? null;
            $total = 0;
            $rows = $payload['data'] ?? [];

            foreach ($rows as $row) {
                $key = $this->rowKey($row['account_code'] ?? '', $row['account_name'] ?? '');
                $byKey[$key] ??= [
                    'account_code' => (string) ($row['account_code'] ?? ''),
                    'account_name' => (string) ($row['account_name'] ?? ''),
                    'notes' => $row['notes'] ?? null,
                    'amounts' => [],
                ];
                $byKey[$key]['amounts'][$license->id] = (int) ($row['amount'] ?? 0);
                $byKey[$key]['notes'] ??= $row['notes'] ?? null;
                $total += (int) ($row['amount'] ?? 0);
            }

            $columns[] = [
                'license_id' => $license->id,
                'label' => $license->label ?: $license->application->name,
                'application' => $license->application->name,
                'instance_url' => $license->instance_url,
                'total' => $total,
                'available' => $payload !== null,
            ];
        }

        // sort by account_code asc, then by account_name asc; empty codes last
        uasort($byKey, function ($a, $b) {
            $ac = $a['account_code'];
            $bc = $b['account_code'];
            if ($ac === '' && $bc !== '') return 1;
            if ($bc === '' && $ac !== '') return -1;
            $cmp = strcmp($ac, $bc);
            if ($cmp !== 0) return $cmp;
            return strcmp($a['account_name'], $b['account_name']);
        });

        return [
            'rows' => array_values($byKey),
            'columns' => $columns,
            'report_type' => $reportType,
            'period' => $period,
        ];
    }

    /**
     * Composite key untuk matching baris laporan.
     */
    public function rowKey(string $accountCode, string $accountName): string
    {
        return $accountCode . '||' . $accountName;
    }
}
