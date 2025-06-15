<?php

namespace App\Http\Requests;

use App\Services\RoleService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
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
    public function rules(): array
    {
        $roleService = app(RoleService::class);
        $availableActions = $roleService->getAvailableActions();

        return [
            'slug' => [
                'required',
                'string',
                'max:50',
                'unique:roles,slug',
                'regex:/^[a-z0-9-_]+$/',
            ],
            'title' => 'required|string|max:100',
            'forbidden' => 'sometimes|array',
            'forbidden.*' => ['string', Rule::in($availableActions)],
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
            'forbidden.*.in' => 'Invalid action provided.',
        ];
    }
}

class UpdateRoleRequest extends FormRequest
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
    public function rules(): array
    {
        $roleService = app(RoleService::class);
        $availableActions = $roleService->getAvailableActions();
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
            'forbidden' => 'sometimes|array',
            'forbidden.*' => ['string', Rule::in($availableActions)],
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
            'forbidden.*.in' => 'Invalid action provided.',
        ];
    }
}
