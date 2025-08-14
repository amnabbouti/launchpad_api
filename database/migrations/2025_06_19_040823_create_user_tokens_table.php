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
        Schema::dropIfExists('user_tokens');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('user_tokens', static function (Blueprint $table): void {
            $table->uuid('id');
            $table->primary('id');
            $table->uuid('user_id');
            $table->enum('token_type', ['mobile', 'admin'])->default('mobile');
            $table->text('plain_text_token');
            $table->unsignedBigInteger('personal_access_token_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('personal_access_token_id')->references('id')->on('personal_access_tokens')->onDelete('set null');
            $table->index(['user_id', 'token_type']);
            $table->index(['user_id', 'is_active']);
            $table->unique(['user_id', 'token_type']);
        });
    }
};
