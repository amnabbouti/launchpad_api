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
            $table->dropIndex(['stripe_id']);
            $table->dropColumn([
                'stripe_id',
                'pm_type',
                'pm_last_four',
                'trial_ends_at',
            ]);
        });
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('organizations', static function (Blueprint $table): void {
            $table->string('stripe_id')->nullable()->index();
        });
    }
};
