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
        Schema::dropIfExists('attachments');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('attachments', static function (Blueprint $table): void {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignUuid('org_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('file_type');
            $table->string('extension')->nullable();
            $table->bigInteger('size')->default(0);
            $table->text('file_path');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('org_id');
            $table->index(['org_id', 'id']);
            $table->index(['org_id', 'user_id']);
            $table->index(['org_id', 'category']);
        });
    }
};
