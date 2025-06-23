<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApiKeyUsage;
use App\Models\PersonalAccessToken;
use App\Models\UserToken;
use Illuminate\Support\Facades\DB;

class CheckSecurityData extends Command
{
    protected $signature = 'security:check-data';
    protected $description = 'Check if security analytics data exists in the database';

    public function handle()
    {
        $this->info('🔍 Checking Security Analytics Data...');
        $this->newLine();

        // Check API Key Usage data
        $usageCount = ApiKeyUsage::count();
        $this->line("📊 API Key Usage Records: {$usageCount}");
        
        if ($usageCount > 0) {
            $recentUsage = ApiKeyUsage::where('created_at', '>=', now()->subDays(7))->count();
            $this->line("   └── Last 7 days: {$recentUsage}");
            
            $uniqueIPs = ApiKeyUsage::distinct('ip_address')->count('ip_address');
            $this->line("   └── Unique IPs: {$uniqueIPs}");
            
            $uniqueEndpoints = ApiKeyUsage::distinct('endpoint')->count('endpoint');
            $this->line("   └── Unique Endpoints: {$uniqueEndpoints}");
        }

        // Check Personal Access Tokens (API Keys)
        $apiKeyCount = PersonalAccessToken::count();
        $this->line("🔑 API Keys: {$apiKeyCount}");
        
        if ($apiKeyCount > 0) {
            $activeApiKeys = PersonalAccessToken::where('is_active', true)->count();
            $this->line("   └── Active: {$activeApiKeys}");
        }

        // Check User Tokens
        $userTokenCount = UserToken::count();
        $this->line("👤 User Tokens: {$userTokenCount}");
        
        if ($userTokenCount > 0) {
            $activeUserTokens = UserToken::where('is_active', true)->count();
            $this->line("   └── Active: {$activeUserTokens}");
        }

        $this->newLine();

        // Provide recommendations
        if ($usageCount === 0) {
            $this->warn('⚠️  No API usage data found!');
            $this->info('💡 Run the security test data seeder:');
            $this->line('   php artisan db:seed --class=SecurityTestDataSeeder');
        } else {
            $this->info('✅ Analytics data is available!');
            
            // Test a sample query
            $sampleIpAnalytics = DB::table('api_key_usage')
                ->join('personal_access_tokens', 'api_key_usage.token_id', '=', 'personal_access_tokens.id')
                ->where('api_key_usage.created_at', '>=', now()->subDays(7))
                ->selectRaw('COUNT(DISTINCT api_key_usage.ip_address) as unique_ips, COUNT(*) as total_requests')
                ->first();
                
            if ($sampleIpAnalytics) {
                $this->info("📈 Sample Analytics (7 days):");
                $this->line("   └── Unique IPs: {$sampleIpAnalytics->unique_ips}");
                $this->line("   └── Total Requests: {$sampleIpAnalytics->total_requests}");
            }
        }

        $this->newLine();
        return 0;
    }
} 