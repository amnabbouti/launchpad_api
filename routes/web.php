<?php

use App\Http\Controllers\DashboardController;
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

// Admin Login Routes
Route::get('/admin/login', function () {
    return \Inertia\Inertia::render('Login');
})->name('admin.login');

Route::post('/admin/login', function (\Illuminate\Http\Request $request) {
    // just redirect to dashboard to see the login
    return redirect('/admin/dashboard');
})->name('admin.login.post');

// Dashboard Routes
Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/admin/dashboard/api-management', function () {
    return \Inertia\Inertia::render('api-management/page');
})->name('dashboard.api');
Route::get('/admin/dashboard/client-management', function () {
    return \Inertia\Inertia::render('ClientManagement');
})->name('dashboard.clients');
Route::get('/admin/dashboard/users', [DashboardController::class, 'users'])->name('dashboard.users');
Route::post('/admin/users/update', [DashboardController::class, 'updateUser'])->name('users.update');
Route::get('/admin/dashboard/inventory', function () {
    return \Inertia\Inertia::render('Inventory', [
        'items' => [],
        'stats' => [
            'total' => 0,
            'inStock' => 0,
            'lowStock' => 0,
            'outOfStock' => 0,
            'categories' => [],
        ],
        'activities' => [],
    ]);
})->name('dashboard.inventory');

// Home route
Route::get('/home', function () {
    return \Inertia\Inertia::render('page');
})->name('home');
