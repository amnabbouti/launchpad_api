<?php

namespace App\Http\Controllers\Api\Hierarchy;

use App\Exceptions\UnauthorizedAccessException;
use App\Http\Controllers\Api\BaseController;
use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends BaseController
{
    /**
     * Create a new controller
     */
    public function __construct(
        private UserService $userService,
    ) {}

    /**
     * Get all users.
     */
    public function index(UserRequest $request): JsonResponse
    {
        $filters = $this->userService->processRequestParams($request->query());
        $usersQuery = $this->userService->getFiltered($filters);
        $totalCount = $usersQuery->count();

        $users = $this->paginated($usersQuery, $request);

        return ApiResponseMiddleware::listResponse(
            UserResource::collection($users),
            'user',
            $totalCount
        );
    }

    /**
     * Create a new user.
     */
    public function store(UserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());

        return ApiResponseMiddleware::createResponse(
            new UserResource($user),
            'user',
            $user->toArray()
        );
    }

    /**
     * Get a specific user by ID.
     */
    public function show($id): JsonResponse
    {
        $user = $this->userService->findById($id, ['*'], ['role', 'organization']);

        return ApiResponseMiddleware::showResponse(
            new UserResource($user),
            'user',
            $user->toArray()
        );
    }

    /**
     * Update an existing user.
     */
    public function update(UserRequest $request, int $id): JsonResponse
    {
        $updatedUser = $this->userService->updateUser($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new UserResource($updatedUser),
            'user',
            $updatedUser->toArray()
        );
    }

    /**
     * Delete a user.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->userService->deleteUser($id);

            return ApiResponseMiddleware::deleteResponse('user');
        } catch (UnauthorizedAccessException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
