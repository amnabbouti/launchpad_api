<?php

declare(strict_types = 1);

use App\Http\Controllers\WebhookController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

// API Documentation
Route::get('/', static fn () => response()->file(public_path('dist/docs.html')));

Route::get('/docs', static fn () => response()->file(public_path('dist/docs.html')));

Route::get('/assets/{file}', static function ($file) {
    $path = public_path("dist/assets/{$file}");

    if (! file_exists($path)) {
        return response()->json(['error' => 'Asset not found'], 404);
    }

    $extension   = pathinfo($file, PATHINFO_EXTENSION);
    $contentType = match ($extension) {
        'js'    => 'application/javascript',
        'css'   => 'text/css',
        'svg'   => 'image/svg+xml',
        default => 'application/octet-stream',
    };

    return response()->file($path, ['Content-Type' => $contentType]);
})->where('file', '.*');

Route::get('/swagger/openapi.json', static function () {
    $path = public_path('swagger/openapi.json');

    if (! file_exists($path)) {
        return response()->json(['error' => 'Swagger file not found'], 404);
    }

    return Response::file($path, ['Content-Type' => 'application/json']);
});

Route::prefix('webhooks')->group(static function (): void {
    Route::post('/stripe', [WebhookController::class, 'handleWebhook'])->withoutMiddleware([ValidateCsrfToken::class]);
});
