@extends('tenant.reports.layout-pdf')

@section('content')
@php
    $licensesList = $licenses ?? collect();
    $firstSuccess = null;
    foreach ($licensesList as $lic) {
        if (($payloads[$lic->id]['status'] ?? null) === 'success') { $firstSuccess = $payloads[$lic->id]; break; }
    }
    $rows = is_array($firstSuccess['data'] ?? null) ? $firstSuccess['data'] : [];

    $colKode = 7;
    $colNama = 32;
    $colNum = floor(61 / max(1, $licensesList->count()));

    $fmt = function ($v) {
        if ($v === null) return '—';
        $n = (float) $v;
        $abs = number_format(abs($n), 2, ',', '.');
        return $n < 0 ? "({$abs})" : $abs;
    };

    $findLev3 = function (array $data, $kode, $nama) {
        foreach ($data as $l1) {
            foreach (($l1['akun2'] ?? []) as $l2) {
                foreach (($l2['akun3'] ?? []) as $l3) {
                    if (($l3['kode_akun'] ?? null) === $kode && ($l3['nama_akun'] ?? null) === $nama) {
                        return $l3;
                    }
                }
            }
        }
        return null;
    };
    $findRek = function (array $data, $kodeLev1, $kodeLev2, $kodeLev3, $kodeRek, $namaRek) {
        foreach ($data as $l1) {
            if (($l1['kode_akun'] ?? null) !== $kodeLev1) continue;
            foreach (($l1['akun2'] ?? []) as $l2) {
                if (($l2['kode_akun'] ?? null) !== $kodeLev2) continue;
                foreach (($l2['akun3'] ?? []) as $l3) {
                    if (($l3['kode_akun'] ?? null) !== $kodeLev3) continue;
                    foreach (($l3['rekening'] ?? []) as $rk) {
                        if (($rk['kode_akun'] ?? null) === $kodeRek && ($rk['nama_akun'] ?? null) === $namaRek) {
                            return $rk;
                        }
                    }
                }
            }
        }
        return null;
    };
@endphp

@if(!$firstSuccess)
    <p style="text-align:center; padding: 40px; color: #c00;">Tidak ada aplikasi yang merespons untuk periode ini.</p>
@else
    <p class="pdf-entity">{{ strtoupper($tenant->name ?? 'Holding App') }}</p>
    <p class="pdf-title">Neraca — Komparatif</p>
    <p class="pdf-subtitle">{{ strtoupper($firstSuccess['sub_judul'] ?? $firstSuccess['periode']['sub_judul'] ?? $period) }}</p>
    <p class="pdf-meta">
        Periode: <strong>{{ $period }}</strong>
        @if(!empty($firstSuccess['kecamatan'])) &middot; {{ $firstSuccess['kecamatan'] }} @endif
    </p>

    <table class="pdf">
        <thead>
            <tr>
                <th class="center" style="width: {{ $colKode }}%;">Kode</th>
                <th style="width: {{ $colNama }}%;">Nama Akun</th>
                @foreach($licensesList as $lic)
                    <th class="num" style="width: {{ $colNum }}%;">
                        {{ $lic->label ?: $lic->application->name }}
                        @if(($payloads[$lic->id]['status'] ?? null) !== 'success')<br><span style="color:#fbb; font-weight:normal;">(offline)</span>@endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $lev1)
                <tr class="lev1">
                    <td colspan="{{ 2 + $licensesList->count() }}">{{ $lev1['kode_akun'] ?? '' }}. {{ $lev1['nama_akun'] ?? '' }}</td>
                </tr>
                @foreach(($lev1['akun2'] ?? []) as $lev2)
                    <tr class="lev2">
                        <td colspan="{{ 2 + $licensesList->count() }}">{{ $lev2['kode_akun'] ?? '' }}. {{ $lev2['nama_akun'] ?? '' }}</td>
                    </tr>
                    @foreach(($lev2['akun3'] ?? []) as $i => $lev3)
                        <tr class="lev3 {{ $i % 2 === 1 ? 'alt' : '' }}">
                            <td class="center">{{ $lev3['kode_akun'] ?? '' }}</td>
                            <td class="indent-2">{{ $lev3['nama_akun'] ?? '' }}</td>
                            @foreach($licensesList as $lic)
                                @php
                                    $pl = $payloads[$lic->id] ?? [];
                                    $l3 = $pl['status'] === 'success' ? $findLev3($pl['data'] ?? [], $lev3['kode_akun'] ?? null, $lev3['nama_akun'] ?? null) : null;
                                @endphp
                                <td class="num">{{ $l3 === null ? '—' : $fmt($l3['saldo'] ?? null) }}</td>
                            @endforeach
                        </tr>
                        @foreach(($lev3['rekening'] ?? []) as $rek)
                            <tr>
                                <td class="center">{{ $rek['kode_akun'] ?? '' }}</td>
                                <td class="indent-3" style="font-size: 10px;">{{ $rek['nama_akun'] ?? '' }}</td>
                                @foreach($licensesList as $lic)
                                    @php
                                        $pl = $payloads[$lic->id] ?? [];
                                        $rk = $pl['status'] === 'success' ? $findRek($pl['data'] ?? [], $lev1['kode_akun'] ?? null, $lev2['kode_akun'] ?? null, $lev3['kode_akun'] ?? null, $rek['kode_akun'] ?? null, $rek['nama_akun'] ?? null) : null;
                                    @endphp
                                    <td class="num" style="font-size: 10px;">{{ $rk === null ? '—' : $fmt($rk['saldo'] ?? null) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                @endforeach
                <tr class="subtotal">
                    <td colspan="2" class="indent-1">Jumlah {{ $lev1['nama_akun'] ?? '' }}</td>
                    @foreach($licensesList as $lic)
                        @php
                            $pl = $payloads[$lic->id] ?? [];
                            $licLev1 = null;
                            if ($pl['status'] === 'success') {
                                foreach (($pl['data'] ?? []) as $l1) {
                                    if (($l1['kode_akun'] ?? null) === ($lev1['kode_akun'] ?? null)) { $licLev1 = $l1; break; }
                                }
                            }
                        @endphp
                        <td class="num">{{ $licLev1 === null ? '—' : $fmt($licLev1['saldo'] ?? null) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ 2 + $licensesList->count() }}" class="center" style="padding: 20px; font-style: italic;">Tidak ada data untuk periode ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Jumlah Liabilitas + Ekuitas</td>
                @foreach($licensesList as $lic)
                    <td class="num">{{ $fmt($payloads[$lic->id]['ringkasan']['total_liabilitas_ekuitas'] ?? null) }}</td>
                @endforeach
            </tr>
        </tfoot>
    </table>
@endif
@endsection
