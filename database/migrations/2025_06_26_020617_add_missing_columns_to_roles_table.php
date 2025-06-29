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
        Schema::table('roles', function (Blueprint $table) {
            // Add description column
            $table->text('description')->nullable()->after('title');
            
            // Add org_id to scope custom roles to organizations (NULL for system roles)
            $table->unsignedBigInteger('org_id')->nullable()->after('description');
            $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
            
            // Add is_system flag to identify system roles
            $table->boolean('is_system')->default(false)->after('org_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['org_id']);
            $table->dropColumn(['description', 'org_id', 'is_system']);
        });
    }
};
