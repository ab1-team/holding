@extends('tenant.reports.layout-pdf')

@section('content')
@php
    $licensesList = $licenses ?? collect();
    $firstSuccess = null;
    foreach ($licensesList as $lic) {
        if (($payloads[$lic->id]['status'] ?? null) === 'success') { $firstSuccess = $payloads[$lic->id]; break; }
    }
    $rows = $firstSuccess['data'] ?? [];

    $fmt = fn($v) => $v === null ? '—' : number_format((float) $v, 2, ',', '.');

    $sumRow = function ($kode, $nama) use ($licensesList, $payloads) {
        $totals = ['saldo_awal' => 0, 'mutasi' => 0, 'saldo_akhir' => 0]; $found = false;
        foreach ($licensesList as $lic) {
            $pl = $payloads[$lic->id] ?? null;
            if (! $pl || ($pl['status'] ?? null) !== 'success') continue;
            foreach (($pl['data'] ?? []) as $r) {
                if (($r['kode_akun'] ?? null) === $kode && ($r['nama_akun'] ?? null) === $nama) {
                    $totals['saldo_awal'] += (float) ($r['saldo_awal'] ?? 0);
                    $totals['mutasi'] += (float) ($r['mutasi'] ?? 0);
                    $totals['saldo_akhir'] += (float) ($r['saldo_akhir'] ?? 0);
                    $found = true; break;
                }
            }
        }
        return $found ? $totals : null;
    };

    $sumRingkasan = function (string $key) use ($licensesList, $payloads) {
        $total = 0; $found = false;
        foreach ($licensesList as $lic) {
            $pl = $payloads[$lic->id] ?? null;
            if (! $pl || ($pl['status'] ?? null) !== 'success') continue;
            $val = $pl['ringkasan'][$key] ?? null;
            if ($val !== null) { $total += (float) $val; $found = true; }
        }
        return $found ? $total : null;
    };

    $subCount = 0;
    foreach ($licensesList as $lic) {
        if (($payloads[$lic->id]['status'] ?? null) === 'success') $subCount++;
    }
@endphp

@if(!$firstSuccess)
    <p style="text-align:center; padding: 40px; color: #c00;">Tidak ada aplikasi yang merespons untuk periode ini.</p>
@else
    <p class="pdf-entity">{{ strtoupper($tenant->name ?? 'Holding App') }}</p>
    <p class="pdf-title">Laporan Perubahan Ekuitas — Konsolidasi (Total)</p>
    <p class="pdf-subtitle">{{ strtoupper($firstSuccess['sub_judul'] ?? $firstSuccess['periode']['sub_judul'] ?? $period) }}</p>
    <p class="pdf-meta">Periode: <strong>{{ $period }}</strong> &middot; {{ $subCount }} subsidiary diagregasi</p>

    <table class="pdf">
        <thead>
            <tr>
                <th class="center" style="width: 5%;">No</th>
                <th style="width: 35%;">Rekening Modal</th>
                <th class="num" style="width: 20%;">Saldo Awal</th>
                <th class="num" style="width: 20%;">Mutasi</th>
                <th class="num" style="width: 20%;">Saldo Akhir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
                @php $sum = $sumRow($row['kode_akun'] ?? null, $row['nama_akun'] ?? null); @endphp
                <tr class="lev3 {{ $i % 2 === 1 ? 'alt' : '' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $row['kode_akun'] ?? '' }} {{ $row['nama_akun'] ?? '' }}</td>
                    <td class="num">{{ $sum === null ? '—' : $fmt($sum['saldo_awal']) }}</td>
                    <td class="num">{{ $sum === null ? '—' : $fmt($sum['mutasi']) }}</td>
                    <td class="num"><strong>{{ $sum === null ? '—' : $fmt($sum['saldo_akhir']) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="5" class="center" style="padding: 20px; font-style: italic;">Tidak ada data untuk periode ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total Ekuitas (Konsolidasi)</td>
                <td class="num">{{ $fmt($sumRingkasan('ekuitas_awal')) }}</td>
                <td class="num">{{ $fmt($sumRingkasan('ekuitas_akhir') !== null ? ($sumRingkasan('ekuitas_akhir') - $sumRingkasan('ekuitas_awal')) : null) }}</td>
                <td class="num"><strong>{{ $fmt($sumRingkasan('ekuitas_akhir')) }}</strong></td>
            </tr>
        </tfoot>
    </table>
@endif
@endsection
