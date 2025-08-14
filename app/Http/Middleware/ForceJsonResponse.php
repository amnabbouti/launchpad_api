<?php

declare(strict_types = 1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ForceJsonResponse {
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response {
        $acceptHeader = $request->header('Accept');

        if (! str_contains($acceptHeader, 'application/json')  ) {
            if (! empty($acceptHeader)) {
                $request->headers->set('Accept', $acceptHeader . ', application/json');
            } else {
                $request->headers->set('Accept', 'application/json');
            }
        }

        return $next($request);
    }
}
