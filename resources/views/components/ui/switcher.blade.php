<div x-data="{ on: @js($isChecked()) }" {{ $attributes->only('class')->merge(['class' => 'flex items-start gap-3']) }}>
    <input type="hidden" name="{{ $name }}" value="{{ $offValue }}">
    <label for="switch-{{ $name }}" class="flex cursor-pointer items-center">
        <input
            type="checkbox"
            id="switch-{{ $name }}"
            name="{{ $name }}"
            value="{{ $onValue }}"
            x-model="on"
            class="sr-only"
        >
        <span
            :class="on ? 'bg-primary' : 'bg-outline'"
            class="{{ $trackSize() }} inline-flex items-center rounded-full transition-colors">
            <span
                :class="on
                    ? (@js($size) === 'sm' ? 'translate-x-4' : (@js($size) === 'lg' ? 'translate-x-7' : 'translate-x-5'))
                    : 'translate-x-0.5'"
                class="{{ match($size) { 'sm' => 'h-4 w-4', 'lg' => 'h-6 w-6', default => 'h-5 w-5' } }} inline-block rounded-full bg-surface shadow-elevated transition-transform">
            </span>
        </span>
    </label>
    @if($label || $description)
    <div class="min-w-0 flex-1 pt-0.5">
        @if($label)
        <label for="switch-{{ $name }}" class="block text-sm font-medium text-on-surface cursor-pointer">{{ $label }}</label>
        @endif
        @if($description)
        <p class="mt-0.5 text-xs text-on-surface-variant">{{ $description }}</p>
        @endif
    </div>
    @endif
</div>
