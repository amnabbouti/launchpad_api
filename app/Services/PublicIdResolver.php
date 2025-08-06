<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attachment;
use App\Models\Batch;
use App\Models\Category;
use App\Models\CheckInOut;
use App\Models\Item;
use App\Models\ItemHistoryEvent;
use App\Models\ItemLocation;
use App\Models\ItemMovement;
use App\Models\ItemSupplier;
use App\Models\Location;
use App\Models\Maintenance;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceCondition;
use App\Models\MaintenanceDetail;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Status;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PublicIdResolver
{
    /**
     * UNIVERSAL PUBLIC ID RESOLVER
     */
    public static function resolve(array $data): array
    {
        $user = static::getCurrentUser();
        if (! $user) {
            return $data;
        }

        $orgId = static::isSuperAdmin($user) ? null : $user->org_id;

        foreach ($data as $field => $value) {
            // Only process string values that look like public IDs and end with '_id'
            if (! is_string($value) || ! str_ends_with($field, '_id') || ! preg_match('/^[A-Z]{2,4}-\d+$/', $value)) {
                continue;
            }

            // Extract prefix to determine an entity type
            $prefix = explode('-', $value)[0];
            $entityType = static::getEntityTypeFromPrefix($prefix);

            if (! $entityType) {
                continue;
            }

            // Handle polymorphic relationships (maintainable_id, trackable_id, etc.)
            $modelClass = static::getModelClassForField($field, $entityType, $data);

            if (! $modelClass || ! method_exists($modelClass, 'findByPublicId')) {
                continue;
            }

            // Resolve public ID to internal ID
            $model = $modelClass::findByPublicId($value, $orgId);
            if ($model) {
                $data[$field] = $model->id;
            }
        }

        return $data;
    }

    /**
     * Map entity prefix to an entity type (core migration and additional models)
     */
    private static function getEntityTypeFromPrefix(string $prefix): ?string
    {
        $prefixMap = [
            'ITM' => 'item',
            'BCH' => 'batch',
            'MNT' => 'maintenance',
            'TXN' => 'check_in_out',
            'CAT' => 'category',
            'SUP' => 'supplier',
            'LOC' => 'location',
            'USR' => 'user',
            'UOM' => 'unitofmeasure',
            'STS' => 'status',
            'ROL' => 'role',
            'ORG' => 'organization',
            'ATT' => 'attachment',
            'MCD' => 'maintenancecondition',
            'MCT' => 'maintenancecategory',
            'MDT' => 'maintenancedetail',
            'ILO' => 'itemlocation',
            'IMV' => 'itemmovement',
            'ISP' => 'itemsupplier',
            'IHE' => 'itemhistoryevent',
        ];

        return $prefixMap[$prefix] ?? null;
    }

    /**
     * Get a model class for a field, handling polymorphic relationships
     */
    private static function getModelClassForField(string $field, string $entityType, array $data): ?string
    {
        // Handle polymorphic relationships
        if ($field === 'maintainable_id' && isset($data['maintainable_type'])) {
            return $data['maintainable_type'];
        }

        if ($field === 'trackable_id' && isset($data['trackable_type'])) {
            return $data['trackable_type'];
        }

        // Handle special field mappings
        $fieldMappings = [
            'parent_id' => function ($entityType) {
                // Parent ID can refer to the same model type (Location->Location, Category->Category)
                return static::getModelClassFromEntityType($entityType);
            },
            'checkout_location_id' => 'location',
            'checkin_location_id' => 'location',
            'checkin_user_id' => 'user',
            'status_out_id' => 'status',
            'status_in_id' => 'status',
        ];

        if (isset($fieldMappings[$field])) {
            if (is_callable($fieldMappings[$field])) {
                return $fieldMappings[$field]($entityType);
            }
            $entityType = $fieldMappings[$field];
        }

        return static::getModelClassFromEntityType($entityType);
    }

    /**
     * Get model class from entity type (core migration and additional models)
     */
    private static function getModelClassFromEntityType(string $entityType): ?string
    {
        $modelMap = [
            'item' => Item::class,
            'batch' => Batch::class,
            'maintenance' => Maintenance::class,
            'check_in_out' => CheckInOut::class,
            'category' => Category::class,
            'supplier' => Supplier::class,
            'location' => Location::class,
            'user' => User::class,
            'unitofmeasure' => UnitOfMeasure::class,
            'status' => Status::class,
            'role' => Role::class,
            'organization' => Organization::class,
            'attachment' => Attachment::class,
            'maintenancecondition' => MaintenanceCondition::class,
            'maintenancecategory' => MaintenanceCategory::class,
            'maintenancedetail' => MaintenanceDetail::class,
            'itemlocation' => ItemLocation::class,
            'itemmovement' => ItemMovement::class,
            'itemsupplier' => ItemSupplier::class,
            'itemhistoryevent' => ItemHistoryEvent::class,
        ];

        return $modelMap[$entityType] ?? null;
    }

    /**
     * Get a current authenticated user
     */
    private static function getCurrentUser(): ?User
    {
        return Auth::guard('api')->user() ?? Auth::user();
    }

    /**
     * Check if the user is super admin
     */
    private static function isSuperAdmin(?User $user = null): bool
    {
        $user = $user ?? static::getCurrentUser();

        return $user && $user->isSuperAdmin();
    }
}
