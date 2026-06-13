<div wire:key="smart-table-{{ $model }}" class="rounded-2xl bg-surface-container-lowest shadow-elevated">
    <div
        class="flex flex-col gap-3 border-b border-outline-variant px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        @if ($overline || $title || $subtitle)
            <div class="min-w-0">
                @if ($overline)
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">
                        {{ $overline }}</p>
                @endif
                @if ($title)
                    <h2 class="mt-0.5 text-base font-semibold text-on-surface">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-sm text-on-surface-variant">{{ $subtitle }}</p>
                @endif
            </div>
        @endif
        <div class="flex w-full flex-nowrap items-center justify-between gap-2">
            <livewire:form.smart-select name="perPage" :options="array_combine($perPageOptions, $perPageOptions)" wire:model.live="perPage" :searchable="false"
                :clearable="false" placeholder="15" class="w-20 shrink-0" />
            <div class="flex flex-1 items-center justify-end gap-2 min-w-0">
                <x-ui.input type="search" name="search" leading-icon="search" placeholder="{{ $searchPlaceholder }}"
                    wire:model.live.debounce.300ms="search" :clearable="!empty($search)" class="min-w-0 flex-1 sm:max-w-xs" />
                @if ($createUrl)
                    <x-ui.button :href="$createUrl" size="sm" :icon="$createIcon"
                        class="shrink-0">{{ $createLabel }}</x-ui.button>
                @endif
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-outline-variant">
            <thead class="bg-surface-container">
                <tr>
                    @foreach ($columns as $col)
                        <th
                            class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant {{ $loop->last ? 'sm:px-6' : '' }} {{ ($col['align'] ?? 'left') === 'right' ? 'text-right' : '' }}">
                            {{ $col['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant relative" wire:loading.class="opacity-50 transition-opacity"
                wire:target="search,perPage,goToPage">
                @forelse($items as $row)
                    <tr wire:key="row-{{ $row->id }}" class="hover:bg-surface-container transition">
                        @foreach ($columns as $col)
                            <td
                                class="whitespace-nowrap px-5 py-3.5 text-sm {{ ($col['align'] ?? 'left') === 'right' ? 'text-right sm:px-6' : 'sm:px-6' }}">
                                @php
                                    $field = $col['field'] ?? null;
                                    $view = $col['view'] ?? null;
                                @endphp
                                @if ($view)
                                    @include($view, ['row' => $row, 'value' => data_get($row, $field)])
                                @elseif($field)
                                    {{ data_get($row, $field) }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="px-5 py-12 sm:px-6">
                            <x-ui.empty-state icon="inbox" :title="$empty" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div wire:loading.class.remove="hidden" wire:target="search,perPage,goToPage"
        class="hidden absolute inset-x-0 top-1/2 z-10 flex -translate-y-1/2 items-center justify-center py-8">
        <svg class="h-6 w-6 animate-spin text-primary" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25" />
            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
        </svg>
    </div>

    @if ($items->total() > 0 || $items->hasPages())
        <div
            class="flex flex-col gap-3 border-t border-outline-variant bg-surface-container px-5 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="flex items-center gap-2 text-xs text-on-surface-variant">
                <span>Menampilkan {{ $items->firstItem() }}–{{ $items->lastItem() }} dari
                    {{ $items->total() }}</span>
            </div>
            <div class="flex items-center gap-1">
                @if ($items->hasPages())
                    @if ($items->onFirstPage())
                        <span
                            class="inline-flex items-center justify-center rounded-full text-on-surface-variant/40 cursor-not-allowed h-9 px-3.5 text-xs">&lsaquo;</span>
                    @else
                        <button type="button" wire:click="goToPage({{ $items->currentPage() - 1 }})"
                            class="inline-flex items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container h-9 px-3.5 text-xs">&lsaquo;</button>
                    @endif

                    @foreach (range(max(1, $items->currentPage() - 2), min($items->lastPage(), $items->currentPage() + 2)) as $p)
                        @if ($p === $items->currentPage())
                            <span
                                class="inline-flex items-center justify-center rounded-full bg-primary text-on-primary h-9 px-3.5 text-xs font-semibold">{{ $p }}</span>
                        @else
                            <button type="button" wire:click="goToPage({{ $p }})"
                                class="inline-flex items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container h-9 px-3.5 text-xs">{{ $p }}</button>
                        @endif
                    @endforeach

                    @if ($items->currentPage() === $items->lastPage())
                        <span
                            class="inline-flex items-center justify-center rounded-full text-on-surface-variant/40 cursor-not-allowed h-9 px-3.5 text-xs">&rsaquo;</span>
                    @else
                        <button type="button" wire:click="goToPage({{ $items->currentPage() + 1 }})"
                            class="inline-flex items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container h-9 px-3.5 text-xs">&rsaquo;</button>
                    @endif
                @endif
            </div>
        </div>
    @endif
</div>
