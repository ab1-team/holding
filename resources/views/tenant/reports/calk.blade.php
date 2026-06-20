@extends('layouts.app')

@section('title', "{$reportLabel} — Catatan Komparatif")

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
    $rincianAkun = $firstPayload['data']['rincian_akun'] ?? [];
@endphp

{{-- Bagian A: Narasi point_a per license --}}
<x-ui.card class="mb-4">
    <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-on-surface">A. Gambaran Umum</h2>
    <div class="space-y-4">
        @foreach($licensesList as $lic)
            @php $pl = $payloads[$lic->id] ?? []; @endphp
            <div class="rounded-lg border border-outline-variant bg-surface-container-lowest p-4">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs font-semibold text-on-surface">{{ $lic->label ?: $lic->application->name }}</span>
                    @if(($pl['status'] ?? null) !== 'success')
                        <x-ui.badge variant="error" icon="warning" size="sm">Offline</x-ui.badge>
                    @endif
                </div>
                @if(!empty($pl['point_a']))
                    <div class="prose prose-sm max-w-none text-justify text-on-surface">{!! nl2br(e($pl['point_a'])) !!}</div>
                @else
                    <p class="text-xs italic text-on-surface-variant">Belum ada narasi Bagian A untuk periode ini.</p>
                @endif
                @if(!empty($pl['catatan']))
                    <div class="mt-3 border-t border-outline-variant pt-3">
                        <h3 class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Catatan (Bagian B)</h3>
                        <div class="prose prose-sm max-w-none text-justify text-on-surface">{!! $pl['catatan'] !!}</div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</x-ui.card>

{{-- Bagian C: 4-level hierarchy --}}
<x-ui.card class="mb-4">
    <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-on-surface">C. Rincian Akun per Rekening</h2>
    <div class="overflow-x-auto -mx-5 sm:-mx-6">
        <table class="min-w-full divide-y divide-outline-variant">
            <thead class="bg-surface-container">
                <tr>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6 w-1/3">Rekening</th>
                    @foreach($licensesList as $lic)
                    <th class="px-3 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">
                        {{ $lic->label ?: $lic->application->name }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant bg-surface-container-lowest">
                @forelse($rincianAkun as $lev1)
                    <tr class="bg-surface-container-high/40">
                        <td colspan="{{ 1 + $licensesList->count() }}" class="px-5 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-on-surface sm:px-6">{{ $lev1['kode_akun'] ?? '' }}. {{ $lev1['nama_akun'] ?? '' }}</td>
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
                                        $licLev3 = null;
                                        foreach (($payloads[$lic->id]['data']['rincian_akun'] ?? []) as $l1) {
                                            if (($l1['kode_akun'] ?? null) !== ($lev1['kode_akun'] ?? null)) continue;
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
                            @foreach(($lev3['rekening'] ?? []) as $rek)
                                <tr>
                                    <td class="px-5 py-1.5 text-xs text-on-surface sm:px-6 pl-24">{{ $rek['kode_akun'] ?? '' }}. {{ $rek['nama_akun'] ?? '' }}</td>
                                    @foreach($licensesList as $lic)
                                        @php
                                            $licRek = null;
                                            foreach (($payloads[$lic->id]['data']['rincian_akun'] ?? []) as $l1) {
                                                if (($l1['kode_akun'] ?? null) !== ($lev1['kode_akun'] ?? null)) continue;
                                                foreach (($l1['akun2'] ?? []) as $l2) {
                                                    foreach (($l2['akun3'] ?? []) as $l3) {
                                                        if (($l3['kode_akun'] ?? null) !== ($lev3['kode_akun'] ?? null)) continue;
                                                        foreach (($l3['rekening'] ?? []) as $rk) {
                                                            if (($rk['kode_akun'] ?? null) === ($rek['kode_akun'] ?? null) && ($rk['nama_akun'] ?? null) === ($rek['nama_akun'] ?? null)) {
                                                                $licRek = $rk; break 4;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        @endphp
                                        <td class="px-3 py-1.5 text-right text-xs font-mono whitespace-nowrap text-on-surface-variant">
                                            {{ $licRek === null ? '—' : number_format((float) ($licRek['saldo'] ?? 0), 2, ',', '.') }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    @endforeach
                    <tr class="border-t-2 border-outline-variant bg-surface-container/80 font-semibold">
                        <td class="px-5 py-2 text-[11px] font-bold uppercase tracking-wider text-on-surface sm:px-6 pl-10">Jumlah {{ $lev1['nama_akun'] ?? '' }}</td>
                        @foreach($licensesList as $lic)
                            @php
                                $sum = null;
                                foreach (($payloads[$lic->id]['data']['rincian_akun'] ?? []) as $l1) {
                                    if (($l1['kode_akun'] ?? null) === ($lev1['kode_akun'] ?? null)) { $sum = $l1['saldo'] ?? null; break; }
                                }
                            @endphp
                            <td class="px-3 py-2 text-right text-sm font-mono font-semibold text-on-surface whitespace-nowrap">
                                {{ $sum === null ? '—' : 'Rp ' . number_format((float) $sum, 2, ',', '.') }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ 1 + $licensesList->count() }}" class="px-5 py-8 text-center text-sm text-on-surface-variant sm:px-6">Tidak ada rincian akun untuk periode ini.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-surface-container">
                <tr class="border-t-4 border-outline font-semibold">
                    <td class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-on-surface sm:px-6">Jumlah Liabilitas + Ekuitas</td>
                    @foreach($licensesList as $lic)
                        @php
                            $total = $payloads[$lic->id]['ringkasan']['total_liabilitas_ekuitas'] ?? null;
                            $aset = $payloads[$lic->id]['ringkasan']['total_aset'] ?? null;
                            $selisih = $payloads[$lic->id]['ringkasan']['selisih'] ?? null;
                        @endphp
                        <td class="px-3 py-3 text-right text-sm font-mono font-bold text-on-surface whitespace-nowrap">
                            {{ $total === null ? '—' : 'Rp ' . number_format((float) $total, 2, ',', '.') }}
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        </table>
    </div>
    @php $anySelisih = false; @endphp
    @foreach($licensesList as $lic)
        @php $s = $payloads[$lic->id]['ringkasan']['selisih'] ?? null; if($s !== null && abs((float)$s) > 0.01) $anySelisih = true; @endphp
    @endforeach
    @if($anySelisih)
        <x-ui.alert variant="warning" icon="exclamation" title="Selisih Aset vs Liabilitas+Ekuitas" class="mt-3">
            <ul class="mt-1.5 list-disc space-y-0.5 pl-5 text-xs">
                @foreach($licensesList as $lic)
                    @php
                        $r = $payloads[$lic->id]['ringkasan'] ?? [];
                        $selisih = (float) ($r['selisih'] ?? 0);
                    @endphp
                    @if(abs($selisih) > 0.01)
                    <li><span class="font-semibold">{{ $lic->label ?: $lic->application->name }}</span> — selisih Rp {{ number_format($selisih, 2, ',', '.') }} (Aset {{ number_format((float)($r['total_aset'] ?? 0), 2, ',', '.') }} vs L+E {{ number_format((float)($r['total_liabilitas_ekuitas'] ?? 0), 2, ',', '.') }})</li>
                    @endif
                @endforeach
            </ul>
        </x-ui.alert>
    @endif
</x-ui.card>

{{-- Penandatangan per license --}}
@php
    $penandatanganRoles = ['sekretaris', 'bendahara', 'pengawas', 'direktur'];
@endphp
<x-ui.card>
    <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-on-surface">Penandatangan</h2>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-{{ min(4, count($licensesList)) }}">
        @foreach($licensesList as $lic)
            @php
                $ptd = $payloads[$lic->id]['penandatangan'] ?? [];
            @endphp
            <div class="rounded-lg border border-outline-variant bg-surface-container-lowest p-4">
                <h3 class="mb-2 text-xs font-semibold text-on-surface">{{ $lic->label ?: $lic->application->name }}</h3>
                <dl class="space-y-1.5 text-xs">
                    @foreach($penandatanganRoles as $role)
                        @php $u = $ptd[$role] ?? null; @endphp
                        <div class="flex items-baseline gap-2">
                            <dt class="w-20 shrink-0 text-on-surface-variant">{{ ucfirst($role) }}</dt>
                            <dd class="text-on-surface">{{ $u['name'] ?? '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endforeach
    </div>
</x-ui.card>
@endif
@endsection
