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
        Schema::table('organizations', function (Blueprint $table) {
            if (! Schema::hasColumn('organizations', 'plan_id')) {
                $table->unsignedBigInteger('plan_id')->nullable()->after('id');
            }
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('set null');
            if (! Schema::hasColumn('organizations', 'license_id')) {
                $table->unsignedBigInteger('license_id')->nullable()->after('plan_id');
            }
            $table->foreign('license_id')->references('id')->on('licenses')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropForeign(['license_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['plan_id', 'license_id']);
        });
    }
};
