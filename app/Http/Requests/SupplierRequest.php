<?php

namespace App\Http\Requests;

class SupplierRequest extends BaseRequest
{
    // Rules
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'tax_id' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    // Messages
    public function messages(): array
    {
        return [
            'name.required' => 'The supplier name is required',
            'email.email' => 'Please enter a valid email address',
            'website.url' => 'Please enter a valid website URL',
        ];
    }
}
