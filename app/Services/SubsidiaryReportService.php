<?php

namespace App\Services;

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
            // HOLDING-API.md §3: bulan int 1-12, no zero-pad (validator reject string "06").
            'bulan' => (int) $month,
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
     * SELALU return array:
     * - Success: `['status' => 'success', 'period' => ..., 'generated_at' => ..., 'data' => [...]]`
     * - Error:   `['status' => 'error', 'reason' => '...', 'http_code' => ..., 'message' => '...']`
     *
     * @return array{status:string,period?:string,generated_at?:?string,data?:array<int,array<string,mixed>>,reason?:string,http_code?:?int,message?:string}
     */
    public function fetchLive(TenantApplication $license, string $reportType, string $period): array
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
                    'X-Holding-Tenant' => $this->resolveTenantHeader($license),
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
            return [
                'status' => 'error',
                'reason' => 'unreachable',
                'http_code' => null,
                'message' => 'Tidak dapat menghubungi subsidiary: ' . $e->getMessage(),
            ];
        }

        if (! $response->successful()) {
            $httpCode = $response->status();
            $reason = match (true) {
                $httpCode === 401 || $httpCode === 403 => 'token_rejected',
                $httpCode === 422 => 'validation_error',
                $httpCode >= 500 => 'server_error',
                $httpCode >= 400 => 'forbidden',
                default => 'unknown',
            };
            Log::warning('Subsidiary returned non-2xx', [
                'license_id' => $license->id,
                'status' => $httpCode,
                'reason' => $reason,
            ]);
            return [
                'status' => 'error',
                'reason' => $reason,
                'http_code' => $httpCode,
                'message' => "Subsidiary menjawab HTTP {$httpCode}.",
            ];
        }

        $body = $response->json();
        if (! is_array($body)) {
            Log::warning('Subsidiary returned non-JSON response', [
                'license_id' => $license->id,
            ]);
            return [
                'status' => 'error',
                'reason' => 'malformed',
                'http_code' => $response->status(),
                'message' => 'Respons subsidiary bukan JSON valid.',
            ];
        }

        // Adapt: subsidiary bisa return 2 format:
        // 1. HOLDING-API.md: `{success: true, data: [...]}` (dengan `success` boolean)
        // 2. Expected shape: `{status: 'success', data: [...]}` (dengan `status` string)
        $adapted = self::adaptSubsidiaryPayload($body, $reportType, $period);

        if (($adapted['status'] ?? null) === 'error') {
            Log::warning('Subsidiary adaptasi gagal', [
                'license_id' => $license->id,
                'report_type' => $reportType,
                'reason' => $adapted['reason'] ?? 'unknown',
            ]);
            return [
                'status' => 'error',
                'reason' => $adapted['reason'] ?? 'malformed',
                'http_code' => $response->status(),
                'message' => $adapted['message'] ?? 'Respons subsidiary tidak sesuai format.',
            ];
        }

        return $adapted;
    }

    /**
     * Ambil report langsung dari subsidiary (tanpa cache).
     *
     * @return array{status:string,...}
     */
    public function get(TenantApplication $license, string $reportType, string $period): array
    {
        // No cache. Selalu fetch live.
        // Rationale: cache bikin "April-Mei 0 baris" walaupun subsidiary sudah
        // punya data baru (cached empty success dengan TTL 30 menit). Untuk
        // konsistensi financial report, lebih baik selalu live. Latency ~1-2s
        // masih acceptable untuk single-tenant.
        return $this->fetchLive($license, $reportType, $period);
    }

    /**
     * Adaptasi response subsidiary ke format internal holding.
     *
     * Per HOLDING-API.md §2-4: pass-through payload apa adanya.
     * Tiap endpoint punya struktur sendiri (Neraca hierarki 3-level,
     * Laba Rugi object {pendapatan, beban, ...}, CALK object {point_a,
     * catatan, rincian_akun, ...}). Holding view render struktur asli
     * per buku — JANGAN flatten di adapter.
     *
     * Normalisasi minimum:
     * - status: 'success' | 'error' (boolean → string)
     * - period: YYYY-MM (derived dari tgl_kondisi)
     * - generated_at: ISO timestamp (derived dari tgl_kondisi)
     * - data: array apa adanya
     * - sub_judul, ringkasan, point_a, catatan, saldo_calk, penandatangan: pass-through
     */
    public static function adaptSubsidiaryPayload(array $body, string $reportType, string $fallbackPeriod): array
    {
        // Already-normalized shape (defensive).
        if (isset($body['status']) && $body['status'] === 'success' && array_key_exists('data', $body)) {
            return $body;
        }

        $success = (bool) ($body['success'] ?? false);
        if (! $success) {
            return [
                'status' => 'error',
                'reason' => 'malformed',
                'message' => 'Subsidiary response: success=false atau tidak ada data.',
            ];
        }

        // tgl_kondisi bisa di top-level (neraca) atau di periode.{tgl_kondisi} (laba_rugi, calk).
        $tglKondisi = (string) (
            $body['tgl_kondisi']
            ?? ($body['periode']['tgl_kondisi'] ?? '')
        );
        $period = $tglKondisi !== '' ? substr($tglKondisi, 0, 7) : $fallbackPeriod;
        $generatedAt = $tglKondisi !== '' ? $tglKondisi . 'T00:00:00Z' : null;

        // Pass-through data apa adanya — biar view render per-buku.
        return [
            'status' => 'success',
            'report_type' => $reportType,
            'period' => $period,
            'tgl_kondisi' => $tglKondisi,
            'generated_at' => $generatedAt,
            'laporan' => $body['laporan'] ?? null,
            'kecamatan' => $body['kecamatan'] ?? null,
            'sub_judul' => $body['sub_judul']
                ?? ($body['periode']['sub_judul'] ?? null),
            'tgl_mad' => $body['periode']['tgl_mad'] ?? ($body['tgl_mad'] ?? null),
            'data' => $body['data'] ?? null,
            'ringkasan' => $body['ringkasan'] ?? null,
            'point_a' => $body['point_a']
                ?? ($body['data']['point_a'] ?? null)
                ?? ($body['ringkasan']['point_a'] ?? null),
            'catatan' => $body['catatan'] ?? ($body['data']['catatan'] ?? null),
            'saldo_calk' => $body['saldo_calk'] ?? ($body['data']['saldo_calk'] ?? null),
            'penandatangan' => $body['penandatangan'] ?? ($body['data']['penandatangan'] ?? null),
        ];
    }

    /**
     * Resolve nilai X-Holding-Tenant yang dikirim ke subsidiary.
     *
     * Prioritas:
     * 1. Host dari `instance_url` (mis. `https://app.sidbm.net/` → `app.sidbm.net`).
     *    Reliable karena subsidiary validasi terhadap `web_kec` atau `web_alternatif`
     *    di DB mereka — biasanya match dengan host yang dipakai holding.
     * 2. Fallback ke `tenant->getHoldingTenantId()`.
     */
    public function resolveTenantHeader(TenantApplication $license): string
    {
        $host = parse_url($license->instance_url, PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            return $host;
        }

        return $license->tenant->getHoldingTenantId();
    }
}
