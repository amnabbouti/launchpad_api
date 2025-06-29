<?php

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Models\CheckInOut;
use App\Models\ItemLocation;
use App\Services\AuthorizationEngine;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class CheckInOutService extends BaseService
{
    public function __construct(CheckInOut $checkInOut)
    {
        parent::__construct($checkInOut);
    }

    /**
     * Create checkout record with business rules.
     */
    public function createCheckout(array $data): CheckInOut
    {
        $data = $this->applyCheckoutBusinessRules($data);
        $this->validateCheckoutBusinessRules($data);

        return $this->create($data);
    }

    /**
     * Create checkin record with business rules.
     */
    public function createCheckin(array $data): CheckInOut
    {
        $data = $this->applyCheckinBusinessRules($data);
        $this->validateCheckinBusinessRules($data);

        return $this->create($data);
    }

    /**
     * Get filtered check-in/out records
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        // Apply filters
        $query
            ->when($filters['user_id'] ?? null, fn($q, $value) => $q->where('user_id', $value))
            ->when($filters['trackable_id'] ?? null, fn($q, $value) => $q->where('trackable_id', $value))
            ->when($filters['trackable_type'] ?? null, fn($q, $value) => $q->where('trackable_type', $value))
            ->when($filters['checkout_location_id'] ?? null, fn($q, $value) => $q->where('checkout_location_id', $value))
            ->when($filters['checkin_location_id'] ?? null, fn($q, $value) => $q->where('checkin_location_id', $value))
            ->when($filters['status_out_id'] ?? null, fn($q, $value) => $q->where('status_out_id', $value))
            ->when($filters['status_in_id'] ?? null, fn($q, $value) => $q->where('status_in_id', $value))
            ->when($filters['is_active'] ?? null, fn($q, $value) => $q->where('is_active', $value))
            ->when($filters['is_checked_out'] ?? null, fn($q) => $q->whereNull('checkin_date'))
            ->when($filters['is_checked_in'] ?? null, fn($q) => $q->whereNotNull('checkin_date'))
            ->when($filters['is_overdue'] ?? null, fn($q) => $q->where('expected_return_date', '<', now())->whereNull('checkin_date'))
            ->when($filters['checkout_date_from'] ?? null, fn($q, $value) => $q->where('checkout_date', '>=', $value))
            ->when($filters['checkout_date_to'] ?? null, fn($q, $value) => $q->where('checkout_date', '<=', $value))
            ->when($filters['expected_return_date_from'] ?? null, fn($q, $value) => $q->where('expected_return_date', '>=', $value))
            ->when($filters['expected_return_date_to'] ?? null, fn($q, $value) => $q->where('expected_return_date', '<=', $value))
            ->when($filters['with'] ?? null, fn($q, $relations) => $q->with($relations));

        return $query->get();
    }

    /**
     * Get active checkouts (not checked in).
     */
    public function getActiveCheckouts(): Collection
    {
        return $this->getQuery()->whereNull('checkin_date')->get();
    }

    /**
     * Get overdue checkouts.
     */
    public function getOverdueCheckouts(): Collection
    {
        return $this->getQuery()
            ->where('expected_return_date', '<', now())
            ->whereNull('checkin_date')
            ->get();
    }

    /**
     * Get availability data for item location.
     */
    public function getAvailabilityData($itemLocationId): array
    {
        $itemLocation = ItemLocation::findOrFail($itemLocationId);
        $activeCheckouts = $this->getQuery()
            ->where('trackable_id', $itemLocation->id)
            ->where('trackable_type', ItemLocation::class)
            ->whereNull('checkin_date')
            ->sum('quantity');

        $availableQuantity = $itemLocation->quantity - $activeCheckouts;

        return [
            'total_quantity' => $itemLocation->quantity,
            'checked_out_quantity' => $activeCheckouts,
            'available_quantity' => $availableQuantity,
            'availability_status' => $this->calculateAvailabilityStatus($itemLocation->quantity, $activeCheckouts)
        ];
    }

    /**
     * Calculate availability status.
     */
    private function calculateAvailabilityStatus($total, $checkedOut): string
    {
        $available = $total - $checkedOut;
        if ($available <= 0) return 'unavailable';
        if ($available < $total) return 'partially_available';
        return 'available';
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id',
            'user_id',
            'trackable_id',
            'trackable_type',
            'checkout_location_id',
            'checkin_location_id',
            'status_out_id',
            'status_in_id',
            'is_active',
            'is_checked_out',
            'is_checked_in',
            'is_overdue',
            'checkout_date_from',
            'checkout_date_to',
            'expected_return_date_from',
            'expected_return_date_to',
        ]);
    }

    /**
     * Process request parameters with validation and type conversion.
     */
    public function processRequestParams(array $params): array
    {
        // Validate parameters against whitelist
        $this->validateParams($params);

        return [
            'org_id' => $this->toInt($params['org_id'] ?? null),
            'user_id' => $this->toInt($params['user_id'] ?? null),
            'trackable_id' => $this->toInt($params['trackable_id'] ?? null),
            'trackable_type' => $this->toString($params['trackable_type'] ?? null),
            'checkout_location_id' => $this->toInt($params['checkout_location_id'] ?? null),
            'checkin_location_id' => $this->toInt($params['checkin_location_id'] ?? null),
            'status_out_id' => $this->toInt($params['status_out_id'] ?? null),
            'status_in_id' => $this->toInt($params['status_in_id'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'is_checked_out' => $this->toBool($params['is_checked_out'] ?? null),
            'is_checked_in' => $this->toBool($params['is_checked_in'] ?? null),
            'is_overdue' => $this->toBool($params['is_overdue'] ?? null),
            'checkout_date_from' => $this->toString($params['checkout_date_from'] ?? null),
            'checkout_date_to' => $this->toString($params['checkout_date_to'] ?? null),
            'expected_return_date_from' => $this->toString($params['expected_return_date_from'] ?? null),
            'expected_return_date_to' => $this->toString($params['expected_return_date_to'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Find check-in/out by ID with all relationships.
     */
    public function findByIdWithRelations($id): CheckInOut
    {
        return $this->findById($id, ['*'], [
            'user',
            'checkinUser',
            'trackable',
            'checkoutLocation',
            'checkinLocation',
            'statusOut',
            'statusIn',
            'organization'
        ]);
    }

    /**
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array
    {
        return [
            'user',
            'checkinUser',
            'trackable',
            'checkoutLocation',
            'checkinLocation',
            'statusOut',
            'statusIn',
            'organization'
        ];
    }

    /**
     * Determine operation type from route or context.
     */
    public function determineOperationType(string $routeName = null): string
    {
        if ($routeName === 'checks.out') {
            return 'checkout';
        }
        
        if ($routeName === 'checks.in') {
            return 'checkin';
        }

        return 'default';
    }

    /**
     * Apply business rules for checkout operations.
     */
    private function applyCheckoutBusinessRules(array $data): array
    {
        // Set checkout date if not provided
        if (!isset($data['checkout_date'])) {
            $data['checkout_date'] = now();
        }

        // Set current user if not provided
        if (!isset($data['user_id'])) {
            $data['user_id'] = AuthorizationEngine::getCurrentUser()?->id;
        }

        return $data;
    }

    /**
     * Apply business rules for checkin operations.
     */
    private function applyCheckinBusinessRules(array $data): array
    {
        // Set checkin date if not provided
        if (!isset($data['checkin_date'])) {
            $data['checkin_date'] = now();
        }

        // Set checkin user if not provided
        if (!isset($data['checkin_user_id'])) {
            $data['checkin_user_id'] = AuthorizationEngine::getCurrentUser()?->id;
        }

        return $data;
    }

    /**
     * Validate business rules for checkout operations.
     */
    private function validateCheckoutBusinessRules(array $data): void
    {
        // Required fields for checkout
        if (empty($data['quantity'])) {
            throw new InvalidArgumentException('The quantity field is required for checkout');
        }

        if ($data['quantity'] <= 0) {
            throw new InvalidArgumentException('The quantity must be greater than 0');
        }

        if (empty($data['org_id'])) {
            throw new InvalidArgumentException('The organization ID is required');
        }

        // Validate expected return date is in the future
        if (isset($data['expected_return_date'])) {
            $expectedDate = \Carbon\Carbon::parse($data['expected_return_date']);
            if ($expectedDate->isPast()) {
                throw new InvalidArgumentException('Expected return date must be in the future');
            }
        }
    }

    /**
     * Validate business rules for checkin operations.
     */
    private function validateCheckinBusinessRules(array $data): void
    {
        // Required fields for checkin
        if (empty($data['checkin_quantity'])) {
            throw new InvalidArgumentException('The checkin quantity field is required');
        }

        if ($data['checkin_quantity'] <= 0) {
            throw new InvalidArgumentException('The checkin quantity must be greater than 0');
        }

        if (empty($data['org_id'])) {
            throw new InvalidArgumentException('The organization ID is required');
        }
    }
}
