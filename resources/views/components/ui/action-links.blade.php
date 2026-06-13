@props(['links' => []])
{{-- links: [['label' => '...', 'href' => '...', 'variant' => 'primary|muted|danger']] --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 text-sm font-medium']) }}>
    @foreach($links as $i => $link)
        @if($i > 0)<span class="text-outline-variant">·</span>@endif
        <a href="{{ $link['href'] }}" class="{{ match($link['variant'] ?? 'muted') {
            'primary' => 'text-primary hover:underline',
            'danger' => 'text-error hover:underline',
            default => 'text-on-surface-variant hover:text-on-surface',
        } }}">{{ $link['label'] }}</a>
    @endforeach
</span>
