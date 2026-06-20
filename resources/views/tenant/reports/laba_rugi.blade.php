@extends('layouts.app')

@section('title', "{$reportLabel} — Laba Rugi Komparatif")

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

    // Section list per Laba Rugi structure.
    $sections = [
        'pendapatan' => '4. Pendapatan',
        'beban' => '5. Beban',
        'pendapatan_non_ops' => 'Pendapatan Non Operasional',
        'beban_non_ops' => 'Beban Non Operasional',
    ];

    $fmt = fn ($v) => $v === null ? '—' : 'Rp ' . number_format((float) $v, 2, ',', '.');
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
    <div class="overflow-x-auto -mx-5 sm:-mx-6">
        <table class="min-w-full divide-y divide-outline-variant">
            <thead class="bg-surface-container">
                <tr>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6">Rekening</th>
                    <th class="px-3 py-3.5 text-center text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Kolom</th>
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
                @foreach($sections as $key => $label)
                    @php
                        $firstPayload = null;
                        foreach ($licensesList as $lic) {
                            if (($payloads[$lic->id]['status'] ?? null) === 'success') { $firstPayload = $payloads[$lic->id]; break; }
                        }
                        $sectionRows = $firstPayload['data'][$key] ?? [];
                    @endphp
                    <tr class="bg-surface-container-high/40">
                        <td colspan="{{ 2 + $licensesList->count() }}" class="px-5 py-2 text-[11px] font-bold uppercase tracking-wider text-on-surface sm:px-6">{{ $label }}</td>
                    </tr>
                    @forelse($sectionRows as $row)
                        <tr class="hover:bg-surface-container transition">
                            <td class="px-5 py-2 text-sm text-on-surface sm:px-6 pl-10 font-semibold" colspan="2">{{ $row['kode_akun'] ?? '' }}. {{ $row['nama_akun'] ?? '' }}</td>
                            @foreach($licensesList as $lic)
                                @php
                                    $licRow = null;
                                    foreach (($payloads[$lic->id]['data'][$key] ?? []) as $r) {
                                        if (($r['kode_akun'] ?? null) === ($row['kode_akun'] ?? null)) { $licRow = $r; break; }
                                    }
                                @endphp
                                <td class="px-3 py-2 text-right text-sm font-mono whitespace-nowrap text-on-surface">{{ $licRow === null ? '—' : '' }}</td>
                            @endforeach
                        </tr>
                        {{-- rekening detail per-license --}}
                        @php
                            $firstRekening = $row['rekening'] ?? [];
                        @endphp
                        @if(!empty($firstRekening))
                        <tr class="bg-surface-container/40">
                            <td class="px-5 py-1 text-[10px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6 pl-16">Rekening</td>
                            <td class="px-2 py-1 text-center text-[10px] font-semibold uppercase tracking-wider text-on-surface-variant">s.d bln lalu / periode ini / s.d sekarang</td>
                            @foreach($licensesList as $lic)
                                <td class="px-3 py-1 text-right text-[10px] font-semibold uppercase tracking-wider text-on-surface-variant">{{ $lic->label ?: $lic->application->name }}</td>
                            @endforeach
                        </tr>
                        @foreach($firstRekening as $rek)
                            <tr>
                                <td class="px-5 py-1.5 text-xs text-on-surface sm:px-6 pl-16">{{ $rek['kode_akun'] ?? '' }}. {{ $rek['nama_akun'] ?? '' }}</td>
                                <td class="px-2 py-1.5 text-center text-[10px] text-on-surface-variant">3 kolom</td>
                                @foreach($licensesList as $lic)
                                    @php
                                        $licRow = null;
                                        foreach (($payloads[$lic->id]['data'][$key] ?? []) as $r) {
                                            if (($r['kode_akun'] ?? null) === ($row['kode_akun'] ?? null)) {
                                                foreach (($r['rekening'] ?? []) as $rk) {
                                                    if (($rk['kode_akun'] ?? null) === ($rek['kode_akun'] ?? null) && ($rk['nama_akun'] ?? null) === ($rek['nama_akun'] ?? null)) { $licRow = $rk; break 2; }
                                                }
                                            }
                                        }
                                    @endphp
                                    <td class="px-3 py-1.5 text-right text-xs font-mono whitespace-nowrap text-on-surface-variant">
                                        @if($licRow === null) —
                                        @else
                                            {{ number_format((float) ($licRow['saldo_bln_lalu'] ?? 0), 2, ',', '.') }}
                                            / {{ number_format((float) ($licRow['saldo_periode_ini'] ?? 0), 2, ',', '.') }}
                                            / {{ number_format((float) ($licRow['saldo'] ?? 0), 2, ',', '.') }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        @endif
                        {{-- Group total (Jumlah {kode}. {nama}) per license --}}
                        <tr class="border-t border-outline-variant bg-surface-container/60 font-semibold">
                            <td class="px-5 py-1.5 text-[11px] text-on-surface sm:px-6 pl-10" colspan="2">Jumlah {{ $row['kode_akun'] ?? '' }}. {{ $row['nama_akun'] ?? '' }}</td>
                            @foreach($licensesList as $lic)
                                @php
                                    $licRow = null;
                                    foreach (($payloads[$lic->id]['data'][$key] ?? []) as $r) {
                                        if (($r['kode_akun'] ?? null) === ($row['kode_akun'] ?? null)) { $licRow = $r; break; }
                                    }
                                @endphp
                                <td class="px-3 py-1.5 text-right text-xs font-mono whitespace-nowrap text-on-surface">
                                    @if($licRow === null) —
                                    @else
                                        {{ number_format((float) ($licRow['saldo_bln_lalu'] ?? 0), 2, ',', '.') }}
                                        / {{ number_format((float) ($licRow['saldo_periode_ini'] ?? 0), 2, ',', '.') }}
                                        / {{ number_format((float) ($licRow['saldo'] ?? 0), 2, ',', '.') }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ 2 + $licensesList->count() }}" class="px-5 py-3 text-center text-xs text-on-surface-variant sm:px-6 pl-10">Tidak ada rekening di bagian ini.</td></tr>
                    @endforelse
                @endforeach

                {{-- Ringkasan A/B/C/PPh --}}
                @php
                    $ringkasanRows = [
                        'lr_operasional' => 'A. Laba Rugi Operasional (Pendapatan - Beban)',
                        'lr_non_operasional' => 'B. Laba Rugi Non Operasional',
                        'sebelum_pajak' => 'C. Sebelum Pajak (A + B)',
                        'pph' => 'PPh',
                        'setelah_pajak' => 'Setelah Pajak',
                    ];
                @endphp
                @foreach($ringkasanRows as $rk => $label)
                    <tr class="bg-surface-container-high/30 font-semibold">
                        <td colspan="2" class="px-5 py-2 text-[11px] font-bold uppercase tracking-wider text-on-surface sm:px-6">{{ $label }}</td>
                        @foreach($licensesList as $lic)
                            @php $r = $payloads[$lic->id]['ringkasan'][$rk] ?? null; @endphp
                            <td class="px-3 py-2 text-right text-xs font-mono font-semibold text-on-surface whitespace-nowrap">
                                @if($r === null) —
                                @elseif(is_array($r))
                                    {{ number_format((float) ($r['s_d_bulan_lalu'] ?? 0), 2, ',', '.') }}
                                    / {{ number_format((float) ($r['periode_ini'] ?? 0), 2, ',', '.') }}
                                    / {{ number_format((float) ($r['s_d_sekarang'] ?? 0), 2, ',', '.') }}
                                @else
                                    {{ number_format((float) $r, 2, ',', '.') }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="mt-3 text-[10px] text-on-surface-variant">Format per kolom license: <em>s.d bln lalu / periode ini / s.d sekarang</em>. Rp dengan 2 desimal, negatif pakai prefix <code>-</code>.</p>
</x-ui.card>
@endif
@endsection
