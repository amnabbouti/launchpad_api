<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Controllers\Api\BaseController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifyOrganizationAccess extends BaseController
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {
        if ($guard) {
            Auth::shouldUse($guard);
        }

        if (! Auth::check()) {
            return $this->errorResponse('Authentication required', Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (! $user->org_id) {
            return $this->errorResponse('User must belong to an organization', Response::HTTP_FORBIDDEN);
        }

        $organization = \App\Models\Organization::find($user->org_id);
        if (! $organization || ! $organization->hasActiveLicense()) {
            return $this->errorResponse('Organization does not have an active license', Response::HTTP_PAYMENT_REQUIRED);
        }

        return $next($request);
    }
}
