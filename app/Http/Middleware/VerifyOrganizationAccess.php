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

        if (! Auth::check() || (! Auth::user()->org_id && ! Auth::user()->isSuperAdmin())) {
            return $this->errorResponse('User must belong to an organization', Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
