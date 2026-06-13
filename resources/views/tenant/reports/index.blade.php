@extends('layouts.app')

@section('title', 'Laporan Keuangan — Holding App')

@section('content')
<x-ui.page-header
    overline="Laporan"
    title="Laporan Keuangan Komparatif"
    subtitle="Pilih tipe laporan dan periode untuk membandingkan data dari semua aplikasi subsidiary Anda."
/>

@if($apps->isEmpty())
    <x-ui.card>
        <x-ui.empty-state
            icon="chart-bar"
            title="Belum ada aplikasi aktif"
            description="Hubungi vendor untuk aktivasi lisensi aplikasi yang memiliki fitur laporan keuangan."
        />
    </x-ui.card>
@else
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($reportTypes as $slug => $label)
        <a href="{{ route('tenant.reports.show', ['type' => $slug, 'period' => now()->format('Y-m')]) }}"
           class="group flex flex-col gap-3 rounded-2xl bg-surface-container-lowest p-5 shadow-elevated transition hover:bg-surface-container">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-container text-on-primary-container">
                <x-ui.icon name="chart-bar" class="h-6 w-6" />
            </div>
            <div>
                <div class="text-base font-semibold text-on-surface">{{ $label }}</div>
                <div class="mt-0.5 text-xs text-on-surface-variant">Bandingkan antar aplikasi</div>
            </div>
            <span class="mt-auto inline-flex items-center gap-1 text-xs font-semibold text-primary group-hover:underline">
                Lihat laporan
                <x-ui.icon name="arrow-right" class="h-3.5 w-3.5" />
            </span>
        </a>
        @endforeach
    </div>
@endif
@endsection
