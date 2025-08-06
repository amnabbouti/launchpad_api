<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\ApiKeyController;
use App\Http\Controllers\Api\Admin\ThreatDetectionController;
use App\Http\Controllers\Api\Attachment\AttachmentController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Hierarchy\LicenseController;
use App\Http\Controllers\Api\Hierarchy\OrganizationController;
use App\Http\Controllers\Api\Hierarchy\PlanController;
use App\Http\Controllers\Api\Hierarchy\RoleController;
use App\Http\Controllers\Api\Hierarchy\UserController;
use App\Http\Controllers\Api\Location\ItemLocationController;
use App\Http\Controllers\Api\Location\LocationController;
use App\Http\Controllers\Api\Maintenance\MaintenanceCategoryController;
use App\Http\Controllers\Api\Maintenance\MaintenanceConditionController;
use App\Http\Controllers\Api\Maintenance\MaintenanceController;
use App\Http\Controllers\Api\Maintenance\MaintenanceDetailController;
use App\Http\Controllers\Api\Operations\CheckInOutController;
use App\Http\Controllers\Api\Operations\EventsController;
use App\Http\Controllers\Api\Operations\ItemMovementController;
use App\Http\Controllers\Api\Operations\StatusController;
use App\Http\Controllers\Api\Operations\SupplierController;
use App\Http\Controllers\Api\Stock\BatchController;
use App\Http\Controllers\Api\Stock\CategoryController;
use App\Http\Controllers\Api\Stock\ItemController;
use App\Http\Controllers\Api\Stock\UnitOfMeasureController;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Middleware\VerifyOrganizationAccess;
use Illuminate\Support\Facades\Route;

Route::get('/get-env-key', fn() => response()->json([
    'apiKey' => env('OPENROUTER_API_KEY', ''),
]));

Route::get('/test', fn() => response()->json(['message' => 'API is working']));

// V1 API Routes
Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/admin/login', [AdminAuthController::class, 'login']);

    // Translation routes
    Route::get('/translations/{locale?}', [TranslationController::class, 'getTranslations']);
    Route::get('/translations/{locale}/{key}', [TranslationController::class, 'getTranslation']);

    // Organization routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('organizations')->group(function () {
        Route::get('/active', [OrganizationController::class, 'getActive']);
        Route::apiResource('/', OrganizationController::class, ['as' => 'organizations', 'parameters' => ['' => 'organization']])->except(['put']);
    });

    // Users routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('users')->group(function () {
        Route::apiResource('/', UserController::class, ['as' => 'users', 'parameters' => ['' => 'user']])->except(['put']);
    });

    // Roles routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('roles')->group(function () {
        Route::get('/all', [RoleController::class, 'all']);
        Route::get('/organization', [RoleController::class, 'organizationRoles']);
        Route::apiResource('/', RoleController::class, ['as' => 'roles', 'parameters' => ['' => 'role']])->except(['put']);
    });

    // Locations routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('locations')->group(function () {
        Route::apiResource('/', LocationController::class, ['as' => 'locations', 'parameters' => ['' => 'location']])->except(['put']);
    });

    // Maintenances routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('maintenances')->group(function () {
        Route::post('/item', [MaintenanceController::class, 'createItemMaintenance']);
        Route::post('/condition', [MaintenanceController::class, 'createFromCondition']);

        Route::apiResource('categories', MaintenanceCategoryController::class)->except(['put']);
        Route::apiResource('conditions', MaintenanceConditionController::class)->except(['put']);
        Route::apiResource('details', MaintenanceDetailController::class)->except(['put']);

        Route::apiResource('/', MaintenanceController::class, ['as' => 'maintenances', 'parameters' => ['' => 'maintenance']])->except(['put', 'store']);
        Route::patch('/{maintenance}/complete', [MaintenanceController::class, 'completeMaintenance']);
    });

    // Item status routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('statuses')->group(function () {
        Route::apiResource('/', StatusController::class, ['as' => 'statuses', 'parameters' => ['' => 'status']])->except(['put']);
    });

    // Item routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('items')->group(function () {
        Route::apiResource('/', ItemController::class, ['as' => 'items', 'parameters' => ['' => 'item']])->except(['put']);
    });

    // Batches routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('batches')->group(function () {
        Route::apiResource('/', BatchController::class, ['as' => 'batches', 'parameters' => ['' => 'batch']])->except(['put']);
    });

    // ItemLocation routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('item-locations')->group(function () {
        Route::apiResource('/', ItemLocationController::class, ['as' => 'item-locations', 'parameters' => ['' => 'id']])->except(['put', 'delete']);
    });

    // Movement routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('movements')->group(function () {
        Route::post('/move', [ItemMovementController::class, 'move']);
        Route::post('/initial-placement', [ItemMovementController::class, 'initialPlacement']);
        Route::post('/adjust-quantity', [ItemMovementController::class, 'adjustQuantity']);
        Route::get('/history/{itemId}', [ItemMovementController::class, 'history']);
        Route::post('/validate-integrity', [ItemMovementController::class, 'validateIntegrity']);
    });

    // Events routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('events')->group(function () {
        Route::get('/', [EventsController::class, 'index'])->name('events.index');
        Route::get('/items/{itemId}', [EventsController::class, 'itemHistory']);
        Route::get('/items/{itemId}/movements', [EventsController::class, 'itemMovements']);
        Route::get('/items/{itemId}/checkinout', [EventsController::class, 'itemCheckInOut']);
        Route::get('/items/{itemId}/maintenance', [EventsController::class, 'itemMaintenance']);
        Route::get('/items/{itemId}/all', [EventsController::class, 'itemAllEvents']);
        Route::get('/system', [EventsController::class, 'systemEvents']);
    });

    // CheckInOut routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('checks')->group(function () {
        Route::get('/', [CheckInOutController::class, 'index'])->name('checks.index');
        Route::post('out/{itemLocationId}', [CheckInOutController::class, 'checkout']);
        Route::post('in/{itemLocationId}', [CheckInOutController::class, 'checkin']);
        Route::get('history/{itemLocationId}', [CheckInOutController::class, 'history']);
        Route::get('availability/{itemLocationId}', [CheckInOutController::class, 'checkAvailability']);
    });

    // Units of measure routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('uom')->group(function () {
        Route::apiResource('/', UnitOfMeasureController::class, ['as' => 'uom', 'parameters' => ['' => 'unit_of_measure']])->except(['put']);
    });

    // Category routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('categories')->group(function () {
        Route::apiResource('/', CategoryController::class, ['as' => 'categories', 'parameters' => ['' => 'category']])->except(['put']);
    });

    // Suppliers routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('suppliers')->group(function () {
        Route::apiResource('/', SupplierController::class, ['as' => 'suppliers', 'parameters' => ['' => 'supplier']])->except(['put']);
    });

    // Attachments routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('attachments')->group(function () {
        Route::get('/type-options', [AttachmentController::class, 'getTypeOptions']);
        Route::get('/{attachment}/stats', [AttachmentController::class, 'getStats']);
        Route::apiResource('/', AttachmentController::class, ['as' => 'attachments', 'parameters' => ['' => 'attachment']])->except(['put']);
    });

    // API Key Management routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('admin/api-keys')->group(function () {
        Route::get('/', [ApiKeyController::class, 'index'])->name('admin.api-keys.index');
        Route::post('/', [ApiKeyController::class, 'store']);
        Route::get('/{id}', [ApiKeyController::class, 'show']);
        Route::patch('/{id}', [ApiKeyController::class, 'update']);
        Route::delete('/{id}', [ApiKeyController::class, 'destroy']);
        Route::post('/{id}/revoke', [ApiKeyController::class, 'revoke']);
        Route::post('/{id}/regenerate', [ApiKeyController::class, 'regenerate']);
        Route::get('/{id}/usage', [ApiKeyController::class, 'usage']);
        Route::get('/overview', [ApiKeyController::class, 'overview']);
    });

    // Admin security routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('admin/security')->group(function () {
        Route::get('/threats', [ThreatDetectionController::class, 'overview']);
    });

    // Plans routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('plans')->group(function () {
        Route::apiResource('/', PlanController::class, ['as' => 'plans', 'parameters' => ['' => 'plan']])->except(['put']);
    });

    // Licenses routes
    Route::middleware(['auth:sanctum', VerifyOrganizationAccess::class])->prefix('licenses')->group(function () {
        Route::post('/{id}/activate', [LicenseController::class, 'activate']);
        Route::post('/{id}/suspend', [LicenseController::class, 'suspend']);
        Route::post('/{id}/expire', [LicenseController::class, 'expire']);
        Route::apiResource('/', LicenseController::class, ['as' => 'licenses', 'parameters' => ['' => 'license']])->except(['put']);
    });

    // Auth routes
    Route::middleware(['session.validation', 'auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });

    // Admin auth routes
    Route::middleware(['session.validation', 'auth:sanctum'])->prefix('admin')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/user', [AdminAuthController::class, 'user']);
    });
});

// Fallback route
Route::fallback(fn() => response()->json(['error' => 'API resource not found'], 404));
