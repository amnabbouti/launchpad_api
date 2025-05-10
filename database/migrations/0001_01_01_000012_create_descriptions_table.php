<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descriptions', function (Blueprint $table) {
            $table->id();
            $table->string('description_string');
            $table->string('language', 10);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('maintenance_category_id')->nullable();
            $table->index('maintenance_category_id');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descriptions');
    }
};
