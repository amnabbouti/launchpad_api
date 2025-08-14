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
        Schema::dropIfExists('categories');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('categories', static function (Blueprint $table): void {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignUuid('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 100);
            $table->foreignUuid('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('path', 500)->nullable()->index();
            $table->enum('default_tracking_mode', ['abstract', 'standard', 'serialized'])->nullable();
            $table->boolean('allow_tracking_transitions')->default(true);
            $table->decimal('min_value_for_serialized', 10, 2)->nullable();
            $table->timestamps();
            $table->index('org_id');
            $table->index('parent_id');
            $table->unique(['org_id', 'name', 'parent_id']);
        });
    }
};
