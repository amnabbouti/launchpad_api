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
        Schema::dropIfExists('organizations');
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('organizations', static function (Blueprint $table): void {
            $table->uuid('id');
            $table->primary('id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->string('street')->nullable();
            $table->string('street_number')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->text('remarks')->nullable();
            $table->string('website')->nullable();
            $table->string('logo')->nullable();
            $table->string('industry')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('country')->nullable();
            $table->string('timezone')->nullable();
            $table->string('status')->default('active');
            $table->jsonb('settings')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
