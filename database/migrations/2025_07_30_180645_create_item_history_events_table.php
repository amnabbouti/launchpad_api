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
        Schema::dropIfExists('item_history_events');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('item_history_events', static function (Blueprint $table): void {
            $table->uuid('id');
            $table->foreignUuid('org_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignUuid('item_id')->constrained()->onDelete('cascade');
            $table->enum('event_type', ['created', 'updated', 'moved', 'tracking_changed', 'maintenance_in', 'maintenance_out']);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained()->onDelete('set null');
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['org_id', 'item_id']);
            $table->index('event_type');
            $table->index('created_at');
        });
    }
};
