@php
$isEdit = isset($application);
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.applications.update', $application) : route('admin.applications.store') }}" class="space-y-5">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <x-ui.input name="name" label="Nama Aplikasi" :value="$application->name ?? ''" required />
        </div>
        <div>
            <x-ui.input name="slug" label="Slug" :value="$application->slug ?? ''" placeholder="contoh: enstore" required hint="Huruf kecil, angka, dan tanda hubung saja." />
        </div>
    </div>

    <x-ui.textarea name="description" label="Deskripsi" :value="$application->description ?? ''" :rows="2" />

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <x-ui.input name="base_url" type="url" label="Base URL" :value="$application->base_url ?? ''" placeholder="https://app.example.com" required leading-icon="link" />
        </div>
        <div>
            <x-ui.input name="icon_path" label="Path Ikon" :value="$application->icon_path ?? ''" placeholder="images/icon.png" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl bg-surface-container p-4">
            <x-ui.switcher name="has_financial_report" label="Mendukung Laporan Keuangan" :checked="$application->has_financial_report ?? true" />
        </div>
        <div class="rounded-2xl bg-surface-container p-4">
            <x-ui.switcher name="is_active" label="Aplikasi Aktif" :checked="$application->is_active ?? true" />
        </div>
    </div>

    <div class="flex justify-end gap-2 border-t border-outline-variant pt-4">
        <x-ui.button :href="route('admin.applications.index')" variant="outlined">Batal</x-ui.button>
        <x-ui.button type="submit" icon="check">{{ $isEdit ? 'Simpan Perubahan' : 'Tambah Aplikasi' }}</x-ui.button>
    </div>
</form>
