@php
    $roleVariants = ['superadmin' => 'info', 'tenant_owner' => 'success', 'tenant_staff' => 'warning'];
    $roleLabels = ['superadmin' => 'Vendor', 'tenant_owner' => 'Pemilik', 'tenant_staff' => 'Staff'];
@endphp
<x-ui.badge :variant="$roleVariants[$row->role] ?? 'neutral'">
    {{ $roleLabels[$row->role] ?? $row->role }}
</x-ui.badge>
