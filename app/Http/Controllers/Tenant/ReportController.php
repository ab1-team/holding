<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\TenantApplication;
use App\Services\SubsidiaryReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly SubsidiaryReportService $service)
    {
    }

    /**
     * Index: pilih tipe laporan + periode.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $tenant = $user->tenant;
        $apps = $tenant
            ? $tenant->tenantApplications()
                ->with('application')
                ->where('is_active', true)
                ->get()
            : collect();

        return view('tenant.reports.index', [
            'apps' => $apps,
            'reportTypes' => $this->service->reportTypeLabels(),
        ]);
    }

    /**
     * Tampilkan laporan per-buku (struktur asli subsidiary).
     */
    public function show(Request $request, string $type): View
    {
        $context = $this->buildReportContext($request, $type);

        return view("tenant.reports.{$type}", $context);
    }

    /**
     * Export CSV (Excel-compatible via UTF-8 BOM).
     */
    public function exportCsv(Request $request, string $type)
    {
        $context = $this->buildReportContext($request, $type, log: 'export_report_csv');

        $filename = sprintf(
            'laporan-%s-%s-%s.csv',
            $type,
            $context['period'],
            now()->format('Ymd-His')
        );

        $licenses = $context['licenses'];
        $payloads = $context['payloads'];

        $callback = function () use ($licenses, $payloads, $type) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            $header = ['Kode', 'Nama Akun'];
            foreach ($licenses as $license) {
                $header[] = $license->label ?: $license->application->name;
            }
            fputcsv($out, $header, ';');

            // Flatten rows per license, indexed by composite key, output per line.
            // (Per-buku structure: pass through all leaf nodes with kode_akun+saldo)
            $byKey = [];
            foreach ($licenses as $license) {
                $payload = $payloads[$license->id] ?? null;
                if (! is_array($payload) || ($payload['status'] ?? null) !== 'success') {
                    continue;
                }
                $this->collectRowsForCsv($payload['data'] ?? [], $byKey, $license->id, $type);
            }

            foreach ($byKey as $row) {
                $line = [$row['account_code'], $row['account_name']];
                foreach ($licenses as $license) {
                    $amount = $row['amounts'][$license->id] ?? null;
                    $line[] = $amount === null ? '' : $amount;
                }
                fputcsv($out, $line, ';');
            }

            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Recursively collect leaf rows (with kode_akun + saldo) into $byKey.
     */
    private function collectRowsForCsv(array $node, array &$byKey, int $licenseId, string $type, string $section = ''): void
    {
        if (! is_array($node)) {
            return;
        }

        $isAssoc = ! array_is_list($node);

        if ($isAssoc && isset($node['rincian_akun']) && is_array($node['rincian_akun'])) {
            $this->collectRowsForCsv($node['rincian_akun'], $byKey, $licenseId, $type, $section);
            return;
        }

        if ($isAssoc) {
            $sections = ['pendapatan', 'beban', 'pendapatan_non_ops', 'beban_non_ops'];
            $found = false;
            foreach ($sections as $sec) {
                if (isset($node[$sec]) && is_array($node[$sec])) {
                    $found = true;
                    $this->collectRowsForCsv($node[$sec], $byKey, $licenseId, $type, $sec);
                }
            }
            if ($found) {
                return;
            }
        }

        $elements = $isAssoc ? [$node] : $node;
        foreach ($elements as $element) {
            if (! is_array($element)) {
                continue;
            }
            // Support both subsidiary shape (kode_akun/nama_akun/saldo) and
            // normalized shape (account_code/account_name/amount).
            $kode = $element['kode_akun'] ?? $element['account_code'] ?? ($element['id'] ?? null);
            $nama = $element['nama_akun'] ?? $element['account_name'] ?? ($element['nama'] ?? '');
            if ($kode !== null) {
                $amount = (float) ($element['saldo'] ?? $element['amount'] ?? 0);
                $key = (string) $kode . '||' . (string) $nama;
                $byKey[$key] ??= [
                    'account_code' => (string) $kode,
                    'account_name' => (string) $nama,
                    'amounts' => [],
                ];
                $byKey[$key]['amounts'][$licenseId] = $amount;
            }
            foreach (['rekening', 'detail', 'akun2', 'akun3'] as $childKey) {
                if (isset($element[$childKey]) && is_array($element[$childKey])) {
                    $this->collectRowsForCsv($element[$childKey], $byKey, $licenseId, $type, $section);
                }
            }
        }
    }

    /**
     * Export PDF (dompdf).
     *
     * View mode:
     * - `?view=comparative` (default) — multi-license side-by-side, kolom per subsidiary
     * - `?view=total` — konsolidasi, 1 kolom total (sum dari semua subsidiary)
     */
    public function exportPdf(Request $request, string $type)
    {
        $context = $this->buildReportContext($request, $type, log: 'export_report_pdf');

        $view = $request->input('view', 'comparative');
        if (! in_array($view, ['comparative', 'total'], true)) {
            $view = 'comparative';
        }

        $filename = sprintf(
            'laporan-%s-%s-%s.pdf',
            $type,
            $view,
            $context['period'],
            now()->format('Ymd-His')
        );

        // Semua laporan landscape. Multi-license komparatif (license baru muncul di sisi kanan),
        // jadi tabel memanjang horizontal. Landscape lebih natural untuk komparatif.
        $pdf = Pdf::loadView("tenant.reports.{$type}-{$view}-pdf", $context)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
            ]);

        if ($request->boolean('inline')) {
            // Preview inline di tab baru (browser native viewer).
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Orientation PDF: semua landscape. Multi-license komparatif side-by-side,
     * jadi tabel memanjang horizontal — landscape lebih natural.
     */
    public static function pdfOrientation(string $type): string
    {
        return 'landscape';
    }

    /**
     * Bangun konteks laporan: licenses, payloads, + log view/export.
     *
     * Setiap payload adalah data asli subsidiary (pass-through per HOLDING-API.md §2-4).
     * Tidak ada flatten di adapter — view per-buku render struktur asli.
     *
     * @return array<string, mixed>
     */
    private function buildReportContext(Request $request, string $type, ?string $log = 'view_report'): array
    {
        abort_unless(
            in_array($type, SubsidiaryReportService::reportTypes(), true),
            404,
            'Tipe laporan tidak dikenal.'
        );

        $user = $request->user();
        $tenant = $user->tenant;
        abort_unless($tenant, 403, 'Akun Anda belum terikat ke tenant.');

        $validated = $request->validate([
            'period' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'apps' => ['nullable', 'array'],
            'apps.*' => ['integer'],
        ]);

        $period = $validated['period'] ?? now()->format('Y-m');
        $selectedIds = $validated['apps'] ?? null;

        $query = $tenant->tenantApplications()
            ->with('application')
            ->where('is_active', true)
            ->whereHas('application', fn ($q) => $q->where('has_financial_report', true));

        if ($selectedIds !== null) {
            $query->whereIn('id', $selectedIds);
        }
        $licenses = $query->get()->filter(fn ($l) => $l->tenant_id === $tenant->id)->values();

        $payloads = [];
        foreach ($licenses as $license) {
            $payloads[$license->id] = $this->service->get($license, $type, $period);
        }

        if ($log) {
            ActivityLog::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'action' => $log,
                'subject_type' => 'Report',
                'subject_id' => null,
                'metadata' => [
                    'report_type' => $type,
                    'period' => $period,
                    'apps' => $licenses->pluck('id')->all(),
                ],
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        }

        return [
            'payloads' => $payloads,
            'reportType' => $type,
            'reportLabel' => SubsidiaryReportService::reportTypeLabels()[$type],
            'period' => $period,
            'tenant' => $tenant,
            'licenses' => $licenses,
            'apps' => $licenses,
            'selectedIds' => $licenses->pluck('id')->all(),
        ];
    }
}
