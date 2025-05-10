<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    // get all users
    public function index(): JsonResponse
    {
        $users = $this->userService->all();
        return $this->successResponse(UserResource::collection($users));
    }

    // create new user
    public function store(UserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());
        return $this->successResponse(new UserResource($user), 'User created successfully', 201);
    }

    // display a user by id
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->findById($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        return $this->successResponse(new UserResource($user));
    }

    // update a user
    public function update(UserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->findById($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        $updatedUser = $this->userService->update($id, $request->validated());
        return $this->successResponse(new UserResource($updatedUser), 'User updated successfully');
    }

    // remove a user
    public function destroy(int $id): JsonResponse
    {
        $user = $this->userService->findById($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        $this->userService->delete($id);
        return $this->successResponse(null, 'User deleted successfully');
    }

    // get a user by role
    public function getByRole(string $role): JsonResponse
    {
        $users = $this->userService->getByRole($role);
        return $this->successResponse(UserResource::collection($users));
    }

    // get only active users
    public function getActive(): JsonResponse
    {
        $users = $this->userService->getActive();
        return $this->successResponse(UserResource::collection($users));
    }

    // get users with items
    public function getWithItems(): JsonResponse
    {
        $users = $this->userService->getWithItems();
        return $this->successResponse(UserResource::collection($users));
    }
}
