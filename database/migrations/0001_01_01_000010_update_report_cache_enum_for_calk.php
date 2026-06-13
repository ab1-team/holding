<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL/SQLite portable: drop check constraint lalu recreate dengan enum baru.
        // Untuk SQLite (dipakai project ini), CHECK constraints di-attach via raw SQL.
        if (DB::getDriverName() === 'sqlite') {
            // SQLite tidak punya ALTER COLUMN MODIFY. Pakai table-recreate pattern.
            DB::statement('PRAGMA foreign_keys=OFF');
            DB::statement('ALTER TABLE report_cache RENAME TO _report_cache_old');
            DB::statement("CREATE TABLE report_cache (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_application_id INTEGER NOT NULL,
                report_type TEXT NOT NULL CHECK (report_type IN ('neraca','laba_rugi','arus_kas','perubahan_ekuitas','calk')),
                period TEXT NOT NULL,
                payload TEXT NOT NULL,
                fetched_at TEXT NOT NULL,
                expires_at TEXT NOT NULL,
                FOREIGN KEY (tenant_application_id) REFERENCES tenant_applications(id) ON DELETE CASCADE
            )");
            DB::statement('INSERT INTO report_cache SELECT id, tenant_application_id, report_type, period, payload, fetched_at, expires_at FROM _report_cache_old');
            DB::statement('DROP TABLE _report_cache_old');
            DB::statement('CREATE INDEX idx_report_cache_lookup ON report_cache (tenant_application_id, report_type, period)');
            DB::statement('PRAGMA foreign_keys=ON');
        } else {
            // Cari nama constraint check (MySQL auto-generate nama) lalu drop.
            $constraint = DB::selectOne(
                "SELECT CONSTRAINT_NAME FROM information_schema.CHECK_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                 AND CHECK_CLAUSE LIKE '%report_type%'
                 LIMIT 1"
            );
            $constraintName = $constraint?->CONSTRAINT_NAME ?? 'report_cache_report_type_chk';
            try {
                DB::statement("ALTER TABLE report_cache DROP CHECK {$constraintName}");
            } catch (\Throwable $e) {
                // Constraint tidak ada, skip
            }
            DB::statement("ALTER TABLE report_cache ADD CONSTRAINT {$constraintName} CHECK (report_type IN ('neraca','laba_rugi','arus_kas','perubahan_ekuitas','calk'))");
        }
    }

    public function down(): void
    {
        DB::statement("UPDATE report_cache SET report_type = 'catatan' WHERE report_type = 'calk'");
    }
};
