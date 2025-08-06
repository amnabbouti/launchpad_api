<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AppConstants;
use App\Constants\ErrorMessages;
use App\Models\Batch;
use App\Models\Item;
use App\Models\ItemLocation;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ItemLocationService extends BaseService
{
    public function __construct(ItemLocation $itemLocation)
    {
        parent::__construct($itemLocation);
    }

    /**
     * Process request parameters.
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
    public function getFiltered(array $filters = []): Builder
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
                } catch (Exception) {
                    throw new InvalidArgumentException(__(ErrorMessages::INVALID_DATE_FORMAT));
                }
            })
            ->when($filters['positive_quantity'] ?? null, fn($q) => $q->where('quantity', '>', 0))
            ->orderBy('created_at', 'desc');
    }

    /**
     * Create a new item location.
     */
    public function createItemLocation(array $data): Model
    {
        $data = $this->applyCommonBusinessRules($data);
        $this->validateBusinessRules($data, 'item_location');

        return $this->create($data);
    }

    /**
     * Delete an item location.
     */
    public function deleteItemLocation(string $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id',
            'location_id',
            'item_id',
            'moved_date',
            'positive_quantity',
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
            'organization',
        ];
    }

    /**
     * Find an item location by ID with proper relationships.
     */
    public function findById($id, array $columns = ['*'], array $relations = [], array $appends = []): Model
    {
        $defaultRelationships = [
            'organization',
            'item' => function ($query) {
                $query->with(['category', 'status']);
            },
            'location',
        ];

        $relationships = array_merge($defaultRelationships, $relations);

        return parent::findById($id, $columns, $relationships, $appends);
    }

    /**
     * Apply common business rules for all operations.
     */
    private function applyCommonBusinessRules(array $data): array
    {
        if (! isset($data['moved_date'])) {
            $data['moved_date'] = now();
        }

        if (! empty($data['batch_id']) && ! empty($data['item_id'])) {
            $item = Item::find($data['item_id']);
            if ($item && ! $item->batch_id) {
                $item->update(['batch_id' => $data['batch_id']]);
            }
        }

        return $data;
    }

    /**
     * Validate business rules for operations.
     */
    private function validateBusinessRules(array $data, string $operation = 'general'): void
    {
        $requiredFieldsMap = [
            'item_location' => ['org_id', 'item_id', 'location_id', 'quantity'],
            'move' => ['org_id', 'item_id', 'from_location_id', 'to_location_id', 'quantity'],
            'update_quantity' => ['org_id', 'item_id', 'location_id', 'quantity'],
            'general' => ['org_id', 'item_id', 'location_id', 'quantity'],
        ];

        $requiredFields = $requiredFieldsMap[$operation] ?? $requiredFieldsMap['general'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $fieldName = str_replace('_', ' ', $field);
                throw new InvalidArgumentException(
                    __(ErrorMessages::FIELD_REQUIRED, [
                        'field' => ucfirst($fieldName),
                    ])
                );
            }
        }

        if (isset($data['quantity']) && $data['quantity'] < 0) {
            throw new InvalidArgumentException(__(ErrorMessages::QUANTITY_MIN));
        }

        if (isset($data['quantity']) && $data['quantity'] > AppConstants::ITEM_MAX_QUANTITY) {
            throw new InvalidArgumentException(
                __(ErrorMessages::QUANTITY_MAX_EXCEEDED, [
                    'max_quantity' => number_format(AppConstants::ITEM_MAX_QUANTITY),
                ])
            );
        }

        if ($operation === 'move') {
            if ($data['from_location_id'] == $data['to_location_id']) {
                throw new InvalidArgumentException(__(ErrorMessages::ITEM_LOCATIONS_MUST_DIFFER));
            }
        }

        if (! empty($data['batch_id'])) {
            $this->validateBatchData($data);
        }
    }

    /**
     * Validate batch-specific data.
     */
    private function validateBatchData(array $data): void
    {
        if (! is_numeric($data['batch_id']) || $data['batch_id'] <= 0) {
            throw new InvalidArgumentException(__(ErrorMessages::BATCH_ID_INVALID));
        }

        $batch = Batch::find($data['batch_id']);
        if (! $batch) {
            throw new InvalidArgumentException(__(ErrorMessages::BATCH_NOT_EXISTS));
        }

        if (! $batch->is_active) {
            throw new InvalidArgumentException(__(ErrorMessages::BATCH_INACTIVE));
        }

        if ($batch->is_expired) {
            throw new InvalidArgumentException(__(ErrorMessages::BATCH_EXPIRED));
        }
    }
}
