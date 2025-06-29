<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * Validation rules for Plan operations.
     * Plans are global resources managed by super admin.
     */
    protected function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'user_limit' => 'nullable|integer|min:1',
            'features' => 'nullable|array',
            'interval' => 'nullable|string|in:monthly,yearly,lifetime',
            'is_active' => 'nullable|boolean',
        ];
    }
}
