<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\UnitOfMeasureController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\AuthController;

// Auth routes
Route::post('/login', [AuthController::class, 'login']);

// Items routes
Route::prefix('items')->group(function () {
    Route::get('/', [ItemController::class, 'index']);
    Route::post('/', [ItemController::class, 'store']);
    Route::get('/category/{categoryId}', [ItemController::class, 'getByCategory']);
    Route::get('/stock/{stockId}', [ItemController::class, 'getByStock']);
    Route::get('/active', [ItemController::class, 'getActive']);
    Route::get('/{id}/locations', [ItemController::class, 'itemLocations']);
    Route::get('/{id}', [ItemController::class, 'show']);
    Route::put('/{id}', [ItemController::class, 'update']);
    Route::delete('/{id}', [ItemController::class, 'destroy']);
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
