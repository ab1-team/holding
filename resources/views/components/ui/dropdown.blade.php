<div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative inline-block text-left">
    <div @click="open = !open">
        {{ $trigger }}
    </div>
    <div x-show="open" x-cloak x-transition.opacity
         class="absolute z-30 mt-2 {{ $width }} {{ $alignmentClasses() }} rounded-2xl border border-outline-variant bg-surface-container-lowest p-1 shadow-elevated-lg focus:outline-none">
        {{ $slot }}
    </div>
</div>
