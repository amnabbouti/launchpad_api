<?php

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Models\Item;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ItemMovementService
{
    /**
     * Validate movement data based on business rules.
     */
    public function validateMovementData(Item $item, array $data): void
    {
        // Abstract items cannot be moved
        if ($item->isAbstract()) {
            throw new \InvalidArgumentException('Abstract items cannot be moved to physical locations.');
        }

        // For serialized items, quantity should always be 1
        if ($item->isSerialized()) {
            if (isset($data['quantity']) && $data['quantity'] != 1) {
                throw new \InvalidArgumentException('Serialized items can only move in quantities of 1.');
            }
        }

        // For bulk items, quantity is required and must be > 0
        if ($item->isBulk()) {
            if (!isset($data['quantity']) || $data['quantity'] <= 0) {
                throw new \InvalidArgumentException('Quantity is required for bulk items and must be greater than 0.');
            }
        }

        // Validate that from_location actually contains the item (for bulk items)
        if ($item->isBulk() && isset($data['from_location_id'])) {
            $currentLocation = $item->locations()
                ->where('location_id', $data['from_location_id'])
                ->first();

            if (!$currentLocation) {
                throw new \InvalidArgumentException('The item is not currently in the specified source location.');
            }

            if ($currentLocation->pivot->quantity < $data['quantity']) {
                throw new \InvalidArgumentException(
                    'Insufficient quantity in source location. Available: ' . $currentLocation->pivot->quantity
                );
            }
        }

        // Validate destination location exists and is different from source
        if (isset($data['from_location_id']) && $data['to_location_id'] == $data['from_location_id']) {
            throw new \InvalidArgumentException('The source and destination locations must be different.');
        }
    }

    /**
     * Move an item to a location
     */
    public function moveItem(Item $item, array $data): array
    {
        $this->validateMovementData($item, $data);
        if ($item->isSerialized()) {
            $data['quantity'] = 1;
        }

        return $this->performMove($item, $data);
    }
    /**
     * Perform the actual item movement
     */
    private function performMove(Item $item, array $data): array
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            throw new \RuntimeException(ErrorMessages::UNAUTHORIZED);
        }
        $toLocationId = $data['to_location_id'];
        $fromLocationId = $data['from_location_id'] ?? null;
        $quantity = $data['quantity'] ?? 1;
        $notes = $data['notes'] ?? null;

        DB::beginTransaction();

        try {
            // Handle bulk updates
            if ($item->isBulk()) {
                $this->handleBulkLocationUpdate($item, $fromLocationId, $toLocationId, $quantity, $notes);
            }

            // Handle serialized updates
            if ($item->isSerialized()) {
                $this->handleSerializedLocationUpdate($item, $toLocationId, $notes);
            }

            // Create movement record
            $movement = $item->movements()->create([
                'org_id' => $item->org_id,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'quantity' => $quantity,
                'user_id' => $user?->id,
                'moved_at' => now(),
                'notes' => $notes,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Item moved successfully',
                'movement' => $movement->toArray(),
            ];
        } catch (\Exception $e) {
            DB::rollback();
            throw new \RuntimeException(ErrorMessages::ITEM_MOVE_FAILED . ': ' . $e->getMessage());
        }
    }

    /**
     * Handle location updates for bulk items.
     */
    private function handleBulkLocationUpdate(Item $item, ?int $fromLocationId, int $toLocationId, float $quantity, ?string $notes): void
    {
        // Remove quantity from source location
        if ($fromLocationId) {
            $fromRecord = $item->locations()->where('location_id', $fromLocationId)->first();
            $newQuantity = $fromRecord->pivot->quantity - $quantity;

            if ($newQuantity <= 0) {
                $item->locations()->detach($fromLocationId);
            } else {
                $item->locations()->updateExistingPivot($fromLocationId, ['quantity' => $newQuantity]);
            }
        }

        // Add quantity to destination location
        $existing = $item->locations()->where('location_id', $toLocationId)->first();
        if ($existing) {
            $newQuantity = $existing->pivot->quantity + $quantity;
            $item->locations()->updateExistingPivot($toLocationId, [
                'quantity' => $newQuantity,
                'moved_date' => now(),
            ]);
        } else {
            $item->locations()->attach($toLocationId, [
                'org_id' => $item->org_id,
                'quantity' => $quantity,
                'moved_date' => now(),
                'notes' => $notes,
            ]);
        }
    }

    /**
     * Handle location updates for serialized items.
     */
    private function handleSerializedLocationUpdate(Item $item, int $toLocationId, ?string $notes): void
    {
        // For serialized items: replace current location
        $item->locations()->detach();
        $item->locations()->attach($toLocationId, [
            'org_id' => $item->org_id,
            'quantity' => 1,
            'moved_date' => now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Get location history for an item.
     */
    public function getLocationHistory(Item $item): \Illuminate\Database\Eloquent\Collection
    {
        if ($item->isAbstract()) {
            throw new \InvalidArgumentException('Abstract items do not have location history');
        }

        return $item->movements()
            ->with(['fromLocation', 'toLocation', 'user'])
            ->orderBy('moved_at', 'desc')
            ->get();
    }

    /**
     * Get recent movements for an item.
     */
    public function getRecentMovements(Item $item, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        if ($item->isAbstract()) {
            throw new \InvalidArgumentException('Abstract items do not have movements');
        }

        return $item->movements()
            ->with(['fromLocation', 'toLocation', 'user'])
            ->orderBy('moved_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
