<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Models\EntityId;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EntityIdService extends BaseService
{
    protected array $entityTypeConfig = [
        'item' => ['prefix' => 'ITM', 'table' => 'items'],
        'stock' => ['prefix' => 'BCH', 'table' => 'stocks'],
        'maintenance' => ['prefix' => 'MNT', 'table' => 'maintenances'],
        'check_in_out' => ['prefix' => 'TXN', 'table' => 'check_ins_outs'],
        'category' => ['prefix' => 'CAT', 'table' => 'categories'],
        'supplier' => ['prefix' => 'SUP', 'table' => 'suppliers'],
        'location' => ['prefix' => 'LOC', 'table' => 'locations'],
        'user' => ['prefix' => 'USR', 'table' => 'users'],
        'status' => ['prefix' => 'STA', 'table' => 'statuses'],
        'organization' => ['prefix' => 'ORG', 'table' => 'organizations'],
        'license' => ['prefix' => 'LIC', 'table' => 'licenses'],
        'role' => ['prefix' => 'ROL', 'table' => 'roles'],
        'unit_of_measure' => ['prefix' => 'UOM', 'table' => 'unit_of_measures'],
        'item_supplier' => ['prefix' => 'ISU', 'table' => 'item_suppliers'],
        'item_location' => ['prefix' => 'ITL', 'table' => 'item_locations'],
        'maintenance_category' => ['prefix' => 'MCA', 'table' => 'maintenance_categories'],
        'maintenance_condition' => ['prefix' => 'MCO', 'table' => 'maintenance_conditions'],
        'maintenance_detail' => ['prefix' => 'MDE', 'table' => 'maintenance_details'],
        'attachment' => ['prefix' => 'ATT', 'table' => 'attachments'],
        'item_movement' => ['prefix' => 'IMV', 'table' => 'item_movements'],
        'item_history_event' => ['prefix' => 'IHE', 'table' => 'item_history_events'],
    ];

    public function __construct()
    {
        parent::__construct(new EntityId);
    }

    /**
     * Generate a new public ID for an entity
     */
    public function generatePublicId(int $orgId, string $entityType, int $entityInternalId): string
    {
        $this->validateOrgId($orgId);
        $this->validateEntityType($entityType);
        $this->validateEntityInternalId($entityInternalId);

        try {
            return DB::transaction(function () use ($orgId, $entityType, $entityInternalId) {
                // Check if an entity already has a public ID
                $existing = EntityId::where('org_id', $orgId)
                    ->where('entity_type', $entityType)
                    ->where('entity_internal_id', $entityInternalId)
                    ->first();

                if ($existing) {
                    return $existing->public_id;
                }

                // Get the next sequence number with row locking to prevent race conditions
                $lastSequence = EntityId::where('org_id', $orgId)
                    ->where('entity_type', $entityType)
                    ->lockForUpdate()
                    ->max('sequence_number');

                $sequenceNumber = ($lastSequence ?? 0) + 1;
                $prefix = $this->entityTypeConfig[$entityType]['prefix'];

                // Create the entity ID record
                $entityId = EntityId::create([
                    'org_id' => $orgId,
                    'entity_type' => $entityType,
                    'entity_prefix' => $prefix,
                    'sequence_number' => $sequenceNumber,
                    'entity_internal_id' => $entityInternalId,
                ]);

                return $entityId->public_id;
            });
        } catch (QueryException $e) {
            if ($this->isDuplicateKeyError($e)) {
                throw new InvalidArgumentException(__(ErrorMessages::ALREADY_EXISTS));
            }
            throw $e;
        }
    }

    /**
     * Generate public IDs for multiple entities in a batch
     */
    public function generateBatchPublicIds(int $orgId, string $entityType, array $entityInternalIds): array
    {
        $this->validateOrgId($orgId);
        $this->validateEntityType($entityType);

        if (empty($entityInternalIds)) {
            throw new InvalidArgumentException(__(ErrorMessages::EMPTY_DATA));
        }

        foreach ($entityInternalIds as $id) {
            $this->validateEntityInternalId($id);
        }

        try {
            return DB::transaction(function () use ($orgId, $entityType, $entityInternalIds) {
                $results = [];
                $prefix = $this->entityTypeConfig[$entityType]['prefix'];

                // Get existing public IDs
                $existing = EntityId::where('org_id', $orgId)
                    ->where('entity_type', $entityType)
                    ->whereIn('entity_internal_id', $entityInternalIds)
                    ->pluck('public_id', 'entity_internal_id')
                    ->toArray();

                // Get the next sequence number with locking
                $lastSequence = EntityId::where('org_id', $orgId)
                    ->where('entity_type', $entityType)
                    ->lockForUpdate()
                    ->max('sequence_number');

                $currentSequence = $lastSequence ?? 0;
                $batchData = [];

                foreach ($entityInternalIds as $internalId) {
                    if (isset($existing[$internalId])) {
                        $results[$internalId] = $existing[$internalId];

                        continue;
                    }

                    $currentSequence++;
                    $publicId = $prefix . '-' . str_pad((string)$currentSequence, 8, '0', STR_PAD_LEFT);

                    $batchData[] = [
                        'org_id' => $orgId,
                        'entity_type' => $entityType,
                        'entity_prefix' => $prefix,
                        'sequence_number' => $currentSequence,
                        'entity_internal_id' => $internalId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $results[$internalId] = $publicId;
                }

                if (! empty($batchData)) {
                    EntityId::insert($batchData);
                }

                return $results;
            });
        } catch (QueryException $e) {
            if ($this->isDuplicateKeyError($e)) {
                throw new InvalidArgumentException(__(ErrorMessages::ALREADY_EXISTS));
            }
            throw $e;
        }
    }

    /**
     * Resolve public ID to internal ID
     */
    public function resolveToInternalId(string $publicId, int $orgId): int
    {
        $this->validateOrgId($orgId);
        $this->validatePublicId($publicId);

        $entityId = EntityId::findByPublicId($publicId, $orgId);

        if (! $entityId) {
            throw new InvalidArgumentException(__(ErrorMessages::NOT_FOUND));
        }

        return $entityId->entity_internal_id;
    }

    /**
     * Get public ID for internal ID
     */
    public function getPublicId(int $entityInternalId, string $entityType, int $orgId): string
    {
        $this->validateOrgId($orgId);
        $this->validateEntityType($entityType);
        $this->validateEntityInternalId($entityInternalId);

        $publicId = EntityId::getPublicId($entityInternalId, $entityType, $orgId);

        if (! $publicId) {
            throw new InvalidArgumentException(__(ErrorMessages::NOT_FOUND));
        }

        return $publicId;
    }

    /**
     * Get entity details by public ID
     */
    public function getEntityByPublicId(string $publicId, int $orgId): EntityId
    {
        $this->validateOrgId($orgId);
        $this->validatePublicId($publicId);

        $entityId = EntityId::findByPublicId($publicId, $orgId);

        if (! $entityId) {
            throw new InvalidArgumentException(__(ErrorMessages::NOT_FOUND));
        }

        return $entityId;
    }

    /**
     * Check if public ID exists
     */
    public function publicIdExists(string $publicId, int $orgId): bool
    {
        $this->validateOrgId($orgId);
        $this->validatePublicId($publicId);

        return EntityId::byPublicId($publicId, $orgId)->exists();
    }

    /**
     * Get all supported entity types
     */
    public function getSupportedEntityTypes(): array
    {
        return array_keys($this->entityTypeConfig);
    }

    /**
     * Validate organization ID
     */
    private function validateOrgId(int $orgId): void
    {
        if ($orgId <= 0) {
            throw new InvalidArgumentException(__(ErrorMessages::INVALID_ORG));
        }
    }

    /**
     * Validate entity type
     */
    private function validateEntityType(string $entityType): void
    {
        if (! isset($this->entityTypeConfig[$entityType])) {
            throw new InvalidArgumentException(
                __(ErrorMessages::INVALID_ENTITY_TYPE) . '. Supported types: ' . implode(', ', array_keys($this->entityTypeConfig))
            );
        }
    }

    /**
     * Validate entity internal ID
     */
    private function validateEntityInternalId(int $entityInternalId): void
    {
        if ($entityInternalId <= 0) {
            throw new InvalidArgumentException(__(ErrorMessages::INVALID_ID));
        }
    }

    /**
     * Validate public ID format
     */
    private function validatePublicId(string $publicId): void
    {
        if (empty($publicId)) {
            throw new InvalidArgumentException(__(ErrorMessages::INVALID_PUBLIC_ID_FORMAT));
        }

        if (! preg_match('/^[A-Z]{2,4}-\d{8}$/', $publicId)) {
            throw new InvalidArgumentException(__(ErrorMessages::INVALID_PUBLIC_ID_FORMAT));
        }
    }

    /**
     * Check if the query exception is a duplicate key error
     */
    private function isDuplicateKeyError(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'Duplicate entry') ||
            str_contains($e->getMessage(), 'UNIQUE constraint failed') ||
            $e->getCode() === '23000';
    }
}
