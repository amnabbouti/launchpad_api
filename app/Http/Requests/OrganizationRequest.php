<?php

namespace App\Http\Requests;

class OrganizationRequest extends BaseRequest
{
    /**
     * Validation rules.
     */
    public function rules(): array
    {
        // For GET requests (like index with query parameters), no validation needed
        if ($this->isMethod('GET')) {
            return [];
        }

        $organizationId = $this->route('organization')?->id ?? $this->organization_id ?? null;

        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'website' => 'nullable|url|max:255',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The organization name is required',
            'name.string' => 'The organization name must be a string',
            'name.max' => 'The organization name cannot exceed 255 characters',
            'email.email' => 'The email must be a valid email address',
            'email.max' => 'The email cannot exceed 255 characters',
            'telephone.string' => 'The telephone must be a string',
            'telephone.max' => 'The telephone cannot exceed 50 characters',
            'address.string' => 'The address must be a string',
            'address.max' => 'The address cannot exceed 255 characters',
            'remarks.string' => 'The remarks must be a string',
            'website.url' => 'The website must be a valid URL',
            'website.max' => 'The website cannot exceed 255 characters',
        ];
    }
}
