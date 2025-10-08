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
        Schema::dropIfExists('unit_of_measures');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('unit_of_measures', static function (Blueprint $table): void {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignUuid('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('symbol')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['org_id', 'code']);
            $table->index('org_id');
            $table->index(['org_id', 'id']);
        });
    }
};
