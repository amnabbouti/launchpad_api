<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthorizationEngine;
use App\Services\LicenseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class UserService extends BaseService
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    /**
     * Base query with correct organization scoping for users.
     */
    protected function getQuery(): Builder
    {
        $query = $this->model->newQuery();
        $currentUser = AuthorizationEngine::getCurrentUser();

        // Super admin can see all users
        if ($currentUser && AuthorizationEngine::isSuperAdmin($currentUser)) {
            return $query;
        }

        // Regular users can only see users in their own organization
        if ($currentUser && $currentUser->org_id) {
            return $query->where('org_id', $currentUser->org_id);
        }

        // No organization context
        return $query->whereRaw('1 = 0');
    }

    // Create a new user with password hashing and role assignment
    public function createUser(array $data): User
    {
        // Validate first, then apply business rules
        $this->validateUserBusinessRules($data);
        $data = $this->applyUserBusinessRules($data);

        // Enforce license seat limits before creating user
        $targetOrgId = $data['org_id'] ?? AuthorizationEngine::getCurrentUser()?->org_id;
        if ($targetOrgId) {
            $organization = Organization::find($targetOrgId);
            if ($organization) {
                $licenseService = app(LicenseService::class);
                $licenseService->assertCanAddUser($organization);
            }
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $this->convertNameToFirstLastName($data);

        return DB::transaction(fn() => $this->create($data));
    }

    // Update the user with password hashing and role assignment
    public function updateUser(int $userId, array $data): User
    {
        // Validate first, then apply business rules
        $this->validateUserBusinessRules($data, $userId);
        $data = $this->applyUserBusinessRules($data);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (isset($data['name'])) {
            $this->convertNameToFirstLastName($data);
        }

        // Disallow changing a user's organization
        if (array_key_exists('org_id', $data)) {
            $existing = User::findOrFail($userId);
            if ((int) $data['org_id'] !== (int) $existing->org_id) {
                throw new InvalidArgumentException('Users cannot be moved to a different organization');
            }
            // If unchanged, drop the field to avoid unintended side effects
            unset($data['org_id']);
        }

        return DB::transaction(fn() => $this->update($userId, $data));
    }

    // Delete user with authorization checks
    public function deleteUser(int $userId): bool
    {
        return DB::transaction(fn() => $this->delete($userId));
    }

    /**
     * Get filtered users with optional relationships.
     */
    public function getFiltered(array $filters = []): Builder
    {
        $query = $this->getQuery();

        $query->when($filters['org_id'] ?? null, fn($q, $value) => $q->where('org_id', $value))
            ->when($filters['role_id'] ?? null, fn($q, $value) => $q->where('role_id', $value))
            ->when($filters['email'] ?? null, fn($q, $value) => $q->where('email', 'like', "%$value%"))
            ->when($filters['name'] ?? null, function ($q, $value) {
                return $q->where(function ($query) use ($value) {
                    $query->where('first_name', 'like', "%$value%")
                        ->orWhere('last_name', 'like', "%$value%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%$value%"]);
                });
            })
            ->when($filters['q'] ?? null, function ($q, $value) {
                return $q->where(function ($query) use ($value) {
                    $query->where('email', 'like', "%$value%")
                        ->orWhere('first_name', 'like', "%$value%")
                        ->orWhere('last_name', 'like', "%$value%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%$value%"]);
                });
            })
            ->when($filters['with'] ?? null, fn($q, $relations) => $q->with($relations));

        return $query;
    }

    // Convert the full name to first_name and last_name
    private function convertNameToFirstLastName(array &$data): void
    {
        if (isset($data['name'])) {
            $nameParts = explode(' ', trim($data['name']), 2);
            $data['first_name'] = $nameParts[0] ?? '';
            $data['last_name'] = $nameParts[1] ?? '';
            unset($data['name']);
        }
    }

    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id',
            'role_id',
            'email',
            'name',
            'q',
        ]);
    }

    protected function getValidRelations(): array
    {
        return [
            'organization',
            'role',
        ];
    }

    public function processRequestParams(array $params): array
    {
        $this->validateParams($params);

        return [
            'org_id' => $this->toInt($params['org_id'] ?? null),
            'role_id' => $this->toInt($params['role_id'] ?? null),
            'email' => $this->toString($params['email'] ?? null),
            'name' => $this->toString($params['name'] ?? null),
            'q' => $this->toString($params['q'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    /**
     * Apply business rules for user operations.
     */
    private function applyUserBusinessRules(array $data): array
    {
        // Remove password_confirmation as it's unnecessary for the model
        unset($data['password_confirmation']);

        return $data;
    }

    /**
     * Validate business rules for user operations.
     */
    private function validateUserBusinessRules(array $data, $userId = null): void
    {
        // Validate required fields
        if (empty($data['name'])) {
            throw new InvalidArgumentException('The name field is required');
        }

        if (empty($data['email'])) {
            throw new InvalidArgumentException('The email field is required');
        }

        // Validate email format
        if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('The email must be a valid email address');
        }

        // Validate email uniqueness
        $query = User::where('email', $data['email']);
        if ($userId) {
            $query->where('id', '!=', $userId);
        }
        if ($query->exists()) {
            throw new InvalidArgumentException('The email has already been taken');
        }

        // Validate password requirements for creation
        if (! $userId && empty($data['password'])) {
            throw new InvalidArgumentException('The password field is required');
        }

        // Validate password confirmation logic
        if (isset($data['password']) && empty($data['password_confirmation'])) {
            throw new InvalidArgumentException('The password confirmation is required when password is provided');
        }

        // Validate password confirmation match
        if (isset($data['password']) && isset($data['password_confirmation'])) {
            if ($data['password'] !== $data['password_confirmation']) {
                throw new InvalidArgumentException('The password confirmation does not match');
            }
        }

        // Validate password strength
        if (isset($data['password']) && strlen($data['password']) < 8) {
            throw new InvalidArgumentException('The password must be at least 8 characters');
        }

        // Validate role assignment permissions
        if (isset($data['role_id'])) {
            $role = Role::find($data['role_id']);
            if (! $role) {
                throw new InvalidArgumentException('The selected role does not exist');
            }

            if (! AuthorizationEngine::canAssignRole($role->slug)) {
                throw new InvalidArgumentException('You do not have permission to assign this role');
            }
        }
    }
}
