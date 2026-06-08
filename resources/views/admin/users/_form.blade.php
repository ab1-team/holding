@php
$isEdit = isset($user);
$currentRole = old('role', $user->role ?? 'tenant_staff');
$currentTenantId = old('tenant_id', $user->tenant_id ?? '');
$tenantOptions = $tenants->pluck('name', 'id')->toArray();
$roleOptions = [
    'superadmin' => 'Vendor Admin (Superadmin)',
    'tenant_owner' => 'Pemilik Tenant',
    'tenant_staff' => 'Staff Tenant',
];
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.users.update', $user) : route('admin.users.store') }}" class="space-y-5" x-data="{ role: '{{ $currentRole }}' }">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.input name="name" label="Nama" :value="$user->name ?? ''" required />
        <x-ui.input name="email" type="email" label="Email" :value="$user->email ?? ''" required />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-form.smart-select
            name="role"
            label="Role"
            :options="$roleOptions"
            :value="$currentRole"
            :searchable="false"
            :clearable="false"
            required />
        <div x-show="role !== 'superadmin'" x-cloak>
            <x-form.smart-select
                name="tenant_id"
                label="Tenant"
                :options="$tenantOptions"
                :value="$currentTenantId"
                :searchable="true"
                placeholder="Pilih tenant..."
                search-placeholder="Cari tenant..."
                hint="Wajib untuk role pemilik dan staff." />
        </div>
    </div>

    <div>
        <label for="password" class="mb-1.5 block text-sm font-medium text-on-surface">
            Kata Sandi @if(! $isEdit) <span class="text-error">*</span> @endif
        </label>
        <input type="password" name="password" id="password" {{ $isEdit ? '' : 'required' }} minlength="8" autocomplete="new-password"
               class="block w-full rounded-lg border border-outline bg-surface-container-lowest px-3.5 py-2.5 text-sm text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none @error('password') border-error @enderror">
        <p class="mt-1.5 text-xs text-on-surface-variant">
            @if($isEdit) Kosongkan jika tidak ingin mengubah. Minimal 8 karakter. @else Password akan di-generate otomatis dan ditampilkan sekali setelah simpan. Kosongkan untuk override. @endif
        </p>
        @error('password') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
    </div>

    <div class="rounded-2xl bg-surface-container p-4">
        <x-ui.switcher name="is_active" label="Pengguna Aktif" :checked="$user->is_active ?? true" />
    </div>

    <div class="flex flex-col-reverse justify-end gap-2 border-t border-outline-variant pt-5 sm:flex-row">
        <x-ui.button :href="route('admin.users.index')" variant="outlined">Batal</x-ui.button>
        <x-ui.button type="submit" icon="check">{{ $isEdit ? 'Simpan Perubahan' : 'Tambah Pengguna' }}</x-ui.button>
    </div>
</form>