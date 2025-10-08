<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class CreateItemLocationsTable extends Migration {
    public function down(): void {
        Schema::dropIfExists('item_locations');
    }

    public function up(): void {
        Schema::create('item_locations', static function (Blueprint $table): void {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignUuid('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignUuid('location_id')->constrained('locations')->cascadeOnDelete();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->date('moved_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['org_id', 'item_id', 'location_id']);
            $table->index('org_id');
            $table->index(['org_id', 'id']);
            $table->index(['org_id', 'item_id']);
            $table->index(['org_id', 'location_id']);
            $table->index('moved_date');
        });
    }
}
