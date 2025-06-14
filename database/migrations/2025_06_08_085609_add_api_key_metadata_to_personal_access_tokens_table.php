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
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
            $table->unsignedBigInteger('organization_id')->nullable()->after('tokenable_id');
            $table->integer('rate_limit_per_hour')->default(1000)->after('abilities');
            $table->integer('rate_limit_per_day')->default(24000)->after('rate_limit_per_hour');
            $table->integer('rate_limit_per_month')->default(720000)->after('rate_limit_per_day');
            $table->json('allowed_ips')->nullable()->after('rate_limit_per_month');
            $table->json('allowed_origins')->nullable()->after('allowed_ips');
            $table->boolean('is_active')->default(true)->after('allowed_origins');
            $table->string('key_type', 20)->default('api')->after('is_active'); // api, webhook, integration
            $table->json('metadata')->nullable()->after('key_type');
            
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->index(['organization_id', 'is_active']);
            $table->index(['key_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id', 'is_active']);
            $table->dropIndex(['key_type', 'is_active']);
            
            $table->dropColumn([
                'description',
                'organization_id',
                'rate_limit_per_hour',
                'rate_limit_per_day',
                'rate_limit_per_month',
                'allowed_ips',
                'allowed_origins',
                'is_active',
                'key_type',
                'metadata'
            ]);
        });
    }
};
