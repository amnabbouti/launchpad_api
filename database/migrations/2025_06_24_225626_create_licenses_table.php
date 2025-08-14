<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('licenses');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('licenses', static function (Blueprint $table): void {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignUuid('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('seats')->default(1);
            $table->string('license_key')->unique();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('inactive');
            $table->jsonb('features')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();

            $table->index('org_id');
        });
    }
};
