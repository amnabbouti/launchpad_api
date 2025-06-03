<?php

namespace App\Services;

use App\Models\CheckInOut;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class CheckInOutService extends BaseService
{
    protected ItemService $itemService;

    public function __construct(CheckInOut $checkInOut, ItemService $itemService)
    {
        parent::__construct($checkInOut);
        $this->itemService = $itemService;
    }

    /**
     * Get checkouts by user.
     */
    public function getByUser(int $userId): Collection
    {
        return $this->getQuery()->where('user_id', $userId)->get();
    }

    /**
     * Get checkouts by stock item.
     */
    public function getByStockItem(int $stockItemId): Collection
    {
        return $this->getQuery()->where('stock_item_id', $stockItemId)->get();
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
        'stockItem',
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
     * Get active checkouts for a stock item.
     */
    public function getActiveCheckoutsForStockItem(int $stockItemId): Collection
    {
        return $this->getQuery()
            ->where('stock_item_id', $stockItemId)
            ->whereNull('checkin_date')
            ->get();
    }

    /**
     * Checkout a stock item.
     */
    public function checkout(int $stockItemId, array $data): Model
    {
        $item = $this->itemService->findById($data['stock_item_id'], ['*'], [
            'maintenances' => fn ($q) => $q->whereNull('date_back_from_maintenance'),
            'stockItems' => fn ($q) => $q->where('id', $stockItemId),
        ]);

        if (! $item) {
            throw new InvalidArgumentException('Item not found');
        }

        $stockItem = $item->stockItems->first();

        if (! $stockItem) {
            throw new InvalidArgumentException('Stock item not found for this item');
        }

        if ($item->maintenances->count() > 0) {
            throw new \Exception('Item is in maintenance');
        }

        if ($this->getActiveCheckoutsForStockItem($stockItemId)->count() > 0) {
            throw new \Exception('Stock item is already checked out');
        }

        $checkoutData = [
            'org_id' => $data['org_id'],
            'user_id' => $data['user_id'],
            'stock_item_id' => $stockItemId,
            'checkout_location_id' => $data['checkout_location_id'],
            'checkout_date' => $data['checkout_date'] ?? now(),
            'quantity' => $data['checkout_quantity'],
            'status_out_id' => $data['status_out_id'] ?? null,
            'expected_return_date' => $data['expected_return_date'] ?? null,
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => true,
        ];

        return $this->create($checkoutData);
    }

    /**
     * Check in a stock item.
     */
    public function checkin(int $stockItemId, array $data): Model
    {
        $item = $this->itemService->findById($data['stock_item_id']);

        if (! $item) {
            throw new InvalidArgumentException('Item not found');
        }

        $activeCheckout = $this->getQuery()
            ->where('stock_item_id', $stockItemId)
            ->whereNull('checkin_date')
            ->first();

        if (! $activeCheckout) {
            throw new \Exception('Stock item is not checked out');
        }

        $user = auth()->user();

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
     * Get history of a stock item.
     */
    public function getHistory(int $stockItemId, int $perPage = 15): LengthAwarePaginator
    {
        $item = $this->itemService->findById($stockItemId);

        if (! $item) {
            throw new InvalidArgumentException('Item not found');
        }

        return $this->getQuery()
            ->where('stock_item_id', $stockItemId)
            ->with([
                'user',
                'checkinUser',
                'stockItem',
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

        // Apply filters using Laravel's when() method for clean conditional filtering
        $query->when($filters['user_id'] ?? null, fn ($q, $value) => $q->where('user_id', $value))
            ->when($filters['stock_item_id'] ?? null, fn ($q, $value) => $q->where('stock_item_id', $value))
            ->when($filters['checkout_location_id'] ?? null, fn ($q, $value) => $q->where('checkout_location_id', $value))
            ->when($filters['checkin_location_id'] ?? null, fn ($q, $value) => $q->where('checkin_location_id', $value))
            ->when($filters['status_out_id'] ?? null, fn ($q, $value) => $q->where('status_out_id', $value))
            ->when($filters['status_in_id'] ?? null, fn ($q, $value) => $q->where('status_in_id', $value))
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['active_only'] ?? null, fn ($q) => $q->whereNull('checkin_date'))
            ->when($filters['overdue_only'] ?? null, fn ($q) => $q->whereNull('checkin_date')
                ->whereNotNull('expected_return_date')
                ->where('expected_return_date', '<', now()))
            ->when($filters['date_from'] ?? null, fn ($q, $value) => $q->where('checkout_date', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($q, $value) => $q->where('checkout_date', '<=', $value))
            ->when($filters['with'] ?? null, fn ($q, $relations) => $q->with($relations));

        return $query->get();
    }
}
