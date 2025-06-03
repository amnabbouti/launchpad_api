<?php

namespace App\Services;

use App\Models\StockItemLocation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockItemLocationService extends BaseService
{
    public function __construct(StockItemLocation $stockItemLocation)
    {
        parent::__construct($stockItemLocation);
    }

    /**
     * Process request parameters for query building.
     */
    public function processRequestParams(array $params): array
    {
        return [
            'with' => isset($params['with'])
                ? array_intersect(explode(',', $params['with']), ['stock_item', 'location'])
                : null,
            'location_id' => isset($params['location_id']) ? (int) $params['location_id'] : null,
            'stock_item_id' => isset($params['stock_item_id']) ? (int) $params['stock_item_id'] : null,
            'moved_date' => $params['moved_date'] ?? null,
            'positive_quantity' => isset($params['positive_quantity']) ? filter_var($params['positive_quantity'], FILTER_VALIDATE_BOOLEAN) : null,
        ];
    }

    /**
     * Get filtered stock item locations.
     */
    public function getFiltered(array $filters = []): Collection
    {
        return $this->getQuery()
            ->when($filters['location_id'] ?? null, fn ($q, $id) => $q->where('location_id', $id))
            ->when($filters['stock_item_id'] ?? null, fn ($q, $id) => $q->where('stock_item_id', $id))
            ->when($filters['moved_date'] ?? null, function ($q, $date) {
                try {
                    $parsedDate = Carbon::parse($date)->format('Y-m-d');

                    return $q->whereDate('moved_date', $parsedDate);
                } catch (\Exception $e) {
                    throw new InvalidArgumentException('Invalid date format. Use YYYY-MM-DD.');
                }
            })
            ->when($filters['positive_quantity'] ?? null, fn ($q) => $q->where('quantity', '>', 0))
            ->when($filters['with'] ?? null, fn ($q, $with) => $q->with($with))
            ->get();
    }

    /**
     * Create a new stock item location.
     */
    public function createStockItemLocation(array $data): Model
    {
        return $this->create($data);
    }

    /**
     * Update a stock item location.
     */
    public function updateStockItemLocation(int $id, array $data): Model
    {
        return $this->update($id, $data);
    }

    /**
     * Move stock items between locations.
     */
    public function moveStockItem(
        int $stockItemId,
        int $fromLocationId,
        int $toLocationId,
        float $quantity,
    ): bool {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative');
        }

        $source = $this->getQuery()
            ->where('stock_item_id', $stockItemId)
            ->where('location_id', $fromLocationId)
            ->first();

        if (! $source || $source->quantity < $quantity) {
            return false;
        }

        $destination = $this->getQuery()
            ->where('stock_item_id', $stockItemId)
            ->where('location_id', $toLocationId)
            ->first();

        return DB::transaction(function () use ($source, $destination, $stockItemId, $toLocationId, $quantity) {
            $source->quantity -= $quantity;
            $source->save();

            if ($destination) {
                $destination->quantity += $quantity;
                $destination->moved_date = now();
                $destination->save();
            } else {
                $this->create([
                    'stock_item_id' => $stockItemId,
                    'location_id' => $toLocationId,
                    'quantity' => $quantity,
                    'moved_date' => now(),
                ]);
            }

            return true;
        });
    }
}
