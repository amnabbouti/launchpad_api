<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function down(): void {
        Schema::dropIfExists('printers');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('printers', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('org_id')->nullable()->index();
            $table->string('name');
            $table->string('driver', 32);
            $table->string('host')->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            $table->json('config')->nullable();
            $table->json('capabilities')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['org_id', 'name']);
        });
    }
};
