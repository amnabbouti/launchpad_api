<?php

declare(strict_types=1);

use App\Constants\ErrorMessages;
use App\Http\Middleware\ApiKeyMiddleware;
use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\LogApiUsageMiddleware;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\SessionValidation;
use App\Http\Middleware\SetLocaleMiddleware;
use App\Http\Middleware\VerifyOrganizationAccess;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'api.key' => ApiKeyMiddleware::class,
            'rate.limit' => RateLimitMiddleware::class,
            'session.validation' => SessionValidation::class,
            'org.verify' => VerifyOrganizationAccess::class,
            'log.usage' => LogApiUsageMiddleware::class,
            'set.locale' => SetLocaleMiddleware::class,
        ]);

        // Prepend ForceJsonResponse, SetLocale, SessionValidation, and LogApiUsage to API middleware
        $middleware->api(prepend: [
            ForceJsonResponse::class,
            SetLocaleMiddleware::class,
            SessionValidation::class,
            LogApiUsageMiddleware::class,
        ]);

        // Global API Response Handler Middleware
        $middleware->api(append: [
            ApiResponseMiddleware::class,
        ]);

        // Customize redirect for unauthenticated guests
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => (__(ErrorMessages::UNAUTHORIZED)),
                    'data' => null,
                ], 401);
            } else {
                return route('login');
            }
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Model Not Found Exception (404)
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => (__(ErrorMessages::NOT_FOUND)),
                    'data' => null,
                ], 404);
            }
        });

        // Validation Exception (422)
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => (__(ErrorMessages::VALIDATION_FAILED)),
                    'data' => [
                        'errors' => $e->errors(),
                    ],
                ], 422);
            }
        });

        // Invalid Argument Exception (400/404)
        $exceptions->render(function (InvalidArgumentException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $message = $e->getMessage() ?: ErrorMessages::VALIDATION_FAILED;

                // Handle resource didn't found cases
                if (str_contains($message, 'not found')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => (__($message)),
                        'data' => null,
                    ], 404);
                }

                // Handle unknown query parameters
                if (str_contains($message, 'Unknown query parameter')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => (__($message)),
                        'data' => null,
                    ], 400);
                }

                // Otherwise, return 400 for other validation/argument errors
                return response()->json([
                    'status' => 'error',
                    'message' => (__($message)),
                    'data' => null,
                ], 400);
            }
        });

        // Runtime Exception (500)
        $exceptions->render(function (RuntimeException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => (__(ErrorMessages::SERVER_ERROR)),
                    'data' => null,
                ], 500);
            }
        });

        // Authentication exceptions (401)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => (__(ErrorMessages::UNAUTHORIZED)),
                    'data' => null,
                ], 401);
            }
        });

        // Unauthorized Access Exception (403)
        $exceptions->render(function (\App\Exceptions\UnauthorizedAccessException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage() ?: (__(ErrorMessages::FORBIDDEN)),
                    'data' => null,
                ], 403);
            }
        });

        // General Exception fallback (500)
        $exceptions->render(function (Exception $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                if (
                    $e instanceof InvalidArgumentException ||
                    $e instanceof RuntimeException ||
                    $e instanceof AuthenticationException
                ) {
                    return null;
                }

                return response()->json([
                    'status' => 'error',
                    'message' => (__(ErrorMessages::SERVER_ERROR)),
                    'data' => null,
                ], 500);
            }
        });

        $exceptions->shouldRenderJsonWhen(fn(Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson());
    })->create();
