<x-ui.badge :variant="$row->has_financial_report ? 'info' : 'neutral'">
    {{ $row->has_financial_report ? 'Aktif' : 'Tidak' }}
</x-ui.badge>
