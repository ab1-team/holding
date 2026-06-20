@extends('layouts.app')

@section('title', "{$reportLabel} — Neraca Komparatif")

@section('content')
@php
    $licensesList = $licenses ?? collect();
    $hasLicenses = $licensesList->isNotEmpty();
    $availableCount = 0;
    foreach ($licensesList as $lic) {
        $pl = $payloads[$lic->id] ?? null;
        if (is_array($pl) && ($pl['status'] ?? null) === 'success') {
            $availableCount++;
        }
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
    <x-ui.card><x-ui.empty-state icon="chart-bar" title="Tidak ada aplikasi dengan fitur laporan" description="Pilih minimal satu aplikasi di filter, atau hubungi vendor untuk mengaktifkan fitur laporan." /></x-ui.card>
@else
@php
    $unavailableLicenses = $licensesList->filter(fn ($l) => ($payloads[$l->id]['status'] ?? null) !== 'success');
@endphp
@if($unavailableLicenses->isNotEmpty())
    <x-ui.alert variant="error" icon="exclamation" title="Sebagian aplikasi tidak merespons" class="mb-3">
        <ul class="mt-1.5 list-disc space-y-0.5 pl-5 text-xs">
            @foreach($unavailableLicenses as $lic)
                <li><span class="font-semibold">{{ $lic->label ?: $lic->application->name }}</span> — {{ $payloads[$lic->id]['message'] ?? 'tidak diketahui' }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif

<x-ui.card>
    <div class="overflow-x-auto -mx-5 sm:-mx-6">
        <table class="min-w-full divide-y divide-outline-variant">
            <thead class="bg-surface-container">
                <tr>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6 w-1/3">Rekening</th>
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
                @php
                    // Use first available payload for the hierarchy structure (accounts are same across licenses).
                    $hierarchyPayload = null;
                    foreach ($licensesList as $lic) {
                        if (($payloads[$lic->id]['status'] ?? null) === 'success') {
                            $hierarchyPayload = $payloads[$lic->id];
                            break;
                        }
                    }
                    $rows = is_array($hierarchyPayload['data'] ?? null) ? $hierarchyPayload['data'] : [];
                @endphp
                @forelse($rows as $lev1)
                    @php $lev1Code = $lev1['kode_akun'] ?? ''; @endphp
                    <tr class="bg-surface-container-high/40">
                        <td colspan="{{ 1 + $licensesList->count() }}" class="px-5 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-on-surface sm:px-6">{{ $lev1Code }}. {{ $lev1['nama_akun'] ?? '' }}</td>
                    </tr>
                    @foreach(($lev1['akun2'] ?? []) as $lev2)
                        <tr class="bg-surface-container/60">
                            <td colspan="{{ 1 + $licensesList->count() }}" class="px-5 py-1.5 text-[11px] font-semibold text-on-surface sm:px-6 pl-10">{{ $lev2['kode_akun'] ?? '' }}. {{ $lev2['nama_akun'] ?? '' }}</td>
                        </tr>
                        @foreach(($lev2['akun3'] ?? []) as $lev3)
                            <tr class="hover:bg-surface-container transition">
                                <td class="px-5 py-2 text-sm text-on-surface sm:px-6 pl-16">{{ $lev3['kode_akun'] ?? '' }}. {{ $lev3['nama_akun'] ?? '' }}</td>
                                @foreach($licensesList as $lic)
                                    @php
                                        $licLev1 = $payloads[$lic->id]['data'] ?? [];
                                        $licLev3 = null;
                                        foreach ($licLev1 as $l1) {
                                            foreach (($l1['akun2'] ?? []) as $l2) {
                                                foreach (($l2['akun3'] ?? []) as $l3) {
                                                    if (($l3['kode_akun'] ?? null) === ($lev3['kode_akun'] ?? null) && ($l3['nama_akun'] ?? null) === ($lev3['nama_akun'] ?? null)) {
                                                        $licLev3 = $l3; break 3;
                                                    }
                                                }
                                            }
                                        }
                                        $amount = $licLev3['saldo'] ?? null;
                                    @endphp
                                    <td class="px-3 py-2 text-right text-sm font-mono whitespace-nowrap {{ $amount === null ? 'text-on-surface-variant' : 'text-on-surface' }}">
                                        {{ $amount === null ? '—' : 'Rp ' . number_format((float) $amount, 2, ',', '.') }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                    <tr class="border-t-2 border-outline-variant bg-surface-container/80 font-semibold">
                        <td class="px-5 py-2 text-[11px] font-bold uppercase tracking-wider text-on-surface sm:px-6 pl-10">Jumlah {{ $lev1['nama_akun'] ?? '' }}</td>
                        @foreach($licensesList as $lic)
                            @php
                                $licLev1 = null;
                                foreach (($payloads[$lic->id]['data'] ?? []) as $l1) {
                                    if (($l1['kode_akun'] ?? null) === $lev1Code) { $licLev1 = $l1; break; }
                                }
                                $sum = $licLev1['saldo'] ?? null;
                            @endphp
                            <td class="px-3 py-2 text-right text-sm font-mono font-semibold text-on-surface whitespace-nowrap">
                                {{ $sum === null ? '—' : 'Rp ' . number_format((float) $sum, 2, ',', '.') }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ 1 + $licensesList->count() }}" class="px-5 py-8 text-center text-sm text-on-surface-variant sm:px-6">Tidak ada data untuk periode ini.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-surface-container">
                @php
                    $ringkasanPayload = $hierarchyPayload['ringkasan'] ?? null;
                @endphp
                <tr class="border-t-4 border-outline">
                    <td class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-on-surface sm:px-6">Jumlah Liabilitas + Ekuitas</td>
                    @foreach($licensesList as $lic)
                        @php
                            $total = $payloads[$lic->id]['ringkasan']['total_liabilitas_ekuitas'] ?? null;
                        @endphp
                        <td class="px-3 py-3 text-right text-sm font-mono font-bold text-on-surface whitespace-nowrap">
                            {{ $total === null ? '—' : 'Rp ' . number_format((float) $total, 2, ',', '.') }}
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        </table>
    </div>
</x-ui.card>
@endif
@endsection
