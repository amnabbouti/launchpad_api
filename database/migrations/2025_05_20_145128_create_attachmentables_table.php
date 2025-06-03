<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attachmentables', function (Blueprint $table) {
            $table->id();
            $table->string('attachmentable_type');
            $table->unsignedBigInteger('attachmentable_id');
            $table->foreignId('attachment_id')->constrained('attachments')->onUpdate('cascade')->onDelete('cascade');

            $table->unique(['attachmentable_type', 'attachmentable_id', 'attachment_id'], 'attachmentables_unique');
            $table->index(['attachmentable_type', 'attachmentable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachmentables');
    }
};
