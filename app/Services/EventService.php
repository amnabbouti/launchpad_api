<?php

declare(strict_types = 1);

namespace App\Services;

use App\Models\Item;
use App\Models\ItemHistoryEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

use function in_array;

class EventService extends BaseService {
    public const EVENT_TYPES = [
        'movement'            => 'movement',
        'check_in'            => 'check_in',
        'check_out'           => 'check_out',
        'maintenance_start'   => 'maintenance_start',
        'maintenance_end'     => 'maintenance_end',
        'status_change'       => 'status_change',
        'quantity_adjustment' => 'quantity_adjustment',
        'initial_placement'   => 'initial_placement',
        'transfer'            => 'transfer',
        'system_update'       => 'system_update',
    ];

    public function __construct() {
        parent::__construct(new ItemHistoryEvent);
    }

    public function createCheckInEvent(
        string $itemId,
        string $location,
        float | string $quantity,
        ?string $notes = null,
        ?string $checkedInBy = null,
    ): ItemHistoryEvent {
        $quantity    = (float) $quantity;
        $description = "Item checked in at {$location} (Quantity: {$quantity})";

        $metadata = [
            'old_values' => [
                'status'   => 'checked_out',
                'location' => null,
            ],
            'new_values' => [
                'status'        => 'checked_in',
                'location'      => $location,
                'quantity'      => $quantity,
                'notes'         => $notes,
                'checked_in_by' => $checkedInBy ?: Auth::id(),
            ],
        ];

        return $this->createEvent(
            $itemId,
            self::EVENT_TYPES['check_in'],
            $description,
            $metadata,
            $checkedInBy,
        );
    }

    public function createCheckOutEvent(
        string $itemId,
        string $location,
        float | string $quantity,
        ?string $notes = null,
        ?string $checkedOutBy = null,
    ): ItemHistoryEvent {
        $quantity    = (float) $quantity;
        $description = "Item checked out from {$location} (Quantity: {$quantity})";

        $metadata = [
            'old_values' => [
                'status'   => 'available',
                'location' => $location,
            ],
            'new_values' => [
                'status'         => 'checked_out',
                'location'       => $location,
                'quantity'       => $quantity,
                'notes'          => $notes,
                'checked_out_by' => $checkedOutBy ?: Auth::id(),
            ],
        ];

        return $this->createEvent(
            $itemId,
            self::EVENT_TYPES['check_out'],
            $description,
            $metadata,
            $checkedOutBy,
        );
    }

    public function createEvent(
        string $itemId,
        string $eventType,
        string $description,
        array $metadata = [],
        ?string $userId = null,
    ): ItemHistoryEvent {
        if (! in_array($eventType, self::EVENT_TYPES, true)) {
            throw new InvalidArgumentException("Invalid event type: {$eventType}");
        }

        $item = Item::findOrFail($itemId);

        $user = null;
        if ($userId !== null) {
            $user = User::findOrFail($userId);
        } elseif (Auth::check()) {
            $user = Auth::user();
        }

        return ItemHistoryEvent::create([
            'org_id'     => $item->org_id,
            'item_id'    => $item->id,
            'user_id'    => $user?->id,
            'event_type' => $this->mapEventTypeToEnum($eventType),
            'old_values' => $metadata['old_values'] ?? null,
            'new_values' => $metadata['new_values'] ?? null,
            'reason'     => $description,
        ]);
    }

    public function createMaintenanceEvent(
        string $itemId,
        string $maintenanceType,
        string $description,
        string $maintenanceId,
        mixed $expectedReturnDate = null,
        mixed $dateInMaintenance = null,
        mixed $dateBackFromMaintenance = null,
        ?string $maintenanceConditionId = null,
        ?string $maintenanceCategory = null,
        ?string $triggerValue = null,
        ?string $userId = null,
    ): ItemHistoryEvent {
        $expectedReturnDate      = $expectedReturnDate ? (string) $expectedReturnDate : null;
        $dateInMaintenance       = $dateInMaintenance ? (string) $dateInMaintenance : null;
        $dateBackFromMaintenance = $dateBackFromMaintenance ? (string) $dateBackFromMaintenance : null;

        $eventType = $maintenanceType === 'start' ? self::EVENT_TYPES['maintenance_start'] : self::EVENT_TYPES['maintenance_end'];

        $metadata = [
            'old_values' => [
                'status' => $maintenanceType === 'start' ? 'available' : 'in_maintenance',
            ],
            'new_values' => [
                'status'         => $maintenanceType === 'start' ? 'in_maintenance' : 'available',
                'maintenance_id' => $maintenanceId,
            ],
        ];

        // Add additional data for start event
        if ($maintenanceType === 'start') {
            if ($dateInMaintenance) {
                $metadata['new_values']['date_in_maintenance'] = $dateInMaintenance;
            }
            if ($expectedReturnDate) {
                $metadata['new_values']['expected_return_date'] = $expectedReturnDate;
            }
            if ($maintenanceConditionId) {
                $metadata['new_values']['maintenance_condition_id'] = $maintenanceConditionId;
            }
            if ($maintenanceCategory) {
                $metadata['new_values']['maintenance_category'] = $maintenanceCategory;
            }
            if ($triggerValue) {
                $metadata['new_values']['trigger_value'] = $triggerValue;
            }
        }

        // Add additional data for the end event
        if ($maintenanceType === 'end') {
            if ($dateInMaintenance) {
                $metadata['new_values']['date_in_maintenance'] = $dateInMaintenance;
            }
            if ($dateBackFromMaintenance) {
                $metadata['new_values']['date_back_from_maintenance'] = $dateBackFromMaintenance;
            }
            $metadata['old_values']['maintenance_id'] = $maintenanceId;
        }

        return $this->createEvent(
            $itemId,
            $eventType,
            $description,
            $metadata,
            $userId,
        );
    }

    public function createMovementEvent(
        string $itemId,
        ?string $fromLocation,
        string $toLocation,
        float $oldQuantity,
        float $newQuantity,
        string $movementType,
        ?string $notes = null,
    ): ItemHistoryEvent {
        $description = $this->buildMovementDescription($fromLocation, $toLocation, $oldQuantity, $newQuantity, $movementType);

        $metadata = [
            'old_values' => [
                'location' => $fromLocation,
                'quantity' => $oldQuantity,
            ],
            'new_values' => [
                'location'      => $toLocation,
                'quantity'      => $newQuantity,
                'movement_type' => $movementType,
                'notes'         => $notes,
            ],
        ];

        return $this->createEvent(
            $itemId,
            self::EVENT_TYPES['movement'],
            $description,
            $metadata,
        );
    }

    public function getItemEventHistory(
        null | int | string $itemId = null,
        array $eventTypes = [],
        array $filters = [],
    ): Builder {
        if ($itemId !== null) {
            $query = ItemHistoryEvent::where('item_id', $itemId);
        } else {
            // System-wide events
            $query = $this->getQuery();
        }

        $query = $query->with(['user', 'item'])
            ->orderBy('created_at', 'desc');

        if (! empty($eventTypes)) {
            $mappedEventTypes = array_map([$this, 'mapEventTypeToEnum'], $eventTypes);
            $query->whereIn('event_type', $mappedEventTypes);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query;
    }

    public function mapEventTypeToEnum(string $eventType): string {
        $mapping = [
            'movement'            => 'moved',
            'check_in'            => 'updated',
            'check_out'           => 'updated',
            'maintenance_start'   => 'maintenance_in',
            'maintenance_end'     => 'maintenance_out',
            'status_change'       => 'updated',
            'quantity_adjustment' => 'updated',
            'initial_placement'   => 'created',
            'transfer'            => 'moved',
            'system_update'       => 'updated',
        ];

        return $mapping[$eventType] ?? 'updated';
    }

    private function buildMovementDescription(
        ?string $fromLocation,
        string $toLocation,
        float $oldQuantity,
        float $newQuantity,
        string $movementType,
    ): string {
        switch ($movementType) {
            case 'initial':
                return "Initial placement at {$toLocation} (Quantity: {$newQuantity})";
            case 'move':
                if ($fromLocation && $fromLocation !== $toLocation) {
                    return "Moved from {$fromLocation} to {$toLocation} (Quantity: {$newQuantity})";
                }

                return "Quantity adjusted at {$toLocation} (From: {$oldQuantity} to {$newQuantity})";
            case 'adjust':
                $change     = $newQuantity - $oldQuantity;
                $changeText = $change > 0 ? "+{$change}" : (string) $change;

                return "Quantity adjusted at {$toLocation} ({$changeText}, Total: {$newQuantity})";
            default:
                return "Item movement at {$toLocation} (Quantity: {$newQuantity})";
        }
    }
}
