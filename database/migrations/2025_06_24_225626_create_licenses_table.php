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
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('org_id');
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('seats')->default(1);
            $table->string('license_key')->unique();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('inactive');
            $table->json('features')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->index('org_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
