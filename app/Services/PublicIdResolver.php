<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\User;

class PublicIdResolver
{
    /**
     * UNIVERSAL PUBLIC ID RESOLVER
     * Automatically resolves any public ID to internal ID without configuration
     * Works for any model with HasPublicId trait
     */
    public static function resolve(array $data): array
    {
        $user = static::getCurrentUser();
        if (!$user) {
            return $data;
        }

        $orgId = static::isSuperAdmin($user) ? null : $user->org_id;

        foreach ($data as $field => $value) {
            // Only process string values that look like public IDs and end with '_id'
            if (!is_string($value) || !str_ends_with($field, '_id') || !preg_match('/^[A-Z]{2,4}-\d+$/', $value)) {
                continue;
            }

            // Extract prefix to determine entity type
            $prefix = explode('-', $value)[0];
            $entityType = static::getEntityTypeFromPrefix($prefix);
            
            if (!$entityType) {
                continue;
            }

            // Handle polymorphic relationships (maintainable_id, trackable_id, etc.)
            $modelClass = static::getModelClassForField($field, $entityType, $data);
            
            if (!$modelClass || !method_exists($modelClass, 'findByPublicId')) {
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
     * Map entity prefix to entity type (core migration + additional models)
     */
    private static function getEntityTypeFromPrefix(string $prefix): ?string
    {
        $prefixMap = [
            // Core migration prefixes
            'ITM' => 'item',
            'BCH' => 'stock', 
            'MNT' => 'maintenance',
            'TXN' => 'check_in_out',
            'CAT' => 'category',
            'SUP' => 'supplier',
            'LOC' => 'location',
            'USR' => 'user',
            // Additional models with HasPublicId (inferred prefixes)
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
        ];

        return $prefixMap[$prefix] ?? null;
    }

    /**
     * Get model class for a field, handling polymorphic relationships
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
            'parent_id' => function($entityType) {
                // Parent ID can refer to same model type (Location->Location, Category->Category)
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
        } else {
            // For standard fields like item_id, supplier_id, etc., 
            // the entity type from prefix should match the field
            // e.g., ITM-0001 in item_id field -> use 'item' entity type
        }

        return static::getModelClassFromEntityType($entityType);
    }

    /**
     * Get model class from entity type (core migration + additional models)
     */
    private static function getModelClassFromEntityType(string $entityType): ?string
    {
        $modelMap = [
            // Core migration models
            'item' => \App\Models\Item::class,
            'stock' => \App\Models\Stock::class,
            'maintenance' => \App\Models\Maintenance::class,
            'check_in_out' => \App\Models\CheckInOut::class,
            'category' => \App\Models\Category::class,
            'supplier' => \App\Models\Supplier::class,
            'location' => \App\Models\Location::class,
            'user' => \App\Models\User::class,
            // Additional models with HasPublicId
            'unitofmeasure' => \App\Models\UnitOfMeasure::class,
            'status' => \App\Models\Status::class,
            'role' => \App\Models\Role::class,
            'organization' => \App\Models\Organization::class,
            'attachment' => \App\Models\Attachment::class,
            'maintenancecondition' => \App\Models\MaintenanceCondition::class,
            'maintenancecategory' => \App\Models\MaintenanceCategory::class,
            'maintenancedetail' => \App\Models\MaintenanceDetail::class,
            'itemlocation' => \App\Models\ItemLocation::class,
            'itemmovement' => \App\Models\ItemMovement::class,
            'itemsupplier' => \App\Models\ItemSupplier::class,
        ];

        return $modelMap[$entityType] ?? null;
    }

    /**
     * Get current authenticated user
     */
    private static function getCurrentUser(): ?User
    {
        return Auth::guard('api')->user() ?? Auth::user();
    }

    /**
     * Check if user is super admin
     */
    private static function isSuperAdmin(?User $user = null): bool
    {
        $user = $user ?? static::getCurrentUser();
        return $user && $user->isSuperAdmin();
    }
} 