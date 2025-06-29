<?php

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Models\Item;
use App\Models\ItemLocation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ItemLocationService extends BaseService
{
    public function __construct(ItemLocation $itemLocation)
    {
        parent::__construct($itemLocation);
    }

    /**
     * Process request parameters
     */
    public function processRequestParams(array $params): array
    {
        $this->validateParams($params);

        return [
            'location_id' => $this->toInt($params['location_id'] ?? null),
            'item_id' => $this->toInt($params['item_id'] ?? null),
            'moved_date' => $this->toString($params['moved_date'] ?? null),
            'positive_quantity' => $this->toBool($params['positive_quantity'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Get filtered item locations.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $defaultRelationships = [
            'organization',
            'item' => function ($query) {
                $query->with(['category', 'status']);
            },
            'location',
        ];

        $relationships = array_merge($defaultRelationships, $filters['with'] ?? []);

        return $this->getQuery()
            ->with($relationships)
            ->when($filters['location_id'] ?? null, fn($q, $id) => $q->where('location_id', $id))
            ->when($filters['item_id'] ?? null, fn($q, $id) => $q->where('item_id', $id))
            ->when($filters['moved_date'] ?? null, function ($q, $date) {
                try {
                    $parsedDate = Carbon::parse($date)->format('Y-m-d');

                    return $q->whereDate('moved_date', $parsedDate);
                } catch (\Exception $e) {
                    throw new InvalidArgumentException(ErrorMessages::INVALID_DATE_FORMAT);
                }
            })
            ->when($filters['positive_quantity'] ?? null, fn($q) => $q->where('quantity', '>', 0))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new item location.
     */
    public function createItemLocation(array $data): Model
    {
        $data = $this->applyItemLocationBusinessRules($data);
        $this->validateItemLocationBusinessRules($data);

        $itemLocation = $this->create($data);

        // Update tracking_mode
        $item = Item::find($data['item_id']);
        if ($item && $item->tracking_mode === Item::TRACKING_ABSTRACT) {
            $item->update(['tracking_mode' => Item::TRACKING_BULK]);
        }

        return $itemLocation;
    }

    /**
     * Move items between locations with business rules.
     */
    public function moveItemWithRules(array $data): bool
    {
        $data = $this->applyMoveBusinessRules($data);
        $this->validateMoveBusinessRules($data);

        return $this->moveItem(
            $data['item_id'],
            $data['from_location_id'],
            $data['to_location_id'],
            $data['quantity']
        );
    }

    /**
     * Update quantity at location with business rules.
     */
    public function updateQuantityWithRules(array $data): Model
    {
        $data = $this->applyUpdateQuantityBusinessRules($data);
        $this->validateUpdateQuantityBusinessRules($data);

        $itemLocation = $this->getQuery()
            ->where('item_id', $data['item_id'])
            ->where('location_id', $data['location_id'])
            ->first();

        if (!$itemLocation) {
            return $this->createItemLocation($data);
        }

        return $this->update($itemLocation->id, $data);
    }

    /**
     * Move items between locations.
     */
    public function moveItem(
        int $itemId,
        ?int $fromLocationId,
        int $toLocationId,
        float $quantity,
    ): bool {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative');
        }

        // Handle initial placement (when fromLocationId is null)
        if ($fromLocationId === null) {
            return $this->initialPlacement($itemId, $toLocationId, $quantity);
        }

        // Handle movement between existing locations
        $source = $this->getQuery()
            ->where('item_id', $itemId)
            ->where('location_id', $fromLocationId)
            ->first();

        if (! $source || $source->quantity < $quantity) {
            return false;
        }

        $destination = $this->getQuery()
            ->where('item_id', $itemId)
            ->where('location_id', $toLocationId)
            ->first();

        return DB::transaction(function () use ($source, $destination, $itemId, $toLocationId, $quantity) {
            $source->quantity -= $quantity;

            if ($source->quantity <= 0) {
                $source->delete();
            } else {
                $source->save();
            }

            if ($destination) {
                $destination->quantity += $quantity;
                $destination->moved_date = now();
                $destination->save();
            } else {
                $this->create([
                    'org_id' => $source->org_id,
                    'item_id' => $itemId,
                    'location_id' => $toLocationId,
                    'quantity' => $quantity,
                    'moved_date' => now(),
                ]);
            }

            return true;
        });
    }

    /**
     * Handle initial placement of item in a location.
     */
    private function initialPlacement(int $itemId, int $toLocationId, float $quantity): bool
    {
        // Get the item to determine org_id
        $item = \App\Models\Item::findOrFail($itemId);
        
        // Check if item already exists in the destination location
        $existing = $this->getQuery()
            ->where('item_id', $itemId)
            ->where('location_id', $toLocationId)
            ->first();

        return DB::transaction(function () use ($existing, $item, $itemId, $toLocationId, $quantity) {
            if ($existing) {
                // Add to existing quantity
                $existing->quantity += $quantity;
                $existing->moved_date = now();
                $existing->save();
            } else {
                // Create new item location record
                $this->create([
                    'org_id' => $item->org_id,
                    'item_id' => $itemId,
                    'location_id' => $toLocationId,
                    'quantity' => $quantity,
                    'moved_date' => now(),
                ]);
            }

            return true;
        });
    }

    /**
     * Delete an item location and handle tracking_mode
     */
    public function deleteItemLocation(string $id): bool
    {
        $itemLocation = $this->findById($id, ['*'], ['item']);
        $item = $itemLocation->item;
        $deleted = $this->delete($id);

        if ($deleted) {
            $item->refresh();
            $remainingLocations = $item->locations()->count();
            if ($remainingLocations === 0 && $item->tracking_mode === 'bulk') {
                $item->update(['tracking_mode' => 'abstract']);
            }
        }

        return $deleted;
    }

    /**
     * Get allowed query parameters
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id', 'location_id', 'item_id', 'moved_date', 'positive_quantity',
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return [
            'item',
            'item.category',
            'item.status',
            'item.unitOfMeasure',
            'location',
            'organization'
        ];
    }

    /**
     * Get item with all its location distribution.
     */
    public function getItemWithLocations(int $itemId, array $filters = []): array
    {
        $item = Item::findOrFail($itemId);

        if ($item->isAbstract()) {
            throw new \InvalidArgumentException('Abstract items do not have physical locations');
        }

        // Get item locations with filtering
        $query = $item->locations();

        // Apply filters if provided
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                if ($value !== null) {
                    $query->where($key, $value);
                }
            }
        }

        $locations = $query->withPivot('quantity', 'moved_date', 'notes')->get();

        return [
            'item' => $item,
            'locations' => $locations,
            'summary' => [
                'total_quantity' => $locations->sum('pivot.quantity'),
                'location_count' => $locations->count(),
                'tracking_mode' => $item->tracking_mode,
                'is_distributed' => $locations->count() > 1,
            ]
        ];
    }

    /**
     * Get inventory distribution overview for physical items.
     */
    public function getInventoryDistribution(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Item::whereIn('tracking_mode', [Item::TRACKING_BULK, Item::TRACKING_SERIALIZED])
            ->with(['locations', 'category', 'unitOfMeasure']);

        // Apply filters
        if (!empty($filters)) {
            if (isset($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }
            if (isset($filters['tracking_mode'])) {
                $query->where('tracking_mode', $filters['tracking_mode']);
            }
            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }
        }

        return $query->get();
    }

    /**
     * Get total quantity of item across all locations.
     */
    public function getTotalQuantity(int $itemId): float
    {
        $item = Item::findOrFail($itemId);

        if ($item->isAbstract()) {
            return 0;
        }

        return $item->locations()->sum('item_locations.quantity');
    }

    /**
     * Find item location by ID with proper relationships loaded.
     */
    public function findById($id, array $with = [], array $columns = ['*'], array $appends = []): Model
    {
        $defaultRelationships = [
            'organization',
            'item' => function ($query) {
                $query->with(['category', 'status']);
            },
            'location',
        ];

        $relationships = array_merge($defaultRelationships, $with);

        return parent::findById($id, $columns, $relationships, $appends);
    }

    /**
     * Apply business rules for item location operations.
     */
    private function applyItemLocationBusinessRules(array $data, $itemLocationId = null): array
    {
        // Set moved_date if not provided
        if (!isset($data['moved_date'])) {
            $data['moved_date'] = now();
        }

        return $data;
    }

    /**
     * Apply business rules for move operations.
     */
    private function applyMoveBusinessRules(array $data): array
    {
        // Set moved_date if not provided
        if (!isset($data['moved_date'])) {
            $data['moved_date'] = now();
        }

        return $data;
    }

    /**
     * Apply business rules for update quantity operations.
     */
    private function applyUpdateQuantityBusinessRules(array $data): array
    {
        // Set moved_date if not provided
        if (!isset($data['moved_date'])) {
            $data['moved_date'] = now();
        }

        return $data;
    }

    /**
     * Validate business rules for item location operations.
     */
    private function validateItemLocationBusinessRules(array $data, $itemLocationId = null): void
    {
        // Validate required fields
        $requiredFields = ['org_id', 'item_id', 'location_id', 'quantity'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $fieldName = str_replace('_', ' ', $field);
                throw new \InvalidArgumentException("The {$fieldName} is required");
            }
        }

        // Validate quantity is not negative
        if (isset($data['quantity']) && $data['quantity'] < 0) {
            throw new \InvalidArgumentException('The quantity cannot be negative');
        }
    }

    /**
     * Validate business rules for move operations.
     */
    private function validateMoveBusinessRules(array $data): void
    {
        // Validate required fields for move operation
        $requiredFields = ['org_id', 'item_id', 'from_location_id', 'to_location_id', 'quantity'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $fieldName = str_replace('_', ' ', $field);
                throw new \InvalidArgumentException("The {$fieldName} is required");
            }
        }

        // Validate locations are different
        if ($data['from_location_id'] == $data['to_location_id']) {
            throw new \InvalidArgumentException('The destination location must be different from the source location');
        }

        // Validate quantity is not negative
        if ($data['quantity'] < 0) {
            throw new \InvalidArgumentException('The quantity cannot be negative');
        }
    }

    /**
     * Validate business rules for update quantity operations.
     */
    private function validateUpdateQuantityBusinessRules(array $data): void
    {
        // Validate required fields for update quantity operation
        $requiredFields = ['org_id', 'item_id', 'location_id', 'quantity'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $fieldName = str_replace('_', ' ', $field);
                throw new \InvalidArgumentException("The {$fieldName} is required");
            }
        }

        // Validate quantity is not negative
        if ($data['quantity'] < 0) {
            throw new \InvalidArgumentException('The quantity cannot be negative');
        }
    }
}
