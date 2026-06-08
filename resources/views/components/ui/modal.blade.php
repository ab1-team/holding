<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" @dispatch.window="{{ $id }}-open="open = true" @dispatch.window="{{ $id }}-close="open = false" class="contents">
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div x-show="open" x-transition.opacity @click="open = false" class="absolute inset-0 bg-black/50"></div>
        <div x-show="open" x-transition @click.stop class="relative w-full {{ $sizeClasses() }} overflow-hidden rounded-2xl bg-surface-container-lowest shadow-elevated-lg">
            @if($title)
            <div class="flex items-start justify-between gap-3 border-b border-outline-variant px-5 py-4 sm:px-6">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-on-surface">{{ $title }}</h2>
                    @if($subtitle)
                    <p class="mt-0.5 text-sm text-on-surface-variant">{{ $subtitle }}</p>
                    @endif
                </div>
                <button type="button" @click="open = false" class="-mr-1 -mt-1 shrink-0 rounded-full p-1.5 text-on-surface-variant hover:bg-surface-container hover:text-on-surface">
                    <x-ui.icon name="x-mark" class="h-5 w-5" />
                </button>
            </div>
            @endif
            <div class="p-5 sm:p-6">
                {{ $slot }}
            </div>
            @if(trim($footer ?? ''))
            <div class="flex flex-col-reverse justify-end gap-2 border-t border-outline-variant bg-surface-container px-5 py-3 sm:flex-row sm:px-6">
                {{ $footer }}
            </div>
            @endif
        </div>
    </div>
</div>
