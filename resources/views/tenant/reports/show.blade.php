@extends('layouts.app')

@section('title', "{$reportLabel} — Laporan Komparatif")

@section('content')
@php
    $rows = collect($comparative['rows']);
    $columns = $comparative['columns'];
    $availableCount = collect($columns)->where('available', true)->count();
    $unavailableCount = count($columns) - $availableCount;
    $subtitle = 'Periode <strong class="font-semibold text-on-surface">' . e($period) . '</strong> &middot; ' . $availableCount . ' aplikasi tersedia'
        . ($unavailableCount > 0 ? ', ' . $unavailableCount . ' tidak dapat dihubungi' : '');
@endphp

<x-ui.breadcrumb :items="[
    ['label' => 'Laporan', 'href' => route('tenant.reports.index')],
    ['label' => $reportLabel],
]" class="mb-2" />

<x-ui.page-header
    :title="$reportLabel . ' Komparatif'"
    :subtitle="$subtitle"
/>

<x-ui.card class="mb-6">
    <form method="GET" action="{{ route('tenant.reports.show', ['type' => $reportType]) }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
        <div class="flex-1">
            <x-ui.input
                name="period"
                type="month"
                label="Periode"
                :value="$period"
                :max="now()->format('Y-m')"
                class="sm:max-w-[12rem]"
            />
        </div>
        <div class="flex-1">
            <label class="mb-1.5 block text-sm font-medium text-on-surface">Aplikasi</label>
            <div class="flex flex-wrap gap-2">
                @foreach($apps ?? $licenses as $app)
                <label class="inline-flex items-center gap-2 rounded-full border border-outline-variant bg-surface-container-lowest px-3 py-1.5 text-xs font-medium text-on-surface hover:bg-surface-container cursor-pointer">
                    <input type="checkbox" name="apps[]" value="{{ $app->id }}" checked
                           class="h-3.5 w-3.5 rounded border-outline text-primary focus:ring-primary/30">
                    {{ $app->label ?: $app->application->name }}
                </label>
                @endforeach
            </div>
            <p class="mt-1 text-[11px] text-on-surface-variant">Centang aplikasi yang ingin dibandingkan.</p>
        </div>
        <x-ui.button type="submit" icon="funnel">Terapkan</x-ui.button>
    </form>
</x-ui.card>

@if($licenses->isNotEmpty())
<div class="mb-3 flex flex-wrap items-center justify-end gap-2">
    <span class="mr-auto text-xs text-on-surface-variant">Ekspor laporan:</span>
    <x-ui.button
        :href="route('tenant.reports.pdf', ['type' => $reportType, 'period' => $period, 'inline' => 1])"
        target="_blank"
        rel="noopener"
        variant="outlined"
        size="sm"
        icon="eye"
    >Preview</x-ui.button>
    <x-ui.button
        :href="route('tenant.reports.csv', ['type' => $reportType, 'period' => $period])"
        variant="outlined"
        size="sm"
        icon="download"
    >Unduh CSV</x-ui.button>
    <x-ui.button
        :href="route('tenant.reports.pdf', ['type' => $reportType, 'period' => $period])"
        size="sm"
        icon="download"
    >Unduh PDF</x-ui.button>
</div>
@endif

@if($licenses->isEmpty())
    <x-ui.card>
        <x-ui.empty-state
            icon="chart-bar"
            title="Tidak ada aplikasi dengan fitur laporan"
            description="Pilih minimal satu aplikasi di filter, atau hubungi vendor untuk mengaktifkan fitur laporan."
        />
    </x-ui.card>
@else
    @if($rows->isEmpty())
        <div class="mb-3">
            <x-ui.alert variant="error" icon="exclamation" title="Tidak ada data yang dapat diambil.">
                @if($unavailableCount > 0)
                    {{ $unavailableCount }} aplikasi sedang tidak dapat dihubungi. Periksa koneksi ke subsidiary.
                @else
                    Coba pilih periode lain.
                @endif
            </x-ui.alert>
        </div>
    @endif
    <x-ui.card>
        <div class="overflow-x-auto -mx-5 sm:-mx-6">
            <table class="min-w-full divide-y divide-outline-variant">
                <thead class="bg-surface-container">
                    <tr>
                        <th class="sticky left-0 z-10 bg-surface-container px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6">Kode</th>
                        <th class="px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Nama Akun</th>
                        @foreach($columns as $col)
                        <th class="px-3 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant whitespace-nowrap">
                            <div class="flex flex-col items-end gap-0.5">
                                <span>{{ $col['label'] }}</span>
                                @if(!$col['available'])
                                <x-ui.badge variant="error" icon="warning" size="sm">Offline</x-ui.badge>
                                @endif
                            </div>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant bg-surface-container-lowest">
                    @foreach($rows as $row)
                    <tr class="hover:bg-surface-container transition">
                        <td class="sticky left-0 z-10 bg-surface-container-lowest px-5 py-3 text-xs font-mono text-on-surface-variant sm:px-6 whitespace-nowrap">{{ $row['account_code'] ?: '—' }}</td>
                        <td class="px-3 py-3 text-sm font-medium text-on-surface">{{ $row['account_name'] }}</td>
                        @foreach($columns as $col)
                        @php
                            $amount = $row['amounts'][$col['license_id']] ?? null;
                            $display = $amount === null ? '—' : 'Rp ' . number_format($amount, 0, ',', '.');
                        @endphp
                        <td class="px-3 py-3 text-right text-sm font-mono whitespace-nowrap {{ $amount === null ? 'text-on-surface-variant' : 'text-on-surface' }}">
                            {{ $display }}
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-surface-container">
                    <tr>
                        <td colspan="2" class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-on-surface sm:px-6">Total</td>
                        @foreach($columns as $col)
                        <td class="px-3 py-3.5 text-right text-sm font-mono font-semibold text-on-surface whitespace-nowrap">
                            Rp {{ number_format($col['total'], 0, ',', '.') }}
                        </td>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-ui.card>

    @if($unavailableCount > 0)
        <p class="mt-3 text-xs text-on-surface-variant">Aplikasi berlabel "Offline" tidak berhasil dihubungi. Data ditampilkan sesuai cache terakhir (jika ada).</p>
    @endif
@endif
@endsection
