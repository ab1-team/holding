<div wire:key="smart-table-{{ $model }}" class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-elevated">
    @if($title || $overline || $subtitle || $createUrl)
    <div class="flex flex-col gap-3 border-b border-outline-variant px-5 py-4 sm:px-6 md:flex-row md:items-center md:justify-between">
        <div class="min-w-0">
            @if($overline)
            <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">{{ $overline }}</p>
            @endif
            @if($title)
            <h2 class="mt-0.5 text-base font-semibold text-on-surface">{{ $title }}</h2>
            @endif
            @if($subtitle)
            <p class="mt-0.5 text-sm text-on-surface-variant">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-on-surface-variant" />
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ $searchPlaceholder }}" autocomplete="off"
                       class="block w-48 rounded-full border border-outline bg-surface-container-lowest pl-9 pr-9 py-1.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none sm:w-64">
                @if($search)
                <button type="button" wire:click="clearSearch" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full p-1 hover:bg-surface-container">
                    <x-ui.icon name="x-mark" class="h-4 w-4 text-on-surface-variant" />
                </button>
                @endif
            </div>
            @if($createUrl)
            <x-ui.button :href="$createUrl" size="sm" :icon="$createIcon">{{ $createLabel }}</x-ui.button>
            @endif
        </div>
    </div>
    @endif

    <div class="overflow-x-auto" wire:loading.class="opacity-60 transition-opacity">
        <table class="min-w-full divide-y divide-outline-variant">
            <thead class="bg-surface-container">
                <tr>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6">Aplikasi</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Base URL</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Laporan</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Status</th>
                    <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($items as $a)
                <tr wire:key="app-{{ $a->id }}" class="hover:bg-surface-container transition">
                    <td class="px-5 py-3.5 sm:px-6">
                        @php
                            $cubeIcon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>';
                        @endphp
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-container text-on-primary-container">{!! $cubeIcon !!}</div>
                            <div>
                                <div class="text-sm font-semibold text-on-surface">{{ $a->name }}</div>
                                <div class="text-xs text-on-surface-variant"><code class="rounded bg-surface-container px-1.5 py-0.5">{{ $a->slug }}</code></div>
                            </div>
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-sm">
                        <a href="{{ $a->base_url }}" target="_blank" rel="noopener" class="font-medium text-primary hover:underline">{{ parse_url($a->base_url, PHP_URL_HOST) }}</a>
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-sm">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $a->has_financial_report ? 'bg-sky-100 text-sky-800' : 'bg-surface-container text-on-surface-variant' }}">{{ $a->has_financial_report ? 'Aktif' : 'Tidak' }}</span>
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-sm">
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $a->is_active ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $a->is_active ? 'bg-secondary' : 'bg-on-surface-variant' }}"></span>
                            {{ $a->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-right text-sm font-medium sm:px-6">
                        <a href="{{ route('admin.applications.show', $a) }}" class="text-primary hover:underline">Detail</a>
                        <a href="{{ route('admin.applications.edit', $a) }}" class="ml-3 text-on-surface-variant hover:text-on-surface">Edit</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-12 text-center text-sm text-on-surface-variant sm:px-6">{{ $empty }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div wire:loading class="absolute inset-0 flex items-center justify-center bg-surface-container-lowest/30 backdrop-blur-sm pointer-events-none">
        <svg class="h-8 w-8 animate-spin text-primary" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"/>
            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
        </svg>
    </div>

    @if($items->total() > 0 || $items->hasPages())
    <div class="flex flex-col gap-3 border-t border-outline-variant bg-surface-container px-5 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex items-center gap-2 text-xs text-on-surface-variant">
            <span>Menampilkan {{ $items->firstItem() }}–{{ $items->lastItem() }} dari {{ $items->total() }}</span>
            <label class="ml-3 flex items-center gap-1.5">
                <span>Per halaman:</span>
                <select wire:model.live="perPage" class="rounded-md border border-outline bg-surface-container-lowest px-2 py-0.5 text-xs focus:border-primary focus:ring-1 focus:ring-primary/30 focus:outline-none">
                    @foreach($perPageOptions as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div>{{ $items->links() }}</div>
    </div>
    @endif
</div>