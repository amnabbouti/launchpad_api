<?php

namespace App\Http\Controllers\Api\Hierarchy;

use App\Constants\ErrorMessages;
use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Exceptions\UnauthorizedAccessException;
use App\Http\Controllers\Api\BaseController;
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
        $users = $this->userService->getFiltered($filters);
        $resourceType = 'users';

        if ($users->isEmpty()) {
            $hasFilters = ! empty(array_filter($filters, fn ($value) => $value !== null && $value !== ''));
            if ($hasFilters) {
                $message = str_replace('resources', $resourceType, ErrorMessages::NO_RESOURCES_FOUND);
            } else {
                $message = str_replace('resources', $resourceType, ErrorMessages::NO_RESOURCES_AVAILABLE);
            }
        } else {
            $message = str_replace('Resources', ucfirst($resourceType), SuccessMessages::RESOURCES_RETRIEVED);
        }

        return $this->successResponse(UserResource::collection($users), $message);
    }

    /**
     * Create a new user.
     */
    public function store(UserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validatedForUser());

            return $this->successResponse(
                new UserResource($user),
                SuccessMessages::RESOURCE_CREATED,
                HttpStatus::HTTP_CREATED,
            );
        } catch (UnauthorizedAccessException $e) {
            return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->errorResponse(ErrorMessages::SERVER_ERROR, HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get a specific user by ID.
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->findVisibleUser($id, ['*'], ['role', 'organization']);

        if (! $user) {
            return $this->errorResponse(ErrorMessages::NOT_FOUND, HttpStatus::HTTP_NOT_FOUND);
        }

        return $this->successResponse(new UserResource($user));
    }

    /**
     * Update an existing user.
     */
    public function update(UserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->findById($id);

        if (! $user) {
            return $this->errorResponse(ErrorMessages::NOT_FOUND, HttpStatus::HTTP_NOT_FOUND);
        }

        $updatedUser = $this->userService->updateUser($user->id, $request->validatedForUser());

        return $this->successResponse(
            new UserResource($updatedUser),
            SuccessMessages::RESOURCE_UPDATED,
        );
    }

    /**
     * Delete a user.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->userService->deleteUser($id);

            return $this->successResponse(null, SuccessMessages::RESOURCE_DELETED, HttpStatus::HTTP_NO_CONTENT);
        } catch (UnauthorizedAccessException $e) {
            return $this->errorResponse($e->getMessage(), HttpStatus::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->errorResponse(ErrorMessages::SERVER_ERROR, HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
