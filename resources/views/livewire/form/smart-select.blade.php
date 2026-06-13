<div class="relative" x-data="{ open: false, search: '', dropup: false }" @click.outside="open = false">
    @if($label)
    <label class="mb-1.5 block text-sm font-medium text-on-surface">
        {{ $label }}
        @if($required)<span class="text-error">*</span>@endif
    </label>
    @endif

    <button type="button" @click="open = !open; $nextTick(() => { const btn = $el; const panel = btn.parentElement.querySelector('[x-show]'); if (panel) { const rect = btn.getBoundingClientRect(); const spaceBelow = window.innerHeight - rect.bottom; dropup = spaceBelow < 220; } })"
            class="inline-flex w-full items-center justify-between gap-2 rounded-full border bg-surface-container-lowest px-4 h-10 text-sm text-left transition focus:ring-2 focus:outline-none min-w-0 {{ $error ? 'border-error focus:border-error focus:ring-error/30' : 'border-outline focus:border-primary focus:ring-primary/30' }}">
        <span class="truncate" :class="!@js($this->selectedLabel) ? 'text-on-surface-variant' : 'text-on-surface'">
            {{ $this->selectedLabel ?? $placeholder }}
        </span>
        <span class="flex items-center gap-1 shrink-0">
            @if($clearable && $value)
            <span @click.stop="$wire.clear()" class="rounded-full p-0.5 hover:bg-surface-container cursor-pointer">
                <x-ui.icon name="x-mark" class="h-4 w-4 text-on-surface-variant" />
            </span>
            @endif
            <x-ui.icon name="chevron-down" class="h-4 w-4 text-on-surface-variant transition-transform" x-bind:class="open && dropup ? 'rotate-180' : ''" />
        </span>
    </button>

    <div x-show="open" x-cloak x-transition.opacity
         :class="dropup ? 'bottom-full mb-2' : 'top-full mt-2'"
         class="absolute left-0 z-50 w-full overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-elevated-lg">
        @if($searchable)
        <div class="border-b border-outline-variant p-2">
            <div class="relative">
                <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-on-surface-variant" />
                <input x-model="search" type="text" placeholder="{{ $searchPlaceholder }}"
                       class="block w-full rounded-full border border-outline bg-surface-container-lowest pl-9 pr-3 py-1.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
            </div>
        </div>
        @endif
        <ul class="max-h-60 overflow-y-auto p-1">
            @foreach($options as $key => $label)
            @php $keyStr = (string) $key; @endphp
            <li x-show="!search || @js(strtolower($label)).includes(search.toLowerCase())">
                <button type="button" wire:click="select('{{ $keyStr }}')" @click="open = false; search = ''"
                        class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-sm transition {{ (string) $value === $keyStr ? 'bg-primary-container text-on-primary-container' : 'text-on-surface hover:bg-surface-container' }}">
                    <span class="truncate">{{ $label }}</span>
                    @if((string) $value === $keyStr)
                    <x-ui.icon name="check" class="h-4 w-4 shrink-0" />
                    @endif
                </button>
            </li>
            @endforeach
            <li x-show="!search && !@js(count($options)) || (search && !@js(count($options)))" class="px-3 py-4 text-center text-sm text-on-surface-variant">Tidak ada hasil.</li>
        </ul>
    </div>

    @if($hint && !$error)
    <p class="mt-1.5 text-xs text-on-surface-variant">{{ $hint }}</p>
    @endif
    @if($error)
    <p class="mt-1.5 text-xs text-error">{{ $error }}</p>
    @endif
</div>