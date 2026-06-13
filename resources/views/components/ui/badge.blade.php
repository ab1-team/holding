@props([
    'variant' => 'neutral',
    'icon' => null,
    'size' => 'md',
])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full font-semibold ' . $variantClasses() . ' ' . $sizeClasses()]) }}>
    @if($icon)
    <x-ui.icon :name="$icon" class="h-3 w-3" />
    @endif
    {{ $slot }}
</span>
