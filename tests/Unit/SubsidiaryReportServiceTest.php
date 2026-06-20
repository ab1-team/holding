<?php

namespace Tests\Unit;

use App\Services\SubsidiaryReportService;
use Tests\TestCase;

class SubsidiaryReportServiceTest extends TestCase
{
    private SubsidiaryReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SubsidiaryReportService();
    }

    public function test_report_types_and_labels(): void
    {
        $types = SubsidiaryReportService::reportTypes();
        $this->assertSame(['neraca', 'laba_rugi', 'arus_kas', 'perubahan_ekuitas', 'calk'], $types);

        $labels = SubsidiaryReportService::reportTypeLabels();
        $this->assertCount(5, $labels);
        $this->assertSame('Neraca', $labels['neraca']);
        $this->assertSame('Laba Rugi', $labels['laba_rugi']);
        $this->assertSame('Catatan (CALK)', $labels['calk']);
    }

    public function test_build_query_for_period(): void
    {
        $this->assertSame('tahun=2026&bulan=1', SubsidiaryReportService::buildQuery('2026-01'));
        $this->assertSame('tahun=2026&bulan=12', SubsidiaryReportService::buildQuery('2026-12'));
    }

    public function test_build_query_with_hari_and_semester(): void
    {
        $q = SubsidiaryReportService::buildQuery('2026-12', hari: 31, semester: 1);
        $this->assertStringContainsString('tahun=2026', $q);
        $this->assertStringContainsString('bulan=12', $q);
        $this->assertStringContainsString('hari=31', $q);
        $this->assertStringContainsString('semester=1', $q);
    }

    public function test_adapt_subsidiary_payload_passes_through_neraca_hierarchy(): void
    {
        $body = [
            'success' => true,
            'laporan' => 'Neraca',
            'kecamatan' => 'Tegalrejo',
            'tgl_kondisi' => '2026-06-30',
            'sub_judul' => 'Per 30 Juni 2026',
            'ringkasan' => ['total_aset' => 100, 'total_liabilitas_ekuitas' => 100, 'selisih' => 0],
            'data' => [
                [
                    'kode_akun' => '1.0.00.00',
                    'nama_akun' => 'Aset',
                    'lev1' => '1',
                    'saldo' => 100,
                    'akun2' => [
                        [
                            'kode_akun' => '1.1.00.00',
                            'nama_akun' => 'Aset Lancar',
                            'saldo' => 100,
                            'akun3' => [
                                ['kode_akun' => '1.1.01', 'nama_akun' => 'Kas', 'saldo' => 100],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $adapted = SubsidiaryReportService::adaptSubsidiaryPayload($body, 'neraca', '2026-06');

        $this->assertSame('success', $adapted['status']);
        $this->assertSame('2026-06', $adapted['period']);
        $this->assertSame('2026-06-30', $adapted['tgl_kondisi']);
        $this->assertSame('Per 30 Juni 2026', $adapted['sub_judul']);
        $this->assertSame('Neraca', $adapted['laporan']);
        $this->assertSame('Tegalrejo', $adapted['kecamatan']);
        // Data passes through apa adanya.
        $this->assertCount(1, $adapted['data']);
        $this->assertSame('1.0.00.00', $adapted['data'][0]['kode_akun']);
        $this->assertCount(1, $adapted['data'][0]['akun2']);
        // Ringkasan passes through.
        $this->assertSame(100, $adapted['ringkasan']['total_aset']);
    }

    public function test_adapt_subsidiary_payload_passes_through_laba_rugi_sections(): void
    {
        $body = [
            'success' => true,
            'laporan' => 'Laba Rugi',
            'kecamatan' => 'Tegalrejo',
            'periode' => [
                'jenis' => 'Bulanan',
                'tgl_kondisi' => '2026-06-30',
                'sub_judul' => 'Periode Januari - Juni 2026',
            ],
            'ringkasan' => ['pendapatan' => 50, 'beban' => 30, 'pendapatan_non_ops' => 0, 'beban_non_ops' => 0],
            'data' => [
                'pendapatan' => [
                    ['kode_akun' => '4.1', 'nama_akun' => 'Pendapatan Operasional', 'saldo' => 50, 'saldo_bln_lalu' => 30, 'saldo_periode_ini' => 20, 'rekening' => []],
                ],
                'beban' => [],
                'pendapatan_non_ops' => [],
                'beban_non_ops' => [],
            ],
        ];

        $adapted = SubsidiaryReportService::adaptSubsidiaryPayload($body, 'laba_rugi', '2026-06');

        $this->assertSame('success', $adapted['status']);
        $this->assertSame('2026-06', $adapted['period']);
        $this->assertSame('2026-06-30', $adapted['tgl_kondisi']);
        $this->assertSame('Periode Januari - Juni 2026', $adapted['sub_judul']);
        $this->assertSame(50, $adapted['ringkasan']['pendapatan']);
        $this->assertCount(1, $adapted['data']['pendapatan']);
        $this->assertSame('4.1', $adapted['data']['pendapatan'][0]['kode_akun']);
    }

    public function test_adapt_subsidiary_payload_passes_through_calk_full_structure(): void
    {
        $body = [
            'success' => true,
            'laporan' => 'Catatan Atas Laporan Keuangan (CALK)',
            'kecamatan' => 'Tegalrejo',
            'periode' => [
                'tgl_kondisi' => '2026-06-30',
                'sub_judul' => 'Bulan Juni Tahun 2026',
                'tgl_mad' => '2025-04-15',
            ],
            'ringkasan' => [
                'point_a' => 'Top-level point_a',
                'total_aset' => 100,
                'total_liabilitas_ekuitas' => 100,
                'selisih' => 0,
            ],
            'data' => [
                'point_a' => 'Nested point_a',
                'catatan' => '<p>Catatan Bagian B</p>',
                'rincian_akun' => [],
                'saldo_calk' => [],
                'penandatangan' => [
                    'sekretaris' => ['id' => 1, 'name' => 'Budi'],
                    'bendahara' => null,
                    'pengawas' => null,
                    'direktur' => null,
                ],
            ],
        ];

        $adapted = SubsidiaryReportService::adaptSubsidiaryPayload($body, 'calk', '2026-06');

        $this->assertSame('success', $adapted['status']);
        $this->assertSame('2026-06', $adapted['period']);
        $this->assertSame('2026-06-30', $adapted['tgl_kondisi']);
        $this->assertSame('Bulan Juni Tahun 2026', $adapted['sub_judul']);
        $this->assertSame('2025-04-15', $adapted['tgl_mad']);
        // point_a prefers data.point_a over ringkasan.point_a over top-level.
        $this->assertSame('Nested point_a', $adapted['point_a']);
        $this->assertSame('<p>Catatan Bagian B</p>', $adapted['catatan']);
        $this->assertSame('Budi', $adapted['penandatangan']['sekretaris']['name']);
    }

    public function test_adapt_subsidiary_payload_returns_error_for_success_false(): void
    {
        $body = ['success' => false, 'message' => 'No data'];

        $adapted = SubsidiaryReportService::adaptSubsidiaryPayload($body, 'neraca', '2026-06');

        $this->assertSame('error', $adapted['status']);
        $this->assertSame('malformed', $adapted['reason']);
    }

    public function test_adapt_subsidiary_payload_passes_through_already_normalized(): void
    {
        $body = [
            'status' => 'success',
            'period' => '2026-06',
            'data' => ['x'],
        ];

        $adapted = SubsidiaryReportService::adaptSubsidiaryPayload($body, 'neraca', '2026-06');

        // Already-normalized shape: pass through apa adanya.
        $this->assertSame($body, $adapted);
    }
}
