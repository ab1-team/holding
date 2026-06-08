# Holding App — Plan & Roadmap

> Aplikasi pusat kendali (holding) berbasis Laravel yang memungkinkan client mengakses semua aplikasi subsidiary mereka dari satu tempat, beserta laporan keuangan komparatif.

---

## Daftar Isi

1. [Konsep & Arsitektur](#konsep--arsitektur)
2. [Struktur Database](#struktur-database)
3. [API Contract Subsidiary](#api-contract-subsidiary)
4. [Roadmap Pengerjaan](#roadmap-pengerjaan)

---

## Konsep & Arsitektur

```
[Vendor/Admin]
     │
     ▼
[Holding App] ─── Laravel (Backend + Blade / API)
     │
     ├── Tenant Management (multi-client)
     ├── App Registry (daftar subsidiary per client)
     ├── Quick Access (redirect ke subsidiary)
     └── Unified Report (consume API tiap subsidiary)
              │
              ├── Subsidiary A  →  GET /api/holding/...
              ├── Subsidiary B  →  GET /api/holding/...
              └── Subsidiary C  →  GET /api/holding/...
```

**Prinsip utama:**
- Holding App hanya **consume** data dari subsidiary via API — tidak menyimpan data transaksi
- Setiap subsidiary wajib mengimplementasikan **API contract standar**
- Laporan ditampilkan **side-by-side** (komparatif), matching by **kode akun + nama akun** (composite key)
- Multi-tenant: tiap client punya ruang terpisah
- Vendor assign aplikasi ke client, client manage user sendiri

---

## Struktur Database

### Tabel Inti

#### `tenants` — Data client/holding
```sql
id              BIGINT UNSIGNED PK
name            VARCHAR(255)       -- Nama perusahaan / holding client
slug            VARCHAR(100)       -- Identifier unik (untuk subdomain/URL)
email           VARCHAR(255)
phone           VARCHAR(20)
address         TEXT NULL
logo_path       VARCHAR(255) NULL
is_active       TINYINT(1) DEFAULT 1
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `users` — Semua user (vendor admin + user client)
```sql
id              BIGINT UNSIGNED PK
tenant_id       BIGINT UNSIGNED FK → tenants.id NULL  -- NULL = vendor/superadmin
name            VARCHAR(255)
email           VARCHAR(255) UNIQUE
password        VARCHAR(255)
role            ENUM('superadmin', 'tenant_owner', 'tenant_staff')
is_active       TINYINT(1) DEFAULT 1
last_login_at   TIMESTAMP NULL
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `applications` — Master daftar aplikasi milik vendor
```sql
id              BIGINT UNSIGNED PK
name            VARCHAR(255)       -- e.g. "EnStore", "EnTopUp", "SI DBM"
slug            VARCHAR(100) UNIQUE
description     TEXT NULL
icon_path       VARCHAR(255) NULL
base_url        VARCHAR(255)       -- Base URL aplikasi (di server vendor)
api_token_key   VARCHAR(255)       -- Key untuk signing request ke subsidiary API
has_financial_report TINYINT(1) DEFAULT 1  -- Apakah support unified report
is_active       TINYINT(1) DEFAULT 1
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `tenant_applications` — Aplikasi yang dimiliki tiap client
```sql
id              BIGINT UNSIGNED PK
tenant_id       BIGINT UNSIGNED FK → tenants.id
application_id  BIGINT UNSIGNED FK → applications.id
label           VARCHAR(255) NULL  -- Custom label per client jika berbeda
instance_url    VARCHAR(255)       -- URL instance spesifik client ini
api_secret      VARCHAR(255)       -- Secret untuk autentikasi ke instance ini
is_active       TINYINT(1) DEFAULT 1
activated_at    TIMESTAMP NULL
expired_at      TIMESTAMP NULL     -- NULL = tidak ada batas waktu
notes           TEXT NULL
created_at      TIMESTAMP
updated_at      TIMESTAMP

UNIQUE (tenant_id, application_id)
```

#### `report_cache` — Cache laporan dari subsidiary (opsional, untuk performa)
```sql
id              BIGINT UNSIGNED PK
tenant_application_id BIGINT UNSIGNED FK → tenant_applications.id
report_type     ENUM('neraca', 'laba_rugi', 'arus_kas', 'perubahan_ekuitas', 'catatan')
period          VARCHAR(7)         -- Format: YYYY-MM
payload         JSON               -- Response dari subsidiary API
fetched_at      TIMESTAMP
expires_at      TIMESTAMP

INDEX (tenant_application_id, report_type, period)
```

#### `activity_logs` — Log aktivitas user
```sql
id              BIGINT UNSIGNED PK
tenant_id       BIGINT UNSIGNED FK → tenants.id NULL
user_id         BIGINT UNSIGNED FK → users.id NULL
action          VARCHAR(100)       -- e.g. "login", "view_report", "access_app"
subject_type    VARCHAR(100) NULL  -- e.g. "TenantApplication"
subject_id      BIGINT NULL
metadata        JSON NULL
ip_address      VARCHAR(45) NULL
created_at      TIMESTAMP
```

---

### Relasi Ringkas

```
tenants ──< users
tenants ──< tenant_applications >── applications
tenant_applications ──< report_cache
```

---

## API Contract Subsidiary

Setiap aplikasi subsidiary **wajib** mengimplementasikan endpoint berikut:

### Autentikasi
Semua request dari Holding App disertai header:
```
X-Holding-Token: {api_secret dari tenant_applications}
X-Holding-Tenant: {tenant slug}
```

### Endpoints Wajib

```
GET /api/holding/neraca?period=YYYY-MM
GET /api/holding/laba-rugi?period=YYYY-MM
GET /api/holding/arus-kas?period=YYYY-MM
GET /api/holding/perubahan-ekuitas?period=YYYY-MM
```

### Format Response Standar

```json
{
  "status": "success",
  "period": "2024-12",
  "generated_at": "2025-01-15T10:00:00Z",
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

> **Catatan:** Matching baris laporan komparatif menggunakan **composite key `account_code` + `account_name`**. Kode akun yang sama tetapi nama berbeda (e.g. "Modal Usaha" vs "Modal Desa") akan ditampilkan sebagai baris terpisah. Contoh tampilan:

| Kode | Nama Akun | App A | App B | App C |
|------|-----------|-------|-------|-------|
| 3.1.02.01 | Modal Usaha | 50jt | - | 25jt |
| 3.1.02.01 | Modal Desa | - | 30jt | - |

---

## Roadmap Pengerjaan

### Phase 1 — Foundation (Minggu 1–2)
**Target: Aplikasi bisa jalan dengan auth dan tenant management**

- [ ] Setup project Laravel (auth, middleware, config multi-tenant)
- [ ] Migrasi database: `tenants`, `users`, `applications`
- [ ] Seeder: superadmin vendor, beberapa aplikasi master
- [ ] Modul Vendor Admin:
  - CRUD Applications (master daftar aplikasi)
  - CRUD Tenants (onboarding client baru)
- [ ] Auth: login, logout, role-based middleware (`superadmin`, `tenant_owner`, `tenant_staff`)

---

### Phase 2 — App Registry & Access (Minggu 3–4)
**Target: Client bisa lihat dan akses aplikasi mereka**

- [ ] Migrasi: `tenant_applications`
- [ ] Vendor Admin: assign/unassign aplikasi ke tenant
- [ ] Tenant Dashboard: daftar aplikasi yang dimiliki
- [ ] Quick Access: redirect ke `instance_url` masing-masing aplikasi
- [ ] Tenant Admin: manage user (invite, non-aktifkan, set role)
- [ ] Activity log dasar (login, akses aplikasi)

---

### Phase 3 — Unified Report (Minggu 5–7)
**Target: Laporan komparatif keuangan bisa ditampilkan**

- [ ] Migrasi: `report_cache`
- [ ] Service: `SubsidiaryReportService` — fetch data dari API subsidiary
- [ ] Logic matching: composite key `account_code` + `account_name` untuk grouping baris laporan
- [ ] Render laporan komparatif (tabel side-by-side per aplikasi)
- [ ] Filter: pilih periode (bulan/tahun) dan pilih aplikasi yang dibandingkan
- [ ] Caching response subsidiary (via `report_cache`)
- [ ] Implementasi API contract di minimal 1 subsidiary sebagai pilot

---

### Phase 4 — Polish & Production (Minggu 8–9)
**Target: Siap dipakai client nyata**

- [ ] Notifikasi: masa aktif langganan hampir habis
- [ ] Export laporan komparatif ke PDF / Excel
- [ ] Rate limiting & error handling API subsidiary (timeout, unavailable)
- [ ] UI/UX refinement
- [ ] Testing: unit test service layer, feature test endpoint utama
- [ ] Dokumentasi internal (setup guide, API contract doc untuk subsidiary)
- [ ] Deployment & environment config (staging → production)

---

### Phase 5 — Enhancement (Future)
> Dikerjakan setelah feedback dari client pertama

- [ ] SSO: seamless login ke subsidiary tanpa re-input password
- [ ] Dashboard summary per tenant (ringkasan angka penting dari semua app)
- [ ] Notifikasi in-app
- [ ] Audit trail lebih detail
- [ ] API untuk subsidiary melaporkan data non-keuangan (jumlah transaksi, user aktif, dll)

---

## Catatan Teknis

| Item | Keputusan |
|------|-----------|
| Stack | Laravel (Blade untuk UI) |
| Auth | Laravel Breeze / Sanctum |
| Multi-tenant | Shared database, tenant_id di setiap tabel |
| Report fetch | Sync (on-demand) + cache TTL |
| Cache | Laravel Cache (Redis direkomendasikan) |
| PDF Export | Laravel-dompdf atau Spatie |

---

*Dokumen ini bersifat living document — update sesuai perkembangan diskusi dan implementasi.*
