<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class ItemService extends BaseService
{
    /**
     * Create a new service instance.
     */
    public function __construct(Item $item)
    {
        parent::__construct($item);
    }

    /**
     * Get filtered items with optional relationships.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery()->with(['maintenances', 'locations', 'category', 'status', 'unitOfMeasure', 'suppliers', 'organization']);

        // Apply filters using model scopes where available
        $query->when($filters['tracking_mode'] ?? null, function ($q, $mode) {
            switch ($mode) {
                case Item::TRACKING_ABSTRACT:
                    return $q->abstract();
                case Item::TRACKING_BULK:
                    return $q->bulk();
                case Item::TRACKING_SERIALIZED:
                    return $q->serialized();
                default:
                    return $q;
            }
        })
            ->when($filters['location_id'] ?? null, fn($q, $value) => $q->byLocation($value))
            ->when($filters['q'] ?? null, fn($q, $value) => $q->search($value))
            ->when(isset($filters['is_active']) && $filters['is_active'], fn($q) => $q->active())
            ->when($filters['org_id'] ?? null, fn($q, $value) => $q->where('org_id', $value))
            ->when($filters['category_id'] ?? null, fn($q, $value) => $q->where('category_id', $value))
            ->when($filters['user_id'] ?? null, fn($q, $value) => $q->where('user_id', $value))
            ->when($filters['name'] ?? null, fn($q, $value) => $q->where('name', 'like', "%{$value}%"))
            ->when($filters['code'] ?? null, fn($q, $value) => $q->where('code', 'like', "%{$value}%"))
            ->when($filters['barcode'] ?? null, fn($q, $value) => $q->where('barcode', $value))
            ->when($filters['with'] ?? null, fn($q, $relations) => $q->with($relations));

        return $query->get();
    }

    /**
     * Get items by tracking mode.
     */
    public function getByTrackingMode(string $trackingMode): Collection
    {
        return $this->getFiltered(['tracking_mode' => $trackingMode]);
    }

    /**
     * Get active items.
     */
    public function getActive(): Collection
    {
        return $this->getFiltered(['is_active' => true]);
    }

    /**
     * Find item by ID
     */
    public function findById($id, array $columns = ['*'], array $relations = [], array $appends = []): Model
    {
        $relations = array_unique(array_merge($relations, ['maintenances', 'locations', 'category', 'status', 'unitOfMeasure', 'suppliers', 'organization']));

        return parent::findById($id, $columns, $relations, $appends);
    }

    /**
     * Create a new item and its locations.
     */
    public function create(array $data): Model
    {
        // Extract locations from data if present
        $locations = $data['locations'] ?? null;
        unset($data['locations']);

        // Create the main item using parent method
        $item = parent::create($data);

        // Handle locations creation if provided
        if ($locations !== null && is_array($locations)) {
            $this->createItemLocations($item, $locations);
        }

        // Reload the item with fresh location data
        return $item->fresh(['locations']);
    }

    /**
     * Update an item and its locations.
     */
    public function update($id, array $data): Model
    {
        // Extract locations from data if present
        $locations = $data['locations'] ?? null;
        unset($data['locations']);

        // Update the main item using parent method
        $item = parent::update($id, $data);

        // Handle locations update if provided
        if ($locations !== null && is_array($locations)) {
            $this->updateItemLocations($item, $locations);
        }

        // Reload the item with fresh location data
        return $item->fresh(['locations']);
    }

    /**
     * Create item locations for a new item.
     */
    private function createItemLocations(Model $item, array $locations): void
    {
        foreach ($locations as $locationData) {
            if (isset($locationData['id']) && isset($locationData['quantity'])) {
                $locationId = $locationData['id'];

                // If it's a public ID (LOC-xxxx), resolve to internal ID
                if (is_string($locationId) && !is_numeric($locationId)) {
                    $location = \App\Models\Location::findByPublicId($locationId, $item->org_id);
                    if (!$location) {
                        continue; // Skip if location not found
                    }
                    $locationId = $location->id;
                }

                // Create the item-location record using the ItemLocation model
                \App\Models\ItemLocation::create([
                    'org_id' => $item->org_id,
                    'item_id' => $item->id,
                    'location_id' => $locationId,
                    'quantity' => $locationData['quantity'],
                    'moved_date' => now(),
                ]);
            }
        }
    }

    /**
     * Update item locations quantities.
     */
    private function updateItemLocations(Model $item, array $locations): void
    {
        foreach ($locations as $locationData) {
            if (isset($locationData['id']) && isset($locationData['quantity'])) {
                $locationId = $locationData['id'];

                // If it's a public ID (LOC-xxxx), resolve to internal ID
                if (is_string($locationId) && !is_numeric($locationId)) {
                    $location = \App\Models\Location::findByPublicId($locationId, $item->org_id);
                    if (!$location) {
                        continue; // Skip if location not found
                    }
                    $locationId = $location->id;
                }

                // Update the quantity in the pivot table
                $item->locations()->updateExistingPivot(
                    $locationId,
                    ['quantity' => $locationData['quantity']]
                );
            }
        }
    }

    /**
     * Get allowed query parameters for filtering items.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id',
            'category_id',
            'user_id',
            'location_id',
            'tracking_mode',
            'is_active',
            'name',
            'code',
            'barcode',
            'q'
        ]);
    }

    /**
     * Get valid relations
     */
    protected function getValidRelations(): array
    {
        return [
            'category',
            'user',
            'organization',
            'unitOfMeasure',
            'status',
            'parentItem',
            'childItems',
            'relatedItem',
            'itemRelations',
            'suppliers',
            'maintenances',
            'locations'
        ];
    }

    /**
     * Process request parameters with validation and type conversion.
     */
    public function processRequestParams(array $params): array
    {
        $this->validateParams($params);

        return [
            'org_id' => $this->toInt($params['org_id'] ?? null),
            'tracking_mode' => $this->toString($params['tracking_mode'] ?? null),
            'category_id' => $this->toInt($params['category_id'] ?? null),
            'user_id' => $this->toInt($params['user_id'] ?? null),
            'location_id' => $this->toInt($params['location_id'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'name' => $this->toString($params['name'] ?? null),
            'code' => $this->toString($params['code'] ?? null),
            'barcode' => $this->toString($params['barcode'] ?? null),
            'q' => $this->toString($params['q'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Toggle item maintenance status.
     */
    public function toggleMaintenance($id, string $action, string $date, ?string $remarks = null, bool $isRepair = false): Model
    {
        $item = $this->findById($id);
        $user = auth()->user();
        $orgId = $user->org_id ?? $item->org_id;

        if ($action === 'in') {
            // Send item to maintenance - create new maintenance record
            $item->maintenances()->create([
                'org_id' => $orgId,
                'date_in_maintenance' => $date,
                'remarks' => $remarks,
                'is_repair' => $isRepair,
                'user_id' => $user->id ?? null,
                'maintainable_id' => $item->id,
                'maintainable_type' => get_class($item),
            ]);
        } elseif ($action === 'out') {
            // Return item from maintenance - update existing active maintenance
            $activeMaintenance = $item->maintenances()
                ->whereNotNull('date_in_maintenance')
                ->whereNull('date_back_from_maintenance')
                ->first();

            if ($activeMaintenance) {
                $activeMaintenance->update([
                    'date_back_from_maintenance' => $date,
                    'remarks' => $remarks ?: $activeMaintenance->remarks,
                ]);
            }
        }

        // Return fresh item with updated maintenance status
        return $item->fresh(['maintenances']);
    }
}
