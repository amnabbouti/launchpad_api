<?php

declare(strict_types = 1);

namespace App\Http\Middleware;

use App\Constants\ErrorMessages;
use App\Exceptions\UnauthorizedAccessException;
use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureActiveLicense {
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {
        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        if (\App\Services\AuthorizationEngine::inSystemScope($user)) {
            return $next($request);
        }

        if (! $user->org_id) {
            throw new UnauthorizedAccessException((__(ErrorMessages::ORG_REQUIRED)));
        }

        $organization = Organization::find($user->org_id);
        if (! $organization || ! $organization->hasActiveLicense()) {
            throw new UnauthorizedAccessException((__(ErrorMessages::LICENSE_REQUIRED)));
        }

        return $next($request);
    }
}
