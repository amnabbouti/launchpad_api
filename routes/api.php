<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ItemLocationController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UnitOfMeasureController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// API key route
Route::get('/get-env-key', function () {
    return response()->json([
        'apiKey' => env('OPENROUTER_API_KEY', '')
    ]);
});

// Users routes
Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/role/{role}', [UserController::class, 'getByRole']);
    Route::get('/with-items', [UserController::class, 'getWithItems']);
    Route::get('/active', [UserController::class, 'getActive']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
});

// Locations routes
Route::prefix('locations')->group(function () {
    Route::get('/', [LocationController::class, 'index']);
    Route::post('/', [LocationController::class, 'store']);
    Route::get('/with-items', [LocationController::class, 'getWithItems']);
    Route::get('/active', [LocationController::class, 'getActive']);
    Route::get('/{id}', [LocationController::class, 'show']);
    Route::put('/{id}', [LocationController::class, 'update']);
    Route::delete('/{id}', [LocationController::class, 'destroy']);
});

// ItemLocation routes
// Maintenances routes
Route::prefix('maintenances')->group(function () {
    Route::get('/', [MaintenanceController::class, 'index']);
    Route::post('/', [MaintenanceController::class, 'store']);
    Route::get('/{id}', [MaintenanceController::class, 'show']);
    Route::put('/{id}', [MaintenanceController::class, 'update']);
    Route::delete('/{id}', [MaintenanceController::class, 'destroy']);
});
Route::prefix('item-locations')->group(function () {
    Route::get('/', [ItemLocationController::class, 'index']);
    Route::post('/', [ItemLocationController::class, 'store']);
    Route::get('/{id}', [ItemLocationController::class, 'show']);
    Route::put('/{id}', [ItemLocationController::class, 'update']);
    Route::delete('/{id}', [ItemLocationController::class, 'destroy']);
});

// Auth routes
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// Items routes
Route::prefix('items')->group(function () {
    Route::get('/', [ItemController::class, 'index']);
    Route::post('/', [ItemController::class, 'store']);
    Route::get('/category/{categoryId}', [ItemController::class, 'getByCategory']);
    Route::get('/items/code/{code}', [ItemController::class, 'getByCode']);
    Route::get('/stock/{stockId}', [ItemController::class, 'getByStock']);
    Route::get('/active', [ItemController::class, 'getActive']);
    Route::get('/{id}/locations', [ItemController::class, 'itemLocations']);
    Route::get('/{id}', [ItemController::class, 'show']);
    Route::put('/{id}', [ItemController::class, 'update']);
    Route::delete('/{id}', [ItemController::class, 'destroy']);

    // All check-in/out endpoints require authentication
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/{id}/checkout', [\App\Http\Controllers\Api\CheckInOutController::class, 'checkout']);
        Route::post('/{id}/checkin', [\App\Http\Controllers\Api\CheckInOutController::class, 'checkin']);
        Route::get('/{id}/checkouts', [\App\Http\Controllers\Api\CheckInOutController::class, 'history']);
    });
});

// Stocks routes
Route::prefix('stocks')->group(function () {
    Route::get('/', [StockController::class, 'index']);
    Route::post('/', [StockController::class, 'store']);
    Route::get('/with-items', [StockController::class, 'getWithItems']);
    Route::get('/active', [StockController::class, 'getActive']);
    Route::get('/{id}', [StockController::class, 'show']);
    Route::put('/{id}', [StockController::class, 'update']);
    Route::delete('/{id}', [StockController::class, 'destroy']);
});

// Units of measure routes
Route::prefix('units-of-measure')->group(function () {
    Route::get('/', [UnitOfMeasureController::class, 'index']);
    Route::post('/', [UnitOfMeasureController::class, 'store']);
    Route::get('/name/{name}', [UnitOfMeasureController::class, 'getByName']);
    Route::get('/active', [UnitOfMeasureController::class, 'getActive']);
    Route::get('/{id}', [UnitOfMeasureController::class, 'show']);
    Route::put('/{id}', [UnitOfMeasureController::class, 'update']);
    Route::delete('/{id}', [UnitOfMeasureController::class, 'destroy']);
});

// Categories routes
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::post('/', [CategoryController::class, 'store']);
    Route::get('/with-items', [CategoryController::class, 'getWithItems']);
    Route::get('/name/{name}', [CategoryController::class, 'getByName']);
    Route::get('/active', [CategoryController::class, 'getActive']);
    Route::get('/{id}', [CategoryController::class, 'show']);
    Route::put('/{id}', [CategoryController::class, 'update']);
    Route::delete('/{id}', [CategoryController::class, 'destroy']);
});

// Suppliers routes
Route::prefix('suppliers')->group(function () {
    Route::get('/', [SupplierController::class, 'index']);
    Route::post('/', [SupplierController::class, 'store']);
    Route::get('/with-items', [SupplierController::class, 'getWithItems']);
    Route::get('/name/{name}', [SupplierController::class, 'getByName']);
    Route::get('/active', [SupplierController::class, 'getActive']);
    Route::get('/{id}', [SupplierController::class, 'show']);
    Route::put('/{id}', [SupplierController::class, 'update']);
    Route::delete('/{id}', [SupplierController::class, 'destroy']);
});
