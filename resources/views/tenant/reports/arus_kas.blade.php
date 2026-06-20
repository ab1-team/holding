@extends('layouts.app')

@section('title', "{$reportLabel} — Arus Kas Komparatif")

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
@php
    $firstPayload = null;
    foreach ($licensesList as $lic) {
        if (($payloads[$lic->id]['status'] ?? null) === 'success') { $firstPayload = $payloads[$lic->id]; break; }
    }
    $rows = $firstPayload['data'] ?? [];
@endphp
<x-ui.card>
    <div class="overflow-x-auto -mx-5 sm:-mx-6">
        <table class="min-w-full divide-y divide-outline-variant">
            <thead class="bg-surface-container">
                <tr>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6">Aktivitas</th>
                    <th class="px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Rincian</th>
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
            </thead>
            <tbody class="divide-y divide-outline-variant bg-surface-container-lowest">
                @forelse($rows as $ak)
                    @php
                        $isSaldoAwal = (int) ($ak['id'] ?? 0) === 1;
                        $detail = $ak['detail'] ?? [];
                    @endphp
                    @if($isSaldoAwal)
                        <tr class="bg-surface-container-high/40 font-bold">
                            <td class="px-5 py-2 text-[11px] font-bold uppercase tracking-wider text-on-surface sm:px-6">{{ $ak['nama'] ?? 'Saldo Awal' }}</td>
                            <td class="px-3 py-2 text-xs text-on-surface-variant">—</td>
                            @foreach($licensesList as $lic)
                                @php
                                    $val = null;
                                    foreach (($payloads[$lic->id]['data'] ?? []) as $r) {
                                        if ((int) ($r['id'] ?? 0) === 1) { $val = $r['saldo'] ?? null; break; }
                                    }
                                @endphp
                                <td class="px-3 py-2 text-right text-sm font-mono font-semibold text-on-surface whitespace-nowrap">
                                    {{ $val === null ? '—' : 'Rp ' . number_format((float) $val, 2, ',', '.') }}
                                </td>
                            @endforeach
                        </tr>
                    @else
                        <tr class="bg-surface-container/60 font-semibold">
                            <td class="px-5 py-2 text-[11px] font-bold uppercase tracking-wider text-on-surface sm:px-6">{{ $ak['nama'] ?? '' }}</td>
                            <td class="px-3 py-2 text-xs text-on-surface-variant">Subtotal grup</td>
                            @foreach($licensesList as $lic)
                                @php
                                    $val = null;
                                    foreach (($payloads[$lic->id]['data'] ?? []) as $r) {
                                        if (($r['id'] ?? null) === ($ak['id'] ?? null)) { $val = $r['saldo'] ?? null; break; }
                                    }
                                @endphp
                                <td class="px-3 py-2 text-right text-sm font-mono font-semibold text-on-surface whitespace-nowrap">
                                    {{ $val === null ? '—' : 'Rp ' . number_format((float) $val, 2, ',', '.') }}
                                </td>
                            @endforeach
                        </tr>
                        @foreach($detail as $child)
                            <tr>
                                <td class="px-5 py-1.5 text-xs text-on-surface-variant sm:px-6 pl-10">{{ $child['kode_akun'] ?? '' }}</td>
                                <td class="px-3 py-1.5 text-xs text-on-surface sm:px-6 pl-10">{{ $child['nama_akun'] ?? '' }}</td>
                                @foreach($licensesList as $lic)
                                    @php
                                        $val = null;
                                        foreach (($payloads[$lic->id]['data'] ?? []) as $r) {
                                            if (($r['id'] ?? null) === ($ak['id'] ?? null)) {
                                                foreach (($r['detail'] ?? []) as $c) {
                                                    if (($c['id'] ?? null) === ($child['id'] ?? null)) { $val = $c['saldo'] ?? null; break 2; }
                                                }
                                            }
                                        }
                                    @endphp
                                    <td class="px-3 py-1.5 text-right text-xs font-mono whitespace-nowrap text-on-surface-variant">
                                        {{ $val === null ? '—' : number_format((float) $val, 2, ',', '.') }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endif
                @empty
                    <tr><td colspan="{{ 2 + $licensesList->count() }}" class="px-5 py-8 text-center text-sm text-on-surface-variant sm:px-6">Tidak ada data untuk periode ini.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-surface-container">
                @php
                    $summaries = [
                        'kas_operasi' => 'Kas Bersih Aktivitas Operasi',
                        'kas_investasi' => 'Kas Bersih Aktivitas Investasi',
                        'kas_pendanaan' => 'Kas Bersih Aktivitas Pendanaan',
                        'kenaikan_penurunan' => 'Kenaikan (Penurunan) Kas',
                        'saldo_akhir' => 'Saldo Akhir Kas',
                    ];
                @endphp
                @foreach($summaries as $key => $label)
                    <tr class="border-t-2 border-outline font-semibold">
                        <td colspan="2" class="px-5 py-2 text-[11px] font-bold uppercase tracking-wider text-on-surface sm:px-6">{{ $label }}</td>
                        @foreach($licensesList as $lic)
                            @php $val = $payloads[$lic->id]['ringkasan'][$key] ?? null; @endphp
                            <td class="px-3 py-2 text-right text-sm font-mono font-bold text-on-surface whitespace-nowrap">
                                {{ $val === null ? '—' : 'Rp ' . number_format((float) $val, 2, ',', '.') }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tfoot>
        </table>
    </div>
</x-ui.card>
@endif
@endsection
