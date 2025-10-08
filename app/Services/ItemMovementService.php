<?php

declare(strict_types = 1);

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Constants\SuccessMessages;
use App\Models\Item;
use App\Models\ItemLocation;
use App\Models\ItemMovement;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

use function count;

class ItemMovementService extends BaseService {
    protected EventService $eventService;

    public function __construct(EventService $eventService) {
        parent::__construct(new ItemMovement);
        $this->eventService = $eventService;
    }

    /**
     * Perform quantity adjustment (increase/decrease without location change).
     */
    public function adjustQuantity(array $data): array {
        $item = Item::findOrFail($data['item_id']);

        if ($item->isAbstract() || $item->isSerialized()) {
            throw new InvalidArgumentException(__(ErrorMessages::ITEM_ADJUSTMENT_STANDARD_ONLY));
        }

        $locationId = $data['to_location_id'] ?? $data['location_id'] ?? null;
        if (! $locationId) {
            throw new InvalidArgumentException(__(ErrorMessages::ITEM_ADJUSTMENT_LOCATION_REQUIRED));
        }

        $adjustmentData = [
            'item_id'          => $data['item_id'],
            'from_location_id' => $locationId,
            'to_location_id'   => $locationId,
            'quantity'         => abs($data['quantity_change']),
            'reason'           => $data['reason'],
            'movement_type'    => ItemMovement::MOVEMENT_ADJUSTMENT,
            'quantity_change'  => $data['quantity_change'],
        ];

        return $this->moveItem($adjustmentData);
    }

    /**
     * Get movement history for an item with filtering options.
     */
    public function getMovementHistory(string $itemId, array $filters = []): array {
        $item = Item::findOrFail($itemId);

        if ($item->isAbstract()) {
            return ['movements' => [], 'total' => 0];
        }

        $query = $this->getQuery()
            ->where('item_id', $item->id)
            ->with(['fromLocation', 'toLocation', 'user'])
            ->orderBy('moved_at', 'desc');

        if (isset($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('moved_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('moved_at', '<=', $filters['date_to']);
        }

        $limit     = $filters['limit'] ?? 50;
        $movements = $query->limit($limit)->get();

        return [
            'movements' => $movements,
            'total'     => $movements->count(),
        ];
    }

    /**
     * Perform initial placement of an item.
     */
    public function initialPlacement(array $data): array {
        $item = Item::findOrFail($data['item_id']);

        if ($item->isAbstract()) {
            throw new InvalidArgumentException(__(ErrorMessages::ITEM_ABSTRACT_NO_LOCATION));
        }

        $data['from_location_id'] = null;
        $data['movement_type']    = ItemMovement::MOVEMENT_INITIAL_PLACEMENT;

        return $this->moveItem($data);
    }

    /**
     * Move an item between locations with comprehensive business rules and audit trail.
     */
    public function moveItem(array $data): array {
        $this->validateMovementData($data);

        $item         = Item::findOrFail($data['item_id']);
        $user         = \App\Services\AuthorizationHelper::getCurrentUser();
        $movementType = $this->determineMovementType($data);

        return DB::transaction(function () use ($item, $data, $user, $movementType) {
            $result = $this->executeMovement($item, $data, $user, $movementType);
            $this->createAuditTrail($item, $data, $movementType);

            return [
                'success'       => true,
                'message'       => __(SuccessMessages::ITEM_MOVED),
                'movement_id'   => $result['movement_id'],
                'movement_type' => $movementType,
            ];
        });
    }

    /**
     * Comprehensive ItemLocation integrity validation and repair.
     * Can be called periodically to ensure database consistency.
     */
    public function validateAndRepairItemLocationIntegrity(?int $itemId = null, bool $autoRepair = false): array {
        $item = $itemId ? Item::find($itemId) : null;

        return $this->validateAndRepairItemLocationIntegrityForItem($item, $autoRepair);
    }

    /**
     * Create comprehensive audit trail.
     */
    private function createAuditTrail(Item $item, array $data, string $movementType): void {
        $fromLocationName = null;
        $toLocationName   = 'Unknown Location';

        if (isset($data['from_location_id'])) {
            $fromLocation     = Location::find($data['from_location_id']);
            $fromLocationName = $fromLocation ? $fromLocation->name : null;
        }

        if (isset($data['to_location_id'])) {
            $toLocation     = Location::find($data['to_location_id']);
            $toLocationName = $toLocation ? $toLocation->name : 'Unknown Location';
        }

        // Get old and new quantities
        $oldQuantity = 0;
        if (isset($data['from_location_id'])) {
            $oldLocation = ItemLocation::where('item_id', $item->id)
                ->where('location_id', $data['from_location_id'])
                ->first();
            $oldQuantity = $oldLocation ? $oldLocation->quantity : 0;
        }

        $newQuantity = $data['quantity'] ?? 1;

        // Create an event using EventService
        $this->eventService->createMovementEvent(
            $item->id,
            $fromLocationName,
            $toLocationName,
            $oldQuantity,
            $newQuantity,
            $movementType,
            $data['reason'] ?? null,
        );
    }

    /**
     * Create a movement record with comprehensive data.
     */
    private function createMovementRecord(Item $item, ?string $fromLocationId, string $toLocationId, float $quantity, string $movementType, array $data, $user): ItemMovement {
        return ItemMovement::create([
            'org_id'           => $item->org_id,
            'item_id'          => $item->id,
            'from_location_id' => $fromLocationId,
            'to_location_id'   => $toLocationId,
            'quantity'         => $quantity,
            'user_id'          => $user->id ?? null,
            'moved_at'         => now(),
            'movement_type'    => $movementType,
            'reason'           => $data['reason'] ?? null,
            'reference_id'     => $data['reference_id'] ?? null,
            'reference_type'   => $data['reference_type'] ?? null,
            'notes'            => $data['notes'] ?? null,
        ]);
    }

    /**
     * Determine a movement type based on data.
     */
    private function determineMovementType(array $data): string {
        if (isset($data['movement_type'])) {
            return $data['movement_type'];
        }

        if (! isset($data['from_location_id'])) {
            return ItemMovement::MOVEMENT_INITIAL_PLACEMENT;
        }

        return ItemMovement::MOVEMENT_TRANSFER;
    }

    /**
     * Execute initial placement
     */
    private function executeInitialPlacement(Item $item, string $toLocationId, float $quantity, $user): array {
        if ($quantity <= 0) {
            throw new InvalidArgumentException(__(ErrorMessages::ITEM_MOVEMENT_QUANTITY_POSITIVE));
        }

        // For serialized items, ensure they don't already exist anywhere
        if ($item->isSerialized()) {
            $existingCount = ItemLocation::where('item_id', $item->id)->count();
            if ($existingCount > 0) {
                throw new InvalidArgumentException(__(ErrorMessages::ITEM_SERIALIZED_INITIAL_EXISTS));
            }
        }

        $existing = ItemLocation::where('item_id', $item->id)
            ->where('location_id', $toLocationId)
            ->first();

        if ($existing) {
            $existing->quantity += $quantity;
            $existing->moved_date = now();
            $existing->save();
            $finalQuantity = $existing->quantity;
        } else {
            ItemLocation::create([
                'item_id'     => $item->id,
                'location_id' => $toLocationId,
                'quantity'    => $quantity,
                'moved_date'  => now(),
                'org_id'      => $item->org_id,
            ]);
            $finalQuantity = $quantity;
        }

        $movement = $this->createMovementRecord($item, null, $toLocationId, $quantity, ItemMovement::MOVEMENT_INITIAL_PLACEMENT, [], $user);

        return ['movement_id' => $movement->id, 'new_quantity' => $finalQuantity];
    }

    /**
     * Execute the movement operation.
     */
    private function executeMovement(Item $item, array $data, $user, string $movementType): array {
        $toLocationId   = $data['to_location_id'];
        $fromLocationId = $data['from_location_id'] ?? null;
        $quantity       = $data['quantity'] ?? 1;

        if ($item->isSerialized()) {
            $quantity = 1;
        }

        $result = match ($movementType) {
            ItemMovement::MOVEMENT_ADJUSTMENT        => $this->executeQuantityAdjustment($item, $data, $user),
            ItemMovement::MOVEMENT_INITIAL_PLACEMENT => $this->executeInitialPlacement($item, $toLocationId, $quantity, $user),
            default                                  => $this->executeStandardMovement($item, $fromLocationId, $toLocationId, $quantity, $user),
        };

        if ($fromLocationId) {
            $this->validateItemLocationIntegrity($item);
        }
        $this->validateItemLocationIntegrity($item);

        return $result;
    }

    /**
     * Execute quantity adjustment
     */
    private function executeQuantityAdjustment(Item $item, array $data, $user): array {
        $locationId     = $data['to_location_id'];
        $quantityChange = $data['quantity_change'];

        $location = ItemLocation::where('item_id', $item->id)
            ->where('location_id', $locationId)
            ->firstOrFail();

        $oldQuantity = $location->quantity;
        $newQuantity = $location->quantity + $quantityChange;

        if ($newQuantity < 0) {
            throw new InvalidArgumentException(
                MessageGeneratorService::generate(ErrorMessages::ITEM_ADJUSTMENT_QUANTITY_BELOW_ZERO, [
                    'available' => $oldQuantity,
                    'reduction' => abs($quantityChange),
                ]),
            );
        }

        if ($newQuantity === 0) {
            $location->delete();
        } else {
            $location->quantity   = $newQuantity;
            $location->moved_date = now();
            $location->save();
        }

        $movement = $this->createMovementRecord($item, $locationId, $locationId, abs($quantityChange), ItemMovement::MOVEMENT_ADJUSTMENT, $data, $user);

        return ['movement_id' => $movement->id, 'old_quantity' => $oldQuantity, 'new_quantity' => $newQuantity];
    }

    /**
     * Execute standard movement between locations with robust ItemLocation management.
     */
    private function executeStandardMovement(Item $item, ?string $fromLocationId, string $toLocationId, float $quantity, $user): array {
        if ($quantity <= 0) {
            throw new InvalidArgumentException(__(ErrorMessages::ITEM_MOVEMENT_QUANTITY_POSITIVE));
        }

        $source = ItemLocation::where('item_id', $item->id)
            ->where('location_id', $fromLocationId)
            ->firstOrFail();

        if ($source->quantity < $quantity) {
            throw new InvalidArgumentException(
                MessageGeneratorService::generate(ErrorMessages::ITEM_INSUFFICIENT_QUANTITY, [
                    'available' => $source->quantity,
                    'required'  => $quantity,
                ]),
            );
        }

        $oldSourceQuantity = $source->quantity;
        $source->quantity -= $quantity;

        $sourceDeleted = false;
        if ($source->quantity <= 0) {
            $source->delete();
            $sourceDeleted = true;
        } else {
            $source->save();
        }

        $destination = ItemLocation::where('item_id', $item->id)
            ->where('location_id', $toLocationId)
            ->first();

        $oldDestQuantity = $destination ? $destination->quantity : 0;

        if ($destination) {
            $destination->quantity += $quantity;
            $destination->moved_date = now();
            $destination->save();
        } else {
            $destination = ItemLocation::create([
                'item_id'     => $item->id,
                'location_id' => $toLocationId,
                'quantity'    => $quantity,
                'moved_date'  => now(),
                'org_id'      => $item->org_id,
            ]);
        }

        $movement = $this->createMovementRecord($item, $fromLocationId, $toLocationId, $quantity, ItemMovement::MOVEMENT_TRANSFER, [], $user);

        return [
            'movement_id'         => $movement->id,
            'source_old_quantity' => $oldSourceQuantity,
            'source_new_quantity' => $sourceDeleted ? 0 : $source->quantity,
            'dest_old_quantity'   => $oldDestQuantity,
            'dest_new_quantity'   => $destination->quantity,
        ];
    }

    /**
     * Internal method for integrity validation with an Item object.
     */
    private function validateAndRepairItemLocationIntegrityForItem(?Item $item = null, bool $autoRepair = false): array {
        $issues  = [];
        $repairs = [];

        $itemsQuery = $item ? collect([$item]) : Item::where('tracking_mode', '!=', 'abstract');

        foreach ($itemsQuery as $currentItem) {
            $badQuantities = ItemLocation::where('item_id', $currentItem->id)
                ->where('quantity', '<=', 0)
                ->get();

            if ($badQuantities->isNotEmpty()) {
                $issues[] = "Item {$currentItem->id} has " . $badQuantities->count() . ' location(s) with zero or negative quantity';

                if ($autoRepair) {
                    $deleted = ItemLocation::where('item_id', $currentItem->id)
                        ->where('quantity', '<=', 0)
                        ->delete();
                    $repairs[] = "Deleted {$deleted} zero/negative quantity records for item {$currentItem->id}";
                }
            }

            // Check serialized items in multiple locations
            if ($currentItem->isSerialized()) {
                $locationCount = ItemLocation::where('item_id', $currentItem->id)->count();
                if ($locationCount > 1) {
                    $locations = ItemLocation::where('item_id', $currentItem->id)
                        ->pluck('location_id')
                        ->toArray();
                    $issues[] = "Serialized item {$currentItem->id} exists in multiple locations: " . implode(', ', $locations);

                    if ($autoRepair) {
                        $keepRecord = ItemLocation::where('item_id', $currentItem->id)
                            ->orderBy('moved_date', 'desc')
                            ->first();

                        $deleted = ItemLocation::where('item_id', $currentItem->id)
                            ->where('id', '!=', $keepRecord->id)
                            ->delete();

                        $repairs[] = "Kept most recent location for serialized item {$currentItem->id}, deleted {$deleted} duplicate records";
                    }
                }
            }

            // Check for orphaned ItemLocation records
            $orphanedLocations = ItemLocation::whereNotIn('item_id', static function ($query): void {
                $query->select('id')->from('items');
            })->get();

            if ($orphanedLocations->isNotEmpty()) {
                $issues[] = 'Found ' . $orphanedLocations->count() . ' orphaned ItemLocation records';

                if ($autoRepair) {
                    $deleted = ItemLocation::whereNotIn('item_id', static function ($query): void {
                        $query->select('id')->from('items');
                    })->delete();
                    $repairs[] = "Deleted {$deleted} orphaned ItemLocation records";
                }
            }
        }

        return [
            'issues_found' => count($issues),
            'repairs_made' => count($repairs),
            'issues'       => $issues,
            'repairs'      => $repairs,
        ];
    }

    /**
     * Validate ItemLocation integrity after movement operations.
     */
    private function validateItemLocationIntegrity(Item $item): void {
        // Clean up any zero or negative quantity records
        ItemLocation::where('item_id', $item->id)
            ->where('quantity', '<=', 0)
            ->delete();

        if ($item->isSerialized()) {
            $totalLocations = ItemLocation::where('item_id', $item->id)->count();
            if ($totalLocations > 1) {
                $locations = ItemLocation::where('item_id', $item->id)
                    ->pluck('location_id', 'quantity')
                    ->toArray();

                throw new RuntimeException(__(ErrorMessages::ITEM_SERIALIZED_MULTIPLE_LOCATIONS) .
                    ' Found in locations: ' . json_encode($locations));
            }
        }

        if ($item->isStandard()) {
            $totalQuantity = ItemLocation::where('item_id', $item->id)
                ->sum('quantity');

            if ($totalQuantity < 0) {
                throw new RuntimeException(__(ErrorMessages::QUANTITY_MIN) .
                    ' Current total: ' . $totalQuantity);
            }
        }
    }

    /**
     * Validate movement data based on business rules.
     */
    private function validateMovementData(array $data): void {
        if (empty($data)) {
            throw new InvalidArgumentException(__(ErrorMessages::EMPTY_DATA));
        }

        if (! isset($data['item_id'])) {
            throw new InvalidArgumentException('Item ID is required');
        }

        $item = Item::findOrFail($data['item_id']);

        if ($item->isAbstract()) {
            throw new InvalidArgumentException(__(ErrorMessages::ITEM_ABSTRACT_NO_LOCATION));
        }

        if ($item->isSerialized() && isset($data['quantity']) && $data['quantity'] !== 1) {
            throw new InvalidArgumentException(__(ErrorMessages::ITEM_SERIALIZED_QUANTITY_ONE));
        }

        if ($item->isStandard()) {
            if (! isset($data['quantity']) || $data['quantity'] <= 0) {
                throw new InvalidArgumentException(__(ErrorMessages::ITEM_QUANTITY_REQUIRED));
            }
        }

        if (isset($data['from_location_id']) && $data['from_location_id'] === $data['to_location_id']) {
            if (! isset($data['movement_type']) || $data['movement_type'] !== ItemMovement::MOVEMENT_ADJUSTMENT) {
                throw new InvalidArgumentException(__(ErrorMessages::ITEM_LOCATIONS_MUST_DIFFER));
            }
        }

        if ($item->isStandard() && isset($data['from_location_id'])) {
            $this->validateSourceLocationQuantity($item, $data);
        }
    }

    /**
     * Validate that source location has sufficient quantity.
     */
    private function validateSourceLocationQuantity(Item $item, array $data): void {
        $currentLocation = ItemLocation::where('item_id', $item->id)
            ->where('location_id', $data['from_location_id'])
            ->first();

        if (! $currentLocation) {
            throw new InvalidArgumentException(__(ErrorMessages::ITEM_NOT_IN_SOURCE_LOCATION));
        }

        $quantityNeeded = $data['quantity'];
        if (isset($data['quantity_change']) && $data['quantity_change'] < 0) {
            $quantityNeeded = abs($data['quantity_change']);
        }

        if ($currentLocation->quantity < $quantityNeeded) {
            throw new InvalidArgumentException(
                MessageGeneratorService::generate(ErrorMessages::ITEM_INSUFFICIENT_QUANTITY, [
                    'available' => $currentLocation->quantity,
                    'required'  => $quantityNeeded,
                ]),
            );
        }
    }
}
