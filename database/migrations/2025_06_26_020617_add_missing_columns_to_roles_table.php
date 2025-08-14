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
        Schema::table('roles', static function (Blueprint $table): void {
            $table->dropForeign(['org_id']);
            $table->dropColumn(['description', 'org_id', 'is_system']);
        });
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('roles', static function (Blueprint $table): void {
            $table->text('description')->nullable()->after('title');
            $table->uuid('org_id')->nullable()->after('description');
            $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->boolean('is_system')->default(false)->after('org_id');
        });
    }
};
