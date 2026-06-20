@extends('tenant.reports.layout-pdf')

@section('content')
@php
    $licensesList = $licenses ?? collect();
    $firstSuccess = null;
    foreach ($licensesList as $lic) {
        if (($payloads[$lic->id]['status'] ?? null) === 'success') { $firstSuccess = $payloads[$lic->id]; break; }
    }

    $sections = [
        'pendapatan' => '4. Pendapatan',
        'beban' => '5. Beban',
        'pendapatan_non_ops' => 'Pendapatan Non Operasional',
        'beban_non_ops' => 'Beban Non Operasional',
    ];

    $colRek = 22;
    $colPerLicense = floor(78 / max(1, $licensesList->count()));

    $computeRingkasan = function (array $payload) {
        $r = $payload['ringkasan'] ?? null;
        if (is_array($r) && isset($r['lr_operasional'])) {
            return $r;
        }
        $pendapatan = 0; $beban = 0; $pNo = 0; $bNo = 0;
        foreach (($payload['data']['pendapatan'] ?? []) as $row) { $pendapatan += (float) ($row['saldo'] ?? 0); }
        foreach (($payload['data']['beban'] ?? []) as $row) { $beban += (float) ($row['saldo'] ?? 0); }
        foreach (($payload['data']['pendapatan_non_ops'] ?? []) as $row) { $pNo += (float) ($row['saldo'] ?? 0); }
        foreach (($payload['data']['beban_non_ops'] ?? []) as $row) { $bNo += (float) ($row['saldo'] ?? 0); }
        $op = $pendapatan - $beban;
        $nop = $pNo - $bNo;
        $sebelum = $op + $nop;
        return [
            'lr_operasional' => $op,
            'lr_non_operasional' => $nop,
            'sebelum_pajak' => $sebelum,
            'pph' => 0,
            'setelah_pajak' => $sebelum,
        ];
    };
    $fmtNum = fn($v) => $v === null ? '—' : number_format((float) $v, 2, ',', '.');
@endphp

@if(!$firstSuccess)
    <p style="text-align:center; padding: 40px; color: #c00;">Tidak ada aplikasi yang merespons untuk periode ini.</p>
@else
    <p class="pdf-entity">{{ strtoupper($tenant->name ?? 'Holding App') }}</p>
    <p class="pdf-title">Laporan Laba Rugi — Komparatif</p>
    <p class="pdf-subtitle">{{ strtoupper($firstSuccess['sub_judul'] ?? $firstSuccess['periode']['sub_judul'] ?? $period) }}</p>
    <p class="pdf-meta">Periode: <strong>{{ $period }}</strong></p>

    <table class="pdf">
        <thead>
            <tr>
                <th style="width: {{ $colRek }}%;">Rekening</th>
                @foreach($licensesList as $lic)
                    <th class="num" style="width: {{ $colPerLicense }}%;">
                        {{ $lic->label ?: $lic->application->name }}
                        @if(($payloads[$lic->id]['status'] ?? null) !== 'success')<br><span style="color:#fbb; font-weight:normal;">(offline)</span>@endif
                    </th>
                @endforeach
            </tr>
            <tr style="background:#333;">
                <th></th>
                @foreach($licensesList as $lic)
                    <th class="num" style="font-weight:normal; font-size:8px; color:#fff;">s.d bln lalu<br>periode ini<br>s.d sekarang</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($sections as $key => $label)
                <tr class="section-header">
                    <td colspan="{{ 1 + $licensesList->count() }}">{{ $label }}</td>
                </tr>
                @php $sectionRows = $firstSuccess['data'][$key] ?? []; @endphp
                @forelse($sectionRows as $row)
                    <tr>
                        <td colspan="{{ 1 + $licensesList->count() }}"><strong>{{ $row['kode_akun'] ?? '' }}. {{ $row['nama_akun'] ?? '' }}</strong></td>
                    </tr>
                    @foreach(($row['rekening'] ?? []) as $rek)
                        <tr>
                            <td class="indent-1">{{ $rek['kode_akun'] ?? '' }}. {{ $rek['nama_akun'] ?? '' }}</td>
                            @foreach($licensesList as $lic)
                                @php
                                    $pl = $payloads[$lic->id] ?? [];
                                    $licRow = null;
                                    if ($pl['status'] === 'success') {
                                        foreach (($pl['data'][$key] ?? []) as $r) {
                                            if (($r['kode_akun'] ?? null) === ($row['kode_akun'] ?? null)) {
                                                foreach (($r['rekening'] ?? []) as $rk) {
                                                    if (($rk['kode_akun'] ?? null) === ($rek['kode_akun'] ?? null) && ($rk['nama_akun'] ?? null) === ($rek['nama_akun'] ?? null)) {
                                                        $licRow = $rk; break 2;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    $saldo = $licRow ? (float) ($licRow['saldo'] ?? 0) : null;
                                    $saldoLalu = $licRow ? (float) ($licRow['saldo_bln_lalu'] ?? 0) : null;
                                    $periodeIni = ($saldo !== null && $saldoLalu !== null) ? $saldo - $saldoLalu : null;
                                @endphp
                                <td class="num">
                                    @if($licRow === null) —
                                    @else
                                        {{ $fmtNum($saldoLalu) }}<br>
                                        {{ $fmtNum($periodeIni) }}<br>
                                        <strong>{{ $fmtNum($saldo) }}</strong>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    <tr class="subtotal">
                        <td class="indent-1">Jumlah {{ $row['kode_akun'] ?? '' }}. {{ $row['nama_akun'] ?? '' }}</td>
                        @foreach($licensesList as $lic)
                            @php
                                $pl = $payloads[$lic->id] ?? [];
                                $sumLalu = 0; $sumSaldo = 0; $found = false;
                                if ($pl['status'] === 'success') {
                                    foreach (($pl['data'][$key] ?? []) as $r) {
                                        if (($r['kode_akun'] ?? null) === ($row['kode_akun'] ?? null)) {
                                            $found = true;
                                            foreach (($r['rekening'] ?? []) as $rk) {
                                                $sumLalu += (float) ($rk['saldo_bln_lalu'] ?? 0);
                                                $sumSaldo += (float) ($rk['saldo'] ?? 0);
                                            }
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            <td class="num">
                                @if(!$found) —
                                @else
                                    {{ $fmtNum($sumLalu) }}<br>
                                    {{ $fmtNum($sumSaldo - $sumLalu) }}<br>
                                    <strong>{{ $fmtNum($sumSaldo) }}</strong>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ 1 + $licensesList->count() }}" class="center" style="padding:6px; font-style:italic; color:#666;">— tidak ada rekening —</td></tr>
                @endforelse
            @endforeach

            @php
                $ringkasanRows = [
                    'lr_operasional' => 'A. Laba Rugi Operasional',
                    'lr_non_operasional' => 'B. Laba Rugi Non Operasional',
                    'sebelum_pajak' => 'C. Sebelum Pajak (A + B)',
                    'pph' => 'PPh',
                    'setelah_pajak' => 'Setelah Pajak (C − PPh)',
                ];
            @endphp
            @foreach($ringkasanRows as $rk => $label)
                <tr class="subtotal">
                    <td>{{ $label }}</td>
                    @foreach($licensesList as $lic)
                        @php
                            $pl = $payloads[$lic->id] ?? [];
                            $r = $pl['status'] === 'success' ? $computeRingkasan($pl) : [];
                            $val = $r[$rk] ?? null;
                        @endphp
                        <td class="num">
                            @if($val === null) —
                            @elseif(is_array($val))
                                {{ $fmtNum($val['s_d_bulan_lalu'] ?? 0) }}<br>
                                {{ $fmtNum($val['periode_ini'] ?? 0) }}<br>
                                <strong>{{ $fmtNum($val['s_d_sekarang'] ?? 0) }}</strong>
                            @else
                                <strong>{{ $fmtNum($val) }}</strong>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
@endsection
