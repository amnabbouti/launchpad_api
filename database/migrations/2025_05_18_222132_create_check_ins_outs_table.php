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
        Schema::dropIfExists('check_ins_outs');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('check_ins_outs', static function (Blueprint $table): void {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignUuid('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('trackable_id');
            $table->string('trackable_type');
            $table->foreignUuid('checkout_location_id')->constrained('locations')->cascadeOnDelete();
            $table->timestamp('checkout_date');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->foreignUuid('status_out_id')->nullable()->constrained('statuses')->nullOnDelete();
            $table->foreignUuid('checkin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('checkin_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->timestamp('checkin_date')->nullable();
            $table->decimal('checkin_quantity', 10, 2)->nullable();
            $table->foreignUuid('status_in_id')->nullable()->constrained('statuses')->nullOnDelete();
            $table->timestamp('expected_return_date')->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['org_id', 'reference']);
            $table->index('org_id');
            $table->index(['org_id', 'id']);
            $table->index(['org_id', 'user_id']);
            $table->index(['org_id', 'trackable_id', 'trackable_type']);
            $table->index(['trackable_id', 'trackable_type']);
            $table->index(['org_id', 'checkout_location_id']);
            $table->index(['org_id', 'checkin_user_id']);
            $table->index(['org_id', 'checkin_location_id']);
            $table->index('status_out_id');
            $table->index('status_in_id');
        });
    }
};
