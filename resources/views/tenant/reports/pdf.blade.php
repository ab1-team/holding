<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportLabel }} - {{ $period }} - {{ $tenant->name }}</title>
    <style>
        @page { margin: 1.2cm 1cm; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10px; }
        h1 { font-size: 16px; margin: 0 0 4px 0; }
        .meta { color: #6b7280; font-size: 9px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; }
        th { background: #f3f4f6; text-align: left; font-weight: 600; font-size: 9px; text-transform: uppercase; }
        .num { text-align: right; font-family: monospace; }
        .code { font-family: monospace; color: #6b7280; font-size: 9px; }
        .total-row { background: #f3f4f6; font-weight: 600; }
        .offline { color: #b91c1c; font-style: italic; font-size: 8px; }
        footer { margin-top: 12px; color: #9ca3af; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $reportLabel }} Komparatif</h1>
    <div class="meta">
        <strong>Tenant:</strong> {{ $tenant->name }} &middot;
        <strong>Periode:</strong> {{ $period }} &middot;
        <strong>Dicetak:</strong> {{ now()->translatedFormat('d F Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 70px;">Kode</th>
                <th>Nama Akun</th>
                @foreach($comparative['columns'] as $col)
                <th class="num">
                    {{ $col['label'] }}
                    @if(!$col['available'])<br><span class="offline">(offline)</span>@endif
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($comparative['rows'] as $row)
            <tr>
                <td class="code">{{ $row['account_code'] ?: '—' }}</td>
                <td>{{ $row['account_name'] }}</td>
                @foreach($comparative['columns'] as $col)
                    @php $amount = $row['amounts'][$col['license_id']] ?? null; @endphp
                    <td class="num">{{ $amount === null ? '—' : 'Rp ' . number_format($amount, 0, ',', '.') }}</td>
                @endforeach
            </tr>
            @empty
            <tr>
                <td colspan="{{ 2 + count($comparative['columns']) }}" style="text-align: center; color: #6b7280; padding: 12px;">
                    Tidak ada data untuk periode ini.
                </td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" class="num">Total</td>
                @foreach($comparative['columns'] as $col)
                <td class="num">Rp {{ number_format($col['total'], 0, ',', '.') }}</td>
                @endforeach
            </tr>
        </tfoot>
    </table>

    <footer>
        Dokumen ini dihasilkan otomatis oleh Holding App.
    </footer>
</body>
</html>
