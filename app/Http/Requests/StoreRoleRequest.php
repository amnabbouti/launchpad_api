<?php

declare(strict_types = 1);

namespace App\Http\Requests;

use App\Constants\Permissions;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends BaseRequest {
    public function authorize(): bool {
        // Authorization is handled by PermissionMiddleware
        return true;
    }

    public function messages(): array {
        return [
            'slug.required'  => 'The role slug is required.',
            'slug.regex'     => 'The slug field may only contain lowercase letters, numbers, hyphens, and underscores.',
            'slug.unique'    => 'A role with this slug already exists.',
            'title.required' => 'The role title is required.',
            'forbidden.*.in' => 'Invalid permission provided.',
        ];
    }

    protected function getValidationRules(): array {
        return [
            'slug' => [
                'required',
                'string',
                'max:50',
                'unique:roles,slug',
                'regex:/^[a-z0-9-_]+$/',
            ],
            'title'       => 'required|string|max:100',
            'description' => 'sometimes|string|max:255',
            'forbidden'   => 'sometimes|array',
            'forbidden.*' => ['string', Rule::in(Permissions::getAvailablePermissionKeys())],
        ];
    }
}
