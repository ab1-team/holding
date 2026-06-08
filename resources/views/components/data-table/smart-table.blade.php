@php
    $hasHeader = $title || $subtitle || $overline || trim($actions ?? '');
    $currentPerPage = (int) request('per_page', $items->perPage());
@endphp
<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl bg-surface-container-lowest shadow-elevated']) }} x-data="{ loading: false }" x-init="$el.addEventListener('submit', () => loading = true)">
    @if($hasHeader || $searchable)
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
            @if(trim($actions ?? ''))
            {{ $actions }}
            @endif
            @if($searchable)
            <form method="GET" action="{{ url()->current() }}" class="flex items-center gap-2">
                @foreach(request()->except(['search', 'page', 'per_page']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <div class="relative">
                    <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-on-surface-variant" />
                    <input type="search" name="search" value="{{ $search }}" placeholder="{{ $searchPlaceholder }}" autocomplete="off"
                           @input.debounce.400ms="$event.target.form.submit()"
                           class="block w-48 rounded-full border border-outline bg-surface-container-lowest pl-9 pr-3 py-1.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none sm:w-64">
                    <button x-show="loading" type="button" class="absolute right-3 top-1/2 -translate-y-1/2 hidden" x-cloak>
                        <svg class="h-4 w-4 animate-spin text-on-surface-variant" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"/><path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    </button>
                </div>
                @if($search)
                <a href="{{ url()->current() }}?{{ http_build_query(array_diff_key(request()->query(), ['search' => '', 'page' => ''])) }}" class="rounded-full bg-surface-container px-3 py-1.5 text-xs font-semibold text-on-surface-variant hover:bg-surface-container-high transition">Reset</a>
                @else
                <button type="submit" class="rounded-full bg-surface-container px-3 py-1.5 text-xs font-semibold text-on-surface hover:bg-surface-container-high transition">Cari</button>
                @endif
            </form>
            @endif
        </div>
    </div>
    @endif

    <div class="overflow-x-auto" :class="loading ? 'opacity-60 transition-opacity' : ''">
        <table class="min-w-full divide-y divide-outline-variant">
            <thead class="bg-surface-container">
                <tr>
                    @foreach($columns as $col)
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6 {{ $col['align'] ?? '' === 'right' ? 'text-right' : ($col['align'] ?? '' === 'center' ? 'text-center' : '') }}">
                        {{ $col['label'] }}
                    </th>
                    @endforeach
                    @if($rowActions)
                    <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant sm:px-6">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($items as $row)
                <tr class="hover:bg-surface-container transition">
                    @foreach($columns as $col)
                    <td class="whitespace-nowrap px-5 py-3.5 text-sm text-on-surface sm:px-6 {{ $col['align'] ?? '' === 'right' ? 'text-right' : ($col['align'] ?? '' === 'center' ? 'text-center' : '') }}">
                        @if(isset($col['format']) && is_callable($col['format']))
                            {!! $col['format']($row) !!}
                        @elseif(isset($col['key']))
                            {{ data_get($row, $col['key']) }}
                        @else
                            {{ $row->{$col['field']} ?? '' }}
                        @endif
                    </td>
                    @endforeach
                    @if($rowActions)
                    <td class="whitespace-nowrap px-5 py-3.5 text-right text-sm font-medium sm:px-6">
                        {!! $rowActions($row) !!}
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($columns) + ($rowActions ? 1 : 0) }}" class="px-5 py-12 text-center text-sm text-on-surface-variant sm:px-6">
                        {{ $empty }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($items->total() > 0 || $items->hasPages())
    <div class="flex flex-col gap-3 border-t border-outline-variant bg-surface-container px-5 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex items-center gap-2 text-xs text-on-surface-variant">
            <span>Menampilkan {{ $items->firstItem() }}–{{ $items->lastItem() }} dari {{ $items->total() }}</span>
            <form method="GET" action="{{ url()->current() }}" class="ml-3 flex items-center gap-1.5">
                @foreach(request()->except(['page', 'per_page']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <label for="per_page" class="text-xs">Per halaman:</label>
                <select name="per_page" id="per_page" @change="$event.target.form.submit()" class="rounded-md border border-outline bg-surface-container-lowest px-2 py-0.5 text-xs focus:border-primary focus:ring-1 focus:ring-primary/30 focus:outline-none">
                    @foreach($perPageOptions as $opt)
                    <option value="{{ $opt }}" {{ $currentPerPage === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div>{{ $items->links() }}</div>
    </div>
    @endif
</div>