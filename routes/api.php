<?php

use App\Http\Controllers\Api\Attachment\AttachmentController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Hierarchy\OrganizationController;
use App\Http\Controllers\Api\Hierarchy\RoleController;
use App\Http\Controllers\Api\Hierarchy\UserController;
use App\Http\Controllers\Api\Location\LocationController;
use App\Http\Controllers\Api\Location\ItemLocationController;
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
use App\Http\Controllers\Api\Stock\UnitOfMeasureController;
use App\Http\Middleware\VerifyOrganizationAccess;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\ApiKeyController;
use App\Http\Controllers\Api\Admin\ThreatDetectionController;


// API key route
Route::get('/get-env-key', fn() => response()->json([
    'apiKey' => env('OPENROUTER_API_KEY', ''),
]));

// Public routes
Route::post('/login', [AuthController::class, 'login']); // For regular app users
Route::post('/admin/login', [AdminAuthController::class, 'login']); // For super admin dashboard
Route::get('/test', fn() => response()->json(['message' => 'API is working']));

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

// Item status routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('statuses')->group(function () {
    Route::apiResource('/', StatusController::class, ['parameters' => ['' => 'status']])->except(['put'])->names('statuses');
});

// Items routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('items')->group(function () {
    Route::post('/{item}/maintenance/toggle', [ItemController::class, 'toggleMaintenance']);
    Route::apiResource('/', ItemController::class, ['parameters' => ['' => 'item']])->except(['put']);
});

// Stocks routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('stocks')->group(function () {
    Route::apiResource('/', StockController::class, ['parameters' => ['' => 'stock']])->except(['put']);
});

// ItemLocation routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('item-locations')->group(function () {
    Route::apiResource('/', ItemLocationController::class, ['parameters' => ['' => 'id']])->except(['put', 'delete']);
    Route::post('/move', [ItemLocationController::class, 'moveItem']);
});

// CheckInOut routes
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('checks')->group(function () {
    Route::get('/', [CheckInOutController::class, 'index'])->name('checks.index');
    Route::post('out/{itemLocationId}', [CheckInOutController::class, 'checkout'])->name('checks.out');
    Route::post('in/{itemLocationId}', [CheckInOutController::class, 'checkin'])->name('checks.in');
    Route::get('history/{itemLocationId}', [CheckInOutController::class, 'history'])->name('checks.history');
    Route::get('availability/{itemLocationId}', [CheckInOutController::class, 'checkAvailability'])->name('checks.availability');
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
    Route::get('/type-options', [AttachmentController::class, 'getTypeOptions']);
    Route::apiResource('/', AttachmentController::class, ['parameters' => ['' => 'attachment']])->except(['put']);
});

// API Key Management routes (Admin only)
Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('admin/api-keys')->group(function () {
    Route::get('/', [ApiKeyController::class, 'index']);
    Route::post('/', [ApiKeyController::class, 'store']);
    Route::get('/{id}', [ApiKeyController::class, 'show']);
    Route::patch('/{id}', [ApiKeyController::class, 'update']);
    Route::delete('/{id}', [ApiKeyController::class, 'destroy']);
    Route::post('/{id}/revoke', [ApiKeyController::class, 'revoke']);
    Route::post('/{id}/regenerate', [ApiKeyController::class, 'regenerate']);
    Route::get('/{id}/usage', [ApiKeyController::class, 'usage']);
    Route::get('/overview', [ApiKeyController::class, 'overview']);
});

Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('admin/security')->group(function () {
    Route::get('/threats', [ThreatDetectionController::class, 'overview']);
});



Route::middleware(['decrypt.token', 'auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});

Route::middleware(['decrypt.token', 'auth:sanctum'])->prefix('admin')->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout']);
    Route::get('/user', [AdminAuthController::class, 'user']);
});

// Fallback route
Route::fallback(fn() => response()->json(['error' => 'API resource not found'], 404));
