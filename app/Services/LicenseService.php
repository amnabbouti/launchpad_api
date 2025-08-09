<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\License;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

class LicenseService extends BaseService
{
    public function __construct(License $license)
    {
        parent::__construct($license);
    }

    /**
     * Create a new license with business rules.
     * Licenses are organization-scoped resources.
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
    public function getFiltered(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->getQuery();

        $query->when($filters['organization_id'] ?? null, fn($q, $value) => $q->where('org_id', $value))
            ->when($filters['org_id'] ?? null, fn($q, $value) => $q->where('org_id', $value))
            ->when($filters['status'] ?? null, fn($q, $value) => $q->where('status', $value))
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
            ->when($filters['with'] ?? null, fn($q, $relations) => $q->with($relations));

        return $query;
    }

    /**
     * Throws exception if the organization cannot add another user (seat enforcement).
     */
    public function assertCanAddUser(Organization $organization): void
    {
        if (! $organization->hasAvailableSeats()) {
            throw new \InvalidArgumentException('Organization has reached its license seat limit. Cannot add more users.');
        }
    }

    

    

    /**
     * Activate a license (set status to active and starts_at to now if not set).
     */
    public function activateLicense($licenseIdentifier): License
    {
        $license = $this->resolveLicense($licenseIdentifier);

        $updateData = [
            'status' => 'active',
        ];

        // Set starts_at to now if not already set
        if (! $license->starts_at || $license->starts_at->isFuture()) {
            $updateData['starts_at'] = now();
        }

        $updated = $this->updateLicense($license->id, $updateData);

        // Ensure this license is assigned as the organization's current license
        $organization = Organization::find($updated->org_id);
        if ($organization && $organization->license_id !== $updated->id) {
            $organization->license_id = $updated->id;
            $organization->save();
        }

        return $updated;
    }

    /**
     * Suspend a license (set status to suspended).
     */
    public function suspendLicense($licenseIdentifier): License
    {
        $license = $this->resolveLicense($licenseIdentifier);
        $updated = $this->updateLicense($license->id, ['status' => 'suspended']);

        // Optional: clear current license pointer if suspended license is assigned
        $organization = Organization::find($updated->org_id);
        if ($organization && $organization->license_id === $updated->id) {
            $organization->license_id = null;
            $organization->save();
        }

        return $updated;
    }

    /**
     * Expire a license (set status to expired and ends_at to now).
     */
    public function expireLicense($licenseIdentifier): License
    {
        $license = $this->resolveLicense($licenseIdentifier);
        $updated = $this->updateLicense($license->id, [
            'status' => 'expired',
            'ends_at' => now(),
        ]);

        // Clear current license pointer if expired license is assigned
        $organization = Organization::find($updated->org_id);
        if ($organization && $organization->license_id === $updated->id) {
            $organization->license_id = null;
            $organization->save();
        }

        return $updated;
    }

    /**
     * Resolve a license by numeric id, public id, or license_key.
     */
    private function resolveLicense($identifier): License
    {
        // Try numeric id or public id
        try {
            return $this->findById($identifier);
        } catch (\Throwable $e) {
            // Fallback to license_key lookup
        }

        $license = License::where('license_key', (string) $identifier)->first();
        if (! $license) {
            throw new \InvalidArgumentException('License not found');
        }

        return $license;
    }

    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id',
            'status',
            'active_only',
            'expired_only',
        ]);
    }

    protected function getValidRelations(): array
    {
        return [
            'organization',
        ];
    }

    public function processRequestParams(array $params): array
    {
        $this->validateParams($params);

        return [
            'organization_id' => $this->toInt($params['org_id'] ?? $params['organization_id'] ?? null),
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
        // Set default seats only on create
        if ($licenseId === null && ! isset($data['seats'])) {
            $data['seats'] = 1;
        }

        // Default start date to now if not provided
        if (! isset($data['starts_at']) || empty($data['starts_at'])) {
            $data['starts_at'] = now();
        }

        return $data;
    }

    /**
     * Validate business rules for license operations.
     */
    private function validateLicenseBusinessRules(array $data, $licenseId = null): void
    {
        $isUpdate = $licenseId !== null;

        // For CREATE operations, validate required fields
        if (!$isUpdate) {
            if (empty($data['org_id'])) {
                throw new \InvalidArgumentException('The organization_id field is required');
            }
        }

        // For both CREATE and UPDATE, validate seats if provided
        if (isset($data['seats']) && ($data['seats'] < 1)) {
            throw new \InvalidArgumentException('The seats field must be at least 1');
        }

        // For both CREATE and UPDATE, validate organization exists if provided
        if (isset($data['org_id']) && !Organization::find($data['org_id'])) {
            throw new \InvalidArgumentException('The selected organization does not exist');
        }

        // No plan validation in licenses-only model

        // For both CREATE and UPDATE, validate license key uniqueness if provided (model auto-generates when absent)
        if (isset($data['license_key'])) {
            $query = License::where('license_key', $data['license_key']);
            if ($licenseId) {
                $query->where('id', '!=', $licenseId);
            }
            if ($query->exists()) {
                throw new \InvalidArgumentException('The license key has already been taken');
            }
        }

        // For both CREATE and UPDATE, validate dates if both are provided
        if (isset($data['ends_at']) && isset($data['starts_at'])) {
            if (strtotime($data['ends_at']) <= strtotime($data['starts_at'])) {
                throw new \InvalidArgumentException('The end date must be after the start date');
            }
        }

        // For both CREATE and UPDATE, validate status if provided
        if (isset($data['status']) && !in_array($data['status'], ['active', 'inactive', 'expired', 'suspended'])) {
            throw new \InvalidArgumentException('Invalid status. Must be active, inactive, expired, or suspended');
        }
    }
}
