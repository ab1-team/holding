# Panduan Integrasi Subsidiary dengan Holding App

> Dokumen ini untuk **tim developer subsidiary** (EnStore, EnTopUp, SI DBM, BUMDesma, dll.) yang ingin mengintegrasikan aplikasi mereka dengan **Holding App** — supaya tenant di Holding bisa mengakses data laporan keuangan dari aplikasi subsidiary Anda.

---

## Overview Arsitektur

```
┌─────────────────┐         ┌──────────────────┐         ┌──────────────────┐
│  Holding App    │         │  Subsidiary Anda │         │  Subsidiary Lain │
│  (Laravel)      │         │  (Laravel/etc)   │         │  (EnStore dll)   │
│                 │         │                  │         │                  │
│  /app/reports/  │ ──HTTP─▶│  /api/v1/holding/│         │  /api/v1/holding/│
│  neraca         │         │  laporan/neraca  │         │  laporan/...     │
└─────────────────┘         └──────────────────┘         └──────────────────┘
       ▲                            │                          │
       │                            ▼                          ▼
   Vendor/Admin              ┌────────────────┐         ┌────────────────┐
   - Setup aplikasi          │ Database Anda  │         │ Database lain  │
   - Generate API secret     │ (akunting dll) │         │                │
   - Assign ke tenant        └────────────────┘         └────────────────┘
   - Set domain tenant
```

**Holding App** yang bertindak sebagai **client** — menarik data laporan dari banyak subsidiary secara komparatif. **Subsidiary** yang menjadi **server** — menyediakan endpoint API yang dipanggil Holding.

---

## Yang Perlu Diset di Subsidiary Anda

### 1. Implementasi 5 Endpoint Laporan

Buat route + controller untuk melayani 5 tipe laporan keuangan:

#### a. Neraca (Balance Sheet)
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

#### b. Query Parameters (Wajib + Opsional)

| Param      | Tipe   | Wajib | Keterangan                                              |
|------------|--------|-------|---------------------------------------------------------|
| `tahun`    | string | ✅    | Format `YYYY`, mis. `2025`                              |
| `bulan`    | string | ✅    | Format `MM` (01–12), mis. `12`                          |
| `hari`     | string | ❌    | Untuk neraca: `01–31` (tanggal cut-off)                |
| `semester` | string | ❌    | Untuk arus kas: `1` (Jan–Jun) atau `2` (Jul–Des)        |
| `file_type`| string | ❌    | `1` = PDF, `0`/kosong = JSON                            |

**Contoh request dari Holding:**
```http
GET /api/v1/holding/laporan/neraca?tahun=2025&bulan=12&hari=31
GET /api/v1/holding/laporan/arus-kas?tahun=2025&bulan=12&semester=2
```

#### c. Response Standar (Wajib)

**Status 200 — sukses:**
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
    },
    {
      "account_code": "1.1.01.02",
      "account_name": "Kas di Bank",
      "amount": 3200000,
      "notes": null
    }
  ]
}
```

**Penomoran akun:** boleh sesuai standar internal Anda (PSAK, IFRS, atau SAK-EP). Holding App akan match baris menggunakan **composite key `account_code` + `account_name`** — kode akun sama dengan nama berbeda akan dianggap baris terpisah di laporan komparatif.

**Contoh kode akun sama + nama beda:**
| Kode Akun | Nama Akun | Amount |
|-----------|-----------|--------|
| `3.1.02.01` | Modal Usaha | 50,000,000 |
| `3.1.02.01` | Modal Desa | 30,000,000 |

Dua baris ini akan tampil terpisah di tabel komparatif Holding.

#### d. Response Error

| HTTP Code | Arti                                                                |
|-----------|---------------------------------------------------------------------|
| 401       | Token tidak valid / tenant tidak cocok / `is_active=false`           |
| 403       | Lisensi kedaluwarsa (`expired_at` lewat)                            |
| 5xx       | Error server (Holding retry 2x)                                    |

**Body error (opsional):**
```json
{ "message": "Token tidak valid." }
```

---

### 2. Middleware Autentikasi (Wajib)

Setiap request dari Holding **wajib** divalidasi. Buat middleware `HoldingTokenMiddleware`:

```php
// app/Http/Middleware/HoldingTokenMiddleware.php
namespace App\Http\Middleware;

use App\Models\License; // model license di subsidiary Anda
use Closure;
use Illuminate\Http\Request;

class HoldingTokenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-Holding-Token');
        $tenantId = $request->header('X-Holding-Tenant');

        if (! $token || ! $tenantId) {
            abort(401, 'Token atau tenant tidak ditemukan.');
        }

        $license = License::where('api_secret', $token)
            ->where('is_active', true)
            ->whereHas('tenant', function ($q) use ($tenantId) {
                // Subsidiary support matching by domain ATAU slug.
                // Prioritas: domain → fallback slug (untuk backward compat).
                $q->where('domain', $tenantId)
                  ->orWhere('slug', $tenantId);
            })
            ->first();

        if (! $license) {
            abort(401, 'Token tidak valid atau tenant tidak cocok.');
        }

        if ($license->isExpired()) {
            abort(403, 'Lisensi kedaluwarsa.');
        }

        // Set license ke request agar controller bisa akses
        $request->attributes->set('holding_license', $license);

        return $next($request);
    }
}
```

**Registrasi di Kernel:**
```php
// app/Http/Kernel.php
protected $middlewareAliases = [
    'holding.token' => \App\Http\Middleware\HoldingTokenMiddleware::class,
];
```

**Wajib validate SEMUA ini:**
- ✅ `X-Holding-Token` ada dan cocok dengan `api_secret` di tabel license Anda
- ✅ `X-Holding-Tenant` ada dan cocok dengan **domain** atau **slug** tenant
- ✅ License `is_active = true` (tidak dicabut)
- ✅ `expired_at` NULL atau di masa depan
- ✅ Return `401` untuk token/tenant invalid
- ✅ Return `403` untuk license nonaktif/expired

---

### 3. Controller — Query ke Database Anda

```php
// app/Http/Controllers/HoldingReportController.php
namespace App\Http\Controllers;

use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HoldingReportController extends Controller
{
    /**
     * Neraca per periode (bulan + opsional hari cut-off).
     */
    public function neraca(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tahun' => ['required', 'regex:/^\d{4}$/'],
            'bulan' => ['required', 'regex:/^(0[1-9]|1[0-2])$/'],
            'hari'  => ['nullable', 'regex:/^(0[1-9]|[12]\d|3[01])$/'],
        ]);

        $period = $validated['tahun'] . '-' . $validated['bulan'];
        $cutoff = $validated['hari'] ?? null;

        // Query database Anda — sesuaikan dengan struktur Anda
        $rows = \DB::table('chart_of_accounts')
            ->join('account_balances', 'chart_of_accounts.id', '=', 'account_balances.account_id')
            ->where('account_balances.period', $period)
            ->when($cutoff, fn($q) => $q->where('account_balances.as_of_date', '<=', "{$period}-{$cutoff}"))
            ->whereIn('chart_of_accounts.account_type', ['asset', 'liability', 'equity'])
            ->orderBy('chart_of_accounts.code')
            ->get([
                'chart_of_accounts.code as account_code',
                'chart_of_accounts.name as account_name',
                'account_balances.balance as amount',
            ]);

        return response()->json([
            'status' => 'success',
            'period' => $period,
            'generated_at' => now()->toIso8601String(),
            'data' => $rows->map(fn($r) => [
                'account_code' => (string) $r->account_code,
                'account_name' => (string) $r->account_name,
                'amount' => (int) $r->amount,
                'notes' => null,
            ])->all(),
        ]);
    }

    public function labaRugi(Request $request): JsonResponse
    {
        // Mirip neraca tapi filter account_type = revenue/expense
    }

    public function arusKas(Request $request): JsonResponse
    {
        // Opsional: handle ?semester=1 atau ?semester=2
    }

    public function perubahanEkuitas(Request $request): JsonResponse
    {
        // ...
    }

    public function calk(Request $request): JsonResponse
    {
        // Catatan Atas Laporan Keuangan — boleh return teks panjang di field 'notes'
    }
}
```

**Tips performa:**
- ✅ Index di kolom `period` dan `account_id`
- ✅ Cache query hasil per 5-10 menit (opsional, Holding sudah ada cache 30 menit)
- ✅ Response time **< 15 detik** (Holding timeout default)

---

### 4. Setup License di Database Subsidiary

Anda butuh tabel `licenses` (atau nama lain) untuk menyimpan data lisensi yang di-generate vendor di Holding App.

**Contoh struktur tabel minimal:**

```sql
CREATE TABLE licenses (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,           -- FK ke tabel tenants Anda
    api_secret VARCHAR(255) NOT NULL,            -- 40 char random string
    is_active TINYINT(1) DEFAULT 1,
    activated_at TIMESTAMP NULL,
    expired_at TIMESTAMP NULL,                   -- NULL = perpetual
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX (api_secret),
    INDEX (tenant_id, is_active)
);
```

**Kolom `tenants` perlu punya `domain`:**
```sql
ALTER TABLE tenants ADD COLUMN domain VARCHAR(255) NULL AFTER slug;
```

Domain ini yang akan dikirim Holding sebagai `X-Holding-Tenant`.

---

### 5. Konfigurasi HTTPS (Wajib untuk Production)

API secret dikirim via HTTP header — **wajib HTTPS** agar tidak bocor di network.

**Setup SSL (opsional, untuk dev/testing):**
- Laragon: otomatis handle `*.test` dengan self-signed
- Production: pakai Let's Encrypt atau SSL provider

**Verifikasi HTTPS aktif:**
```bash
curl -I https://tenant.subsidiaryanda.com/api/v1/holding/laporan/neraca
# Should return 401 (no token), not 503/SSL error
```

---

### 6. Rate Limiting (Recommended)

Tambahkan internal rate limiter di middleware untuk proteksi dari abuse:

```php
// app/Http/Middleware/HoldingTokenMiddleware.php
public function handle(Request $request, Closure $next)
{
    // ... validasi token seperti di atas ...

    // Rate limit: 60 request per menit per tenant
    $key = 'holding:' . $license->tenant_id;
    if (RateLimiter::tooManyAttempts($key, 60)) {
        abort(429, 'Terlalu banyak request. Coba lagi nanti.');
    }
    RateLimiter::hit($key, 60);

    return $next($request);
}
```

---

## Flow Onboarding dengan Holding Vendor

### Step 1: Konfirmasi Spec

Tim Anda baca spec ini, setujui endpoint + format response. Diskusikan dengan vendor jika ada custom field yang dibutuhkan.

### Step 2: Development & Testing Lokal

Setup development environment Anda:

```bash
# Clone repo subsidiary Anda
git clone <repo-subsidiary>
cd subsidiary

# Install dependencies
composer install
npm install

# Setup database lokal
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed

# Setup license test (lihat Step 3)
php artisan tinker
> \App\Models\License::create([
>     'tenant_id' => 1,
>     'api_secret' => 'test-token-development-40-chars-aaa',
>     'is_active' => true,
> ]);
> exit
```

### Step 3: Generate API Secret & Kirim ke Vendor

Setelah endpoint siap, **vendor Holding** yang akan generate API secret via panel admin mereka. Anda **tidak** generate sendiri.

**Anda cukup:**
1. Siapkan tabel `licenses` di database Anda
2. Buka komunikasi dengan vendor Holding
3. Vendor akan:
   - Generate `api_secret` 40 char di panel mereka
   - Mengirim secret ke Anda via kanal aman (mis. encrypted chat, password manager, atau HTTPS POST ke endpoint register Anda)
4. Anda simpan secret ke tabel `licenses` Anda (mapping ke tenant_id)

**Contoh API register dari vendor ke subsidiary** (opsional, kalau Anda buat endpoint ini):
```php
// POST /api/integrations/holding/register
// Headers: Authorization: Bearer <internal-vendor-secret>
// Body: { tenant_id, api_secret, instance_url, activated_at, expired_at }
```

### Step 4: Test End-to-End dari Holding

Vendor akan test dari Holding App:

```bash
# Dari server vendor (Holding)
curl -X GET "https://tenant.subsidiaryanda.com/api/v1/holding/laporan/neraca?tahun=2025&bulan=12" \
     -H "X-Holding-Token: <api_secret>" \
     -H "X-Holding-Tenant: <domain atau slug>" \
     -H "Accept: application/json"
```

**Expected response jika OK:**
```json
{
  "status": "success",
  "period": "2025-12",
  "generated_at": "...",
  "data": [...]
}
```

**Expected response jika token salah:**
```
HTTP 401
```

### Step 5: Monitor & Maintain

- ✅ Cek log aplikasi Anda untuk request dari Holding (`X-Holding-Token` header)
- ✅ Monitor response time — kalau > 5 detik, optimasi query
- ✅ Backup database `licenses` — kalau hilang, perlu vendor regenerate
- ✅ Rotasi API secret berkala (vendor bisa regenerate via panel)

---

## Troubleshooting

### Holding dapat "Aplikasi Offline" terus

**Penyebab:**
- Subsidiary tidak bisa diakses (DNS/network down)
- Response time > 15 detik
- 5xx error dari server Anda
- SSL certificate expired
- Middleware Anda reject token yang valid

**Cara debug:**
```bash
# Test dari server vendor
curl -v -X GET "https://tenant.subsidiaryanda.com/api/v1/holding/laporan/neraca?tahun=2025&bulan=12" \
     -H "X-Holding-Token: <api_secret>" \
     -H "X-Holding-Tenant: <domain>"

# Cek log Laravel Anda
tail -f storage/logs/laravel.log
```

### "Token tidak valid" padahal secret sudah benar

**Cek:**
- Apakah `api_secret` di DB Anda **persis sama** dengan yang dikirim vendor (case-sensitive, no extra whitespace)?
- Apakah `X-Holding-Tenant` value di subsidiary match dengan `tenants.domain` ATAU `tenants.slug`?
- Apakah `tenants.id` di subsidiary match dengan `tenant_id` foreign key di `licenses`?

### "Lisensi kedaluwarsa" padahal belum expired

**Cek:**
- Timezone server Anda berbeda dengan vendor
- `expired_at` di tabel license Anda benar
- Middleware Anda validasi `expired_at` dengan benar: `if ($license->expired_at && $license->expired_at->isPast())`

### Holding menampilkan kolom kosong (Offline badge)

**Cek:**
- Response JSON Anda valid? Test dengan `jq` atau Postman
- Apakah ada field `account_code`, `account_name`, `amount` di response?
- Apakah `data` array tidak kosong?

### Test dari local tapi Holding expect HTTPS

**Solusi:**
- Setup self-signed SSL di local
- Atau expose local via ngrok: `ngrok http 80` → dapat URL `https://xxx.ngrok.io` → kasih ke vendor

---

## Referensi Tambahan

- **API Contract lengkap**: [./subsidiary-api-contract.md](./subsidiary-api-contract.md)
- **Holding App Plan**: [./holding-app-plan.md](./holding-app-plan.md)
- **Setup Guide Holding**: [./setup-guide.md](./setup-guide.md)

## Kontak

Untuk pertanyaan teknis integrasi, hubungi **tim vendor Holding** Anda. Siapkan informasi berikut saat kontak:
- URL base subsidiary Anda (`https://tenant.subsidiaryanda.com`)
- Daftar tipe laporan yang sudah diimplementasi (neraca, laba-rugi, dll)
- Sample response JSON untuk satu tipe laporan
- Waktu rata-rata response time per endpoint
- Konfirmasi HTTPS aktif dan certificate valid
