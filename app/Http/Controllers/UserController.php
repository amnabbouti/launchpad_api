<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
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
     * Delete a user.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->userService->deleteUser($id);

        return ApiResponseMiddleware::deleteResponse('user');
    }

    /**
     * Get all users.
     */
    public function index(UserRequest $request): JsonResponse
    {
        $filters    = $this->userService->processRequestParams($request->query());
        $usersQuery = $this->userService->getFiltered($filters);
        $totalCount = $usersQuery->count();

        $users = $this->paginated($usersQuery, $request);

        return ApiResponseMiddleware::listResponse(
            UserResource::collection($users),
            'user',
            $totalCount,
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
            $user->toArray(),
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
            $user->toArray(),
        );
    }

    /**
     * Update an existing user.
     */
    public function update(UserRequest $request, string $id): JsonResponse
    {
        $updatedUser = $this->userService->updateUser($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new UserResource($updatedUser),
            'user',
            $updatedUser->toArray(),
        );
    }
}
