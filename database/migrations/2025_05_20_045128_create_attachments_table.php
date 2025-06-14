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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->nullable()->constrained('organizations')->onDelete('cascade');
            $table->string('filename');
            $table->string('original_filename');
            $table->string('file_type');
            $table->string('extension')->nullable();
            $table->bigInteger('size')->default(0);
            $table->text('file_path');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            $table->index('org_id');
            $table->index(['org_id', 'id']);
            $table->index(['org_id', 'user_id']);
            $table->index(['org_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
