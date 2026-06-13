@props(['trigger' => null])
<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" class="contents">
    <span @click="open = true" class="contents">
        @if($trigger)
            {{ $trigger }}
        @else
            {{ $slot }}
        @endif
    </span>
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div x-show="open" x-transition.opacity @click="open = false" class="absolute inset-0 bg-black/50"></div>
        <div x-show="open" x-transition @click.stop class="relative w-full max-w-md overflow-hidden rounded-2xl bg-surface-container-lowest shadow-elevated-lg">
            <div class="px-5 py-5 sm:px-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full {{ $variant === 'danger' ? 'bg-error-container text-error' : 'bg-primary-container text-primary' }}">
                        <x-ui.icon name="{{ $variant === 'danger' ? 'exclamation' : 'info' }}" class="h-6 w-6" />
                    </div>
                    <div class="min-w-0 flex-1">
                        @if($title)
                        <h2 class="text-base font-semibold text-on-surface">{{ $title }}</h2>
                        @endif
                        @if($message)
                        <p class="mt-1.5 text-sm text-on-surface-variant">{{ $message }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex flex-col-reverse justify-end gap-2 border-t border-outline-variant bg-surface-container px-5 py-3 sm:flex-row sm:px-6">
                <button type="button" @click="open = false" class="rounded-full border border-outline bg-surface-container-lowest px-5 py-2 text-sm font-semibold text-on-surface-variant hover:bg-surface-container transition">{{ $cancelLabel }}</button>
                @if($action)
                <form method="POST" action="{{ $action }}" class="inline">
                    @csrf
                    @if($method !== 'POST') @method($method) @endif
                    <button type="submit" class="rounded-full px-5 py-2 text-sm font-semibold transition {{ $buttonClasses() }}">{{ $confirmLabel }}</button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>