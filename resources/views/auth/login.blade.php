@extends('layouts.auth')

@section('title', 'Masuk — Holding App')

@section('content')
@php
    $baseDomain = config('app.domain_base', 'holding.test');
    $brandName = $currentTenant?->name ?? 'Holding App';
    $brandSubtitle = $currentTenant
        ? 'Tenant Panel'
        : 'Pusat Kendali Multi-Tenant';
    $brandIcon = $currentTenant ? 'building' : 'home';
@endphp
<div class="w-full max-w-md">
    <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-elevated-lg">
        <div class="flex items-center gap-3 bg-primary px-6 py-5 text-on-primary">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-on-primary/15">
                <x-ui.icon :name="$brandIcon" class="h-7 w-7" />
            </div>
            <div class="min-w-0">
                <div class="text-xl font-bold tracking-tight leading-tight">{{ $brandName }}</div>
                <div class="mt-0.5 text-[11px] font-medium uppercase tracking-wider opacity-80">{{ $brandSubtitle }}</div>
            </div>
        </div>

        <div class="p-6 sm:p-8">
            <h1 class="mb-1 text-2xl font-semibold tracking-tight text-on-surface">Masuk</h1>
            <p class="mb-6 text-sm text-on-surface-variant">
                @if ($currentTenant)
                    Gunakan akun <strong class="font-semibold text-on-surface">{{ $currentTenant->name }}</strong> Anda untuk melanjutkan.
                @else
                    Gunakan akun Anda untuk melanjutkan.
                @endif
            </p>

            @if ($errors->any())
                <div class="mb-5">
                    <x-ui.alert variant="error" icon="x-circle" :title="$errors->first()">Periksa kembali email dan kata sandi Anda.</x-ui.alert>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <x-ui.input name="email" type="email" label="Email" placeholder="nama@contoh.com" leading-icon="link" required autofocus autocomplete="email" />
                <x-ui.input name="password" type="password" label="Kata Sandi" placeholder="Masukkan kata sandi" leading-icon="lock-closed" required autocomplete="current-password" />

                <div class="flex items-center justify-between pt-1">
                    <label for="remember" class="inline-flex cursor-pointer items-center gap-2 text-sm text-on-surface">
                        <input id="remember" name="remember" type="checkbox" class="h-4 w-4 rounded border-outline text-primary focus:ring-2 focus:ring-primary/30 focus:ring-offset-0 transition">
                        Ingat saya
                    </label>
                </div>

                <x-ui.button type="submit" icon="arrow-right" iconPosition="right" class="mt-2 w-full">Masuk</x-ui.button>
            </form>
        </div>
    </div>

    <div class="mt-6 flex flex-col items-center gap-1 text-center text-[11px] font-medium uppercase tracking-wider text-on-surface-variant">
        @if ($currentTenant)
            <span>v0.1.0 &middot; Anda login di panel tenant</span>
            <a href="http://admin.{{ $baseDomain }}/login" class="text-primary hover:underline">Login sebagai vendor &rarr;</a>
        @else
            <span>v0.1.0 &middot; Anda login di panel vendor</span>
        @endif
    </div>
</div>
@endsection
