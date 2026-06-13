# Setup Guide — Holding App

## Requirements

- PHP 8.3+
- Composer
- SQLite (default) atau MySQL/PostgreSQL
- Node.js 20+ & npm (untuk build asset Vite)

## Quick Start (Development)

```bash
# Clone & install
git clone <repo>
cd holding
composer install
npm install

# Env
cp .env.example .env
php artisan key:generate
touch database/database.sqlite

# Database
php artisan migrate --seed

# Run
php artisan serve          # → http://127.0.0.1:8000
npm run dev                # asset watcher (Tailwind, JS)
```

### Akun Default (Seeder)

| Role         | Email                   | Password   |
|--------------|-------------------------|------------|
| Superadmin   | admin@holding.local     | password   |

Login sebagai superadmin dulu untuk membuat aplikasi & tenant.

## Multi-Tenant

Single shared database. Setiap tabel utama memiliki `tenant_id`. Middleware `role:tenant_owner,tenant_staff` membatasi akses ke data tenant user.

## Struktur Role

| Role          | Akses                                                                 |
|---------------|-----------------------------------------------------------------------|
| superadmin    | CRUD Applications, Tenants, Licenses, Users, Activity Logs            |
| tenant_owner  | Dashboard tenant, Quick Access apps, Laporan, Manage Staff            |
| tenant_staff  | Dashboard tenant, Quick Access apps, Laporan (read-only)              |

## Laporan Komparatif (Phase 3)

Lokasi: `/app/reports` setelah login sebagai user tenant.

1. Pilih tipe laporan (Neraca / Laba Rugi / Arus Kas / Perubahan Ekuitas).
2. Pilih periode (`YYYY-MM`) dan centang aplikasi yang ingin dibandingkan.
3. Tabel side-by-side muncul dengan composite key (kode akun + nama akun).
4. Cache TTL 30 menit; refetch otomatis jika expired atau subsidiary error.
5. Ekspor ke **CSV** (Excel-compatible via UTF-8 BOM) atau **PDF** (dompdf, A4 landscape).

## Setup Subsidiary untuk Integrasi Laporan

Lihat: [subsidiary-api-contract.md](subsidiary-api-contract.md)

Ringkas:
1. Subsidiary implement 4 endpoint (`/api/holding/{neraca,laba-rugi,arus-kas,perubahan-ekuitas}`).
2. Header wajib: `X-Holding-Token`, `X-Holding-Tenant`, `Accept: application/json`.
3. Response JSON sesuai format di spec.
4. Vendor generate `api_secret` per license → paste ke config subsidiary.
5. Set `has_financial_report = true` di master aplikasi Holding.

## Test

```bash
php artisan test              # semua suite (Unit + Feature)
php artisan test --testsuite=Unit
php artisan test --filter=UnifiedReportTest
```

Total: **94 tests** (Unit: 5, Feature: 89). Semua test menggunakan SQLite in-memory + `Http::fake()` untuk simulasi subsidiary.

## Deploy (Production)

1. `composer install --optimize-autoloader --no-dev`
2. `npm run build`
3. Set `APP_ENV=production`, `APP_DEBUG=false`
4. Konfigurasi database (MySQL/PostgreSQL direkomendasikan)
5. `php artisan migrate --force`
6. `php artisan config:cache route:cache view:cache`
7. Setup scheduler (`* * * * * cd /path && php artisan schedule:run`) — placeholder untuk notifikasi harian
8. Web server: nginx + php-fpm, atau Apache + mod_php
9. SSL: wajib untuk production
10. Set permission: `chmod -R 775 storage bootstrap/cache`

## Notifikasi Expired License (Phase 4)

Banner otomatis muncul di dashboard tenant jika ada license yang akan expired dalam 30 hari ke depan atau sudah lewat `expired_at`. Tenant diminta menghubungi vendor untuk perpanjangan. (Notifikasi email/queue — Phase 5.)

## Roadmap Status

- [x] Phase 1 — Foundation
- [x] Phase 2 — App Registry & Access
- [x] Phase 3 — Unified Report
- [x] Phase 4 — Polish & Production
- [ ] Phase 5 — Enhancement (SSO, summary dashboard, in-app notif, audit)
