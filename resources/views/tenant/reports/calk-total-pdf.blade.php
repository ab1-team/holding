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

    $firstSuccess = null;
    foreach ($licensesList as $lic) {
        if (($payloads[$lic->id]['status'] ?? null) === 'success') { $firstSuccess = $payloads[$lic->id]; break; }
    }

    $sumLev3 = function ($kode, $nama) use ($licensesList, $payloads) {
        $total = 0; $found = false;
        foreach ($licensesList as $lic) {
            $pl = $payloads[$lic->id] ?? null;
            if (! $pl || ($pl['status'] ?? null) !== 'success') continue;
            foreach (($pl['data']['rincian_akun'] ?? []) as $l1) {
                foreach (($l1['akun2'] ?? []) as $l2) {
                    foreach (($l2['akun3'] ?? []) as $l3) {
                        if (($l3['kode_akun'] ?? null) === $kode && ($l3['nama_akun'] ?? null) === $nama) {
                            $total += (float) ($l3['saldo'] ?? 0);
                            $found = true;
                            break 3;
                        }
                    }
                }
            }
        }
        return $found ? $total : null;
    };

    $sumRek = function ($kodeLev1, $kodeLev2, $kodeLev3, $kodeRek, $namaRek) use ($licensesList, $payloads) {
        $total = 0; $found = false;
        foreach ($licensesList as $lic) {
            $pl = $payloads[$lic->id] ?? null;
            if (! $pl || ($pl['status'] ?? null) !== 'success') continue;
            foreach (($pl['data']['rincian_akun'] ?? []) as $l1) {
                if (($l1['kode_akun'] ?? null) !== $kodeLev1) continue;
                foreach (($l1['akun2'] ?? []) as $l2) {
                    if (($l2['kode_akun'] ?? null) !== $kodeLev2) continue;
                    foreach (($l2['akun3'] ?? []) as $l3) {
                        if (($l3['kode_akun'] ?? null) !== $kodeLev3) continue;
                        foreach (($l3['rekening'] ?? []) as $rk) {
                            if (($rk['kode_akun'] ?? null) === $kodeRek && ($rk['nama_akun'] ?? null) === $namaRek) {
                                $total += (float) ($rk['saldo'] ?? 0);
                                $found = true;
                                break 4;
                            }
                        }
                    }
                }
            }
        }
        return $found ? $total : null;
    };

    $sumLev1 = function ($kode) use ($licensesList, $payloads) {
        $total = 0; $found = false;
        foreach ($licensesList as $lic) {
            $pl = $payloads[$lic->id] ?? null;
            if (! $pl || ($pl['status'] ?? null) !== 'success') continue;
            foreach (($pl['data']['rincian_akun'] ?? []) as $l1) {
                if (($l1['kode_akun'] ?? null) === $kode) {
                    $total += (float) ($l1['saldo'] ?? 0);
                    $found = true;
                    break;
                }
            }
        }
        return $found ? $total : null;
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
    <p class="pdf-title">Catatan Atas Laporan Keuangan (CALK) — Konsolidasi (Total)</p>
    <p class="pdf-subtitle">{{ strtoupper($firstSuccess['sub_judul'] ?? $firstSuccess['periode']['sub_judul'] ?? $period) }}</p>
    <p class="pdf-meta">Periode: <strong>{{ $period }}</strong> &middot; {{ $subCount }} subsidiary diagregasi</p>

    <table class="pdf">
        <thead>
            <tr>
                <th class="center" style="width: 8%;">Kode</th>
                <th>Nama Akun</th>
                <th class="num" style="width: 22%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $rincian = $firstSuccess['data']['rincian_akun'] ?? []; @endphp
            @forelse($rincian as $lev1)
                <tr class="lev1">
                    <td colspan="3">{{ $lev1['kode_akun'] ?? '' }}. {{ $lev1['nama_akun'] ?? '' }}</td>
                </tr>
                @foreach(($lev1['akun2'] ?? []) as $lev2)
                    <tr class="lev2">
                        <td colspan="3">{{ $lev2['kode_akun'] ?? '' }}. {{ $lev2['nama_akun'] ?? '' }}</td>
                    </tr>
                    @foreach(($lev2['akun3'] ?? []) as $i => $lev3)
                        <tr class="lev3 {{ $i % 2 === 1 ? 'alt' : '' }}">
                            <td class="center">{{ $lev3['kode_akun'] ?? '' }}</td>
                            <td class="indent-2">{{ $lev3['nama_akun'] ?? '' }}</td>
                            <td class="num">{{ $fmt($sumLev3($lev3['kode_akun'] ?? null, $lev3['nama_akun'] ?? null)) }}</td>
                        </tr>
                        @foreach(($lev3['rekening'] ?? []) as $rek)
                            <tr>
                                <td class="center">{{ $rek['kode_akun'] ?? '' }}</td>
                                <td class="indent-3" style="font-size: 10px;">{{ $rek['nama_akun'] ?? '' }}</td>
                                <td class="num" style="font-size: 10px;">{{ $fmt($sumRek($lev1['kode_akun'] ?? null, $lev2['kode_akun'] ?? null, $lev3['kode_akun'] ?? null, $rek['kode_akun'] ?? null, $rek['nama_akun'] ?? null)) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                @endforeach
                <tr class="subtotal">
                    <td colspan="2" class="indent-1">Jumlah {{ $lev1['nama_akun'] ?? '' }}</td>
                    <td class="num">{{ $fmt($sumLev1($lev1['kode_akun'] ?? null)) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="center" style="padding: 14px; font-style: italic;">Tidak ada rincian akun untuk periode ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Jumlah Liabilitas + Ekuitas (Konsolidasi)</td>
                <td class="num">{{ $fmt($sumRingkasan('total_liabilitas_ekuitas')) }}</td>
            </tr>
        </tfoot>
    </table>

    @php
        $selisih = (float) ($sumRingkasan('selisih') ?? 0);
    @endphp
    @if(abs($selisih) > 0.01)
        <p style="margin-top: 10px; padding: 8px 12px; background: #fff3cd; border: 1px solid #856404; font-size: 10px;">
            <strong>PERHATIAN:</strong> Selisih konsolidasi {{ $fmt($selisih) }}
            (Aset {{ $fmt($sumRingkasan('total_aset')) }} vs L+E {{ $fmt($sumRingkasan('total_liabilitas_ekuitas')) }}).
        </p>
    @endif
@endif
@endsection
