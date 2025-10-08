<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Constants\AppConstants;

class UserRequest extends BaseRequest
{
    /**
     * Error messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'name.string'   => 'The name must be a string.',
            'name.max'      => 'The name may not be greater than 255 characters.',
            'email.required' => 'The email field is required.',
            'email.email'    => 'The email must be a valid email address.',
            'email.max'      => 'The email may not be greater than 255 characters.',
            'password.min' => 'The password must be at least 8 characters.',
            'role_id.string' => 'The role ID must be a string.',
            'role_id.exists'  => 'The selected role is invalid.',
            'org_id.string' => 'The organization ID must be a string.',
            'org_id.exists'  => 'The selected organization is invalid.',
            'is_active.boolean' => 'The active status must be true or false.',
        ];
    }

    /**
     * Validation rules
     */
    protected function getValidationRules(): array
    {
        return [
            'name'                  => 'required|string|max:' . AppConstants::EMAIL_MAX_LENGTH,
            'email'                 => 'required|string|email|max:' . AppConstants::EMAIL_MAX_LENGTH,
            'password'              => 'nullable|string|min:' . AppConstants::PASSWORD_MIN_LENGTH,
            'password_confirmation' => 'nullable|string',
            'role_id'               => 'nullable|integer|exists:roles,id',
            'org_id'                => 'nullable|integer|exists:organizations,id',
            'is_active'             => 'nullable|boolean',
        ];
    }
}
