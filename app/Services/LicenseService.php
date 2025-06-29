<?php

namespace App\Services;

use App\Models\License;
use App\Models\Organization;
use App\Models\Plan;
use App\Exceptions\LicenseLimitExceededException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LicenseService extends BaseService
{
    public function __construct(License $license)
    {
        parent::__construct($license);
    }

    /**
     * Create new license with business rules.
     * Licenses are organization-scoped resources (organizations purchase/activate plans).
     */
    public function createLicense(array $data): License
    {
        // Apply business rules and validation
        $data = $this->applyLicenseBusinessRules($data);
        $this->validateLicenseBusinessRules($data);

        return DB::transaction(fn() => $this->create($data));
    }

    /**
     * Update license with business rules.
     */
    public function updateLicense(int $licenseId, array $data): License
    {
        // Apply business rules and validation
        $data = $this->applyLicenseBusinessRules($data, $licenseId);
        $this->validateLicenseBusinessRules($data, $licenseId);

        return DB::transaction(fn() => $this->update($licenseId, $data));
    }

    /**
     * Delete license with business rules.
     */
    public function deleteLicense(int $licenseId): bool
    {
        return DB::transaction(fn() => $this->delete($licenseId));
    }

    /**
     * Get filtered licenses.
     */
    public function getFiltered(array $filters = []): Collection
    {
        $query = $this->getQuery();

        $query->when($filters['organization_id'] ?? null, fn ($q, $value) => $q->where('organization_id', $value))
            ->when($filters['plan_id'] ?? null, fn ($q, $value) => $q->where('plan_id', $value))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['active_only'] ?? null, function ($q, $value) {
                if ($value) {
                    $now = now();
                    return $q->where('status', 'active')
                        ->where('starts_at', '<=', $now)
                        ->where(function ($subQ) use ($now) {
                            $subQ->whereNull('ends_at')->orWhere('ends_at', '>', $now);
                        });
                }
                return $q;
            })
            ->when($filters['expired_only'] ?? null, function ($q, $value) {
                if ($value) {
                    $now = now();
                    return $q->where(function ($subQ) use ($now) {
                        $subQ->where('status', 'expired')
                            ->orWhere('ends_at', '<=', $now);
                    });
                }
                return $q;
            })
            ->when($filters['with'] ?? null, fn ($q, $relations) => $q->with($relations));

        return $query->get();
    }

    /**
     * Throws exception if the organization cannot add another user (seat enforcement).
     */
    public function assertCanAddUser(Organization $organization): void
    {
        if (!$organization->hasAvailableSeats()) {
            $this->throwLicenseLimitExceeded();
        }
    }

    /**
     * Returns true if the organization has at least one active license.
     */
    public function hasActiveLicense(Organization $organization): bool
    {
        $now = now();
        return $organization->licenses()
            ->where('status', 'active')
            ->where('starts_at', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', $now);
            })
            ->exists();
    }

    /**
     * Get all active licenses for the organization.
     */
    public function getActiveLicenses(Organization $organization)
    {
        $now = now();
        return $organization->licenses()
            ->where('status', 'active')
            ->where('starts_at', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', $now);
            })
            ->get();
    }

    /**
     * Activate a license (set status to active and starts_at to now if not set).
     */
    public function activateLicense(int $licenseId): License
    {
        $license = $this->findById($licenseId);
        
        $updateData = [
            'status' => 'active',
        ];

        // Set starts_at to now if not already set
        if (!$license->starts_at || $license->starts_at->isFuture()) {
            $updateData['starts_at'] = now();
        }

        return $this->updateLicense($licenseId, $updateData);
    }

    /**
     * Suspend a license (set status to suspended).
     */
    public function suspendLicense(int $licenseId): License
    {
        return $this->updateLicense($licenseId, ['status' => 'suspended']);
    }

    /**
     * Expire a license (set status to expired and ends_at to now).
     */
    public function expireLicense(int $licenseId): License
    {
        return $this->updateLicense($licenseId, [
            'status' => 'expired',
            'ends_at' => now(),
        ]);
    }

    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'organization_id',
            'plan_id',
            'status',
            'active_only',
            'expired_only',
        ]);
    }

    protected function getValidRelations(): array
    {
        return [
            'organization',
            'plan',
        ];
    }

    public function processRequestParams(array $params): array
    {
        $this->validateParams($params);
        return [
            'organization_id' => $this->toInt($params['organization_id'] ?? null),
            'plan_id' => $this->toInt($params['plan_id'] ?? null),
            'status' => $this->toString($params['status'] ?? null),
            'active_only' => $this->toBool($params['active_only'] ?? null),
            'expired_only' => $this->toBool($params['expired_only'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Apply business rules for license operations.
     */
    private function applyLicenseBusinessRules(array $data, $licenseId = null): array
    {
        // Generate license key if not provided
        if (!isset($data['license_key']) || empty($data['license_key'])) {
            $data['license_key'] = $this->generateLicenseKey();
        }

        // Set default status
        if (!isset($data['status'])) {
            $data['status'] = 'active';
        }

        // Set default seats
        if (!isset($data['seats'])) {
            $data['seats'] = 1;
        }

        return $data;
    }

    /**
     * Validate business rules for license operations.
     */
    private function validateLicenseBusinessRules(array $data, $licenseId = null): void
    {
        // Validate required fields
        if (empty($data['organization_id'])) {
            throw new \InvalidArgumentException('The organization_id field is required');
        }

        if (empty($data['plan_id'])) {
            throw new \InvalidArgumentException('The plan_id field is required');
        }

        if (empty($data['seats']) || $data['seats'] < 1) {
            throw new \InvalidArgumentException('The seats field is required and must be at least 1');
        }

        if (empty($data['starts_at'])) {
            throw new \InvalidArgumentException('The starts_at field is required');
        }

        // Validate organization exists
        if (!Organization::find($data['organization_id'])) {
            throw new \InvalidArgumentException('The selected organization does not exist');
        }

        // Validate plan exists and is active
        $plan = Plan::find($data['plan_id']);
        if (!$plan) {
            throw new \InvalidArgumentException('The selected plan does not exist');
        }

        if (!$plan->is_active) {
            throw new \InvalidArgumentException('Cannot create license for inactive plan');
        }

        // Validate license key uniqueness
        if (isset($data['license_key'])) {
            $query = License::where('license_key', $data['license_key']);
            if ($licenseId) {
                $query->where('id', '!=', $licenseId);
            }
            if ($query->exists()) {
                throw new \InvalidArgumentException('The license key has already been taken');
            }
        }

        // Validate dates
        if (isset($data['ends_at']) && isset($data['starts_at'])) {
            if (strtotime($data['ends_at']) <= strtotime($data['starts_at'])) {
                throw new \InvalidArgumentException('The end date must be after the start date');
            }
        }

        // Validate status
        if (isset($data['status']) && !in_array($data['status'], ['active', 'inactive', 'expired', 'suspended'])) {
            throw new \InvalidArgumentException('Invalid status. Must be active, inactive, expired, or suspended');
        }

        // Validate seats against plan user_limit if set
        if (isset($data['seats']) && $plan->user_limit && $data['seats'] > $plan->user_limit) {
            throw new \InvalidArgumentException("Seats cannot exceed plan's user limit of {$plan->user_limit}");
        }
    }

    /**
     * Generate a unique license key.
     */
    private function generateLicenseKey(): string
    {
        do {
            $key = 'LIC-' . strtoupper(Str::random(16));
        } while (License::where('license_key', $key)->exists());

        return $key;
    }

    /**
     * Throw license limit exceeded exception.
     */
    private function throwLicenseLimitExceeded(): void
    {
        throw new LicenseLimitExceededException('Organization has reached its license seat limit');
    }
}
