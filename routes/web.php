<?php

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

// API Documentation
Route::get('/', fn () => response()->file(public_path('docs.html')));

Route::get('/docs', fn () => response()->file(public_path('docs.html')));

Route::get('/swagger/openapi.yaml', function () {
    $path = public_path('swagger/openapi.yaml');

    if (! file_exists($path)) {
        return response()->json(['error' => 'Swagger file not found'], 404);
    }

    return Response::file($path, ['Content-Type' => 'application/x-yaml']);
});
