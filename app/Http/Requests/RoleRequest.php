<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Check authorization.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermission('users.edit');
    }

    /**
     * Get the validation rules.
     */
    public function rules(): array
    {
        $availablePermissions = Role::getAvailablePermissions();
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
            'permissions' => 'sometimes|array',
            'permissions.*' => ['string', Rule::in($availablePermissions)],
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
            'permissions.*.in' => 'Invalid permission provided.',
        ];
    }
}
