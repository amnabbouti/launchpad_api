<?php

use App\Constants\ErrorMessages;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'api.key' => \App\Http\Middleware\ApiKeyMiddleware::class,
            'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class,
            'decrypt.token' => \App\Http\Middleware\DecryptToken::class,
            'org.verify' => \App\Http\Middleware\VerifyOrganizationAccess::class,
        ]);

        // Prepend ForceJsonResponse and DecryptToken to API middleware
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
            \App\Http\Middleware\DecryptToken::class,
        ]);

        // Customize redirect for unauthenticated guests
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => ErrorMessages::UNAUTHORIZED,
                ], 401);
            } else {
                return route('login');
            }
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\InvalidArgumentException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $message = $e->getMessage() ?: ErrorMessages::VALIDATION_FAILED;

                // Handle resource not found cases
                if (str_contains($message, 'not found')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $message,
                        'code' => 404,
                    ], 404);
                }

                // Handle unknown query parameters
                if (str_contains($message, 'Unknown query parameter')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $message,
                        'code' => 400,
                    ], 400);
                }

                // Otherwise, return 400 for other validation/argument errors
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                    'code' => 400,
                ], 400);
            }
        });

        // Authentication exceptions
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => ErrorMessages::UNAUTHORIZED,
                ], 401);
            }
        });

        // Ensure JSON responses for API routes
        $exceptions->shouldRenderJsonWhen(fn(Request $request, \Throwable $e) => $request->is('api/*') || $request->expectsJson());
    })->create();
