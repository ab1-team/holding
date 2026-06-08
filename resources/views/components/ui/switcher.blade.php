<div {{ $attributes->only('class')->merge(['class' => 'flex items-start gap-3']) }}>
    <input type="hidden" name="{{ $name }}" value="{{ $offValue }}">
    <label for="switch-{{ $name }}" class="flex cursor-pointer items-center">
        <input
            type="checkbox"
            id="switch-{{ $name }}"
            name="{{ $name }}"
            value="{{ $onValue }}"
            @checked($isChecked())
            class="peer sr-only"
        >
        <span class="{{ $trackSize() }} {{ $trackClasses() }} inline-flex items-center rounded-full transition-colors">
            <span class="{{ $thumbClasses() }} inline-block rounded-full bg-surface shadow-elevated"></span>
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
