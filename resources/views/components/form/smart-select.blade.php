@php
    $selectedLabel = $selectedLabel();
    $initJson = json_encode([
        'open' => false,
        'value' => (string) $resolvedValue(),
        'label' => $selectedLabel,
        'search' => '',
        'dropup' => false,
        'options' => $options,
    ], JSON_UNESCAPED_UNICODE);
@endphp
<div x-data='@php echo $initJson; @endphp'
     x-on:click.outside="open = false"
     {{ $attributes->merge(['class' => 'relative w-full'])}}>
    @if($label)
    <label class="mb-1.5 block text-sm font-medium text-on-surface">
        {{ $label }}
        @if($required)<span class="text-error">*</span>@endif
    </label>
    @endif

    <input type="hidden" name="{{ $name }}" :value="value">

    <button type="button" x-on:click="open = !open; $nextTick(() => { const btn = $el; const panel = btn.parentElement.querySelector('[x-show]'); if (panel) { const rect = btn.getBoundingClientRect(); const spaceBelow = window.innerHeight - rect.bottom; dropup = spaceBelow < 220; } })"
            class="inline-flex w-full items-center justify-between gap-2 rounded-full border bg-surface-container-lowest px-4 h-10 text-sm text-left transition focus:ring-2 focus:outline-none min-w-0 {{ $hasError() ? 'border-error focus:border-error focus:ring-error/30' : 'border-outline focus:border-primary focus:ring-primary/30' }}">
        <span x-text="label || '{{ $placeholder }}'" :class="!label ? 'text-on-surface-variant' : 'text-on-surface'" class="truncate"></span>
        <span class="flex items-center gap-1 shrink-0">
            @if($clearable)
            <span x-show="value" x-on:click.stop="value=''; label=null" class="rounded-full p-0.5 hover:bg-surface-container">
                <x-ui.icon name="x-mark" class="h-4 w-4 text-on-surface-variant" />
            </span>
            @endif
            <x-ui.icon name="chevron-down" class="h-4 w-4 text-on-surface-variant transition-transform" x-bind:class="open && dropup ? 'rotate-180' : ''" />
        </span>
    </button>

    <div x-show="open" x-cloak x-transition.opacity
         :class="dropup ? 'bottom-full mb-2' : 'top-full mt-2'"
         class="absolute left-0 z-50 w-full overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-elevated-lg">
        <div class="border-b border-outline-variant p-2">
            <div class="relative">
                <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-on-surface-variant" />
                <input x-model="search" type="text" placeholder="{{ $searchPlaceholder }}" class="block w-full rounded-full border border-outline bg-surface-container-lowest pl-9 pr-3 py-1.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
            </div>
        </div>
        <ul class="max-h-60 overflow-y-auto p-1">
            <template x-for="[key, lbl] in Object.entries(options).filter(([k, v]) => !search || v.toLowerCase().includes(search.toLowerCase()))" :key="key">
                <li>
                    <button type="button" x-on:click="value=String(key); label=lbl; open=false; search=''" :class="value === String(key) ? 'bg-primary-container text-on-primary-container' : 'text-on-surface hover:bg-surface-container'" class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-sm transition">
                        <span x-text="lbl" class="truncate"></span>
                        <x-ui.icon x-show="value === String(key)" name="check" class="h-4 w-4 shrink-0" />
                    </button>
                </li>
            </template>
            <li x-show="Object.entries(options).filter(([k, v]) => !search || v.toLowerCase().includes(search.toLowerCase())).length === 0" class="px-3 py-4 text-center text-sm text-on-surface-variant">Tidak ada hasil.</li>
        </ul>
    </div>

    @if($hint && !$hasError())
    <p class="mt-1.5 text-xs text-on-surface-variant">{{ $hint }}</p>
    @endif
    @if($hasError())
    <p class="mt-1.5 text-xs text-error">{{ $errorMessage() }}</p>
    @endif
</div>
