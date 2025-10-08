<?php

declare(strict_types = 1);

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Models\CheckInOut;
use App\Models\ItemLocation;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class CheckInOutService extends BaseService {
    protected EventService $eventService;

    public function __construct(CheckInOut $checkInOut, EventService $eventService) {
        parent::__construct($checkInOut);
        $this->eventService = $eventService;
    }

    /**
     * Create checkin record with business rules.
     */
    public function createCheckin(array $data): CheckInOut {
        $data = $this->applyCheckinBusinessRules($data);
        $this->validateCheckinBusinessRules($data);

        $checkInOut = $this->create($data);

        $this->createCheckinEvent($checkInOut);

        return $checkInOut;
    }

    /**
     * Determine an operation type from route or context.
     */
    public function determineOperationType(?string $routeName = null): string {
        if ($routeName === 'checks.out') {
            return 'checkout';
        }

        if ($routeName === 'checks.in') {
            return 'checkin';
        }

        return 'default';
    }

    /**
     * Find check-in/out by ID with all relationships.
     */
    public function findByIdWithRelations($id): CheckInOut {
        /** @var CheckInOut $checkInOut */
        return $this->findById($id, ['*'], [
            'user',
            'checkinUser',
            'trackable',
            'checkoutLocation',
            'checkinLocation',
            'statusOut',
            'statusIn',
            'organization',
        ]);
    }

    /**
     * Get active checkouts (not checked in).
     */
    public function getActiveCheckouts(): Collection {
        return $this->getQuery()->whereNull('checkin_date')->get();
    }

    /**
     * Get availability data for item location.
     */
    public function getAvailabilityData($itemLocationId): array {
        $itemLocation    = ItemLocation::findOrFail($itemLocationId);
        $activeCheckouts = $this->getQuery()
            ->where('trackable_id', $itemLocation->id)
            ->where('trackable_type', ItemLocation::class)
            ->whereNull('checkin_date')
            ->sum('quantity');

        $availableQuantity = $itemLocation->quantity - $activeCheckouts;

        return [
            'total_quantity'       => $itemLocation->quantity,
            'checked_out_quantity' => $activeCheckouts,
            'available_quantity'   => $availableQuantity,
            'availability_status'  => $this->calculateAvailabilityStatus($itemLocation->quantity, $activeCheckouts),
        ];
    }

    /**
     * Get filtered check-in/out records
     */
    public function getFiltered(array $filters = []): Builder {
        $query = $this->getQuery();
        $query
            ->when($filters['user_id'] ?? null, static fn ($q, $value) => $q->where('user_id', $value))
            ->when($filters['trackable_id'] ?? null, static fn ($q, $value) => $q->where('trackable_id', $value))
            ->when($filters['trackable_type'] ?? null, static fn ($q, $value) => $q->where('trackable_type', $value))
            ->when($filters['checkout_location_id'] ?? null, static fn ($q, $value) => $q->where('checkout_location_id', $value))
            ->when($filters['checkin_location_id'] ?? null, static fn ($q, $value) => $q->where('checkin_location_id', $value))
            ->when($filters['status_out_id'] ?? null, static fn ($q, $value) => $q->where('status_out_id', $value))
            ->when($filters['status_in_id'] ?? null, static fn ($q, $value) => $q->where('status_in_id', $value))
            ->when($filters['is_active'] ?? null, static fn ($q, $value) => $q->where('is_active', $value))
            ->when($filters['is_checked_out'] ?? null, static fn ($q) => $q->whereNull('checkin_date'))
            ->when($filters['is_checked_in'] ?? null, static fn ($q) => $q->whereNotNull('checkin_date'))
            ->when($filters['is_overdue'] ?? null, static fn ($q) => $q->where('expected_return_date', '<', now())->whereNull('checkin_date'))
            ->when($filters['checkout_date_from'] ?? null, static fn ($q, $value) => $q->where('checkout_date', '>=', $value))
            ->when($filters['checkout_date_to'] ?? null, static fn ($q, $value) => $q->where('checkout_date', '<=', $value))
            ->when($filters['expected_return_date_from'] ?? null, static fn ($q, $value) => $q->where('expected_return_date', '>=', $value))
            ->when($filters['expected_return_date_to'] ?? null, static fn ($q, $value) => $q->where('expected_return_date', '<=', $value))
            ->when($filters['with'] ?? null, static fn ($q, $relations) => $q->with($relations));

        return $query;
    }

    /**
     * Get availability for an item location.
     */
    public function getItemLocationAvailability($itemLocationId): array {
        $itemLocationService = app(ItemLocationService::class);
        $itemLocation        = $itemLocationService->findById($itemLocationId);

        return $this->getAvailabilityData($itemLocation->id);
    }

    /**
     * Get history for an item location.
     */
    public function getItemLocationHistory($itemLocationId): Collection {
        $itemLocationService = app(ItemLocationService::class);
        $itemLocation        = $itemLocationService->findById($itemLocationId);

        return $this->getFiltered([
            'trackable_id'   => $itemLocation->id,
            'trackable_type' => ItemLocation::class,
            'with'           => ['user', 'checkinUser', 'checkoutLocation', 'checkinLocation', 'statusOut', 'statusIn'],
        ])->get();
    }

    /**
     * Get overdue checkouts.
     */
    public function getOverdueCheckouts(): Collection {
        return $this->getQuery()
            ->where('expected_return_date', '<', now())
            ->whereNull('checkin_date')
            ->get();
    }

    /**
     * Process item checkin and create an audit event.
     */
    public function processItemCheckin(int | string $checkInOutId, array $data): CheckInOut {
        /** @var CheckInOut $existingCheckout */
        $existingCheckout = $this->findById($checkInOutId);

        $data = $this->applyCheckinBusinessRules($data);
        $this->validateCheckinBusinessRules($data);

        /** @var CheckInOut $checkInOut */
        $checkInOut = $this->update($checkInOutId, $data);

        $this->createCheckinEvent($checkInOut);

        return $checkInOut;
    }

    /**
     * Process item checkout and create an audit event.
     */
    public function processItemCheckout(array $data): CheckInOut {
        $data = $this->applyCheckoutBusinessRules($data);
        $this->validateCheckoutBusinessRules($data);

        $checkInOut = $this->create($data);

        $this->createCheckoutEvent($checkInOut);

        return $checkInOut;
    }

    /**
     * Process checkin for an item location.
     */
    public function processItemLocationCheckin($itemLocationId, array $data): CheckInOut {
        $itemLocationService = app(ItemLocationService::class);
        $itemService         = app(ItemService::class);

        $itemLocation = null;

        try {
            $itemLocation = $itemLocationService->findById($itemLocationId);
        } catch (Exception) {
            $item = $itemService->findById($itemLocationId);

            $itemLocations = $item->itemLocations()->get();

            foreach ($itemLocations as $il) {
                $activeCheckouts = $this->getFiltered([
                    'trackable_id'   => $il->id,
                    'trackable_type' => ItemLocation::class,
                    'user_id'        => auth()->id(),
                    'is_checked_out' => true,
                ])->get();

                if ($activeCheckouts->isNotEmpty()) {
                    $itemLocation = $il;

                    break;
                }
            }

            if (! $itemLocation) {
                throw new InvalidArgumentException(__(ErrorMessages::CHECKINOUT_NO_ACTIVE_CHECKOUT));
            }
        }

        $activeCheckout = $this->getFiltered([
            'trackable_id'   => $itemLocation->id,
            'trackable_type' => ItemLocation::class,
            'user_id'        => auth()->id(),
            'is_checked_out' => true,
        ])->first();

        if (! $activeCheckout) {
            throw new InvalidArgumentException(__(ErrorMessages::CHECKINOUT_NO_ACTIVE_CHECKOUT));
        }

        $data = array_merge($data, [
            'checkin_date'    => now(),
            'checkin_user_id' => auth()->id(),
            'is_active'       => false,
        ]);

        return $this->processItemCheckin($activeCheckout->id, $data);
    }

    /**
     * Process checkout for an item location.
     */
    public function processItemLocationCheckout($itemLocationId, array $data): CheckInOut {
        $itemLocationService = app(ItemLocationService::class);
        $itemLocation        = $itemLocationService->findById($itemLocationId);

        $data = array_merge($data, [
            'org_id'               => $itemLocation->org_id,
            'trackable_id'         => $itemLocation->id,
            'trackable_type'       => ItemLocation::class,
            'checkout_location_id' => $itemLocation->location_id,
            'checkout_date'        => now(),
            'user_id'              => auth()->id(),
            'is_active'            => true,
        ]);

        return $this->processItemCheckout($data);
    }

    /**
     * Process request parameters with validation and type conversion.
     */
    public function processRequestParams(array $params): array {
        $this->validateParams($params);

        return [
            'user_id'                   => $this->toInt($params['user_id'] ?? null),
            'trackable_id'              => $this->toInt($params['trackable_id'] ?? null),
            'trackable_type'            => $this->toString($params['trackable_type'] ?? null),
            'checkout_location_id'      => $this->toInt($params['checkout_location_id'] ?? null),
            'checkin_location_id'       => $this->toInt($params['checkin_location_id'] ?? null),
            'status_out_id'             => $this->toInt($params['status_out_id'] ?? null),
            'status_in_id'              => $this->toInt($params['status_in_id'] ?? null),
            'is_active'                 => $this->toBool($params['is_active'] ?? null),
            'is_checked_out'            => $this->toBool($params['is_checked_out'] ?? null),
            'is_checked_in'             => $this->toBool($params['is_checked_in'] ?? null),
            'is_overdue'                => $this->toBool($params['is_overdue'] ?? null),
            'checkout_date_from'        => $this->toString($params['checkout_date_from'] ?? null),
            'checkout_date_to'          => $this->toString($params['checkout_date_to'] ?? null),
            'expected_return_date_from' => $this->toString($params['expected_return_date_from'] ?? null),
            'expected_return_date_to'   => $this->toString($params['expected_return_date_to'] ?? null),
            'with'                      => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Get allowed query parameters.
     */
    protected function getAllowedParams(): array {
        return array_merge(parent::getAllowedParams(), [
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
     * Get valid relations for the model.
     */
    protected function getValidRelations(): array {
        return [
            'user',
            'checkinUser',
            'trackable',
            'checkoutLocation',
            'checkinLocation',
            'statusOut',
            'statusIn',
            'organization',
        ];
    }

    /**
     * Apply business rules for checkin operations.
     */
    private function applyCheckinBusinessRules(array $data): array {
        if (! isset($data['checkin_date'])) {
            $data['checkin_date'] = now();
        }

        if (! isset($data['checkin_user_id'])) {
            $data['checkin_user_id'] = \App\Services\AuthorizationHelper::getCurrentUser()?->id;
        }

        return $data;
    }

    /**
     * Apply business rules for checkout operations.
     */
    private function applyCheckoutBusinessRules(array $data): array {
        if (! isset($data['checkout_date'])) {
            $data['checkout_date'] = now();
        }

        if (! isset($data['user_id'])) {
            $data['user_id'] = \App\Services\AuthorizationHelper::getCurrentUser()?->id;
        }

        return $data;
    }

    /**
     * Calculate availability status.
     */
    private function calculateAvailabilityStatus($total, $checkedOut): string {
        $available = $total - $checkedOut;
        if ($available <= 0) {
            return 'unavailable';
        }
        if ($available < $total) {
            return 'partially_available';
        }

        return 'available';
    }

    /**
     * Create checkin event for audit trail.
     */
    private function createCheckinEvent(CheckInOut $checkInOut): void {
        if ($checkInOut->trackable_type === ItemLocation::class) {
            $itemLocation = ItemLocation::with(['item', 'location'])->find($checkInOut->trackable_id);

            if (! $itemLocation || ! $itemLocation->item) {
                throw new InvalidArgumentException(__(ErrorMessages::CHECKINOUT_ITEM_LOCATION_NOT_FOUND));
            }

            $checkinLocation  = $checkInOut->checkinLocation;
            $checkoutLocation = $checkInOut->checkoutLocation;
            $locationName     = $checkinLocation?->name ?? $checkoutLocation?->name ?? 'Unknown Location';

            $this->eventService->createCheckInEvent(
                $itemLocation->item->id,
                $locationName,
                $checkInOut->checkin_quantity ?? $checkInOut->quantity,
                $checkInOut->notes,
                $checkInOut->checkinUser->id ?? null,
            );
        }
    }

    /**
     * Create checkout event for audit trail.
     */
    private function createCheckoutEvent(CheckInOut $checkInOut): void {
        if ($checkInOut->trackable_type === ItemLocation::class) {
            $itemLocation = ItemLocation::with(['item', 'location'])->find($checkInOut->trackable_id);

            if (! $itemLocation || ! $itemLocation->item) {
                throw new InvalidArgumentException(__(ErrorMessages::CHECKINOUT_ITEM_LOCATION_NOT_FOUND));
            }

            $locationName = $itemLocation->location->name ?? 'Unknown Location';

            $this->eventService->createCheckOutEvent(
                $itemLocation->item->id,
                $locationName,
                $checkInOut->quantity,
                $checkInOut->notes,
                $checkInOut->user->id ?? null,
            );
        }
    }

    /**
     * Validate business rules for checkin operations.
     */
    private function validateCheckinBusinessRules(array $data): void {
        if (empty($data['checkin_quantity'])) {
            throw new InvalidArgumentException(__(ErrorMessages::CHECKINOUT_CHECKIN_QUANTITY_REQUIRED));
        }

        if ($data['checkin_quantity'] <= 0) {
            throw new InvalidArgumentException(__(ErrorMessages::CHECKINOUT_CHECKIN_QUANTITY_POSITIVE));
        }
    }

    /**
     * Validate business rules for checkout operations.
     */
    private function validateCheckoutBusinessRules(array $data): void {
        if (empty($data['quantity'])) {
            throw new InvalidArgumentException(__(ErrorMessages::CHECKINOUT_QUANTITY_REQUIRED));
        }

        if ($data['quantity'] <= 0) {
            throw new InvalidArgumentException(__(ErrorMessages::CHECKINOUT_QUANTITY_POSITIVE));
        }

        if (isset($data['expected_return_date'])) {
            $expectedDate = Carbon::parse($data['expected_return_date']);
            if ($expectedDate->isPast()) {
                throw new InvalidArgumentException(__(ErrorMessages::CHECKINOUT_RETURN_DATE_FUTURE));
            }
        }
    }
}
