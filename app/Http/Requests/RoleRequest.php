<?php

namespace App\Http\Requests;

use App\Constants\Permissions;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends BaseRequest
{
    /**
     * Check authorization.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermission('roles.create');
    }

    /**
     * Get the validation rules.
     */
    protected function getValidationRules(): array
    {
        return [
            'slug' => [
                'required',
                'string',
                'max:50',
                'unique:roles,slug',
                'regex:/^[a-z0-9-_]+$/',
            ],
            'title' => 'required|string|max:100',
            'description' => 'sometimes|string|max:255',
            'forbidden' => 'sometimes|array',
            'forbidden.*' => ['string', Rule::in(Permissions::getAvailablePermissionKeys())],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'slug.required' => 'The role slug is required.',
            'slug.regex' => 'The slug field may only contain lowercase letters, numbers, hyphens, and underscores.',
            'slug.unique' => 'A role with this slug already exists.',
            'title.required' => 'The role title is required.',
            'forbidden.*.in' => 'Invalid permission provided.',
        ];
    }
}

class UpdateRoleRequest extends BaseRequest
{
    /**
     * Check authorization.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermission('roles.update');
    }

    /**
     * Get the validation rules.
     */
    protected function getValidationRules(): array
    {
        $roleId = $this->route('role');

        return [
            'slug' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('roles', 'slug')->ignore($roleId),
                'regex:/^[a-z0-9-_]+$/',
            ],
            'title' => 'sometimes|string|max:100',
            'description' => 'sometimes|string|max:255',
            'forbidden' => 'sometimes|array',
            'forbidden.*' => ['string', Rule::in(Permissions::getAvailablePermissionKeys())],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug field may only contain lowercase letters, numbers, hyphens, and underscores.',
            'slug.unique' => 'A role with this slug already exists.',
            'forbidden.*.in' => 'Invalid permission provided.',
        ];
    }
}
