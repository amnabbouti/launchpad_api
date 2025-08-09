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
            if (! Schema::hasColumn('organizations', 'license_id')) {
                $table->unsignedBigInteger('license_id')->nullable()->after('id');
            }
            // Keep license FK only. Removed Cashier-related/plan-related constraints if any
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
            $table->dropForeign(['license_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn('license_id');
        });
    }
};
