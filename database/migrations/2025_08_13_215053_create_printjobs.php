<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function down(): void {
        Schema::dropIfExists('printjobs');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('printjobs', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('org_id')->nullable()->index();
            $table->uuid('user_id')->nullable()->index();
            $table->string('entity_type', 64);
            $table->json('entity_ids');
            $table->string('format', 16);
            $table->string('preset', 64)->nullable();
            $table->json('options')->nullable();
            $table->uuid('printer_id')->nullable()->index();
            $table->unsignedInteger('copies')->default(1);
            $table->string('status', 24)->default('queued');
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->string('artifact_path')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['org_id', 'status']);
        });
    }
};
