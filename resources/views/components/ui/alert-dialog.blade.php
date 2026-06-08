<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" @dispatch.window="{{ $id }}-open="open = true" class="contents">
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div x-show="open" x-transition.opacity @click="open = false" class="absolute inset-0 bg-black/50"></div>
        <div x-show="open" x-transition @click.stop class="relative w-full max-w-sm overflow-hidden rounded-2xl bg-surface-container-lowest shadow-elevated-lg">
            <div class="px-5 py-5 text-center sm:px-6">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full {{ $iconBg() }}">
                    <x-ui.icon :name="$iconName()" class="h-6 w-6" />
                </div>
                @if($title)
                <h2 class="text-base font-semibold text-on-surface">{{ $title }}</h2>
                @endif
                @if($message)
                <p class="mt-1.5 text-sm text-on-surface-variant">{{ $message }}</p>
                @endif
            </div>
            <div class="flex justify-end border-t border-outline-variant bg-surface-container px-5 py-3 sm:px-6">
                <button type="button" @click="open = false" class="rounded-full bg-primary px-5 py-2 text-sm font-semibold text-on-primary hover:bg-indigo-700 transition">{{ $okLabel }}</button>
            </div>
        </div>
    </div>
</div>
