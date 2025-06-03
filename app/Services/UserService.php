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
     * Create a new user with authorization and business logic.
     */
    public function createUser(array $data): User
    {
        $currentUser = auth()->user();

        // Role-based permission check
        if (! $currentUser->isSuperAdmin() && $currentUser->lacksPermission('users.create')) {
            throw new UnauthorizedAccessException(ErrorMessages::INSUFFICIENT_PERMISSIONS);
        }

        // Prepare data
        $data = $this->prepareCreateData($data, $currentUser);

        return DB::transaction(function () use ($data) {
            $user = new User($data);
            // Use trait to validate and set org_id (includes auth check)
            User::validateCreateOrgAuthorization($user);

            return $this->create($data);
        });
    }

    /**
     * Update a user with authorization and business logic.
     */
    public function updateUser(int $userId, array $data): User
    {
        $currentUser = auth()->user();
        $user = $this->findVisibleUser($userId);

        if (! $user) {
            // This will trigger BaseService's ResourceNotFoundException
            $this->findById($userId);
        }

        // Role-based permission check
        if (! $currentUser->isSuperAdmin() && $currentUser->lacksPermission('users.edit')) {
            throw new UnauthorizedAccessException(ErrorMessages::INSUFFICIENT_PERMISSIONS);
        }

        // Use trait to validate org_id (includes auth check)
        User::validateUpdateOrgAuthorization($user);

        // Prepare data
        $data = $this->prepareUpdateData($data, $user, $currentUser);

        return DB::transaction(fn () => $this->update($userId, $data));
    }

    /**
     * Delete a user with authorization.
     */
    public function deleteUser(int $userId): bool
    {
        $currentUser = auth()->user();
        $user = $this->findVisibleUser($userId);

        if (! $user) {
            // This will trigger BaseService's ResourceNotFoundException
            $this->findById($userId);
        }

        // Role-based permission and self-deletion checks
        if (! $currentUser->isSuperAdmin() && $currentUser->lacksPermission('users.delete')) {
            throw new UnauthorizedAccessException(ErrorMessages::INSUFFICIENT_PERMISSIONS);
        }

        if ($currentUser->id === $user->id) {
            throw new UnauthorizedAccessException(ErrorMessages::SELF_DELETION_FORBIDDEN);
        }

        // Use trait to validate org_id (includes auth check)
        User::validateDeleteOrgAuthorization($user);

        return DB::transaction(fn () => $this->delete($userId));
    }

    /**
     * Get filtered users with optional relationships based on current user's visibility.
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
        if ($id <= 0) {
            throw new InvalidArgumentException('ID must be a positive integer');
        }

        $user = $this->findVisibleUser($id, $columns, $relations);

        if (! $user) {
            // This will trigger BaseService's ResourceNotFoundException
            return parent::findById($id, $columns, $relations, $appends);
        }

        if (! empty($appends)) {
            $user->append($appends);
        }

        return $user;
    }

    /**
     * Get users visible to the current user based on role.
     */
    public function getVisibleUsers(array $columns = ['*'], array $relations = []): Collection
    {
        $currentUser = auth()->user();

        if (! $currentUser) {
            return new Collection;
        }

        $query = User::query()->with($relations);

        if ($currentUser->isSuperAdmin()) {
            return $query->get($columns);
        }

        if ($currentUser->isManager()) {
            return $query->where('org_id', $currentUser->org_id)->get($columns);
        }

        if ($currentUser->isEmployee()) {
            return $query->where('id', $currentUser->id)->get($columns);
        }

        return new Collection;
    }

    /**
     * Find user by ID with role-based visibility check.
     */
    public function findVisibleUser(int $id, array $columns = ['*'], array $relations = []): ?User
    {
        $currentUser = auth()->user();

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
     * Get users by organization ID (super admin only).
     */
    public function getUsersByOrganization(int $orgId): Collection
    {
        $currentUser = auth()->user();

        if (! $currentUser || ! $currentUser->isSuperAdmin()) {
            throw new UnauthorizedAccessException(ErrorMessages::INSUFFICIENT_PERMISSIONS);
        }

        return User::with(['role', 'organization'])
            ->where('org_id', $orgId)
            ->get();
    }

    /**
     * Check if current user can see target user based on role hierarchy.
     */
    protected function canUserSeeUser(User $currentUser, User $targetUser): bool
    {
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
     * Prepare data for user creation.
     */
    private function prepareCreateData(array $data, User $currentUser): array
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }

    /**
     * Get allowed query parameters for filtering users.
     */
    protected function getAllowedParams(): array
    {
        return array_merge(parent::getAllowedParams(), [
            'org_id', 'role_id', 'email', 'name', 'is_active', 'with',
        ]);
    }

    /**
     * Get valid relations for the user model.
     */
    protected function getValidRelations(): array
    {
        return [
            'organization', 'role', // add more if your User model supports
        ];
    }

    /**
     * Process and sanitize request parameters for filtering users.
     */
    public function processRequestParams(array $params): array
    {
        $processed = parent::processRequestParams($params);
        $processed['org_id'] = isset($params['org_id']) && is_numeric($params['org_id']) ? (int) $params['org_id'] : null;
        $processed['role_id'] = isset($params['role_id']) && is_numeric($params['role_id']) ? (int) $params['role_id'] : null;
        $processed['email'] = $params['email'] ?? null;
        $processed['name'] = $params['name'] ?? null;
        $processed['is_active'] = isset($params['is_active']) ? filter_var($params['is_active'], FILTER_VALIDATE_BOOLEAN) : null;
        $processed['with'] = isset($params['with']) ? (array) $params['with'] : [];

        return $processed;
    }

    /**
     * Prepare data for user update.
     */
    private function prepareUpdateData(array $data, User $user, User $currentUser): array
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }
}
