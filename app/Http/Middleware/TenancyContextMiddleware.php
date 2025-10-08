<?php

declare(strict_types = 1);

namespace App\Http\Middleware;

use App\Services\TenancyPolicyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class TenancyContextMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {
        // Set RLS context if user is authenticated
        if ($user = auth()->user()) {
            Log::info('TenancyContextMiddleware: Setting context for user', [
                'user_id' => $user->id,
                'org_id'  => $user->org_id,
            ]);
            app(TenancyPolicyService::class)->setContext($user);
        } else {
            Log::warning('TenancyContextMiddleware: No authenticated user found');
        }

        $response = $next($request);

        // Clear RLS context after request
        app(TenancyPolicyService::class)->clearContext();

        return $response;
    }
}
