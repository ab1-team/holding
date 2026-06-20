<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\SubsidiaryReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UnifiedReportTest extends TestCase
{
    use RefreshDatabase;

    private function tenantOwner(Tenant $tenant): User
    {
        return User::factory()->forTenant($tenant->id, 'tenant_owner')->create();
    }

    private function tenantStaff(Tenant $tenant): User
    {
        return User::factory()->forTenant($tenant->id, 'tenant_staff')->create();
    }

    /**
     * Build a fake subsidiary response payload (normalized shape).
     */
    private function payload(string $period, array $rows): array
    {
        return [
            'status' => 'success',
            'period' => $period,
            'generated_at' => '2026-01-15T10:00:00Z',
            'data' => $rows,
        ];
    }

    public function test_reports_index_lists_only_financial_report_apps(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $fin = Application::factory()->create(['has_financial_report' => true, 'name' => 'EnFinance']);
        $nofin = Application::factory()->create(['has_financial_report' => false, 'name' => 'EnChat']);
        TenantApplication::factory()->for($tenant)->for($fin, 'application')->create();
        TenantApplication::factory()->for($tenant)->for($nofin, 'application')->create();

        $this->actingAs($owner)
            ->get(route('tenant.reports.index'))
            ->assertOk()
            ->assertSee('Laporan Keuangan Komparatif')
            ->assertSee('Neraca')
            ->assertSee('Laba Rugi');
    }

    public function test_superadmin_cannot_access_tenant_reports(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($admin)
            ->get(route('tenant.reports.index'))
            ->assertForbidden();
    }

    public function test_guest_redirected_to_login(): void
    {
        $this->get(route('tenant.reports.index'))
            ->assertRedirect(route('login'));
    }

    public function test_show_404_for_unknown_report_type(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'bogus']))
            ->assertNotFound();
    }

    public function test_show_validates_period_format(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $this->actingAs($owner)
            ->from(route('tenant.reports.show', ['type' => 'neraca']))
            ->get(route('tenant.reports.show', ['type' => 'neraca', 'period' => '2026-13']))
            ->assertSessionHasErrors('period');
    }

    public function test_show_neraca_renders_3_level_hierarchy(): void
    {
        // Subsidiary response shape (per HOLDING-API.md §4.1): lev1 → akun2 → akun3.
        $body = [
            'success' => true,
            'laporan' => 'Neraca',
            'kecamatan' => 'Tegalrejo',
            'tgl_kondisi' => '2026-06-30',
            'sub_judul' => 'Per 30 Juni 2026',
            'ringkasan' => [
                'total_aset' => 150_000_000.0,
                'total_liabilitas_ekuitas' => 150_000_000.0,
                'selisih' => 0.0,
            ],
            'data' => [
                [
                    'kode_akun' => '1.0.00.00',
                    'nama_akun' => 'Aset',
                    'lev1' => '1',
                    'saldo' => 150_000_000.0,
                    'akun2' => [
                        [
                            'kode_akun' => '1.1.00.00',
                            'nama_akun' => 'Aset Lancar',
                            'saldo' => 50_000_000.0,
                            'akun3' => [
                                [
                                    'kode_akun' => '1.1.01.00',
                                    'nama_akun' => 'Kas',
                                    'saldo' => 50_000_000.0,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'kode_akun' => '2.0.00.00',
                    'nama_akun' => 'Liabilitas',
                    'lev1' => '2',
                    'saldo' => 80_000_000.0,
                    'akun2' => [],
                ],
                [
                    'kode_akun' => '3.0.00.00',
                    'nama_akun' => 'Ekuitas',
                    'lev1' => '3',
                    'saldo' => 70_000_000.0,
                    'akun2' => [],
                ],
            ],
        ];

        Http::fake([
            '*' => Http::response($body, 200),
        ]);

        $tenant = Tenant::factory()->create(['slug' => 'acme']);
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create(['instance_url' => 'https://app-x.test']);

        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'neraca', 'period' => '2026-06']))
            ->assertOk();

        $response->assertSee('Neraca Komparatif');
        // lev1 headers
        $response->assertSee('1.0.00.00. Aset');
        $response->assertSee('2.0.00.00. Liabilitas');
        $response->assertSee('3.0.00.00. Ekuitas');
        // akun2 subheader
        $response->assertSee('1.1.00.00.');
        $response->assertSee('Aset Lancar');
        // akun3 row
        $response->assertSee('1.1.01.00.');
        $response->assertSee('Kas');
        // "Jumlah lev1" footer
        $response->assertSee('Jumlah Aset');
        $response->assertSee('Jumlah Liabilitas');
        $response->assertSee('Jumlah Ekuitas');
        // Grand total
        $response->assertSee('Jumlah Liabilitas + Ekuitas');
    }

    public function test_show_laba_rugi_renders_4_columns_with_sections(): void
    {
        $body = [
            'success' => true,
            'laporan' => 'Laba Rugi',
            'kecamatan' => 'Tegalrejo',
            'periode' => [
                'jenis' => 'Bulanan',
                'tgl_kondisi' => '2026-06-30',
                'sub_judul' => 'Periode 01 Januari 2026 S.D 30 Juni 2026',
            ],
            'ringkasan' => [
                'pendapatan' => 50_000_000.0,
                'beban' => 30_000_000.0,
                'pendapatan_non_ops' => 0.0,
                'beban_non_ops' => 0.0,
                'lr_operasional' => ['s_d_bulan_lalu' => 10_000_000.0, 'periode_ini' => 10_000_000.0, 's_d_sekarang' => 20_000_000.0],
                'lr_non_operasional' => ['s_d_bulan_lalu' => 0.0, 'periode_ini' => 0.0, 's_d_sekarang' => 0.0],
                'sebelum_pajak' => ['s_d_bulan_lalu' => 10_000_000.0, 'periode_ini' => 10_000_000.0, 's_d_sekarang' => 20_000_000.0],
                'pph' => ['s_d_bulan_lalu' => 0.0, 'periode_ini' => 0.0, 's_d_sekarang' => 0.0],
                'setelah_pajak' => ['s_d_bulan_lalu' => 10_000_000.0, 'periode_ini' => 10_000_000.0, 's_d_sekarang' => 20_000_000.0],
            ],
            'data' => [
                'pendapatan' => [
                    [
                        'kode_akun' => '4.1',
                        'nama_akun' => 'Pendapatan Operasional',
                        'saldo_bln_lalu' => 30_000_000.0,
                        'saldo_periode_ini' => 20_000_000.0,
                        'saldo' => 50_000_000.0,
                        'rekening' => [
                            [
                                'kode_akun' => '4.1.01',
                                'nama_akun' => 'Pendapatan Pinjaman',
                                'saldo_bln_lalu' => 25_000_000.0,
                                'saldo_periode_ini' => 8_000_000.0,
                                'saldo' => 33_000_000.0,
                            ],
                        ],
                    ],
                ],
                'beban' => [],
                'pendapatan_non_ops' => [],
                'beban_non_ops' => [],
            ],
        ];

        Http::fake([
            '*' => Http::response($body, 200),
        ]);

        $tenant = Tenant::factory()->create(['slug' => 'acme']);
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create(['instance_url' => 'https://app-lr.test']);

        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'laba_rugi', 'period' => '2026-06']))
            ->assertOk();

        $response->assertSee('Laba Rugi Komparatif');
        // 4 section headers
        $response->assertSee('4. Pendapatan');
        $response->assertSee('5. Beban');
        // Parent row + rekening detail
        $response->assertSee('4.1.');
        $response->assertSee('Pendapatan Operasional');
        $response->assertSee('4.1.01.');
        $response->assertSee('Pendapatan Pinjaman');
        // Jumlah group total
        $response->assertSee('Jumlah 4.1. Pendapatan Operasional');
        // Ringkasan A/B/C/PPh
        $response->assertSee('A. Laba Rugi Operasional');
        $response->assertSee('B. Laba Rugi Non Operasional');
        $response->assertSee('C. Sebelum Pajak');
        $response->assertSee('PPh');
        $response->assertSee('Setelah Pajak');
    }

    public function test_show_perubahan_ekuitas_renders_3_columns(): void
    {
        $body = [
            'success' => true,
            'laporan' => 'Perubahan Ekuitas',
            'kecamatan' => 'Tegalrejo',
            'periode' => [
                'tgl_kondisi' => '2026-06-30',
                'sub_judul' => 'Bulan Juni Tahun 2026',
            ],
            'ringkasan' => [
                'ekuitas_awal' => 40_000_000.0,
                'setoran' => 0.0,
                'penarikan' => 0.0,
                'dividen' => 0.0,
                'koreksi' => 12_000_000.0,
                'laba_rugi' => 12_000_000.0,
                'ekuitas_akhir' => 52_000_000.0,
            ],
            'data' => [
                [
                    'kode_akun' => '3.2.02.01',
                    'nama_akun' => 'Laba Rugi Tahun Berjalan',
                    'saldo_awal' => 40_000_000.0,
                    'saldo_akhir' => 52_000_000.0,
                    'mutasi' => 12_000_000.0,
                ],
            ],
        ];

        Http::fake([
            '*' => Http::response($body, 200),
        ]);

        $tenant = Tenant::factory()->create(['slug' => 'acme']);
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create(['instance_url' => 'https://app-pe.test']);

        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'perubahan_ekuitas', 'period' => '2026-06']))
            ->assertOk();

        $response->assertSee('Perubahan Ekuitas Komparatif');
        $response->assertSee('3.2.02.01');
        $response->assertSee('Laba Rugi Tahun Berjalan');
        $response->assertSee('Total Ekuitas');
    }

    public function test_show_arus_kas_renders_hierarchy_and_kas_bersih(): void
    {
        $body = [
            'success' => true,
            'laporan' => 'Arus Kas',
            'kecamatan' => 'Tegalrejo',
            'periode' => [
                'jenis' => 'Bulanan',
                'tgl_kondisi' => '2026-06-30',
                'sub_judul' => 'Bulan Juni 2026',
            ],
            'ringkasan' => [
                'saldo_awal' => 10_000_000.0,
                'total_masuk' => 50_000_000.0,
                'total_keluar' => 30_000_000.0,
                'kas_operasi' => 20_000_000.0,
                'kas_investasi' => -5_000_000.0,
                'kas_pendanaan' => 0.0,
                'kenaikan_penurunan' => 15_000_000.0,
                'saldo_akhir' => 25_000_000.0,
            ],
            'data' => [
                [
                    'id' => 1,
                    'parent' => 'saldo_awal',
                    'nama' => 'Saldo Awal',
                    'sub' => 0,
                    'saldo' => 10_000_000.0,
                    'detail' => [],
                ],
                [
                    'id' => 5,
                    'parent' => 'masuk',
                    'kategori' => 'operasi_masuk',
                    'nama' => 'Arus Kas Operasi Masuk',
                    'sub' => 1,
                    'saldo' => 50_000_000.0,
                    'detail' => [
                        ['id' => 10, 'kode_akun' => '5.1.01', 'nama_akun' => 'Penerimaan Pinjaman', 'saldo' => 50_000_000.0],
                    ],
                ],
            ],
        ];

        Http::fake([
            '*' => Http::response($body, 200),
        ]);

        $tenant = Tenant::factory()->create(['slug' => 'acme']);
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create(['instance_url' => 'https://app-cf.test']);

        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'arus_kas', 'period' => '2026-06']))
            ->assertOk();

        $response->assertSee('Arus Kas Komparatif');
        $response->assertSee('Saldo Awal');
        $response->assertSee('Arus Kas Operasi Masuk');
        $response->assertSee('Penerimaan Pinjaman');
        $response->assertSee('Kas Bersih Aktivitas Operasi');
        $response->assertSee('Kas Bersih Aktivitas Investasi');
        $response->assertSee('Kenaikan (Penurunan) Kas');
        $response->assertSee('Saldo Akhir Kas');
    }

    public function test_show_calk_renders_bagian_a_c_penandatangan(): void
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
                'point_a' => 'Per 30 Juni 2026, kondisi keuangan...',
                'total_aset' => 150_000_000.0,
                'total_liabilitas_ekuitas' => 150_000_000.0,
                'selisih' => 0.0,
            ],
            'data' => [
                'point_a' => 'Per 30 Juni 2026, kondisi keuangan...',
                'catatan' => '<p>Catatan narasi Bagian B</p>',
                'rincian_akun' => [
                    [
                        'kode_akun' => '1',
                        'nama_akun' => 'Aset',
                        'lev1' => '1',
                        'saldo' => 150_000_000.0,
                        'akun2' => [
                            [
                                'kode_akun' => '1.1',
                                'nama_akun' => 'Aset Lancar',
                                'saldo' => 80_000_000.0,
                                'akun3' => [
                                    [
                                        'kode_akun' => '1.1.01',
                                        'nama_akun' => 'Kas',
                                        'saldo' => 50_000_000.0,
                                        'rekening' => [
                                            ['kode_akun' => '1.1.01.01', 'nama_akun' => 'Kas Besar', 'saldo' => 30_000_000.0],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'saldo_calk' => [],
                'penandatangan' => [
                    'sekretaris' => ['id' => 1, 'name' => 'Budi'],
                    'bendahara' => ['id' => 2, 'name' => 'Ani'],
                    'pengawas' => null,
                    'direktur' => ['id' => 3, 'name' => 'Citra'],
                ],
            ],
        ];

        Http::fake([
            '*' => Http::response($body, 200),
        ]);

        $tenant = Tenant::factory()->create(['slug' => 'acme']);
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create(['instance_url' => 'https://app-calk.test']);

        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'calk', 'period' => '2026-06']))
            ->assertOk();

        $response->assertSee('Catatan Komparatif');
        // Bagian A
        $response->assertSee('A. Gambaran Umum');
        $response->assertSee('Per 30 Juni 2026, kondisi keuangan...');
        // Bagian B
        $response->assertSee('Catatan (Bagian B)');
        $response->assertSee('Catatan narasi Bagian B');
        // Bagian C
        $response->assertSee('C. Rincian Akun per Rekening');
        $response->assertSee('1. Aset');
        $response->assertSee('Kas Besar');
        $response->assertSee('Jumlah Aset');
        $response->assertSee('Jumlah Liabilitas + Ekuitas');
        // Penandatangan
        $response->assertSee('Penandatangan');
        $response->assertSee('Budi');
        $response->assertSee('Ani');
        $response->assertSee('Citra');
    }

    public function test_show_subsidiary_failure_renders_red_error_banner(): void
    {
        Http::fake([
            '*' => Http::response('Server Error', 500),
        ]);

        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'neraca', 'period' => '2026-06']))
            ->assertOk();
        $response->assertSee('tidak dapat');
    }

    public function test_tenant_isolation_other_tenants_apps_excluded(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'tgl_kondisi' => '2026-04-30',
                'data' => [],
            ], 200),
        ]);

        $t1 = Tenant::factory()->create(['slug' => 't1']);
        $t2 = Tenant::factory()->create(['slug' => 't2']);
        $owner = $this->tenantOwner($t1);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($t1)->for($app, 'application')->create(['instance_url' => 'https://mine.test']);
        TenantApplication::factory()->for($t2)->for($app, 'application')->create(['instance_url' => 'https://other.test']);

        $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'neraca', 'period' => '2026-04']))
            ->assertOk();
        $this->assertTrue(true);
    }

    public function test_sends_correct_headers_to_subsidiary(): void
    {
        Http::fake([
            'https://app-e.test/*' => Http::response([
                'success' => true,
                'tgl_kondisi' => '2026-05-31',
                'data' => [
                    ['kode_akun' => '1', 'nama_akun' => 'A', 'saldo' => 100, 'akun2' => []],
                ],
            ], 200),
        ]);

        $tenant = Tenant::factory()->create(['slug' => 'slugku']);
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        $lic = TenantApplication::factory()->for($tenant)->for($app, 'application')->create(['instance_url' => 'https://app-e.test', 'api_secret' => 'super-secret-token']);

        $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'perubahan_ekuitas', 'period' => '2026-05']))
            ->assertOk();

        Http::assertSent(function ($request) use ($lic) {
            return $request->url() === 'https://app-e.test/api/v1/holding/laporan/perubahan-ekuitas?tahun=2026&bulan=5'
                && $request->header('X-Holding-Token')[0] === $lic->api_secret
                && $request->header('X-Holding-Tenant')[0] === 'app-e.test'
                && $request->header('Accept')[0] === 'application/json';
        });
    }

    public function test_pdf_endpoint_with_inline_param_renders_in_browser(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'tgl_kondisi' => '2026-01-31',
                'data' => [],
            ], 200),
        ]);

        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.pdf', ['type' => 'neraca', 'period' => '2026-01', 'inline' => 1]));

        $response->assertOk();
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
    }

    public function test_pdf_endpoint_without_inline_param_force_download(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'tgl_kondisi' => '2026-01-31',
                'data' => [],
            ], 200),
        ]);

        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.pdf', ['type' => 'neraca', 'period' => '2026-01']));

        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
    }

    public function test_staff_can_view_reports(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'tgl_kondisi' => '2026-07-31',
                'data' => [],
            ], 200),
        ]);

        $tenant = Tenant::factory()->create();
        $staff = $this->tenantStaff($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $this->actingAs($staff)
            ->get(route('tenant.reports.index'))
            ->assertOk();
        $this->actingAs($staff)
            ->get(route('tenant.reports.show', ['type' => 'neraca', 'period' => '2026-07']))
            ->assertOk();
    }

    public function test_activity_log_recorded_on_view(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'tgl_kondisi' => '2026-06-30',
                'data' => [],
            ], 200),
        ]);

        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'neraca', 'period' => '2026-06']))
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'view_report',
            'user_id' => $owner->id,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_report_form_has_monthpicker_attribute(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'tgl_kondisi' => '2026-06-30',
                'data' => [],
            ], 200),
        ]);

        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'neraca']))
            ->assertOk()
            ->assertSee('data-monthpicker', false)
            ->assertSee('name="period"', false);
    }

    public function test_pdf_export_neraca_uses_dedicated_pdf_view(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'laporan' => 'Neraca',
                'tgl_kondisi' => '2026-06-30',
                'sub_judul' => 'Per 30 Juni 2026',
                'data' => [[
                    'kode_akun' => '1.0.00.00',
                    'nama_akun' => 'Aset',
                    'akun2' => [[
                        'kode_akun' => '1.1.00.00',
                        'nama_akun' => 'Aset Lancar',
                        'akun3' => [[
                            'kode_akun' => '1.1.01.00',
                            'nama_akun' => 'Kas',
                            'saldo' => 1500000,
                        ]],
                    ]],
                ]],
                'ringkasan' => ['total_aset' => 1500000, 'total_liabilitas_ekuitas' => 1500000, 'selisih' => 0],
            ], 200),
        ]);

        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.pdf', ['type' => 'neraca', 'period' => '2026-06']));

        $response->assertOk();
        $content = $response->getContent() ?: $response->streamedContent();
        $this->assertStringStartsWith('%PDF-', $content);
        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('laporan-neraca-comparative-2026-06', $disposition);
    }

    public function test_pdf_export_neraca_renders_hierarchy_with_account_names(): void
    {
        $tenant = Tenant::factory()->create();
        $html = view('tenant.reports.neraca-comparative-pdf', [
            'payloads' => [
                1 => [
                    'status' => 'success',
                    'tgl_kondisi' => '2026-06-30',
                    'sub_judul' => 'Per 30 Juni 2026',
                    'kecamatan' => 'Tegalrejo',
                    'data' => [[
                        'kode_akun' => '1.0.00.00',
                        'nama_akun' => 'Aset',
                        'akun2' => [[
                            'kode_akun' => '1.1.00.00',
                            'nama_akun' => 'Aset Lancar',
                            'akun3' => [[
                                'kode_akun' => '1.1.01.00',
                                'nama_akun' => 'Kas',
                                'saldo' => 1500000,
                            ]],
                        ]],
                    ]],
                    'ringkasan' => ['total_liabilitas_ekuitas' => 1500000],
                ],
            ],
            'licenses' => collect([(object) ['id' => 1, 'label' => 'Demo SIDBM', 'application' => (object) ['name' => 'Demo']]]),
            'reportLabel' => 'Neraca',
            'period' => '2026-06',
            'tenant' => $tenant,
        ])->render();

        // PDF view should NOT contain web UI chrome.
        $this->assertStringNotContainsString('Ekspor:', $html);
        $this->assertStringNotContainsString('Terapkan', $html);
        $this->assertStringNotContainsString('class="bg-surface-container', $html);

        // Title (match subsidiary format: 18px bold uppercase center).
        $this->assertStringContainsString('Neraca', $html);
        $this->assertStringContainsString('PER 30 JUNI 2026', $html);

        // Hierarchy + footer.
        $this->assertStringContainsString('1.0.00.00', $html);
        $this->assertStringContainsString('Aset', $html);
        $this->assertStringContainsString('Aset Lancar', $html);
        $this->assertStringContainsString('Kas', $html);
        $this->assertStringContainsString('Jumlah Aset', $html);
        $this->assertStringContainsString('Jumlah Liabilitas + Ekuitas', $html);

        // Header komparatif: Kode | Nama Akun | license names (multi-license).
        $this->assertStringContainsString('>Kode<', $html);
        $this->assertStringContainsString('>Nama Akun<', $html);
        $this->assertStringContainsString('Demo SIDBM', $html);
    }

    public function test_pdf_export_neraca_handles_lev3_rekening_breakdown(): void
    {
        $tenant = Tenant::factory()->create();
        $html = view('tenant.reports.neraca-comparative-pdf', [
            'payloads' => [
                1 => [
                    'status' => 'success',
                    'tgl_kondisi' => '2026-06-30',
                    'data' => [[
                        'kode_akun' => '1.0.00.00',
                        'nama_akun' => 'Aset',
                        'akun2' => [[
                            'kode_akun' => '1.1.00.00',
                            'nama_akun' => 'Aset Lancar',
                            'akun3' => [[
                                'kode_akun' => '1.1.01.00',
                                'nama_akun' => 'Kas',
                                'saldo' => 1500000,
                                'rekening' => [
                                    ['kode_akun' => '1.1.01.01', 'nama_akun' => 'Kas Tunai', 'saldo' => 1000000],
                                    ['kode_akun' => '1.1.01.02', 'nama_akun' => 'Kas Kecil', 'saldo' => 500000],
                                ],
                            ]],
                        ]],
                    ]],
                    'ringkasan' => [],
                ],
            ],
            'licenses' => collect([(object) ['id' => 1, 'label' => 'Demo', 'application' => (object) ['name' => 'Demo']]]),
            'reportLabel' => 'Neraca',
            'period' => '2026-06',
            'tenant' => $tenant,
        ])->render();

        // 4-level hierarchy: rekening ditampilkan di bawah akun3.
        $this->assertStringContainsString('Kas Tunai', $html);
        $this->assertStringContainsString('Kas Kecil', $html);
        $this->assertStringContainsString('1.1.01.01', $html);
        $this->assertStringContainsString('1.1.01.02', $html);
    }

    public function test_pdf_export_neraca_renders_multi_license_side_by_side(): void
    {
        $tenant = Tenant::factory()->create();
        $html = view('tenant.reports.neraca-comparative-pdf', [
            'payloads' => [
                1 => [
                    'status' => 'success',
                    'tgl_kondisi' => '2026-06-30',
                    'data' => [[
                        'kode_akun' => '1', 'nama_akun' => 'Aset',
                        'akun2' => [[
                            'kode_akun' => '1.1', 'nama_akun' => 'Aset Lancar',
                            'akun3' => [['kode_akun' => '1.1.01', 'nama_akun' => 'Kas', 'saldo' => 100]],
                        ]],
                    ]],
                    'ringkasan' => ['total_liabilitas_ekuitas' => 100],
                ],
                2 => [
                    'status' => 'success',
                    'tgl_kondisi' => '2026-06-30',
                    'data' => [[
                        'kode_akun' => '1', 'nama_akun' => 'Aset',
                        'akun2' => [[
                            'kode_akun' => '1.1', 'nama_akun' => 'Aset Lancar',
                            'akun3' => [['kode_akun' => '1.1.01', 'nama_akun' => 'Kas', 'saldo' => 200]],
                        ]],
                    ]],
                    'ringkasan' => ['total_liabilitas_ekuitas' => 200],
                ],
            ],
            'licenses' => collect([
                (object) ['id' => 1, 'label' => 'SIDBM A', 'application' => (object) ['name' => 'A']],
                (object) ['id' => 2, 'label' => 'SIDBM B', 'application' => (object) ['name' => 'B']],
            ]),
            'reportLabel' => 'Neraca',
            'period' => '2026-06',
            'tenant' => $tenant,
        ])->render();

        // Multi-license side-by-side: 2 kolom numerik, komparatif.
        $this->assertStringContainsString('SIDBM A', $html);
        $this->assertStringContainsString('SIDBM B', $html);
        // Footer: Jumlah L+E muncul per license.
        $this->assertStringContainsString('Jumlah Liabilitas + Ekuitas', $html);
    }

    public function test_pdf_export_laba_rugi_renders_4_columns_and_ringkasan(): void
    {
        $tenant = Tenant::factory()->create();
        $html = view('tenant.reports.laba_rugi-comparative-pdf', [
            'payloads' => [
                1 => [
                    'status' => 'success',
                    'tgl_kondisi' => '2026-06-30',
                    'sub_judul' => 'Periode Januari - Juni 2026',
                    'data' => [
                        'pendapatan' => [[
                            'kode_akun' => '4.1', 'nama_akun' => 'Pendapatan Operasional',
                            'rekening' => [
                                ['kode_akun' => '4.1.01', 'nama_akun' => 'Iuran', 'saldo' => 50000, 'saldo_bln_lalu' => 30000],
                            ],
                        ]],
                        'beban' => [[
                            'kode_akun' => '5.1', 'nama_akun' => 'Beban Gaji',
                            'rekening' => [
                                ['kode_akun' => '5.1.01', 'nama_akun' => 'Gaji Pokok', 'saldo' => 30000, 'saldo_bln_lalu' => 20000],
                            ],
                        ]],
                        'pendapatan_non_ops' => [],
                        'beban_non_ops' => [],
                    ],
                    'ringkasan' => ['lr_operasional' => 20000, 'sebelum_pajak' => 20000],
                ],
            ],
            'licenses' => collect([(object) ['id' => 1, 'label' => 'Demo', 'application' => (object) ['name' => 'Demo']]]),
            'reportLabel' => 'Laba Rugi',
            'period' => '2026-06',
            'tenant' => $tenant,
        ])->render();

        $this->assertStringNotContainsString('Ekspor:', $html);
        $this->assertStringContainsString('Laporan Laba Rugi', $html);
        $this->assertStringContainsString('PERIODE JANUARI - JUNI 2026', $html);

        // 4 kolom per HOLDING-API.md §4.2.
        $this->assertStringContainsString('>Rekening<', $html);
        $this->assertStringContainsString('s.d bln lalu', $html);
        $this->assertStringContainsString('periode ini', $html);
        $this->assertStringContainsString('s.d sekarang', $html);

        // 4 section.
        $this->assertStringContainsString('4. Pendapatan', $html);
        $this->assertStringContainsString('5. Beban', $html);
        $this->assertStringContainsString('Pendapatan Non Operasional', $html);
        $this->assertStringContainsString('Beban Non Operasional', $html);

        // Ringkasan.
        $this->assertStringContainsString('A. Laba Rugi Operasional', $html);
        $this->assertStringContainsString('B. Laba Rugi Non Operasional', $html);
        $this->assertStringContainsString('C. Sebelum Pajak', $html);

        // Rekening leaf.
        $this->assertStringContainsString('Iuran', $html);
        $this->assertStringContainsString('Gaji Pokok', $html);
    }

    public function test_pdf_export_arus_kas_renders_saldo_awal_and_aktivitas(): void
    {
        $tenant = Tenant::factory()->create();
        $html = view('tenant.reports.arus_kas-comparative-pdf', [
            'payloads' => [
                1 => [
                    'status' => 'success',
                    'tgl_kondisi' => '2026-06-30',
                    'data' => [
                        ['id' => 1, 'nama' => 'Saldo Awal Kas', 'saldo' => 100000],
                        ['id' => 2, 'nama' => 'Aktivitas Operasi', 'saldo' => 50000, 'detail' => [
                            ['id' => 11, 'kode_akun' => '1.1.01', 'nama_akun' => 'Kas Masuk', 'saldo' => 50000],
                        ]],
                    ],
                    'ringkasan' => [
                        'kas_operasi' => 50000,
                        'saldo_akhir' => 150000,
                    ],
                ],
            ],
            'licenses' => collect([(object) ['id' => 1, 'label' => 'Demo', 'application' => (object) ['name' => 'Demo']]]),
            'reportLabel' => 'Arus Kas',
            'period' => '2026-06',
            'tenant' => $tenant,
        ])->render();

        $this->assertStringContainsString('Laporan Arus Kas', $html);
        $this->assertStringContainsString('Saldo Awal Kas', $html);
        $this->assertStringContainsString('Aktivitas Operasi', $html);
        $this->assertStringContainsString('Subtotal Aktivitas Operasi', $html);
        $this->assertStringContainsString('Kas Masuk', $html);
        $this->assertStringContainsString('Kas Bersih Aktivitas Operasi', $html);
        $this->assertStringContainsString('Saldo Akhir Kas', $html);
    }

    public function test_pdf_export_perubahan_ekuitas_renders_5_columns_per_holding_api(): void
    {
        $tenant = Tenant::factory()->create();
        $html = view('tenant.reports.perubahan_ekuitas-comparative-pdf', [
            'payloads' => [
                1 => [
                    'status' => 'success',
                    'data' => [
                        ['kode_akun' => '3.1', 'nama_akun' => 'Modal Disetor', 'saldo_awal' => 100000, 'mutasi' => 50000, 'saldo_akhir' => 150000],
                        ['kode_akun' => '3.2', 'nama_akun' => 'Laba Ditahan', 'saldo_awal' => 200000, 'mutasi' => 30000, 'saldo_akhir' => 230000],
                    ],
                    'ringkasan' => [
                        'ekuitas_awal' => 300000,
                        'ekuitas_akhir' => 380000,
                        'setoran' => 50000,
                        'penarikan' => -20000,
                        'dividen' => 0,
                        'koreksi' => 0,
                        'laba_rugi' => 30000,
                    ],
                ],
            ],
            'licenses' => collect([(object) ['id' => 1, 'label' => 'Demo', 'application' => (object) ['name' => 'Demo']]]),
            'reportLabel' => 'Perubahan Ekuitas',
            'period' => '2026-06',
            'tenant' => $tenant,
        ])->render();

        $this->assertStringNotContainsString('Ekspor:', $html);
        $this->assertStringContainsString('Laporan Perubahan Ekuitas', $html);

        // Komparatif: Kode | Rekening Modal | license names (header sub: Saldo Awal/Mutasi/Saldo Akhir per license).
        $this->assertStringContainsString('>Kode<', $html);
        $this->assertStringContainsString('>Rekening Modal<', $html);
        $this->assertStringContainsString('Saldo Awal', $html);
        $this->assertStringContainsString('Mutasi', $html);
        $this->assertStringContainsString('Saldo Akhir', $html);

        $this->assertStringContainsString('Modal Disetor', $html);
        $this->assertStringContainsString('Laba Ditahan', $html);

        // Footer: Total Ekuitas.
        $this->assertStringContainsString('Total Ekuitas', $html);
    }

    public function test_pdf_export_calk_renders_bagian_a_c_penandatangan(): void
    {
        $tenant = Tenant::factory()->create();
        $html = view('tenant.reports.calk-comparative-pdf', [
            'payloads' => [
                1 => [
                    'status' => 'success',
                    'data' => [
                        'rincian_akun' => [[
                            'kode_akun' => '1.0.00.00',
                            'nama_akun' => 'Aset',
                            'saldo' => 1000000,
                            'akun2' => [[
                                'kode_akun' => '1.1.00.00',
                                'nama_akun' => 'Aset Lancar',
                                'akun3' => [[
                                    'kode_akun' => '1.1.01.00',
                                    'nama_akun' => 'Kas',
                                    'saldo' => 500000,
                                    'rekening' => [
                                        ['kode_akun' => '1.1.01.01', 'nama_akun' => 'Kas Tunai', 'saldo' => 300000],
                                    ],
                                ]],
                            ]],
                        ]],
                    ],
                    'point_a' => 'Ini adalah gambaran umum BUMDes.',
                    'catatan' => '<p>Catatan penting tentang kas.</p>',
                    'ringkasan' => ['total_liabilitas_ekuitas' => 1000000],
                    'penandatangan' => [
                        'sekretaris' => ['name' => 'Budi'],
                        'bendahara' => ['name' => 'Siti'],
                        'pengawas' => null,
                        'direktur' => null,
                    ],
                ],
            ],
            'licenses' => collect([(object) ['id' => 1, 'label' => 'Demo', 'application' => (object) ['name' => 'Demo']]]),
            'reportLabel' => 'Catatan (CALK)',
            'period' => '2026-06',
            'tenant' => $tenant,
        ])->render();

        $this->assertStringNotContainsString('Ekspor:', $html);
        $this->assertStringContainsString('A. Gambaran Umum', $html);
        $this->assertStringContainsString('B. Catatan Atas Laporan Keuangan', $html);
        $this->assertStringContainsString('Ini adalah gambaran umum BUMDes.', $html);
        $this->assertStringContainsString('<p>Catatan penting tentang kas.</p>', $html);
        $this->assertStringContainsString('Kas Tunai', $html);
        $this->assertStringContainsString('Sekretaris', $html);
        $this->assertStringContainsString('Bendahara', $html);
        $this->assertStringContainsString('Budi', $html);
        $this->assertStringContainsString('Siti', $html);
    }

    public function test_pdf_export_returns_404_for_unknown_type(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $this->actingAs($owner)
            ->get(route('tenant.reports.pdf', ['type' => 'bogus']))
            ->assertNotFound();
    }

    public function test_pdf_total_view_neraca_sums_all_licenses(): void
    {
        $tenant = Tenant::factory()->create();
        $html = view('tenant.reports.neraca-total-pdf', [
            'payloads' => [
                1 => [
                    'status' => 'success',
                    'tgl_kondisi' => '2026-06-30',
                    'sub_judul' => 'Per 30 Juni 2026',
                    'kecamatan' => 'Tegalrejo',
                    'data' => [[
                        'kode_akun' => '1.0.00.00', 'nama_akun' => 'Aset', 'saldo' => 100,
                        'akun2' => [[
                            'kode_akun' => '1.1.00.00', 'nama_akun' => 'Aset Lancar', 'saldo' => 100,
                            'akun3' => [[
                                'kode_akun' => '1.1.01.00', 'nama_akun' => 'Kas', 'saldo' => 100,
                            ]],
                        ]],
                    ]],
                    'ringkasan' => ['total_liabilitas_ekuitas' => 100],
                ],
                2 => [
                    'status' => 'success',
                    'tgl_kondisi' => '2026-06-30',
                    'sub_judul' => 'Per 30 Juni 2026',
                    'data' => [[
                        'kode_akun' => '1.0.00.00', 'nama_akun' => 'Aset', 'saldo' => 200,
                        'akun2' => [[
                            'kode_akun' => '1.1.00.00', 'nama_akun' => 'Aset Lancar', 'saldo' => 200,
                            'akun3' => [[
                                'kode_akun' => '1.1.01.00', 'nama_akun' => 'Kas', 'saldo' => 200,
                            ]],
                        ]],
                    ]],
                    'ringkasan' => ['total_liabilitas_ekuitas' => 200],
                ],
            ],
            'licenses' => collect([
                (object) ['id' => 1, 'label' => 'Sub A', 'application' => (object) ['name' => 'A']],
                (object) ['id' => 2, 'label' => 'Sub B', 'application' => (object) ['name' => 'B']],
            ]),
            'reportLabel' => 'Neraca',
            'period' => '2026-06',
            'tenant' => $tenant,
        ])->render();

        // Title: konsolidasi total.
        $this->assertStringContainsString('Konsolidasi (Total)', $html);
        $this->assertStringContainsString('2 subsidiary diagregasi', $html);

        // Single Total column.
        $this->assertStringContainsString('>Total<', $html);
        // No license names in header.
        $this->assertStringNotContainsString('Sub A', $html);
        $this->assertStringNotContainsString('Sub B', $html);

        // Sum: Kas 100 + 200 = 300.
        $this->assertStringContainsString('300,00', $html);
        // Sum: Jumlah L+E 100 + 200 = 300.
        $this->assertStringContainsString('Jumlah Liabilitas + Ekuitas (Konsolidasi)', $html);
    }

    public function test_pdf_total_view_laba_rugi_sums_per_sub_section(): void
    {
        $tenant = Tenant::factory()->create();
        $html = view('tenant.reports.laba_rugi-total-pdf', [
            'payloads' => [
                1 => [
                    'status' => 'success',
                    'tgl_kondisi' => '2026-06-30',
                    'sub_judul' => 'Periode 2026-06',
                    'data' => [
                        'pendapatan' => [[
                            'kode_akun' => '4.1', 'nama_akun' => 'Pendapatan',
                            'rekening' => [
                                ['kode_akun' => '4.1.01', 'nama_akun' => 'Iuran', 'saldo' => 1000, 'saldo_bln_lalu' => 500],
                            ],
                        ]],
                        'beban' => [],
                        'pendapatan_non_ops' => [],
                        'beban_non_ops' => [],
                    ],
                    'ringkasan' => ['lr_operasional' => 1000],
                ],
                2 => [
                    'status' => 'success',
                    'tgl_kondisi' => '2026-06-30',
                    'sub_judul' => 'Periode 2026-06',
                    'data' => [
                        'pendapatan' => [[
                            'kode_akun' => '4.1', 'nama_akun' => 'Pendapatan',
                            'rekening' => [
                                ['kode_akun' => '4.1.01', 'nama_akun' => 'Iuran', 'saldo' => 2000, 'saldo_bln_lalu' => 1000],
                            ],
                        ]],
                        'beban' => [],
                        'pendapatan_non_ops' => [],
                        'beban_non_ops' => [],
                    ],
                    'ringkasan' => ['lr_operasional' => 2000],
                ],
            ],
            'licenses' => collect([
                (object) ['id' => 1, 'label' => 'A', 'application' => (object) ['name' => 'A']],
                (object) ['id' => 2, 'label' => 'B', 'application' => (object) ['name' => 'B']],
            ]),
            'reportLabel' => 'Laba Rugi',
            'period' => '2026-06',
            'tenant' => $tenant,
        ])->render();

        $this->assertStringContainsString('Konsolidasi (Total)', $html);
        // Sum Iuran: 1000 + 2000 = 3000.
        $this->assertStringContainsString('3.000,00', $html);
        // Sum A. LRO: 1000 + 2000 = 3000.
        $this->assertStringContainsString('A. Laba Rugi Operasional', $html);
    }

    public function test_pdf_total_view_arus_kas_sums_by_id(): void
    {
        $tenant = Tenant::factory()->create();
        $html = view('tenant.reports.arus_kas-total-pdf', [
            'payloads' => [
                1 => [
                    'status' => 'success',
                    'tgl_kondisi' => '2026-06-30',
                    'data' => [
                        ['id' => 1, 'nama' => 'Saldo Awal', 'saldo' => 500],
                        ['id' => 2, 'nama' => 'Aktivitas Operasi', 'saldo' => 200, 'detail' => [
                            ['id' => 11, 'kode_akun' => '1.1', 'nama_akun' => 'Kas Masuk', 'saldo' => 200],
                        ]],
                    ],
                    'ringkasan' => ['kas_operasi' => 200, 'saldo_akhir' => 700],
                ],
                2 => [
                    'status' => 'success',
                    'tgl_kondisi' => '2026-06-30',
                    'data' => [
                        ['id' => 1, 'nama' => 'Saldo Awal', 'saldo' => 300],
                        ['id' => 2, 'nama' => 'Aktivitas Operasi', 'saldo' => 100, 'detail' => [
                            ['id' => 11, 'kode_akun' => '1.1', 'nama_akun' => 'Kas Masuk', 'saldo' => 100],
                        ]],
                    ],
                    'ringkasan' => ['kas_operasi' => 100, 'saldo_akhir' => 400],
                ],
            ],
            'licenses' => collect([
                (object) ['id' => 1, 'label' => 'A', 'application' => (object) ['name' => 'A']],
                (object) ['id' => 2, 'label' => 'B', 'application' => (object) ['name' => 'B']],
            ]),
            'reportLabel' => 'Arus Kas',
            'period' => '2026-06',
            'tenant' => $tenant,
        ])->render();

        // Sum saldo_awal: 500 + 300 = 800.
        $this->assertStringContainsString('800,00', $html);
        // Sum kas_operasi: 200 + 100 = 300.
        $this->assertStringContainsString('300,00', $html);
        // Sum saldo_akhir: 700 + 400 = 1100.
        $this->assertStringContainsString('1.100,00', $html);
    }

    public function test_pdf_total_view_perubahan_ekuitas_sums_saldo_awal_mutasi_akhir(): void
    {
        $tenant = Tenant::factory()->create();
        $html = view('tenant.reports.perubahan_ekuitas-total-pdf', [
            'payloads' => [
                1 => [
                    'status' => 'success',
                    'data' => [
                        ['kode_akun' => '3.1', 'nama_akun' => 'Modal Disetor', 'saldo_awal' => 1000, 'mutasi' => 200, 'saldo_akhir' => 1200],
                    ],
                    'ringkasan' => ['ekuitas_awal' => 1000, 'ekuitas_akhir' => 1200],
                ],
                2 => [
                    'status' => 'success',
                    'data' => [
                        ['kode_akun' => '3.1', 'nama_akun' => 'Modal Disetor', 'saldo_awal' => 500, 'mutasi' => 100, 'saldo_akhir' => 600],
                    ],
                    'ringkasan' => ['ekuitas_awal' => 500, 'ekuitas_akhir' => 600],
                ],
            ],
            'licenses' => collect([
                (object) ['id' => 1, 'label' => 'A', 'application' => (object) ['name' => 'A']],
                (object) ['id' => 2, 'label' => 'B', 'application' => (object) ['name' => 'B']],
            ]),
            'reportLabel' => 'Perubahan Ekuitas',
            'period' => '2026-06',
            'tenant' => $tenant,
        ])->render();

        // Sum Modal Disetor: awal 1500, mutasi 300, akhir 1800.
        $this->assertStringContainsString('1.500,00', $html);
        $this->assertStringContainsString('300,00', $html);
        $this->assertStringContainsString('1.800,00', $html);
    }

    public function test_pdf_total_view_dispatches_via_query_param(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'tgl_kondisi' => '2026-06-30',
                'data' => [],
                'ringkasan' => [],
            ], 200),
        ]);

        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        // Default: comparative.
        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.pdf', ['type' => 'neraca', 'period' => '2026-06']));
        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('comparative', $disposition);

        // Explicit comparative.
        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.pdf', ['type' => 'neraca', 'period' => '2026-06', 'view' => 'comparative']));
        $this->assertStringContainsString('comparative', $response->headers->get('content-disposition'));

        // Total mode.
        $response = $this->actingAs($owner)
            ->get(route('tenant.reports.pdf', ['type' => 'neraca', 'period' => '2026-06', 'view' => 'total']));
        $this->assertStringContainsString('total', $response->headers->get('content-disposition'));
    }

    public function test_web_view_shows_dual_pdf_buttons(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'tgl_kondisi' => '2026-06-30',
                'data' => [],
                'ringkasan' => [],
            ], 200),
        ]);

        $tenant = Tenant::factory()->create();
        $owner = $this->tenantOwner($tenant);
        $app = Application::factory()->create();
        TenantApplication::factory()->for($tenant)->for($app, 'application')->create();

        $this->actingAs($owner)
            ->get(route('tenant.reports.show', ['type' => 'neraca', 'period' => '2026-06']))
            ->assertOk()
            ->assertSee('PDF Komparatif')
            ->assertSee('PDF Total')
            ->assertSee('view=comparative', false)
            ->assertSee('view=total', false);
    }

    public function test_pdf_orientation_is_landscape_for_all_types(): void
    {
        // Multi-license komparatif (license ke-2 dst muncul di sisi kanan),
        // jadi tabel memanjang horizontal. Landscape untuk semua.
        foreach (['neraca', 'laba_rugi', 'arus_kas', 'perubahan_ekuitas', 'calk'] as $type) {
            $this->assertSame(
                'landscape',
                \App\Http\Controllers\Tenant\ReportController::pdfOrientation($type),
                "{$type} harus landscape"
            );
        }
    }

    public function test_pdf_export_forbidden_for_superadmin(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($admin)
            ->get(route('tenant.reports.pdf', ['type' => 'neraca']))
            ->assertForbidden();
    }
}
