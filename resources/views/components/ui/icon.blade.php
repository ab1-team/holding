@props(['name', 'class' => 'h-5 w-5'])
@php
    $icon = new \App\View\Components\Icon($name, $class);
@endphp
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" {{ $attributes->merge(['class' => $class]) }} aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon->path() }}"/>
</svg>
