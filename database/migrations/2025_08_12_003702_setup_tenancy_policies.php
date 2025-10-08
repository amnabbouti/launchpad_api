<?php

declare(strict_types = 1);

use App\Services\TenancyPolicyService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Reverse the migrations.
     */
    public function down(): void {
        app(TenancyPolicyService::class)->dropAllPolicies();
    }

    /**
     * Run the migrations.
     */
    public function up(): void {
        app(TenancyPolicyService::class)->dropAllPolicies();

        app(TenancyPolicyService::class)->setupAllPolicies();
    }
};
