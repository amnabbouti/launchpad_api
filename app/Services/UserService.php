<?php

namespace App\Services;

use App\Models\User;

class UserService extends BaseService
{
    // get a new service instance
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    // get users by role
    public function getByRole(string $role)
    {
        return $this->model->where('role', $role)->get();
    }

    // get active users
    public function getActive()
    {
        return $this->model->where('is_active', true)->get();
    }

    // get users with items
    public function getWithItems()
    {
        return $this->model->with('items')->get();
    }
}
