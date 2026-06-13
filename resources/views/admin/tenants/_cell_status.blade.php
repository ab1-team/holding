<x-ui.status-badge :status="$row->is_active">
    {{ $row->is_active ? 'Aktif' : 'Nonaktif' }}
</x-ui.status-badge>
