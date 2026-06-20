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

    // Sum saldo + saldo_bln_lalu across licenses for a rekening leaf.
    $sumRekening = function ($kodeAkun, $namaAkun, $key) use ($licensesList, $payloads) {
        $totalLalu = 0; $totalSaldo = 0; $found = false;
        foreach ($licensesList as $lic) {
            $pl = $payloads[$lic->id] ?? null;
            if (! $pl || ($pl['status'] ?? null) !== 'success') continue;
            foreach (($pl['data'][$key] ?? []) as $row) {
                if (($row['kode_akun'] ?? null) === $kodeAkun) {
                    foreach (($row['rekening'] ?? []) as $rk) {
                        if (($rk['nama_akun'] ?? null) === $namaAkun) {
                            $totalLalu += (float) ($rk['saldo_bln_lalu'] ?? 0);
                            $totalSaldo += (float) ($rk['saldo'] ?? 0);
                            $found = true;
                        }
                    }
                }
            }
        }
        return $found ? ['lalu' => $totalLalu, 'saldo' => $totalSaldo] : null;
    };

    $sumRow = function ($kodeAkun, $key) use ($licensesList, $payloads) {
        $totalLalu = 0; $totalSaldo = 0; $found = false;
        foreach ($licensesList as $lic) {
            $pl = $payloads[$lic->id] ?? null;
            if (! $pl || ($pl['status'] ?? null) !== 'success') continue;
            foreach (($pl['data'][$key] ?? []) as $row) {
                if (($row['kode_akun'] ?? null) === $kodeAkun) {
                    foreach (($row['rekening'] ?? []) as $rk) {
                        $totalLalu += (float) ($rk['saldo_bln_lalu'] ?? 0);
                        $totalSaldo += (float) ($rk['saldo'] ?? 0);
                        $found = true;
                    }
                }
            }
        }
        return $found ? ['lalu' => $totalLalu, 'saldo' => $totalSaldo] : null;
    };

    // Ringkasan A/B/C/PPh — sum scalar or sum {s_d_bulan_lalu,periode_ini,s_d_sekarang} across licenses.
    $sumRingkasan = function (string $rk) use ($licensesList, $payloads) {
        $totals = ['lalu' => 0, 'periode' => 0, 'sekarang' => 0];
        $foundScalar = false;
        $scalarTotal = 0;
        foreach ($licensesList as $lic) {
            $pl = $payloads[$lic->id] ?? null;
            if (! $pl || ($pl['status'] ?? null) !== 'success') continue;
            $val = $pl['ringkasan'][$rk] ?? null;
            if ($val === null) continue;
            if (is_array($val)) {
                $totals['lalu'] += (float) ($val['s_d_bulan_lalu'] ?? 0);
                $totals['periode'] += (float) ($val['periode_ini'] ?? 0);
                $totals['sekarang'] += (float) ($val['s_d_sekarang'] ?? 0);
            } else {
                $foundScalar = true;
                $scalarTotal += (float) $val;
            }
        }
        if ($foundScalar) return $scalarTotal;
        return $totals;
    };

    $fmt = fn($v) => $v === null ? '—' : number_format((float) $v, 2, ',', '.');
    $subCount = 0;
    foreach ($licensesList as $lic) {
        if (($payloads[$lic->id]['status'] ?? null) === 'success') $subCount++;
    }
@endphp

@if(!$firstSuccess)
    <p style="text-align:center; padding: 40px; color: #c00;">Tidak ada aplikasi yang merespons untuk periode ini.</p>
@else
    <p class="pdf-entity">{{ strtoupper($tenant->name ?? 'Holding App') }}</p>
    <p class="pdf-title">Laporan Laba Rugi — Konsolidasi (Total)</p>
    <p class="pdf-subtitle">{{ strtoupper($firstSuccess['sub_judul'] ?? $firstSuccess['periode']['sub_judul'] ?? $period) }}</p>
    <p class="pdf-meta">Periode: <strong>{{ $period }}</strong> &middot; {{ $subCount }} subsidiary diagregasi</p>

    <table class="pdf">
        <thead>
            <tr>
                <th style="width: 50%;">Rekening</th>
                <th class="num" style="width: 16%;">s.d bln lalu</th>
                <th class="num" style="width: 17%;">periode ini</th>
                <th class="num" style="width: 17%;">s.d sekarang</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sections as $key => $label)
                <tr class="section-header">
                    <td colspan="4">{{ $label }}</td>
                </tr>
                @php $sectionRows = $firstSuccess['data'][$key] ?? []; @endphp
                @forelse($sectionRows as $row)
                    <tr>
                        <td colspan="4"><strong>{{ $row['kode_akun'] ?? '' }}. {{ $row['nama_akun'] ?? '' }}</strong></td>
                    </tr>
                    @foreach(($row['rekening'] ?? []) as $rek)
                        @php $sum = $sumRekening($row['kode_akun'] ?? null, $rek['nama_akun'] ?? null, $key); @endphp
                        <tr>
                            <td class="indent-1">{{ $rek['kode_akun'] ?? '' }}. {{ $rek['nama_akun'] ?? '' }}</td>
                            <td class="num">{{ $sum === null ? '—' : $fmt($sum['lalu']) }}</td>
                            <td class="num">{{ $sum === null ? '—' : $fmt($sum['saldo'] - $sum['lalu']) }}</td>
                            <td class="num">{{ $sum === null ? '—' : $fmt($sum['saldo']) }}</td>
                        </tr>
                    @endforeach
                    @php $sumRowTot = $sumRow($row['kode_akun'] ?? null, $key); @endphp
                    <tr class="subtotal">
                        <td class="indent-1">Jumlah {{ $row['kode_akun'] ?? '' }}. {{ $row['nama_akun'] ?? '' }}</td>
                        <td class="num">{{ $sumRowTot === null ? '—' : $fmt($sumRowTot['lalu']) }}</td>
                        <td class="num">{{ $sumRowTot === null ? '—' : $fmt($sumRowTot['saldo'] - $sumRowTot['lalu']) }}</td>
                        <td class="num">{{ $sumRowTot === null ? '—' : $fmt($sumRowTot['saldo']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="center" style="padding:6px; font-style:italic; color:#666;">— tidak ada rekening —</td></tr>
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
                @php $val = $sumRingkasan($rk); @endphp
                <tr class="subtotal">
                    <td>{{ $label }}</td>
                    @if(is_array($val))
                        <td class="num">{{ $fmt($val['lalu']) }}</td>
                        <td class="num">{{ $fmt($val['periode']) }}</td>
                        <td class="num"><strong>{{ $fmt($val['sekarang']) }}</strong></td>
                    @else
                        <td class="num">{{ $fmt(0) }}</td>
                        <td class="num">{{ $fmt($val) }}</td>
                        <td class="num"><strong>{{ $fmt($val) }}</strong></td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
@endsection
