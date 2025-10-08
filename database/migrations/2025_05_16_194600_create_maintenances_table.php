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
        Schema::dropIfExists('maintenances');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('maintenances', static function (Blueprint $table): void {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignUuid('org_id')->constrained('organizations')->cascadeOnDelete();
            $table->text('remarks')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->timestamp('date_expected_back_from_maintenance')->nullable();
            $table->timestamp('date_back_from_maintenance')->nullable();
            $table->timestamp('date_in_maintenance')->nullable();
            $table->boolean('is_repair')->default(false);
            $table->string('import_id')->nullable();
            $table->string('import_source')->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->uuid('maintainable_id')->nullable();
            $table->string('maintainable_type')->nullable();
            $table->foreignUuid('status_out_id')->nullable()->constrained('statuses')->nullOnDelete();
            $table->foreignUuid('status_in_id')->nullable()->constrained('statuses')->nullOnDelete();
            $table->timestamps();
            $table->index('org_id');
            $table->index(['org_id', 'id']);
            $table->index(['org_id', 'user_id']);
            $table->index(['org_id', 'supplier_id']);
            $table->index(['org_id', 'maintainable_id']);
            $table->index(['org_id', 'maintainable_type']);
            $table->index(['maintainable_id', 'maintainable_type']);
            $table->index('status_out_id');
            $table->index('status_in_id');
        });
    }
};
