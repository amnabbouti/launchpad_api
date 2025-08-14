<?php

declare(strict_types = 1);

namespace App\Http\Requests;

use App\Constants\Permissions;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends BaseRequest {
    public function authorize(): bool {
        return $this->user()->hasPermission('roles.update');
    }

    public function messages(): array {
        return [
            'slug.regex'     => 'The slug field may only contain lowercase letters, numbers, hyphens, and underscores.',
            'slug.unique'    => 'A role with this slug already exists.',
            'forbidden.*.in' => 'Invalid permission provided.',
        ];
    }

    protected function getValidationRules(): array {
        $roleId = $this->route('role');

        return [
            'slug' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('roles', 'slug')->ignore($roleId),
                'regex:/^[a-z0-9-_]+$/',
            ],
            'title'       => 'sometimes|string|max:100',
            'description' => 'sometimes|string|max:255',
            'forbidden'   => 'sometimes|array',
            'forbidden.*' => ['string', Rule::in(Permissions::getAvailablePermissionKeys())],
        ];
    }
}
