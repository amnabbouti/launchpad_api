<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_ins_outs', function (Blueprint $table) {
            $table->id();

            // Checkout information
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('checkout_location_id');
            $table->timestamp('checkout_date');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->unsignedBigInteger('status_out_id')->nullable();

            // Checkin information (they'll be filled when item is returned)
            $table->unsignedBigInteger('checkin_user_id')->nullable();
            $table->unsignedBigInteger('checkin_location_id')->nullable();
            $table->timestamp('checkin_date')->nullable();
            $table->decimal('checkin_quantity', 10, 2)->nullable();
            $table->unsignedBigInteger('status_in_id')->nullable();

            // Additional information
            $table->timestamp('expected_return_date')->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            // Indexes for better performance
            $table->index('user_id');
            $table->index('item_id');
            $table->index('checkout_location_id');
            $table->index('checkin_user_id');
            $table->index('checkin_location_id');
            $table->index('status_out_id');
            $table->index('status_in_id');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_ins_outs');
    }
};
