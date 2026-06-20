@extends('layouts.app')

@section('title', "{$reportLabel} — Perubahan Ekuitas Komparatif")

@section('content')
@php
    $licensesList = $licenses ?? collect();
    $availableCount = 0;
    foreach ($licensesList as $lic) {
        $pl = $payloads[$lic->id] ?? null;
        if (is_array($pl) && ($pl['status'] ?? null) === 'success') $availableCount++;
    }
    $unavailableCount = $licensesList->count() - $availableCount;
    $subtitle = 'Periode <strong class="font-semibold text-on-surface">' . e($period) . '</strong> &middot; ' . $availableCount . ' aplikasi tersedia'
        . ($unavailableCount > 0 ? ', ' . $unavailableCount . ' tidak dapat dihubungi' : '');
@endphp

<x-ui.breadcrumb :items="[
    ['label' => 'Laporan', 'href' => route('tenant.reports.index')],
    ['label' => $reportLabel],
]" class="mb-2" />

<x-ui.page-header :title="$reportLabel . ' Komparatif'" :subtitle="$subtitle" />

<x-ui.card class="mb-6">
    <form method="GET" action="{{ route('tenant.reports.show', ['type' => $reportType]) }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
        <div class="flex-1">
            <x-ui.input name="period" type="month" label="Periode" :value="$period" :max="now()->format('Y-m')" class="sm:max-w-[12rem]" />
        </div>
        <div class="flex-1">
            <label class="mb-1.5 block text-sm font-medium text-on-surface">Aplikasi</label>
            <div class="flex flex-wrap gap-2">
                @foreach($apps ?? $licenses as $app)
                <label class="inline-flex items-center gap-2 rounded-full border border-outline-variant bg-surface-container-lowest px-3 py-1.5 text-xs font-medium text-on-surface hover:bg-surface-container cursor-pointer">
                    <input type="checkbox" name="apps[]" value="{{ $app->id }}" checked class="h-3.5 w-3.5 rounded border-outline text-primary focus:ring-primary/30">
                    {{ $app->label ?: $app->application->name }}
                </label>
                @endforeach
            </div>
        </div>
        <x-ui.button type="submit" icon="funnel">Terapkan</x-ui.button>
    </form>
</x-ui.card>

@if($licensesList->isNotEmpty())
<div class="mb-3 flex flex-wrap items-center justify-end gap-2">
    <span class="mr-auto text-xs text-on-surface-variant">Ekspor:</span>
    <x-ui.button :href="route('tenant.reports.csv', ['type' => $reportType, 'period' => $period])" variant="outlined" size="sm" icon="download">Unduh CSV</x-ui.button>
    <x-ui.button :href="route('tenant.reports.pdf', ['type' => $reportType, 'period' => $period, 'view' => 'comparative'])" variant="outlined" size="sm" icon="download">PDF Komparatif</x-ui.button>
    <x-ui.button :href="route('tenant.reports.pdf', ['type' => $reportType, 'period' => $period, 'view' => 'total'])" size="sm" icon="download">PDF Total</x-ui.button>
</div>
@endif

@if($licensesList->isEmpty())
    <x-ui.card><x-ui.empty-state icon="chart-bar" title="Tidak ada aplikasi dengan fitur laporan" /></x-ui.card>
@else
<x-ui.card>
    @php
        $firstPayload = null;
        foreach ($licensesList as $lic) {
            if (($payloads[$lic->id]['status'] ?? null) === 'success') { $firstPayload = $payloads[$lic->id]; break; }
        }
        $rows = $firstPayload['data'] ?? [];
    @endphp
    <div class="overflow-x-auto -mx-5 sm:-mx-6">
        <table class="min-w-full divide-y divide-outline-variant">
            <thead class="bg-surface-container">
                <tr>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6">Kode</th>
                    <th class="px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Rekening Modal</th>
                    @foreach($licensesList as $lic)
                    <th class="px-3 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">
                        <div class="flex flex-col items-end gap-0.5">
                            <span>{{ $lic->label ?: $lic->application->name }}</span>
                            @if(($payloads[$lic->id]['status'] ?? null) !== 'success')
                                <x-ui.badge variant="error" icon="warning" size="sm">Offline</x-ui.badge>
                            @endif
                        </div>
                    </th>
                    @endforeach
                </tr>
                <tr class="bg-surface-container/60 text-[10px]">
                    <th colspan="2" class="px-5 py-1 text-right text-on-surface-variant sm:px-6">Format per kolom license:</th>
                    @foreach($licensesList as $lic)
                    <th class="px-3 py-1 text-right text-[10px] font-medium uppercase tracking-wider text-on-surface-variant">Saldo Awal / Mutasi / Saldo Akhir</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant bg-surface-container-lowest">
                @forelse($rows as $row)
                    <tr class="hover:bg-surface-container transition">
                        <td class="px-5 py-3 text-xs font-mono text-on-surface-variant sm:px-6 whitespace-nowrap">{{ $row['kode_akun'] ?? '' }}</td>
                        <td class="px-3 py-3 text-sm text-on-surface">{{ $row['nama_akun'] ?? '' }}</td>
                        @foreach($licensesList as $lic)
                            @php
                                $licRow = null;
                                foreach (($payloads[$lic->id]['data'] ?? []) as $r) {
                                    if (($r['kode_akun'] ?? null) === ($row['kode_akun'] ?? null) && ($r['nama_akun'] ?? null) === ($row['nama_akun'] ?? null)) { $licRow = $r; break; }
                                }
                            @endphp
                            <td class="px-3 py-3 text-right text-xs font-mono whitespace-nowrap text-on-surface">
                                @if($licRow === null) —
                                @else
                                    {{ number_format((float) ($licRow['saldo_awal'] ?? 0), 2, ',', '.') }}
                                    / {{ number_format((float) ($licRow['mutasi'] ?? 0), 2, ',', '.') }}
                                    / {{ number_format((float) ($licRow['saldo_akhir'] ?? 0), 2, ',', '.') }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ 2 + $licensesList->count() }}" class="px-5 py-8 text-center text-sm text-on-surface-variant sm:px-6">Tidak ada data untuk periode ini.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-surface-container">
                <tr class="border-t-2 border-outline font-semibold">
                    <td colspan="2" class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-on-surface sm:px-6">Total Ekuitas</td>
                    @foreach($licensesList as $lic)
                        @php
                            $r = $payloads[$lic->id]['ringkasan'] ?? [];
                            $saldoAwal = (float) ($r['ekuitas_awal'] ?? 0);
                            $saldoAkhir = (float) ($r['ekuitas_akhir'] ?? 0);
                            $mutasi = $saldoAkhir - $saldoAwal;
                        @endphp
                        <td class="px-3 py-3 text-right text-xs font-mono font-semibold text-on-surface whitespace-nowrap">
                            {{ number_format($saldoAwal, 2, ',', '.') }} / {{ number_format($mutasi, 2, ',', '.') }} / {{ number_format($saldoAkhir, 2, ',', '.') }}
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        </table>
    </div>
    <p class="mt-3 text-[10px] text-on-surface-variant">Format: <em>Saldo Awal / Mutasi / Saldo Akhir</em>. Saldo Akhir = <code>ringkasan.laba_rugi(tgl_kondisi)</code> untuk akun <code>3.2.02.01</code>.</p>
</x-ui.card>
@endif
@endsection
