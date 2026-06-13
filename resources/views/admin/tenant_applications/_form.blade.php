@php
$isEdit = isset($license);
$selectedAppId = old('application_id', $license->application_id ?? '');
$availableApps = $availableApplications ?? collect();
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.tenants.licenses.update', [$tenant, $license]) : route('admin.tenants.licenses.store', $tenant) }}" class="space-y-5">
    @csrf
    @if($isEdit) @method('PUT') @endif

    @if(! $isEdit)
    <x-form.smart-select
        name="application_id"
        label="Aplikasi"
        :options="$availableApps->pluck('name', 'id')->toArray()"
        :value="$selectedAppId"
        :searchable="true"
        required
        placeholder="Pilih aplikasi..."
        search-placeholder="Cari aplikasi..."
        hint="Hanya aplikasi yang belum terikat ke tenant ini." />
    @else
    <div>
        <label class="mb-1.5 block text-sm font-medium text-on-surface">Aplikasi</label>
        <div class="rounded-lg border border-outline bg-surface-container px-3.5 h-10 text-sm flex items-center text-on-surface">
            <x-ui.icon name="cube" class="h-4 w-4 text-on-surface-variant mr-2" />
            {{ $license->application->name }}
            <code class="ml-2 rounded bg-surface px-1.5 py-0.5 text-xs text-on-surface-variant">{{ $license->application->slug }}</code>
        </div>
    </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <x-ui.input name="instance_url" type="url" label="Instance URL" :value="$license->instance_url ?? ''" placeholder="https://tenant.app.example.com" required leading-icon="link" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.input name="label" label="Label" :value="$license->label ?? ''" placeholder="cth: Produksi" hint="Label internal untuk identifikasi lisensi." />
        <x-ui.input name="activated_at" type="date" label="Tanggal Aktivasi" :value="isset($license) && $license->activated_at ? $license->activated_at->format('Y-m-d') : ''" hint="Tanggal lisensi mulai berlaku." />
        <x-ui.input name="expired_at" type="date" label="Tanggal Kedaluwarsa" :value="isset($license) && $license->expired_at ? $license->expired_at->format('Y-m-d') : ''" hint="Kosongkan untuk lisensi perpetual." />
    </div>

    <x-ui.textarea name="notes" label="Catatan" :value="$license->notes ?? ''" :rows="2" />

    <div class="rounded-2xl bg-surface-container p-4">
        <x-ui.switcher name="is_active" label="Lisensi Aktif" :checked="$license->is_active ?? true" />
    </div>

    <div class="flex justify-end gap-2 border-t border-outline-variant pt-4">
        <x-ui.button :href="route('admin.tenants.show', $tenant)" variant="outlined">Batal</x-ui.button>
        <x-ui.button type="submit" icon="check">{{ $isEdit ? 'Simpan Perubahan' : 'Buat Lisensi' }}</x-ui.button>
    </div>
</form>