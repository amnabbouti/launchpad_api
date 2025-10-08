<?php

declare(strict_types = 1);

namespace App\Services;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class UserService extends BaseService {
    public function __construct(User $user) {
        parent::__construct($user);
    }

    // Create a new user with password hashing and role assignment
    public function createUser(array $data): User {
        $this->validateUserBusinessRules($data);
        $data = $this->applyUserBusinessRules($data);

        // Authorization checks are handled by PermissionMiddleware
        
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $this->convertNameToFirstLastName($data);

        return DB::transaction(fn () => $this->create($data));
    }

    // Delete user with authorization checks
    public function deleteUser(string $userId): bool {
        return DB::transaction(fn () => $this->delete($userId));
    }

    /**
     * Get filtered users with optional relationships.
     */
    public function getFiltered(array $filters = []): Builder {
        $query = $this->getQuery();

        $query->when($filters['role_id'] ?? null, static fn ($q, $value) => $q->where('role_id', $value))
            ->when($filters['email'] ?? null, static fn ($q, $value) => $q->where('email', 'like', "%{$value}%"))
            ->when($filters['name'] ?? null, static function ($q, $value) {
                return $q->where(static function ($query) use ($value): void {
                    $query->where('first_name', 'like', "%{$value}%")
                        ->orWhere('last_name', 'like', "%{$value}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$value}%"]);
                });
            })
            ->when($filters['q'] ?? null, static function ($q, $value) {
                return $q->where(static function ($query) use ($value): void {
                    $query->where('email', 'like', "%{$value}%")
                        ->orWhere('first_name', 'like', "%{$value}%")
                        ->orWhere('last_name', 'like', "%{$value}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$value}%"]);
                });
            })
            ->when($filters['with'] ?? null, static fn ($q, $relations) => $q->with($relations));

        return $query;
    }

    public function processRequestParams(array $params): array {
        $this->validateParams($params);

        return [
            'role_id' => $this->toInt($params['role_id'] ?? null),
            'email'   => $this->toString($params['email'] ?? null),
            'name'    => $this->toString($params['name'] ?? null),
            'q'       => $this->toString($params['q'] ?? null),
            // org_id is allowed as parameter but not used for filtering since RLS handles organization scoping
            'with'    => $this->processWithParameter($params['with'] ?? null),
        ];
    }

    // Update the user with password hashing and role assignment
    public function updateUser(string $userId, array $data): User {
        // Validate first, then apply business rules
        $this->validateUserBusinessRules($data, $userId);
        $data = $this->applyUserBusinessRules($data);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (isset($data['name'])) {
            $this->convertNameToFirstLastName($data);
        }

        return DB::transaction(fn () => $this->update($userId, $data));
    }

    protected function getAllowedParams(): array {
        return array_merge(parent::getAllowedParams(), [
            'role_id',
            'email',
            'name',
            'q',
            'org_id',
        ]);
    }

    /**
     * Base query - RLS handles organization scoping automatically.
     */
    protected function getQuery(): Builder {
        return $this->model->newQuery();
    }

    protected function getValidRelations(): array {
        return [
            'organization',
            'role',
        ];
    }

    /**
     * Apply business rules for user operations.
     */
    private function applyUserBusinessRules(array $data): array {
        // Remove password_confirmation as it's unnecessary for the model
        unset($data['password_confirmation']);

        return $data;
    }

    // Convert the full name to first_name and last_name
    private function convertNameToFirstLastName(array &$data): void {
        if (isset($data['name'])) {
            $nameParts          = explode(' ', mb_trim($data['name']), 2);
            $data['first_name'] = $nameParts[0] ?? '';
            $data['last_name']  = $nameParts[1] ?? '';
            unset($data['name']);
        }
    }

    /**
     * Validate business rules for user operations.
     */
    private function validateUserBusinessRules(array $data, $userId = null): void {
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
        if (isset($data['password'], $data['password_confirmation'])) {
            if ($data['password'] !== $data['password_confirmation']) {
                throw new InvalidArgumentException('The password confirmation does not match');
            }
        }

        // Validate password strength
        if (isset($data['password']) && mb_strlen($data['password']) < 8) {
            throw new InvalidArgumentException('The password must be at least 8 characters');
        }

        // Validate role assignment permissions
        if (isset($data['role_id'])) {
            $role = Role::find($data['role_id']);
            if (! $role) {
                throw new InvalidArgumentException('The selected role does not exist');
            }

            // Role assignment authorization is handled by PermissionMiddleware
        }
    }
}
