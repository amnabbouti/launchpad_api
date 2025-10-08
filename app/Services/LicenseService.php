<?php

declare(strict_types = 1);

namespace App\Services;

use App\Models\License;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

use function in_array;

class LicenseService extends BaseService {
    public function __construct(License $license) {
        parent::__construct($license);
    }

    public function activateLicense($licenseIdentifier): License {
        $license = $this->resolveLicense($licenseIdentifier);

        $updateData = [
            'status' => 'active',
        ];

        if (! $license->starts_at || $license->starts_at->isFuture()) {
            $updateData['starts_at'] = now();
        }

        $updated = $this->updateLicense($license->id, $updateData);

        $organization = Organization::find($updated->org_id);
        if ($organization && $organization->license_id !== $updated->id) {
            $organization->license_id = $updated->id;
            $organization->save();
        }

        return $updated;
    }

    public function assertCanAddUser(Organization $organization): void {
        if (! $organization->hasAvailableSeats()) {
            throw new InvalidArgumentException('Organization has reached its license seat limit. Cannot add more users.');
        }
    }

    public function createLicense(array $data): License {
        $data = $this->applyLicenseBusinessRules($data);
        $this->validateLicenseBusinessRules($data);

        return DB::transaction(fn () => $this->create($data));
    }

    public function deleteLicense(string $licenseId): bool {
        return DB::transaction(fn () => $this->delete($licenseId));
    }

    public function expireLicense($licenseIdentifier): License {
        $license = $this->resolveLicense($licenseIdentifier);
        $updated = $this->updateLicense($license->id, [
            'status'  => 'expired',
            'ends_at' => now(),
        ]);

        $organization = Organization::find($updated->org_id);
        if ($organization && $organization->license_id === $updated->id) {
            $organization->license_id = null;
            $organization->save();
        }

        return $updated;
    }

    public function getFiltered(array $filters = []): Builder {
        $query = $this->getQuery();

        $query->when($filters['status'] ?? null, static fn ($q, $value) => $q->where('status', $value))
            ->when($filters['active_only'] ?? null, static function ($q, $value) {
                if ($value) {
                    $now = now();

                    return $q->where('status', 'active')
                        ->where('starts_at', '<=', $now)
                        ->where(static function ($subQ) use ($now): void {
                            $subQ->whereNull('ends_at')->orWhere('ends_at', '>', $now);
                        });
                }

                return $q;
            })
            ->when($filters['expired_only'] ?? null, static function ($q, $value) {
                if ($value) {
                    $now = now();

                    return $q->where(static function ($subQ) use ($now): void {
                        $subQ->where('status', 'expired')
                            ->orWhere('ends_at', '<=', $now);
                    });
                }

                return $q;
            })
            ->when($filters['with'] ?? null, static fn ($q, $relations) => $q->with($relations));

        return $query;
    }

    public function processRequestParams(array $params): array {
        $this->validateParams($params);

        return [
            'status'       => $this->toString($params['status'] ?? null),
            'active_only'  => $this->toBool($params['active_only'] ?? null),
            'expired_only' => $this->toBool($params['expired_only'] ?? null),
            'with'         => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    public function suspendLicense($licenseIdentifier): License {
        $license = $this->resolveLicense($licenseIdentifier);
        $updated = $this->updateLicense($license->id, ['status' => 'suspended']);

        $organization = Organization::find($updated->org_id);
        if ($organization && $organization->license_id === $updated->id) {
            $organization->license_id = null;
            $organization->save();
        }

        return $updated;
    }

    public function updateLicense(string $licenseId, array $data): License {
        $data = $this->applyLicenseBusinessRules($data, $licenseId);
        $this->validateLicenseBusinessRules($data, $licenseId);

        return DB::transaction(fn () => $this->update($licenseId, $data));
    }

    protected function getAllowedParams(): array {
        return array_merge(parent::getAllowedParams(), [
            'status',
            'active_only',
            'expired_only',
        ]);
    }

    protected function getValidRelations(): array {
        return [
            'organization',
        ];
    }

    private function applyLicenseBusinessRules(array $data, $licenseId = null): array {
        if ($licenseId === null && ! isset($data['seats'])) {
            $data['seats'] = 1;
        }

        if (empty($data['starts_at'])) {
            $data['starts_at'] = now();
        }

        return $data;
    }

    private function resolveLicense($identifier): License {
        try {
            return $this->findById($identifier);
        } catch (Throwable) {
            // Fallback to license_key lookup
        }

        $license = License::where('license_key', (string) $identifier)->first();
        if (! $license) {
            throw new InvalidArgumentException('License not found');
        }

        return $license;
    }

    private function validateLicenseBusinessRules(array $data, $licenseId = null): void {
        if (isset($data['seats']) && ($data['seats'] < 1)) {
            throw new InvalidArgumentException('The seats field must be at least 1');
        }

        if (isset($data['license_key'])) {
            $query = License::where('license_key', $data['license_key']);
            if ($licenseId) {
                $query->where('id', '!=', $licenseId);
            }
            if ($query->exists()) {
                throw new InvalidArgumentException('The license key has already been taken');
            }
        }

        if (isset($data['ends_at'], $data['starts_at'])) {
            if (strtotime($data['ends_at']) <= strtotime($data['starts_at'])) {
                throw new InvalidArgumentException('The end date must be after the start date');
            }
        }

        if (isset($data['status']) && ! in_array($data['status'], ['active', 'inactive', 'expired', 'suspended'], true)) {
            throw new InvalidArgumentException('Invalid status. Must be active, inactive, expired, or suspended');
        }
    }
}
