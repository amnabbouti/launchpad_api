<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Models\Item;
use App\Models\Maintenance;
use App\Models\MaintenanceCondition;
use App\Models\MaintenanceDetail;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class MaintenanceService extends BaseService
{
    protected EventService $eventService;

    public function __construct(Maintenance $maintenance, EventService $eventService)
    {
        parent::__construct($maintenance);
        $this->eventService = $eventService;
    }

    public function getFiltered(array $filters = []): Builder
    {
        $query = $this->getQuery();

        $query->when($filters['maintainable_id'] ?? null, fn($q, $value) => $q->where('maintainable_id', $value))
            ->when($filters['maintainable_type'] ?? null, fn($q, $value) => $q->where('maintainable_type', $value))
            ->when($filters['user_id'] ?? null, fn($q, $value) => $q->where('user_id', $value))
            ->when($filters['supplier_id'] ?? null, fn($q, $value) => $q->where('supplier_id', $value))
            ->when($filters['active_only'] ?? null, fn($q) => $q->whereNull('date_back_from_maintenance'))
            ->when($filters['completed_only'] ?? null, fn($q) => $q->whereNotNull('date_back_from_maintenance'))
            ->when($filters['date_from'] ?? null, fn($q, $value) => $q->where('date_in_maintenance', '>=', $value))
            ->when($filters['date_to'] ?? null, fn($q, $value) => $q->where('date_in_maintenance', '<=', $value))
            ->when($filters['with'] ?? null, fn($q, $relations) => $q->with($relations));

        return $query;
    }

    public function findByIdWithRelations($id): Maintenance
    {
        /** @var Maintenance $maintenance */
        $maintenance = $this->findById($id, ['*'], [
            'maintainable',
            'user',
            'supplier',
            'statusOut',
            'statusIn',
            'maintenanceDetails',
            'organization',
        ]);
        return $maintenance;
    }

    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id',
            'maintainable_id',
            'maintainable_type',
            'user_id',
            'supplier_id',
            'active_only',
            'completed_only',
            'date_from',
            'date_to',
        ]);
    }

    public function processRequestParams(array $params): array
    {
        $this->validateParams($params);

        return [
            'org_id' => $this->toInt($params['org_id'] ?? null),
            'maintainable_id' => $this->toInt($params['maintainable_id'] ?? null),
            'maintainable_type' => $this->toString($params['maintainable_type'] ?? null),
            'user_id' => $this->toInt($params['user_id'] ?? null),
            'supplier_id' => $this->toInt($params['supplier_id'] ?? null),
            'active_only' => $this->toBool($params['active_only'] ?? null),
            'completed_only' => $this->toBool($params['completed_only'] ?? null),
            'date_from' => $this->toString($params['date_from'] ?? null),
            'date_to' => $this->toString($params['date_to'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    protected function getValidRelations(): array
    {
        return [
            'maintainable',
            'user',
            'supplier',
            'statusOut',
            'statusIn',
            'maintenanceDetails',
            'organization',
        ];
    }

    public function createItemMaintenance(array $data): Maintenance
    {
        if (!isset($data['item_id'])) {
            throw new InvalidArgumentException(ErrorMessages::MAINTENANCE_ITEM_REQUIRED);
        }

        $item = Item::findOrFail($data['item_id']);

        $maintenanceData = array_merge($data, [
            'maintainable_id' => $item->id,
            'maintainable_type' => Item::class,
            'date_in_maintenance' => $data['date_in_maintenance'] ?? now(),
        ]);

        unset($maintenanceData['item_id']);

        if (!isset($maintenanceData['user_id'])) {
            $user = AuthorizationEngine::getCurrentUser();
            $maintenanceData['user_id'] = $user?->id;
        }

        $maintenance = $this->create($maintenanceData);

        // Create maintenance start event
        $description = $data['remarks'] ?? "Item sent to maintenance";
        $this->eventService->createMaintenanceEvent(
            $item->public_id,
            'start',
            $description,
            $maintenance->public_id,
            $maintenance->date_expected_back_from_maintenance,
            $maintenance->date_in_maintenance
        );

        return $maintenance;
    }

    public function completeMaintenance(string $maintenanceId, array $data): Maintenance
    {
        $maintenance = $this->findById($maintenanceId);

        if ($maintenance->date_back_from_maintenance) {
            throw new InvalidArgumentException(ErrorMessages::MAINTENANCE_ALREADY_COMPLETED);
        }

        $completionData = array_merge($data, [
            'date_back_from_maintenance' => $data['date_back_from_maintenance'] ?? now(),
        ]);

        $updatedMaintenance = $this->update($maintenanceId, $completionData);

        // Get the maintainable item
        if ($updatedMaintenance->maintainable_type === Item::class) {
            $item = Item::findOrFail($updatedMaintenance->maintainable_id);

            // Create maintenance end event
            $description = $data['remarks'] ?? "Item returned from maintenance";
            $this->eventService->createMaintenanceEvent(
                $item->public_id,
                'end',
                $description,
                $updatedMaintenance->public_id,
                null,
                $updatedMaintenance->date_in_maintenance,
                $updatedMaintenance->date_back_from_maintenance
            );
        }

        return $updatedMaintenance;
    }

    public function createFromCondition(array $data): Maintenance
    {
        if (!isset($data['condition_id'])) {
            throw new InvalidArgumentException(ErrorMessages::MAINTENANCE_CONDITION_REQUIRED);
        }

        $condition = MaintenanceCondition::findOrFail($data['condition_id']);

        if (!$condition->item) {
            throw new InvalidArgumentException(ErrorMessages::MAINTENANCE_CONDITION_ITEM_REQUIRED);
        }

        $maintenanceData = array_merge($data, [
            'maintainable_id' => $condition->item->id,
            'maintainable_type' => Item::class,
            'date_in_maintenance' => $data['date_in_maintenance'] ?? now(),
            'remarks' => $data['remarks'] ?? "Maintenance triggered by condition: {$condition->maintenanceCategory->name}",
        ]);

        unset($maintenanceData['condition_id']);

        if (!isset($maintenanceData['user_id'])) {
            $user = AuthorizationEngine::getCurrentUser();
            $maintenanceData['user_id'] = $user?->id;
        }

        $maintenance = $this->create($maintenanceData);

        $this->createMaintenanceDetail($maintenance, $condition, $data);

        // Create maintenance start event
        $description = $maintenanceData['remarks'];
        $this->eventService->createMaintenanceEvent(
            $condition->item->public_id,
            'start',
            $description,
            $maintenance->public_id,
            $maintenance->date_expected_back_from_maintenance,
            $maintenance->date_in_maintenance,
            null,
            $condition->public_id,
            $condition->maintenanceCategory->name,
            $data['trigger_value'] ?? null
        );

        return $maintenance;
    }

    private function createMaintenanceDetail(Maintenance $maintenance, MaintenanceCondition $condition, array $data): void
    {
        $detailData = [
            'maintenance_id' => $maintenance->id,
            'maintenance_condition_id' => $condition->id,
            'value' => $data['trigger_value'] ?? 0,
            'org_id' => $maintenance->org_id,
        ];

        MaintenanceDetail::create($detailData);
    }
}
