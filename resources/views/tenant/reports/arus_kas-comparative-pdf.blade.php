@extends('tenant.reports.layout-pdf')

@section('content')
@php
    $licensesList = $licenses ?? collect();
    $firstSuccess = null;
    foreach ($licensesList as $lic) {
        if (($payloads[$lic->id]['status'] ?? null) === 'success') { $firstSuccess = $payloads[$lic->id]; break; }
    }
    $rows = $firstSuccess['data'] ?? [];

    $colAktivitas = 36;
    $colPerLicense = floor(64 / max(1, $licensesList->count()));

    $fmt = function ($v) {
        if ($v === null) return '—';
        $n = (float) $v;
        $abs = number_format(abs($n), 2, ',', '.');
        return $n < 0 ? "({$abs})" : $abs;
    };

    $findRow = function (array $data, $id) {
        foreach ($data as $r) {
            if (($r['id'] ?? null) === $id) return $r;
        }
        return null;
    };
@endphp

@if(!$firstSuccess)
    <p style="text-align:center; padding: 40px; color: #c00;">Tidak ada aplikasi yang merespons untuk periode ini.</p>
@else
    <p class="pdf-entity">{{ strtoupper($tenant->name ?? 'Holding App') }}</p>
    <p class="pdf-title">Laporan Arus Kas — Komparatif</p>
    <p class="pdf-subtitle">{{ strtoupper($firstSuccess['sub_judul'] ?? $firstSuccess['periode']['sub_judul'] ?? $period) }}</p>
    <p class="pdf-meta">Periode: <strong>{{ $period }}</strong></p>

    <table class="pdf">
        <thead>
            <tr>
                <th style="width: {{ $colAktivitas }}%;">Aktivitas / Rincian</th>
                @foreach($licensesList as $lic)
                    <th class="num" style="width: {{ $colPerLicense }}%;">
                        {{ $lic->label ?: $lic->application->name }}
                        @if(($payloads[$lic->id]['status'] ?? null) !== 'success')<br><span style="color:#fbb; font-weight:normal;">(offline)</span>@endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $ak)
                @php
                    $isSaldoAwal = (int) ($ak['id'] ?? 0) === 1;
                    $detail = $ak['detail'] ?? [];
                @endphp
                @if($isSaldoAwal)
                    <tr class="lev1">
                        <td colspan="{{ 1 + $licensesList->count() }}">{{ $ak['nama'] ?? 'Saldo Awal Kas' }}</td>
                    </tr>
                    <tr>
                        <td class="indent-1">Saldo per {{ $firstSuccess['tgl_kondisi'] ?? $firstSuccess['periode']['tgl_kondisi'] ?? $period }}</td>
                        @foreach($licensesList as $lic)
                            @php
                                $pl = $payloads[$lic->id] ?? [];
                                $licRow = $pl['status'] === 'success' ? $findRow($pl['data'] ?? [], 1) : null;
                            @endphp
                            <td class="num">{{ $licRow === null ? '—' : $fmt($licRow['saldo'] ?? null) }}</td>
                        @endforeach
                    </tr>
                @else
                    <tr class="lev2">
                        <td colspan="{{ 1 + $licensesList->count() }}">{{ $ak['nama'] ?? '' }}</td>
                    </tr>
                    <tr class="subtotal">
                        <td class="indent-1">Subtotal {{ $ak['nama'] ?? '' }}</td>
                        @foreach($licensesList as $lic)
                            @php
                                $pl = $payloads[$lic->id] ?? [];
                                $licRow = $pl['status'] === 'success' ? $findRow($pl['data'] ?? [], $ak['id'] ?? null) : null;
                            @endphp
                            <td class="num">{{ $licRow === null ? '—' : $fmt($licRow['saldo'] ?? null) }}</td>
                        @endforeach
                    </tr>
                    @foreach($detail as $child)
                        <tr>
                            <td class="indent-2">{{ $child['kode_akun'] ?? '' }} {{ $child['nama_akun'] ?? '' }}</td>
                            @foreach($licensesList as $lic)
                                @php
                                    $pl = $payloads[$lic->id] ?? [];
                                    $val = null;
                                    if ($pl['status'] === 'success') {
                                        $licAk = $findRow($pl['data'] ?? [], $ak['id'] ?? null);
                                        if ($licAk) {
                                            foreach (($licAk['detail'] ?? []) as $c) {
                                                if (($c['id'] ?? null) === ($child['id'] ?? null)) { $val = $c['saldo'] ?? null; break; }
                                            }
                                        }
                                    }
                                @endphp
                                <td class="num">{{ $val === null ? '—' : $fmt($val) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                @endif
            @empty
                <tr><td colspan="{{ 1 + $licensesList->count() }}" class="center" style="padding: 20px; font-style: italic;">Tidak ada data untuk periode ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            @php
                $summaries = [
                    'kas_operasi' => 'Kas Bersih Aktivitas Operasi',
                    'kas_investasi' => 'Kas Bersih Aktivitas Investasi',
                    'kas_pendanaan' => 'Kas Bersih Aktivitas Pendanaan',
                    'kenaikan_penurunan' => 'Kenaikan (Penurunan) Kas',
                    'saldo_akhir' => 'Saldo Akhir Kas',
                ];
            @endphp
            @foreach($summaries as $key => $label)
                <tr>
                    <td>{{ $label }}</td>
                    @foreach($licensesList as $lic)
                        <td class="num">{{ $fmt($payloads[$lic->id]['ringkasan'][$key] ?? null) }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tfoot>
    </table>
@endif
@endsection
