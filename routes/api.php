<?php

use App\Http\Controllers\Api\Attachment\AttachmentController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Hierarchy\OrganizationController;
use App\Http\Controllers\Api\Hierarchy\RoleController;
use App\Http\Controllers\Api\Hierarchy\UserController;
use App\Http\Controllers\Api\Location\LocationController;
use App\Http\Controllers\Api\Location\StockItemLocationController;
use App\Http\Controllers\Api\Maintenance\MaintenanceCategoryController;
use App\Http\Controllers\Api\Maintenance\MaintenanceConditionController;
use App\Http\Controllers\Api\Maintenance\MaintenanceController;
use App\Http\Controllers\Api\Maintenance\MaintenanceDetailController;
use App\Http\Controllers\Api\Operations\CheckInOutController;
use App\Http\Controllers\Api\Operations\StatusController;
use App\Http\Controllers\Api\Operations\SupplierController;
use App\Http\Controllers\Api\Stock\CategoryController;
use App\Http\Controllers\Api\Stock\ItemController;
use App\Http\Controllers\Api\Stock\StockController;
use App\Http\Controllers\Api\Stock\StockItemController;
use App\Http\Controllers\Api\Stock\UnitOfMeasureController;
use App\Http\Middleware\VerifyOrganizationAccess;
use Illuminate\Support\Facades\Route;

// API key route
Route::get('/get-env-key', fn () => response()->json([
    'apiKey' => env('OPENROUTER_API_KEY', ''),
]));

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::get('/test', fn () => response()->json(['message' => 'API is working']));

// Organizations routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('organizations')->group(function () {
    Route::get('/active', [OrganizationController::class, 'getActive']);
    Route::apiResource('/', OrganizationController::class, ['parameters' => ['' => 'organization']])->except(['put']);
});

// Users routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('users')->group(function () {
    Route::apiResource('/', UserController::class, ['parameters' => ['' => 'user']])->except(['put'])->names('users');
});

// Roles routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('roles')->group(function () {
    Route::get('/all', [RoleController::class, 'all']);
    Route::get('/organization', [RoleController::class, 'organizationRoles']);
    Route::apiResource('/', RoleController::class, ['parameters' => ['' => 'role']])->except(['put']);
});

// Locations routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('locations')->group(function () {
    Route::apiResource('/', LocationController::class, ['parameters' => ['' => 'location']])->except(['put']);
});

// Maintenances routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('maintenances')->group(function () {
    Route::apiResource('/', MaintenanceController::class, ['parameters' => ['' => 'maintenance']])->except(['put']);
});

// Maintenance Categories routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('maintenance')->group(function () {
    Route::apiResource('categories', MaintenanceCategoryController::class)->except(['put']);
});

// Maintenance Conditions routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('maintenance-conditions')->group(function () {
    Route::apiResource('/', MaintenanceConditionController::class, ['parameters' => ['' => 'maintenance_condition']])->except(['put']);
});

// Maintenance Details routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('maintenance-details')->group(function () {
    Route::apiResource('/', MaintenanceDetailController::class, ['parameters' => ['' => 'maintenance_detail']])->except(['put']);
});

// Status routes (consolidated - handles both Status and ItemStatus via type parameter)
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('statuses')->group(function () {
    Route::apiResource('/', StatusController::class, ['parameters' => ['' => 'status']])->except(['put'])->names('statuses');
});

// Items routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('items')->group(function () {
    Route::apiResource('/', ItemController::class, ['parameters' => ['' => 'item']])->except(['put']);
});

// Stocks routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('stocks')->group(function () {
    Route::apiResource('/', StockController::class, ['parameters' => ['' => 'stock']])->except(['put']);
});

// StockItems routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('stock-items')->group(function () {
    Route::apiResource('/', StockItemController::class, ['parameters' => ['' => 'stock_item']])->except(['put']);
});

// StockItemLocation routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('stock-item-locations')->group(function () {
    Route::apiResource('/', StockItemLocationController::class, ['parameters' => ['' => 'id']])->except(['put', 'delete']);
    Route::post('/move', [StockItemLocationController::class, 'moveStockItem']);
});

// CheckInOut routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('check-ins-outs')->group(function () {
    Route::post('checkout/{stockItemId}', [CheckInOutController::class, 'checkout'])->name('check-ins-outs.checkout');
    Route::post('checkin/{stockItemId}', [CheckInOutController::class, 'checkin'])->name('check-ins-outs.checkin');
    Route::get('history/{stockItemId}', [CheckInOutController::class, 'history'])->name('check-ins-outs.history');
});

// Units of measure routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('units-of-measure')->group(function () {
    Route::apiResource('/', UnitOfMeasureController::class, ['parameters' => ['' => 'unit_of_measure']])->except(['put']);
});

// Categories routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('categories')->group(function () {
    Route::apiResource('/', CategoryController::class, ['parameters' => ['' => 'category']])->except(['put']);
});

// Suppliers routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('suppliers')->group(function () {
    Route::apiResource('/', SupplierController::class, ['parameters' => ['' => 'supplier']])->except(['put']);
});

// Attachments routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('attachments')->group(function () {
    Route::apiResource('/', AttachmentController::class, ['parameters' => ['' => 'attachment']])->except(['put']);
});

// Auth routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});

// Fallback route
Route::fallback(fn () => response()->json(['error' => 'API resource not found'], 404));
