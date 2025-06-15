<?php

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\UnauthorizedAccessException;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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
     * Create a new user
     */
    public function createUser(array $data, ?User $currentUser = null): User
    {
        $currentUser = $currentUser ?? auth()->user();

        $this->ensurePermission('users.create', $currentUser);
        $data = $this->prepareCreateData($data, $currentUser);

        return DB::transaction(fn () => $this->create($data));
    }

    /**
     * Update a user
     */
    public function updateUser(int $userId, array $data, ?User $currentUser = null): User
    {
        $currentUser = $currentUser ?? auth()->user();
        $user = $this->findById($userId);

        $this->ensurePermission('users.edit', $currentUser);
        $data = $this->prepareUpdateData($data, $user, $currentUser);

        return DB::transaction(fn () => $this->update($userId, $data));
    }

    /**
     * Delete a user
     */
    public function deleteUser(int $userId, ?User $currentUser = null): bool
    {
        $currentUser = $currentUser ?? auth()->user();
        $user = $this->findById($userId);

        $this->ensurePermission('users.delete', $currentUser);
        
        if (!$this->canDeleteUser($currentUser, $user)) {
            $this->throwForbidden();
        }

        return DB::transaction(fn () => $this->delete($userId));
    }


    /**
     * Get filtered users with optional relationships
     */
    public function getFiltered(array $filters = []): Collection
    {
        $filters = $this->processRequestParams($filters);
        $visibleUsers = $this->getVisibleUsers(['*'], $filters['with'] ?? []);

        return $visibleUsers->filter(function ($user) use ($filters) {
            if (isset($filters['role_id']) && $user->role_id !== $filters['role_id']) {
                return false;
            }

            if (isset($filters['email']) && ! str_contains(strtolower($user->email), strtolower($filters['email']))) {
                return false;
            }

            if (isset($filters['name'])) {
                $fullName = strtolower($user->getName());

                if (! str_contains($fullName, strtolower($filters['name']))) {
                    return false;
                }
            }

            if (isset($filters['q'])) {
                $query = strtolower($filters['q']);
                $fullName = strtolower($user->getName());
                $email = strtolower($user->email);

                if (! str_contains($fullName, $query) && ! str_contains($email, $query)) {
                    return false;
                }
            }

            return ! (isset($filters['is_active']) && $user->is_active !== $filters['is_active']);
        });
    }

    /**
     * Find a user by ID with automatic visibility validation.
     */
    public function findById($id, array $columns = ['*'], array $relations = [], array $appends = []): Model
    {
        $user = $this->findVisibleUser($id, $columns, $relations);

        if (! $user) {
            return parent::findById($id, $columns, $relations, $appends);
        }

        if (! empty($appends)) {
            $user->append($appends);
        }

        return $user;
    }

    /**
     * Get users visible to the current user based on role.
     * Super admins are completely excluded via canUserSeeUser logic
     */
    public function getVisibleUsers(array $columns = ['*'], array $relations = [], ?User $currentUser = null): Collection
    {
        $currentUser = $currentUser ?? auth()->user();

        if (! $currentUser) {
            return new Collection;
        }

        $allUsers = User::query()->with($relations)->get($columns);

        // Filter users based on visibility rules
        return $allUsers->filter(function ($user) use ($currentUser) {
            return $this->canUserSeeUser($currentUser, $user);
        });
    }

    /**
     * Find user by ID with role visibility check.
     */
    public function findVisibleUser(int $id, array $columns = ['*'], array $relations = [], ?User $currentUser = null): ?User
    {
        $currentUser = $currentUser ?? auth()->user();

        if (! $currentUser) {
            return null;
        }

        $user = User::with($relations)->find($id, $columns);

        if (! $user) {
            return null;
        }

        if ($this->canUserSeeUser($currentUser, $user)) {
            return $user;
        }

        return null;
    }

    /**
     * Check if current user can see target user based on role hierarchy.
     */
    protected function canUserSeeUser(User $currentUser, User $targetUser): bool
    {
        // Super admins are invisible
        if ($targetUser->isSuperAdmin()) {
            return false;
        }

        // Super admins can see all users
        if ($currentUser->isSuperAdmin()) {
            return true;
        }

        if ($currentUser->org_id !== $targetUser->org_id) {
            return false;
        }

        if ($currentUser->isManager()) {
            return true; 
        }

        if ($currentUser->isEmployee()) {
            return $currentUser->id === $targetUser->id;
        }

        return false;
    }

    /**
     * Ensure user has required permission.
     */
    private function ensurePermission(string $permission, User $user): void
    {
        if ($user->lacksPermission($permission)) {
            $this->throwInsufficientPermissions();
        }
    }

    /**
     * Check if the current user can delete the target user.
     */
    private function canDeleteUser(User $currentUser, User $targetUser): bool
    {
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        if ($currentUser->isEmployee()) {
            return false;
        }

        if ($currentUser->isManager() && $targetUser->isManager()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the current user can assign a specific role.
     */
    private function canAssignRole(User $currentUser, string $roleSlug): bool
    {
        if ($currentUser->isEmployee()) {
            return false;
        }

        if ($currentUser->isManager()) {
            return $roleSlug === 'employee';
        }

        return true;
    }

    /**
     * Check if the current user can create users.
     */
    private function canCreateUsers(User $currentUser): bool
    {
        if ($currentUser->isEmployee()) {
            return false;
        }
        return true;
    }

    /**
     * Validate if the current user can assign a specific role.
     */
    private function validateRoleAssignment(User $currentUser, int $roleId, ?User $targetUser = null): void
    {
        $role = \App\Models\Role::find($roleId);
        
        if (!$role) {
            $this->throwNotFound();
        }

        if (!$this->canAssignRole($currentUser, $role->slug)) {
            if ($currentUser->isEmployee()) {
                $this->throwForbidden();
            }
            
            if ($currentUser->isManager() && $role->slug === 'manager') {
                $this->throwForbidden();
            }
        }
    }

    /**
     * Prepare data for user creation.
     */
    private function prepareCreateData(array $data, User $currentUser): array
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (!$this->canCreateUsers($currentUser)) {
            $this->throwForbidden();
        }

        // default role if not provided
        if (!isset($data['role_id'])) {
            $defaultRoleId = $this->getDefaultRoleForCreation($currentUser);
            if ($defaultRoleId) {
                $data['role_id'] = $defaultRoleId;
            }
        }

        // Role assignment is required
        if (!isset($data['role_id'])) {
            $this->throwValidationFailed();
        }

        $this->validateRoleAssignment($currentUser, $data['role_id']);
        $this->processOrgIdForUser($data, $currentUser);

        return $data;
    }

    /**
     * Prepare data for user update.
     */
    private function prepareUpdateData(array $data, User $user, User $currentUser): array
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (isset($data['role_id']) && $data['role_id'] != $user->role_id) {

            if ($currentUser->id === $user->id && $currentUser->lacksPermission('users.edit.role_self')) {
                $this->throwForbidden();
            }
            
            $this->validateRoleAssignment($currentUser, $data['role_id'], $user);
        }

        if (isset($data['role_id']) || isset($data['org_id'])) {
            $this->processOrgIdForUser($data, $currentUser, $user);
        }

        return $data;
    }

    /**
     * Process and validate org_id based on role assignment and user permissions.
     * Handles both creation and update scenarios.
     */
    private function processOrgIdForUser(array &$data, User $currentUser, ?User $existingUser = null): void
    {
        // Get the role being assigned
        $roleId = $data['role_id'] ?? $existingUser?->role_id;
        if (!$roleId) {
            return; 
        }

        $role = \App\Models\Role::find($roleId);
        if (!$role) {
            return; 
        }

        // Check if a specific org_id is being requested
        $requestedOrgId = $data['org_id'] ?? null;

        // Special handling for super admin role assignment - only other super admins can do this
        if ($role->slug === 'super_admin') {
            if (!$currentUser->isSuperAdmin()) {
                $this->throwForbidden("Only super admins can create/modify super admin users");
            }
            
            // Super admins should have org_id = null
            $data['org_id'] = null;
            return;
        }
        
        // Handle standard users (non-super admins)
        if ($requestedOrgId === null) {
            if ($existingUser && !isset($data['role_id'])) {
                return; // Keep existing org_id if not changing the role
            }
            $data['org_id'] = $currentUser->org_id;
            return;
        }

        if ($currentUser->isSuperAdmin() && $requestedOrgId !== null) {
            $organizationExists = \App\Models\Organization::find($requestedOrgId);
            if (!$organizationExists) {
                $this->throwValidationFailed("Invalid organization specified");
            }
        }

    }

    /**
     * Get the default role ID for users
     */
    private function getDefaultRoleForCreation(User $currentUser): ?int
    {
        if ($currentUser->isManager()) {
            $employeeRole = \App\Models\Role::where('slug', 'employee')->first();
            return $employeeRole?->id;
        }
        return null;
    }

    /**
     * Get allowed query parameters
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id', 'role_id', 'email', 'name', 'is_active', 'with',
        ]);
    }

    /**
     * Get valid relations
     */
    protected function getValidRelations(): array
    {
        return [
            'organization', 'role',
        ];
    }

    /**
     * Process request parameters
     */
    public function processRequestParams(array $params): array
    {
        $this->validateParams($params);
        return [
            'org_id' => $this->toInt($params['org_id'] ?? null),
            'role_id' => $this->toInt($params['role_id'] ?? null),
            'email' => $this->toString($params['email'] ?? null),
            'name' => $this->toString($params['name'] ?? null),
            'is_active' => $this->toBool($params['is_active'] ?? null),
            'with' => $this->processWithParameter($params['with'] ?? null),
        ];
    }
}
