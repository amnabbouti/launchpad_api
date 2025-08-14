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
        Schema::dropIfExists('locations');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('locations', static function (Blueprint $table): void {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignUuid('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('code', 50);
            $table->foreignUuid('parent_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('path', 500)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['org_id', 'code']);
            $table->index('org_id');
            $table->index('parent_id');
        });
    }
};
