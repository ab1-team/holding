<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_application_id')->constrained('tenant_applications')->cascadeOnDelete();
            $table->enum('report_type', ['neraca', 'laba_rugi', 'arus_kas', 'perubahan_ekuitas', 'catatan']);
            $table->string('period', 7);
            $table->json('payload');
            $table->timestamp('fetched_at');
            $table->timestamp('expires_at');

            $table->index(['tenant_application_id', 'report_type', 'period'], 'idx_report_cache_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_cache');
    }
};
