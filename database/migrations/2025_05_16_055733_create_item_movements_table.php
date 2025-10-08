<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function down(): void {
        Schema::dropIfExists('item_movements');
    }

    public function up(): void {
        Schema::create('item_movements', static function (Blueprint $table): void {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignUuid('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignUuid('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignUuid('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('moved_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->enum('movement_type', ['transfer', 'initial_placement', 'adjustment'])->default('transfer');
            $table->text('reason')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->string('reference_type')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['org_id', 'item_id']);
            $table->index('from_location_id');
            $table->index('to_location_id');
        });
    }
};
