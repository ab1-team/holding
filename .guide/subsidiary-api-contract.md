# API Contract — Holding App ↔ Subsidiary

> Setiap aplikasi subsidiary (EnStore, EnTopUp, SI DBM, BUMDesma, dll.) yang ingin diintegrasikan dengan Holding App **wajib** mengimplementasikan kontrak di bawah ini.

## Autentikasi

Setiap request dari Holding App disertai header berikut:

```
X-Holding-Token  : {api_secret milik tenant_applications}
X-Holding-Tenant : {tenant.domain — fallback ke slug jika domain kosong}
Accept           : application/json
```

Subsidiary **wajib** memvalidasi:
- `X-Holding-Token` cocok dengan `api_secret` yang di-generate vendor di Holding App.
- `X-Holding-Tenant` cocok dengan `domain` (atau `slug` jika domain kosong) tenant yang memiliki license tersebut.

Jika token tidak valid, return `401 Unauthorized`. Jika valid tapi license nonaktif/expired, return `403 Forbidden`.

## Endpoints Wajib

| #   | Method | Path                                                                | Keterangan                                    |
|-----|--------|---------------------------------------------------------------------|-----------------------------------------------|
| 1   | GET    | `/api/v1/holding/laporan/neraca?tahun=YYYY&bulan=MM`                 | Laporan neraca per periode                    |
| 2   | GET    | `/api/v1/holding/laporan/laba-rugi?tahun=YYYY&bulan=MM`              | Laporan laba rugi                             |
| 3   | GET    | `/api/v1/holding/laporan/arus-kas?tahun=YYYY&bulan=MM&semester=1`   | Laporan arus kas (semester: 1 atau 2)         |
| 4   | GET    | `/api/v1/holding/laporan/perubahan-ekuitas?tahun=YYYY&bulan=MM`      | Laporan perubahan ekuitas                     |
| 5   | GET    | `/api/v1/holding/laporan/calk?tahun=YYYY&bulan=MM`                   | Catatan atas laporan keuangan (CALK)          |

### Query Parameters

| Param      | Tipe   | Wajib | Keterangan                                              |
|------------|--------|-------|---------------------------------------------------------|
| `tahun`    | string | Ya    | Format `YYYY`, mis. `2025`                              |
| `bulan`    | string | Ya    | Format `MM` (01–12), mis. `12`                          |
| `hari`     | string | Tidak | Untuk neraca: `01–31` (tanggal cut-off)                |
| `semester` | string | Tidak | Untuk arus kas: `1` (Jan–Jun) atau `2` (Jul–Des)        |
| `file_type`| string | Tidak | `1` = PDF, `0`/kosong = JSON                            |

### Response Gagal

| Code | Arti                                                                |
|------|---------------------------------------------------------------------|
| 401  | Token tidak valid / tenant tak cocok / `is_active=false`           |
| 403  | Lisensi kedaluwarsa (`expired_at` lewat)                            |
| 5xx  | Error server (Holding retry 2x lalu tandai "Offline")              |

## Format Response Standar

**Sukses (200):**
```json
{
  "status": "success",
  "period": "2025-12",
  "generated_at": "2026-01-15T10:00:00Z",
  "data": [
    {
      "account_code": "1.1.01.01",
      "account_name": "Kas",
      "amount": 10500000,
      "notes": null
    }
  ]
}
```

## Penomoran Akun

Holding App melakukan **composite-key matching**: baris dianggap sama jika `account_code` + `account_name` identik. Kode akun yang sama dengan nama berbeda akan dianggap baris berbeda di laporan komparatif (mis. "Modal Usaha" vs "Modal Desa" di kode `3.1.02.01` akan tampil di baris terpisah).

## Error Handling & Limit

- Wajib respond dalam **15 detik** (Holding App timeout default). Jika lebih, akan dianggap gagal.
- Holding App melakukan **2 retry** otomatis untuk transient errors (5xx, timeout). Response 4xx tidak di-retry.
- Holding App menyimpan cache hasil per `(tenant_app, report_type, period)` selama **30 menit** (TTL). Tidak ada batasan rate tambahan di sisi subsidiary, tapi implementasi rate-limiter internal sangat disarankan.

## Contoh Implementasi (Laravel)

```php
// routes/api.php
Route::middleware('holding.token')->prefix('api/v1/holding')->group(function () {
    Route::get('/laporan/neraca', [HoldingReportController::class, 'neraca']);
    Route::get('/laporan/laba-rugi', [HoldingReportController::class, 'labaRugi']);
    Route::get('/laporan/arus-kas', [HoldingReportController::class, 'arusKas']);
    Route::get('/laporan/perubahan-ekuitas', [HoldingReportController::class, 'perubahanEkuitas']);
    Route::get('/laporan/calk', [HoldingReportController::class, 'calk']);
});
```

```php
// middleware validasi token
public function handle($request, Closure $next)
{
    $token = $request->header('X-Holding-Token');
    $tenantDomain = $request->header('X-Holding-Tenant');
    $license = License::where('api_secret', $token)
        ->whereHas('tenant', function ($q) use ($tenantDomain) {
            $q->where('domain', $tenantDomain)
              ->orWhere('slug', $tenantDomain); // fallback
        })
        ->where('is_active', true)
        ->first();

    abort_unless($license, 401, 'Token tidak valid.');
    abort_if($license->isExpired(), 403, 'Lisensi kedaluwarsa.');

    return $next($request);
}
```

## Konfigurasi di Holding App (Vendor Admin)

1. Buka menu **Aplikasi** → buat/atur aplikasi subsidiary.
2. Buka **Tenant** → pilih tenant → atur field `domain` (opsional, fallback ke slug).
3. Pilih aplikasi → isi `instance_url` (URL subsidiary tenant tsb).
4. Sistem generate `api_secret` 40-char — **salin dan paste ke konfigurasi subsidiary** sebagai `X-Holding-Token` validator.
5. Set `expired_at` jika ada masa aktif.

## Onboarding Checklist

- [ ] Subsidiary implement 5 endpoint + middleware token
- [ ] Holding App: daftarkan aplikasi di menu Aplikasi
- [ ] Holding App: assign ke tenant + set domain (opsional) + generate api_secret
- [ ] Subsidiary: simpan api_secret di config
- [ ] Test end-to-end via menu Laporan di Holding App
