@php
    $actionVariants = ['login' => 'info', 'logout' => 'neutral', 'access_app' => 'success', 'create' => 'success', 'update' => 'warning', 'delete' => 'error'];
@endphp
<x-ui.badge :variant="$actionVariants[$row->action] ?? 'neutral'">
    {{ $row->action }}
</x-ui.badge>
