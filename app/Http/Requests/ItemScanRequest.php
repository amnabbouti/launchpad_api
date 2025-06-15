<?php

namespace App\Http\Requests;

class ItemScanRequest extends BaseRequest
{
    /**
     * Get validation rules for item scanning.
     */
    public function rules(): array
    {
        return [
            'code' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom error messages for item scanning validation.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'The scan code is required.',
            'code.string' => 'The scan code must be text.',
            'code.max' => 'The scan code cannot exceed 255 characters.',
        ];
    }
}
