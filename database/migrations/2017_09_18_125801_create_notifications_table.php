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
        Schema::dropIfExists('notifications');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', static function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->uuid('notifiable_id');
                $table->string('notifiable_type');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }
};
