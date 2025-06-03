<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class MaintenanceCategoryRequest extends BaseRequest
{
    /**
     * validation rules.
     */
    public function rules(): array
    {
        $categoryId = $this->route('maintenance_category')?->id ?? $this->maintenance_category_id ?? null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('maintenance_categories')
                    ->where('org_id', $this->org_id)
                    ->ignore($categoryId),
            ],
            'remarks' => 'nullable|string|max:65535',
            'is_active' => 'boolean',
            'org_id' => 'required|exists:organizations,id',
        ];
    }

    /**
     * error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The maintenance category name is required',
            'name.unique' => 'This maintenance category name already exists for the organization',
            'org_id.required' => 'The organization ID is required',
            'org_id.exists' => 'The selected organization is invalid',
            'remarks.max' => 'The remarks field is too long',
        ];
    }
}
