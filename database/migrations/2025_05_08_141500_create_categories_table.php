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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->string('name', 100);
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('path', 500)->nullable()->index();
            $table->timestamps();

            $table->index('org_id');
            $table->index('parent_id');
            $table->unique(['org_id', 'name', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
