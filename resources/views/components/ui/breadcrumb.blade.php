@props(['items' => []])

{{-- items: [['label' => '...', 'href' => '...']] atau ['label' => '...'] untuk current --}}
<nav {{ $attributes->merge(['class' => 'flex items-center gap-2 text-xs text-on-surface-variant']) }} aria-label="Breadcrumb">
    @foreach($items as $i => $item)
        @if($i > 0)
        <x-ui.icon name="chevron-right" class="h-3.5 w-3.5 shrink-0" />
        @endif
        @if(isset($item['href']) && $i < count($items) - 1)
        <a href="{{ $item['href'] }}" class="hover:text-primary">{{ $item['label'] }}</a>
        @else
        <span class="text-on-surface font-medium">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
