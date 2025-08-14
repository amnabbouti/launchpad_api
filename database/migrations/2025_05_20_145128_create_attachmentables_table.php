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
        Schema::dropIfExists('attachmentables');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('attachmentables', static function (Blueprint $table): void {
            $table->uuid('id');
            $table->primary('id');
            $table->string('attachmentable_type');
            $table->uuid('attachmentable_id');
            $table->foreignUuid('attachment_id')->constrained('attachments')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unique(['attachmentable_type', 'attachmentable_id', 'attachment_id'], 'attachmentables_unique');
            $table->index(['attachmentable_type', 'attachmentable_id']);
        });
    }
};
