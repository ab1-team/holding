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
     * Tampilkan laporan komparatif.
     */
    public function show(Request $request, string $type): View
    {
        $context = $this->buildReportContext($request, $type);

        return view('tenant.reports.show', $context);
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

        $comparative = $context['comparative'];
        $columns = $comparative['columns'];
        $rows = $comparative['rows'];

        $callback = function () use ($columns, $rows) {
            $out = fopen('php://output', 'w');
            // BOM agar Excel detect UTF-8
            fwrite($out, "\xEF\xBB\xBF");

            $header = array_merge(['Kode', 'Nama Akun'], array_map(fn ($c) => $c['label'], $columns));
            fputcsv($out, $header, ';');

            foreach ($rows as $row) {
                $line = [
                    $row['account_code'],
                    $row['account_name'],
                ];
                foreach ($columns as $col) {
                    $amount = $row['amounts'][$col['license_id']] ?? null;
                    $line[] = $amount === null ? '' : $amount;
                }
                fputcsv($out, $line, ';');
            }

            // Total row
            $totalLine = ['Total', ''];
            foreach ($columns as $col) {
                $totalLine[] = $col['total'];
            }
            fputcsv($out, $totalLine, ';');

            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export PDF (dompdf).
     */
    public function exportPdf(Request $request, string $type)
    {
        $context = $this->buildReportContext($request, $type, log: 'export_report_pdf');

        $filename = sprintf(
            'laporan-%s-%s-%s.pdf',
            $type,
            $context['period'],
            now()->format('Ymd-His')
        );

        $pdf = Pdf::loadView('tenant.reports.pdf', $context)
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
     * Bangun konteks laporan: licenses, payloads, comparative + log view/export.
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

        $comparative = $this->service->mergeComparative($licenses->all(), $payloads, $type, $period);

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
            'comparative' => $comparative,
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
