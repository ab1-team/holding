<div x-data="{ open: true }" x-show="open" x-transition.opacity
     {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-2xl border px-4 py-3 shadow-elevated ' . $containerClasses()]) }}>
    <x-ui.icon :name="$iconName()" class="h-5 w-5 shrink-0 mt-0.5" />
    <div class="flex-1 min-w-0">
        @if($title)
        <p class="text-sm font-semibold">{{ $title }}</p>
        @endif
        <div class="text-sm {{ $title ? 'mt-0.5' : '' }}">{{ $slot }}</div>
    </div>
    @if($dismissible)
    <button type="button" @click="open = false" class="shrink-0 rounded-full p-1 hover:bg-black/5">
        <x-ui.icon name="x-mark" class="h-4 w-4" />
    </button>
    @endif
</div>
