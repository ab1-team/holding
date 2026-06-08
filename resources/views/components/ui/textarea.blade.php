<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    @if($label)
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-on-surface">
        {{ $label }}
        @if($required)<span class="text-error">*</span>@endif
    </label>
    @endif
    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        rows="{{ $rows }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($required) required @endif
        {{ $attributes->except('class')->merge(['class' => 'block w-full rounded-lg border bg-surface-container-lowest px-3.5 py-2.5 text-sm text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:outline-none transition ' . ($hasError() ? 'border-error focus:border-error focus:ring-error/30' : 'border-outline focus:border-primary focus:ring-primary/30')]) }}
    >{{ $resolvedValue() }}</textarea>
    @if($hint && !$hasError())
    <p class="mt-1.5 text-xs text-on-surface-variant">{{ $hint }}</p>
    @endif
    @if($hasError())
    <p class="mt-1.5 text-xs text-error">{{ $errorMessage() }}</p>
    @endif
</div>
