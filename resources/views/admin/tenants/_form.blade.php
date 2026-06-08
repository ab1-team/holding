@php $isEdit = isset($tenant); @endphp

<form method="POST" action="{{ $isEdit ? route('admin.tenants.update', $tenant) : route('admin.tenants.store') }}" class="space-y-5">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <x-ui.input name="name" label="Nama Tenant" :value="$tenant->name ?? ''" required />
        </div>
        <div>
            <x-ui.input name="slug" label="Slug" :value="$tenant->slug ?? ''" placeholder="contoh: pt-maju-jaya" hint="Otomatis dari nama jika kosong." />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.input name="email" type="email" label="Email" :value="$tenant->email ?? ''" required />
        <x-ui.input name="phone" label="Telepon" :value="$tenant->phone ?? ''" placeholder="+62..." />
    </div>

    <x-ui.textarea name="address" label="Alamat" :value="$tenant->address ?? ''" :rows="2" />

    <div class="rounded-2xl bg-surface-container p-4">
        <x-ui.switcher name="is_active" label="Tenant Aktif" :checked="$tenant->is_active ?? true" description="Tenant nonaktif tidak dapat diakses oleh penggunanya." />
    </div>

    <div class="flex justify-end gap-2 border-t border-outline-variant pt-4">
        <x-ui.button :href="route('admin.tenants.index')" variant="outlined">Batal</x-ui.button>
        <x-ui.button type="submit" icon="check">{{ $isEdit ? 'Simpan Perubahan' : 'Tambah Tenant' }}</x-ui.button>
    </div>
</form>