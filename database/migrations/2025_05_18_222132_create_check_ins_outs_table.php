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
        Schema::create('check_ins_outs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('trackable');
            $table->foreignId('checkout_location_id')->constrained('locations')->onDelete('cascade');
            $table->timestamp('checkout_date');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->foreignId('status_out_id')->nullable()->constrained('statuses')->onDelete('set null');
            $table->foreignId('checkin_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('checkin_location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->timestamp('checkin_date')->nullable();
            $table->decimal('checkin_quantity', 10, 2)->nullable();
            $table->foreignId('status_in_id')->nullable()->constrained('statuses')->onDelete('set null');
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_ins_outs');
    }
};
