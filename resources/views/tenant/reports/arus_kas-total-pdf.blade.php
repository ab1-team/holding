@extends('tenant.reports.layout-pdf')

@section('content')
@php
    $licensesList = $licenses ?? collect();
    $firstSuccess = null;
    foreach ($licensesList as $lic) {
        if (($payloads[$lic->id]['status'] ?? null) === 'success') { $firstSuccess = $payloads[$lic->id]; break; }
    }
    $rows = $firstSuccess['data'] ?? [];

    $fmt = function ($v) {
        if ($v === null) return '—';
        $n = (float) $v;
        $abs = number_format(abs($n), 2, ',', '.');
        return $n < 0 ? "({$abs})" : $abs;
    };

    $sumById = function ($id) use ($licensesList, $payloads) {
        $total = 0; $found = false;
        foreach ($licensesList as $lic) {
            $pl = $payloads[$lic->id] ?? null;
            if (! $pl || ($pl['status'] ?? null) !== 'success') continue;
            foreach (($pl['data'] ?? []) as $r) {
                if (($r['id'] ?? null) === $id) { $total += (float) ($r['saldo'] ?? 0); $found = true; break; }
            }
        }
        return $found ? $total : null;
    };

    $sumDetailChild = function ($parentId, $childId) use ($licensesList, $payloads) {
        $total = 0; $found = false;
        foreach ($licensesList as $lic) {
            $pl = $payloads[$lic->id] ?? null;
            if (! $pl || ($pl['status'] ?? null) !== 'success') continue;
            foreach (($pl['data'] ?? []) as $r) {
                if (($r['id'] ?? null) === $parentId) {
                    foreach (($r['detail'] ?? []) as $c) {
                        if (($c['id'] ?? null) === $childId) { $total += (float) ($c['saldo'] ?? 0); $found = true; break 2; }
                    }
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
    <p class="pdf-title">Laporan Arus Kas — Konsolidasi (Total)</p>
    <p class="pdf-subtitle">{{ strtoupper($firstSuccess['sub_judul'] ?? $firstSuccess['periode']['sub_judul'] ?? $period) }}</p>
    <p class="pdf-meta">Periode: <strong>{{ $period }}</strong> &middot; {{ $subCount }} subsidiary diagregasi</p>

    <table class="pdf">
        <thead>
            <tr>
                <th class="center" style="width: 8%;">No</th>
                <th>Aktivitas / Rincian</th>
                <th class="num" style="width: 22%;">Total</th>
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
                        <td colspan="3">{{ $ak['nama'] ?? 'Saldo Awal Kas' }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td class="indent-1">Saldo per {{ $firstSuccess['tgl_kondisi'] ?? $firstSuccess['periode']['tgl_kondisi'] ?? $period }}</td>
                        <td class="num">{{ $fmt($sumById(1)) }}</td>
                    </tr>
                @else
                    <tr class="lev2">
                        <td colspan="3">{{ $ak['nama'] ?? '' }}</td>
                    </tr>
                    <tr class="subtotal">
                        <td></td>
                        <td class="indent-1">Subtotal {{ $ak['nama'] ?? '' }}</td>
                        <td class="num">{{ $fmt($sumById($ak['id'] ?? null)) }}</td>
                    </tr>
                    @foreach($detail as $child)
                        <tr>
                            <td></td>
                            <td class="indent-2">{{ $child['kode_akun'] ?? '' }} {{ $child['nama_akun'] ?? '' }}</td>
                            <td class="num">{{ $fmt($sumDetailChild($ak['id'] ?? null, $child['id'] ?? null)) }}</td>
                        </tr>
                    @endforeach
                @endif
            @empty
                <tr><td colspan="3" class="center" style="padding: 20px; font-style: italic;">Tidak ada data untuk periode ini.</td></tr>
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
                    <td colspan="2">{{ $label }}</td>
                    <td class="num">{{ $fmt($sumRingkasan($key)) }}</td>
                </tr>
            @endforeach
        </tfoot>
    </table>
@endif
@endsection
