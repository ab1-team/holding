@extends('tenant.reports.layout-pdf')

@section('content')
@php
    $licensesList = $licenses ?? collect();
    $firstSuccess = null;
    foreach ($licensesList as $lic) {
        if (($payloads[$lic->id]['status'] ?? null) === 'success') { $firstSuccess = $payloads[$lic->id]; break; }
    }
    $rows = $firstSuccess['data'] ?? [];

    $colKode = 7;
    $colRek = 25;
    $colPerLicense = floor(68 / max(1, $licensesList->count()));

    $fmt = fn($v) => $v === null ? '—' : number_format((float) $v, 2, ',', '.');
@endphp

@if(!$firstSuccess)
    <p style="text-align:center; padding: 40px; color: #c00;">Tidak ada aplikasi yang merespons untuk periode ini.</p>
@else
    <p class="pdf-entity">{{ strtoupper($tenant->name ?? 'Holding App') }}</p>
    <p class="pdf-title">Laporan Perubahan Ekuitas — Komparatif</p>
    <p class="pdf-subtitle">{{ strtoupper($firstSuccess['sub_judul'] ?? $firstSuccess['periode']['sub_judul'] ?? $period) }}</p>
    <p class="pdf-meta">Periode: <strong>{{ $period }}</strong></p>

    <table class="pdf">
        <thead>
            <tr>
                <th class="center" style="width: {{ $colKode }}%;">Kode</th>
                <th style="width: {{ $colRek }}%;">Rekening Modal</th>
                @foreach($licensesList as $lic)
                    <th class="num" style="width: {{ $colPerLicense }}%;">
                        {{ $lic->label ?: $lic->application->name }}
                        @if(($payloads[$lic->id]['status'] ?? null) !== 'success')<br><span style="color:#fbb; font-weight:normal;">(offline)</span>@endif
                    </th>
                @endforeach
            </tr>
            <tr style="background:#333;">
                <th colspan="2" style="font-size:8px; font-weight:normal; color:#fff; text-align:right;">Format per kolom:</th>
                @foreach($licensesList as $lic)
                    <th class="num" style="font-weight:normal; font-size:8px; color:#fff;">Saldo Awal<br>Mutasi<br>Saldo Akhir</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
                <tr class="{{ $i % 2 === 1 ? 'lev3 alt' : 'lev3' }}">
                    <td class="center">{{ $row['kode_akun'] ?? '' }}</td>
                    <td>{{ $row['nama_akun'] ?? '' }}</td>
                    @foreach($licensesList as $lic)
                        @php
                            $pl = $payloads[$lic->id] ?? [];
                            $licRow = null;
                            if ($pl['status'] === 'success') {
                                foreach (($pl['data'] ?? []) as $r) {
                                    if (($r['kode_akun'] ?? null) === ($row['kode_akun'] ?? null) && ($r['nama_akun'] ?? null) === ($row['nama_akun'] ?? null)) { $licRow = $r; break; }
                                }
                            }
                        @endphp
                        <td class="num">
                            @if($licRow === null) —
                            @else
                                {{ $fmt($licRow['saldo_awal'] ?? 0) }}<br>
                                {{ $fmt($licRow['mutasi'] ?? 0) }}<br>
                                <strong>{{ $fmt($licRow['saldo_akhir'] ?? 0) }}</strong>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ 2 + $licensesList->count() }}" class="center" style="padding: 20px; font-style: italic;">Tidak ada data untuk periode ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total Ekuitas (per ringkasan.holding)</td>
                @foreach($licensesList as $lic)
                    @php
                        $r = $payloads[$lic->id]['ringkasan'] ?? [];
                        $saldoAwal = (float) ($r['ekuitas_awal'] ?? 0);
                        $saldoAkhir = (float) ($r['ekuitas_akhir'] ?? 0);
                        $mutasi = $saldoAkhir - $saldoAwal;
                    @endphp
                    <td class="num">
                        {{ $fmt($saldoAwal) }}<br>
                        {{ $fmt($mutasi) }}<br>
                        <strong>{{ $fmt($saldoAkhir) }}</strong>
                    </td>
                @endforeach
            </tr>
        </tfoot>
    </table>
@endif
@endsection
