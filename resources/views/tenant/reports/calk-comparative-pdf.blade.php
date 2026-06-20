@extends('tenant.reports.layout-pdf')

@section('content')
@php
    $licensesList = $licenses ?? collect();
    $fmt = function ($v) {
        if ($v === null) return '—';
        $n = (float) $v;
        $abs = number_format(abs($n), 2, ',', '.');
        return $n < 0 ? "({$abs})" : $abs;
    };

    $colKode = 7;
    $colNama = 32;
    $colPerLicense = floor(61 / max(1, $licensesList->count()));
@endphp

@if($licensesList->isEmpty())
    <p style="text-align:center; padding: 40px; color: #c00;">Tidak ada aplikasi terdaftar.</p>
@else
    <p class="pdf-entity">{{ strtoupper($tenant->name ?? 'Holding App') }}</p>
    <p class="pdf-title">Catatan Atas Laporan Keuangan (CALK) — Komparatif</p>
    <p class="pdf-meta">Periode: <strong>{{ $period }}</strong></p>

    <table class="pdf">
        <thead>
            <tr>
                <th class="center" style="width: {{ $colKode }}%;">Kode</th>
                <th style="width: {{ $colNama }}%;">Nama Akun</th>
                @foreach($licensesList as $lic)
                    <th class="num" style="width: {{ $colPerLicense }}%;">
                        {{ $lic->label ?: $lic->application->name }}
                        @if(($payloads[$lic->id]['status'] ?? null) !== 'success')<br><span style="color:#fbb; font-weight:normal;">(offline)</span>@endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $firstSuccess = null;
                foreach ($licensesList as $lic) {
                    if (($payloads[$lic->id]['status'] ?? null) === 'success') { $firstSuccess = $payloads[$lic->id]; break; }
                }
                $rincian = $firstSuccess['data']['rincian_akun'] ?? [];
            @endphp
            @forelse($rincian as $lev1)
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
                                    $val = null;
                                    if ($pl['status'] === 'success') {
                                        foreach (($pl['data']['rincian_akun'] ?? []) as $l1) {
                                            if (($l1['kode_akun'] ?? null) !== ($lev1['kode_akun'] ?? null)) continue;
                                            foreach (($l1['akun2'] ?? []) as $l2) {
                                                if (($l2['kode_akun'] ?? null) !== ($lev2['kode_akun'] ?? null)) continue;
                                                foreach (($l2['akun3'] ?? []) as $l3) {
                                                    if (($l3['kode_akun'] ?? null) === ($lev3['kode_akun'] ?? null) && ($l3['nama_akun'] ?? null) === ($lev3['nama_akun'] ?? null)) {
                                                        $val = $l3['saldo'] ?? null; break 3;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                <td class="num">{{ $val === null ? '—' : $fmt($val) }}</td>
                            @endforeach
                        </tr>
                        @foreach(($lev3['rekening'] ?? []) as $rek)
                            <tr>
                                <td class="center">{{ $rek['kode_akun'] ?? '' }}</td>
                                <td class="indent-3" style="font-size: 10px;">{{ $rek['nama_akun'] ?? '' }}</td>
                                @foreach($licensesList as $lic)
                                    @php
                                        $pl = $payloads[$lic->id] ?? [];
                                        $val = null;
                                        if ($pl['status'] === 'success') {
                                            foreach (($pl['data']['rincian_akun'] ?? []) as $l1) {
                                                if (($l1['kode_akun'] ?? null) !== ($lev1['kode_akun'] ?? null)) continue;
                                                foreach (($l1['akun2'] ?? []) as $l2) {
                                                    if (($l2['kode_akun'] ?? null) !== ($lev2['kode_akun'] ?? null)) continue;
                                                    foreach (($l2['akun3'] ?? []) as $l3) {
                                                        if (($l3['kode_akun'] ?? null) !== ($lev3['kode_akun'] ?? null)) continue;
                                                        foreach (($l3['rekening'] ?? []) as $rk) {
                                                            if (($rk['kode_akun'] ?? null) === ($rek['kode_akun'] ?? null) && ($rk['nama_akun'] ?? null) === ($rek['nama_akun'] ?? null)) {
                                                                $val = $rk['saldo'] ?? null; break 4;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    @endphp
                                    <td class="num" style="font-size: 10px;">{{ $val === null ? '—' : $fmt($val) }}</td>
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
                                foreach (($pl['data']['rincian_akun'] ?? []) as $l1) {
                                    if (($l1['kode_akun'] ?? null) === ($lev1['kode_akun'] ?? null)) { $licLev1 = $l1; break; }
                                }
                            }
                        @endphp
                        <td class="num">{{ $licLev1 === null ? '—' : $fmt($licLev1['saldo'] ?? null) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ 2 + $licensesList->count() }}" class="center" style="padding: 14px; font-style: italic;">Tidak ada rincian akun untuk periode ini.</td></tr>
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

    {{-- Narasi Bagian A + B per license (di bawah tabel, side-by-side) --}}
    @php
        $anyNarasi = false;
        foreach ($licensesList as $lic) {
            $pl = $payloads[$lic->id] ?? [];
            if (!empty($pl['point_a'] ?? $pl['data']['point_a'] ?? $pl['ringkasan']['point_a'] ?? null)
                || !empty($pl['catatan'] ?? $pl['data']['catatan'] ?? null)) {
                $anyNarasi = true; break;
            }
        }
    @endphp
    @if($anyNarasi)
        <div class="page-break"></div>
        @foreach($licensesList as $lic)
            @php
                $pl = $payloads[$lic->id] ?? [];
                $pointA = $pl['point_a'] ?? $pl['data']['point_a'] ?? $pl['ringkasan']['point_a'] ?? null;
                $catatan = $pl['catatan'] ?? $pl['data']['catatan'] ?? null;
                $penandatangan = $pl['penandatangan'] ?? $pl['data']['penandatangan'] ?? [];
            @endphp
            @if($pointA || $catatan)
                <h3 style="margin: 0 0 8px 0; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #000; padding-bottom: 4px;">
                    {{ $lic->label ?: $lic->application->name }}
                </h3>
                @if($pointA)
                    <p class="narasi-title">A. Gambaran Umum</p>
                    <div class="narasi">{!! nl2br(e($pointA)) !!}</div>
                @endif
                @if($catatan)
                    <p class="narasi-title">B. Catatan Atas Laporan Keuangan</p>
                    <div class="narasi">{!! $catatan !!}</div>
                @endif

                @php
                    $roles = [
                        'sekretaris' => 'Sekretaris',
                        'bendahara' => 'Bendahara',
                        'pengawas' => 'Pengawas',
                        'direktur' => 'Direktur',
                    ];
                    $selisih = (float) ($pl['ringkasan']['selisih'] ?? 0);
                @endphp
                @if(abs($selisih) > 0.01)
                    <p style="margin-top: 8px; padding: 6px 10px; background: #fff3cd; border: 1px solid #856404; font-size: 10px;">
                        <strong>PERHATIAN:</strong> Selisih neraca {{ $fmt($selisih) }}.
                    </p>
                @endif

                <table class="signature">
                    <tr>
                        @foreach($roles as $role => $label)
                            <td style="width: 25%;">
                                <div class="role">{{ $label }}</div>
                                <div class="name">{{ $penandatangan[$role]['name'] ?? '—' }}</div>
                            </td>
                        @endforeach
                    </tr>
                </table>
            @endif
        @endforeach
    @endif
@endif
@endsection
