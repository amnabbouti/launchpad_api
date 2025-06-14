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
        Schema::create('api_key_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('token_id');
            $table->string('endpoint')->nullable();
            $table->string('method', 10)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->integer('response_status')->nullable();
            $table->decimal('response_time', 8, 3)->nullable(); // in milliseconds
            $table->json('request_data')->nullable();
            $table->date('usage_date')->default(now()->toDateString());
            $table->timestamps();

            $table->foreign('token_id')->references('id')->on('personal_access_tokens')->onDelete('cascade');
            $table->index(['token_id', 'usage_date']);
            $table->index('endpoint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_key_usage');
    }
};
