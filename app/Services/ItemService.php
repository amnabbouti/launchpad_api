<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Models\Item;
use App\Models\ItemLocation;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

use function is_array;

class ItemService extends BaseService
{
    public function __construct(Item $item)
    {
        parent::__construct($item);
    }

    public function create(array $data): Item
    {
        $data = $this->applyBusinessRules($data);
        $this->validateBusinessRules($data);

        $locations = $data['locations'] ?? null;
        unset($data['locations']);

        $item = Item::create($data);

        if (is_array($locations)) {
            $this->createItemLocations($item, $locations);
        }

        return $item->load(['locations']);
    }

    public function findById($id, array $columns = ['*'], array $relations = [], array $appends = []): Item
    {
        $relations = array_unique(array_merge($relations, ['maintenances', 'locations', 'category', 'status', 'unitOfMeasure', 'suppliers', 'organization']));

        $item = Item::with($relations)->findOrFail($id, $columns);

        if (! empty($appends)) {
            $item->append($appends);
        }

        return $item;
    }

    public function getFiltered(array $filters = []): Builder
    {
        $query = $this->all(['*'], ['maintenances', 'locations', 'category', 'status', 'unitOfMeasure', 'suppliers', 'organization']);

        $query->when($filters['tracking_mode'] ?? null, static function ($q, $mode) {
            return match ($mode) {
                Item::TRACKING_ABSTRACT   => $q->abstract(),
                Item::TRACKING_STANDARD   => $q->standard(),
                Item::TRACKING_SERIALIZED => $q->serialized(),
                default                   => $q,
            };
        })
            ->when($filters['location_id'] ?? null, static fn($q, $value) => $q->byLocation($value))
            ->when($filters['q'] ?? null, static fn($q, $value) => $q->search($value))
            ->when(isset($filters['is_active']) && $filters['is_active'], static fn($q) => $q->active())
            ->when($filters['category_id'] ?? null, static fn($q, $value) => $q->where('category_id', $value))
            ->when($filters['user_id'] ?? null, static fn($q, $value) => $q->where('user_id', $value))
            ->when($filters['name'] ?? null, static fn($q, $value) => $q->where('name', 'like', "%{$value}%"))
            ->when($filters['code'] ?? null, static fn($q, $value) => $q->where('code', 'like', "%{$value}%"))
            ->when($filters['barcode'] ?? null, static fn($q, $value) => $q->where('barcode', $value))
            ->when($filters['with'] ?? null, static fn($q, $relations) => $q->with($relations));

        return $query;
    }

    public function processRequestParams(array $params): array
    {
        $this->validateParams($params);

        return [
            'tracking_mode' => $this->toString($params['tracking_mode'] ?? null),
            'category_id'   => $this->toInt($params['category_id'] ?? null),
            'user_id'       => $this->toInt($params['user_id'] ?? null),
            'location_id'   => $this->toInt($params['location_id'] ?? null),
            'is_active'     => $this->toBool($params['is_active'] ?? null),
            'name'          => $this->toString($params['name'] ?? null),
            'code'          => $this->toString($params['code'] ?? null),
            'barcode'       => $this->toString($params['barcode'] ?? null),
            'q'             => $this->toString($params['q'] ?? null),
            'with'          => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    public function update($id, array $data): Item
    {
        $data = $this->applyBusinessRules($data);
        $this->validateBusinessRules($data, $id);

        $locations = $data['locations'] ?? null;
        unset($data['locations']);

        $item = Item::findOrFail($id);
        $item->update($data);

        if (is_array($locations)) {
            $this->updateItemLocations($item, $locations);
        }

        return $item->load(['locations']);
    }

    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'category_id',
            'user_id',
            'location_id',
            'tracking_mode',
            'is_active',
            'name',
            'code',
            'barcode',
            'q',
        ]);
    }

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
            'locations',
        ];
    }

    private function applyBusinessRules(array $data): array
    {
        if (isset($data['tracking_mode'])) {
            switch ($data['tracking_mode']) {
                case 'abstract':
                    $data['serial_number'] = null;
                    $data['status_id']     = null;
                    $data['notes']         = null;

                    break;
                case 'standard':
                    $data['serial_number'] = null;

                    break;
                case 'serialized':
                    break;
            }
        }

        return $data;
    }

    private function createItemLocations(Item $item, array $locations): void
    {
        foreach ($locations as $locationData) {
            if (isset($locationData['id'], $locationData['quantity'])) {
                $locationId = (int) $locationData['id'];

                ItemLocation::create([
                    'item_id'     => $item->id,
                    'location_id' => $locationId,
                    'quantity'    => $locationData['quantity'],
                    'moved_date'  => now(),
                ]);
            }
        }
    }

    private function updateItemLocations(Item $item, array $locations): void
    {
        foreach ($locations as $locationData) {
            if (isset($locationData['id'], $locationData['quantity'])) {
                $locationId = (int) $locationData['id'];

                $item->locations()->updateExistingPivot(
                    $locationId,
                    ['quantity' => $locationData['quantity']],
                );
            }
        }
    }

    private function validateBusinessRules(array $data, $itemId = null): void
    {
        if (isset($data['tracking_mode']) && $data['tracking_mode'] === 'serialized') {
            if (empty($data['serial_number'])) {
                throw new InvalidArgumentException(__(ErrorMessages::ITEM_SERIAL_REQUIRED));
            }
        }

        if (isset($data['code'])) {
            $query = Item::where('code', $data['code']);

            if ($itemId) {
                $query->where('id', '!=', $itemId);
            }

            if ($query->exists()) {
                throw new InvalidArgumentException(__(ErrorMessages::ITEM_CODE_EXISTS));
            }
        }

        if (! empty($data['serial_number'])) {
            $query = Item::where('serial_number', $data['serial_number']);

            if ($itemId) {
                $query->where('id', '!=', $itemId);
            }

            if ($query->exists()) {
                throw new InvalidArgumentException(__(ErrorMessages::ITEM_SERIAL_EXISTS));
            }
        }
    }
}
