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
        Schema::table('organizations', static function (Blueprint $table): void {
            $table->dropForeign(['license_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn('license_id');
        });
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('organizations', static function (Blueprint $table): void {
            if (! Schema::hasColumn('organizations', 'license_id')) {
                $table->uuid('license_id')->nullable()->after('id');
            }
            $table->foreign('license_id')->references('id')->on('licenses')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }
};
