<?php

declare(strict_types = 1);

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Models\Status;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class StatusService extends BaseService {
    /**
     * Create a new service instance.
     */
    public function __construct(Status $status) {
        parent::__construct($status);
    }

    /**
     * Create status with business rules.
     */
    public function createStatus(array $data): Status {
        $data = $this->applyStatusBusinessRules($data);
        $this->validateStatusBusinessRules($data);

        return $this->create($data);
    }

    /**
     * Get filtered statuses with optional relationships.
     */
    public function getFiltered(array $filters = []): Builder {
        $query = $this->getQuery();

        $query->when($filters['name'] ?? null, static fn ($q, $name) => $q->where('name', 'like', "%{$name}%"))
            ->when($filters['code'] ?? null, static fn ($q, $code) => $q->where('code', 'like', "%{$code}%"))
            ->when($filters['description'] ?? null, static fn ($q, $desc) => $q->where('description', 'like', "%{$desc}%"))
            ->when(isset($filters['is_active']), static fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['with'] ?? null, static fn ($q, $relations) => $q->with($relations));

        return $query;
    }

    /**
     * Process request parameters with validation and type conversion.
     */
    public function processRequestParams(array $params): array {
        $this->validateParams($params);

        return [
            'name'        => $this->toString($params['name'] ?? null),
            'code'        => $this->toString($params['code'] ?? null),
            'is_active'   => $this->toBool($params['is_active'] ?? null),
            'description' => $this->toString($params['description'] ?? null),
            'with'        => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Update the status with business rules.
     */
    public function updateStatus(int $statusId, array $data): Status {
        $data = $this->applyStatusBusinessRules($data);
        $this->validateStatusBusinessRules($data, $statusId);

        return $this->update($statusId, $data);
    }

    /**
     * Get allowed query parameters for status service.
     */
    protected function getAllowedParams(): array {
        return array_merge(parent::getAllowedParams(), [
            'name',
            'code',
            'is_active',
            'description',
        ]);
    }

    /**
     * Apply business rules for status operations.
     */
    private function applyStatusBusinessRules(array $data): array {
        if (! isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return $data;
    }

    /**
     * Validate business rules for status operations.
     */
    private function validateStatusBusinessRules(array $data, $statusId = null): void {
        if (empty($data['name'])) {
            throw new InvalidArgumentException(__(ErrorMessages::STATUS_NAME_REQUIRED));
        }

        if (empty($data['code'])) {
            throw new InvalidArgumentException(__(ErrorMessages::STATUS_CODE_REQUIRED));
        }

        $query = Status::where('code', $data['code']);

        if ($statusId) {
            $query->where('id', '!=', $statusId);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException(__(ErrorMessages::STATUS_CODE_EXISTS));
        }
    }
}
