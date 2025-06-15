<?php

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Models\CheckInOut;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class CheckInOutService extends BaseService
{
    protected ItemLocationService $itemLocationService;

    public function __construct(CheckInOut $checkInOut, ItemLocationService $itemLocationService)
    {
        parent::__construct($checkInOut);
        $this->itemLocationService = $itemLocationService;
    }

    /**
     * Get checkouts by user.
     */
    public function getByUser(int $userId): Collection
    {
        return $this->getQuery()->where('user_id', $userId)->get();
    }

    /**
     * Get checkouts by item location.
     */
    public function getByItemLocation(string $itemLocationId): Collection
    {
        return $this->getQuery()
            ->where('trackable_type', \App\Models\ItemLocation::class)
            ->where('trackable_id', $itemLocationId)
            ->get();
    }

    /**
     * Get active checkouts.
     */
    public function getActive(): Collection
    {
        return $this->getQuery()->whereNull('checkin_date')->get();
    }

    /**
     * Get checkouts with relations.
     */
    public function getWithRelations(array $relations = [
        'user',
        'trackable',
        'checkoutLocation',
        'checkinLocation',
        'statusOut',
        'statusIn',
    ]): Collection
    {
        return $this->getQuery()->with($relations)->get();
    }

    /**
     * Get overdue checkouts.
     */
    public function getOverdue(): Collection
    {
        return $this->getQuery()
            ->whereNull('checkin_date')
            ->whereNotNull('expected_return_date')
            ->where('expected_return_date', '<', now())
            ->get();
    }

    /**
     * Get active checkouts for an item location.
     */
    public function getActiveCheckoutsForItemLocation(string $itemLocationId): Collection
    {
        return $this->getQuery()
            ->where('trackable_type', \App\Models\ItemLocation::class)
            ->where('trackable_id', $itemLocationId)
            ->whereNull('checkin_date')
            ->get();
    }

    /**
     * Checkout an item location.
     */
    public function checkout(string $itemLocationId, array $data): Model
    {
        $itemLocation = $this->itemLocationService->findById($itemLocationId, ['item', 'item.maintenances' => fn($q) => $q->whereNull('date_back_from_maintenance')]);

        if (! $itemLocation) {
            throw new InvalidArgumentException(ErrorMessages::ITEM_NOT_FOUND);
        }

        $item = $itemLocation->item;

        // Check if item is in maintenance
        if ($item->maintenances->count() > 0) {
            throw new \Exception('Item is in maintenance');
        }

        // Check if this item location is already checked out
        if ($this->getActiveCheckoutsForItemLocation($itemLocationId)->count() > 0) {
            throw new \Exception('Item location is already checked out');
        }

        // Check if requested quantity is available
        $requestedQuantity = $data['checkout_quantity'] ?? 1;
        if ($itemLocation->quantity < $requestedQuantity) {
            throw new \Exception('Insufficient quantity available at this location');
        }

        // For serialized tracking, ensure the item can be checked out
        if ($item->tracking_mode === 'serialized' && $requestedQuantity > 1) {
            throw new \Exception('Serialized items can only be checked out one at a time');
        }

        $checkoutData = [
            'org_id' => $data['org_id'],
            'user_id' => $data['user_id'],
            'trackable_type' => \App\Models\ItemLocation::class,
            'trackable_id' => $itemLocation->id,
            'checkout_location_id' => $data['checkout_location_id'],
            'checkout_date' => $data['checkout_date'] ?? now(),
            'quantity' => $requestedQuantity,
            'status_out_id' => $data['status_out_id'] ?? null,
            'expected_return_date' => $data['expected_return_date'] ?? null,
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => true,
        ];

        return $this->create($checkoutData);
    }

    /**
     * Check in an item location.
     */
    public function checkin(string $itemLocationId, array $data): Model
    {
        $itemLocation = $this->itemLocationService->findById($itemLocationId);

        if (! $itemLocation) {
            throw new InvalidArgumentException(ErrorMessages::ITEM_NOT_FOUND);
        }

        $activeCheckout = $this->getQuery()
            ->where('trackable_type', \App\Models\ItemLocation::class)
            ->where('trackable_id', $itemLocation->id)
            ->whereNull('checkin_date')
            ->first();

        if (! $activeCheckout) {
            throw new \Exception('Item location is not checked out');
        }

        $user = Auth::guard('api')->user();

        if (! $user) {
            throw new \Exception('User not authenticated');
        }

        if ($activeCheckout->user_id !== $user->id && ! ($user->is_admin ?? false)) {
            throw new \Exception('Not authorized to check in this item');
        }

        $checkinData = [
            'checkin_user_id' => $data['checkin_user_id'],
            'checkin_location_id' => $data['checkin_location_id'],
            'checkin_date' => $data['checkin_date'] ?? now(),
            'checkin_quantity' => $data['checkin_quantity'] ?? $activeCheckout->quantity,
            'status_in_id' => $data['status_in_id'] ?? null,
            'notes' => $data['notes'] ?? $activeCheckout->notes,
            'is_active' => false,
        ];

        return $this->update($activeCheckout->id, $checkinData);
    }

    /**
     * Get history of an item location.
     */
    public function getHistory(string $itemLocationId, int $perPage = 15): LengthAwarePaginator
    {
        $itemLocation = $this->itemLocationService->findById($itemLocationId);

        if (! $itemLocation) {
            throw new InvalidArgumentException(ErrorMessages::ITEM_NOT_FOUND);
        }

        return $this->getQuery()
            ->where('trackable_type', \App\Models\ItemLocation::class)
            ->where('trackable_id', $itemLocationId)
            ->with([
                'user',
                'checkinUser',
                'trackable.item',
                'trackable.location',
                'checkoutLocation',
                'checkinLocation',
                'statusOut',
                'statusIn',
            ])
            ->orderByDesc('checkout_date')
            ->paginate($perPage);
    }

    /**
     * Get filtered check-in/out records.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        // Apply filters 
        $query->when($filters['user_id'] ?? null, fn($q, $value) => $q->where('user_id', $value))
            ->when($filters['item_location_id'] ?? null, fn($q, $value) =>
            $q->where('trackable_type', \App\Models\ItemLocation::class)
                ->where('trackable_id', $value))
            ->when($filters['checkout_location_id'] ?? null, fn($q, $value) => $q->where('checkout_location_id', $value))
            ->when($filters['checkin_location_id'] ?? null, fn($q, $value) => $q->where('checkin_location_id', $value))
            ->when($filters['status_out_id'] ?? null, fn($q, $value) => $q->where('status_out_id', $value))
            ->when($filters['status_in_id'] ?? null, fn($q, $value) => $q->where('status_in_id', $value))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['active_only'] ?? null, fn($q) => $q->whereNull('checkin_date'))
            ->when($filters['overdue_only'] ?? null, fn($q) => $q->whereNull('checkin_date')
                ->whereNotNull('expected_return_date')
                ->where('expected_return_date', '<', now()))
            ->when($filters['date_from'] ?? null, fn($q, $value) => $q->where('checkout_date', '>=', $value))
            ->when($filters['date_to'] ?? null, fn($q, $value) => $q->where('checkout_date', '<=', $value))
            ->when($filters['with'] ?? null, fn($q, $relations) => $q->with($relations));

        return $query->get();
    }
}
