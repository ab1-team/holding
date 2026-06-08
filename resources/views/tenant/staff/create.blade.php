@extends('layouts.app')

@section('title', 'Undang Staff — Holding App')

@section('content')
<div class="mb-6">
    <p class="text-xs font-semibold uppercase tracking-wider text-secondary">Staff</p>
    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-on-surface">Undang Staff</h1>
    <p class="mt-1 text-sm text-on-surface-variant">Untuk tenant <strong class="font-semibold text-on-surface">{{ $tenant->name }}</strong></p>
</div>
<x-ui.card>
    <form method="POST" action="{{ route('tenant.staff.store') }}" class="space-y-5">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input name="name" label="Nama" required />
            <x-ui.input name="email" type="email" label="Email" required />
        </div>
        <x-form.smart-select
            name="role"
            label="Role"
            :options="['tenant_staff' => 'Staff', 'tenant_owner' => 'Pemilik Tenant']"
            :value="old('role', 'tenant_staff')"
            :searchable="false"
            :clearable="false"
            required />
        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-on-surface">Password <span class="text-xs font-normal text-on-surface-variant">(opsional)</span></label>
            <input type="password" name="password" id="password" minlength="8" autocomplete="new-password" class="block w-full rounded-lg border border-outline bg-surface-container-lowest px-3.5 py-2.5 text-sm text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none @error('password') border-error @enderror">
            <p class="mt-1.5 text-xs text-on-surface-variant">Kosongkan untuk generate otomatis. Minimal 8 karakter.</p>
            @error('password') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
        </div>
        <div class="rounded-2xl bg-surface-container p-4">
            <x-ui.switcher name="is_active" label="Aktif" :checked="old('is_active', true)" />
        </div>
        <div class="flex justify-end gap-2 border-t border-outline-variant pt-4">
            <x-ui.button :href="route('tenant.staff.index')" variant="outlined">Batal</x-ui.button>
            <x-ui.button type="submit" icon="check">Undang Staff</x-ui.button>
        </div>
    </form>
</x-ui.card>
@endsection