<?php

declare(strict_types = 1);

use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckInOutController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemLocationController;
use App\Http\Controllers\ItemMovementController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MaintenanceCategoryController;
use App\Http\Controllers\MaintenanceConditionController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MaintenanceDetailController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\PrintJobController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ThreatDetectionController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\UnitOfMeasureController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/get-env-key', static fn () => response()->json([
    'apiKey' => env('OPENROUTER_API_KEY', ''),
]));

Route::get('/test', static fn () => response()->json(['message' => 'API is working']));

Route::prefix('v1')->group(static function (): void {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/admin/login', [AdminAuthController::class, 'login']);

    Route::get('/translations/{locale?}', [TranslationController::class, 'getTranslations']);
    Route::get('/translations/{locale}/{key}', [TranslationController::class, 'getTranslation']);

    Route::middleware(['auth:sanctum'])->prefix('organizations')->group(static function (): void {
        Route::get('/active', [OrganizationController::class, 'getActive']);
        Route::apiResource('/', OrganizationController::class, ['as' => 'organizations', 'parameters' => ['' => 'organization']])->except(['put']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context', 'license.active'])->prefix('users')->group(static function (): void {
        Route::apiResource('/', UserController::class, ['as' => 'users', 'parameters' => ['' => 'user']])->except(['put']);
    });

    Route::middleware(['auth:sanctum'])->prefix('roles')->group(static function (): void {
        Route::get('/all', [RoleController::class, 'all']);
        Route::get('/organization', [RoleController::class, 'organizationRoles']);
        Route::apiResource('/', RoleController::class, ['as' => 'roles', 'parameters' => ['' => 'role']])->except(['put']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context', 'license.active'])->prefix('locations')->group(static function (): void {
        Route::apiResource('/', LocationController::class, ['as' => 'locations', 'parameters' => ['' => 'location']])->except(['put']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context', 'license.active'])->prefix('maintenances')->group(static function (): void {
        Route::post('/item', [MaintenanceController::class, 'createItemMaintenance']);
        Route::post('/condition', [MaintenanceController::class, 'createFromCondition']);

        Route::apiResource('categories', MaintenanceCategoryController::class)->except(['put']);
        Route::apiResource('conditions', MaintenanceConditionController::class)->except(['put']);
        Route::apiResource('details', MaintenanceDetailController::class)->except(['put']);

        Route::apiResource('/', MaintenanceController::class, ['as' => 'maintenances', 'parameters' => ['' => 'maintenance']])->except(['put', 'store']);
        Route::patch('/{maintenance}/complete', [MaintenanceController::class, 'completeMaintenance']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context', 'license.active'])->prefix('statuses')->group(static function (): void {
        Route::apiResource('/', StatusController::class, ['as' => 'statuses', 'parameters' => ['' => 'status']])->except(['put']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context', 'license.active'])->prefix('items')->group(static function (): void {
        Route::apiResource('/', ItemController::class, ['as' => 'items', 'parameters' => ['' => 'item']])->except(['put']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context', 'license.active'])->prefix('batches')->group(static function (): void {
        Route::apiResource('/', BatchController::class, ['as' => 'batches', 'parameters' => ['' => 'batch']])->except(['put']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context', 'license.active'])->prefix('item-locations')->group(static function (): void {
        Route::apiResource('/', ItemLocationController::class, ['as' => 'item-locations', 'parameters' => ['' => 'id']])->except(['put', 'delete']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context', 'license.active'])->prefix('movements')->group(static function (): void {
        Route::post('/move', [ItemMovementController::class, 'move']);
        Route::post('/initial-placement', [ItemMovementController::class, 'initialPlacement']);
        Route::post('/adjust-quantity', [ItemMovementController::class, 'adjustQuantity']);
        Route::get('/history/{itemId}', [ItemMovementController::class, 'history']);
        Route::post('/validate-integrity', [ItemMovementController::class, 'validateIntegrity']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context', 'license.active'])->prefix('events')->group(static function (): void {
        Route::get('/', [EventsController::class, 'index'])->name('events.index');
        Route::get('/items/{itemId}', [EventsController::class, 'itemHistory']);
        Route::get('/items/{itemId}/movements', [EventsController::class, 'itemMovements']);
        Route::get('/items/{itemId}/checkinout', [EventsController::class, 'itemCheckInOut']);
        Route::get('/items/{itemId}/maintenance', [EventsController::class, 'itemMaintenance']);
        Route::get('/items/{itemId}/all', [EventsController::class, 'itemAllEvents']);
        Route::get('/system', [EventsController::class, 'systemEvents']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context', 'license.active'])->prefix('checks')->group(static function (): void {
        Route::get('/', [CheckInOutController::class, 'index'])->name('checks.index');
        Route::post('out/{itemLocationId}', [CheckInOutController::class, 'checkout']);
        Route::post('in/{itemLocationId}', [CheckInOutController::class, 'checkin']);
        Route::get('history/{itemLocationId}', [CheckInOutController::class, 'history']);
        Route::get('availability/{itemLocationId}', [CheckInOutController::class, 'checkAvailability']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context', 'license.active'])->prefix('uom')->group(static function (): void {
        Route::apiResource('/', UnitOfMeasureController::class, ['as' => 'uom', 'parameters' => ['' => 'unit_of_measure']])->except(['put']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context', 'license.active'])->prefix('categories')->group(static function (): void {
        Route::apiResource('/', CategoryController::class, ['as' => 'categories', 'parameters' => ['' => 'category']])->except(['put']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context', 'license.active'])->prefix('suppliers')->group(static function (): void {
        Route::apiResource('/', SupplierController::class, ['as' => 'suppliers', 'parameters' => ['' => 'supplier']])->except(['put']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context', 'license.active'])->prefix('attachments')->group(static function (): void {
        Route::get('/type-options', [AttachmentController::class, 'getTypeOptions']);
        Route::get('/{attachment}/stats', [AttachmentController::class, 'getStats']);
        Route::apiResource('/', AttachmentController::class, ['as' => 'attachments', 'parameters' => ['' => 'attachment']])->except(['put']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context', 'license.active'])->prefix('printers')->group(static function (): void {
        Route::apiResource('/', PrinterController::class, ['as' => 'printers', 'parameters' => ['' => 'printer']])->except(['put']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context', 'license.active'])->prefix('printjobs')->group(static function (): void {
        Route::apiResource('/', PrintJobController::class, ['as' => 'printjobs', 'parameters' => ['' => 'printjob']])->except(['put']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context', 'license.active'])->group(static function (): void {
        Route::post('/labels/generate', [LabelController::class, 'generate']);
    });

    Route::middleware(['auth:sanctum'])->prefix('admin/api-keys')->group(static function (): void {
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

    Route::middleware(['auth:sanctum'])->prefix('admin/security')->group(static function (): void {
        Route::get('/threats', [ThreatDetectionController::class, 'overview']);
    });

    Route::middleware(['auth:sanctum', 'tenancy.context'])->prefix('licenses')->group(static function (): void {
        Route::post('/{id}/invoice', [LicenseController::class, 'invoice']);
        Route::apiResource('/', LicenseController::class, ['as' => 'licenses', 'parameters' => ['' => 'license']])->except(['put']);
    });

    Route::middleware(['session.validation', 'auth:sanctum'])->group(static function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });

    Route::middleware(['session.validation', 'auth:sanctum'])->prefix('admin')->group(static function (): void {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/user', [AdminAuthController::class, 'user']);
    });
});

Route::fallback(static fn () => response()->json(['error' => 'API resource not found'], 404));
