<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends BaseController
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    // All
    public function index(): JsonResponse
    {
        $users = $this->userService->all();
        return $this->successResponse(UserResource::collection($users));
    }

    // Create
    public function store(UserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());
        return $this->successResponse(new UserResource($user), 'User created successfully', 201);
    }

    // Show
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->findById($id);
        if (! $user) {
            return $this->errorResponse('User not found', 404);
        }
        return $this->successResponse(new UserResource($user));
    }

    // Update
    public function update(UserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->findById($id);
        if (! $user) {
            return $this->errorResponse('User not found', 404);
        }
        $updatedUser = $this->userService->update($id, $request->validated());
        return $this->successResponse(new UserResource($updatedUser), 'User updated successfully');
    }

    // Delete
    public function destroy(int $id): JsonResponse
    {
        $user = $this->userService->findById($id);
        if (! $user) {
            return $this->errorResponse('User not found', 404);
        }
        $this->userService->delete($id);
        return $this->successResponse(null, 'User deleted successfully');
    }

    // By role
    public function getByRole(string $role): JsonResponse
    {
        $users = $this->userService->getByRole($role);
        return $this->successResponse(UserResource::collection($users));
    }

    // Active
    public function getActive(): JsonResponse
    {
        $users = $this->userService->getActive();
        return $this->successResponse(UserResource::collection($users));
    }

    // With items
    public function getWithItems(): JsonResponse
    {
        $users = $this->userService->getWithItems();
        return $this->successResponse(UserResource::collection($users));
    }
}
