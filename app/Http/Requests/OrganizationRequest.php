<?php

namespace App\Http\Requests;

use App\Constants\AppConstants;

class OrganizationRequest extends BaseRequest
{
    /**
     * Validation rules
     */
    protected function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:' . AppConstants::NAME_MAX_LENGTH,
            'email' => 'required|email|max:' . AppConstants::EMAIL_MAX_LENGTH,
            'country' => 'required|string|max:100',
            'billing_address' => 'required|string|max:255',
            'tax_id' => 'required|string|max:100',
            'telephone' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:255',
            'street_number' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:' . AppConstants::POSTAL_CODE_MAX_LENGTH,
            'logo' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50',
            'subscription_starts_at' => 'nullable|date',
            'subscription_ends_at' => 'nullable|date',
            'settings' => 'nullable|array',
            'created_by' => 'nullable|integer|exists:users,id',
            'remarks' => 'nullable|string',
            'website' => 'nullable|url|max:255',
        ];
    }

    /**
     * Error messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The organization name is required',
            'name.string' => 'The organization name must be a string',
            'name.max' => 'The organization name cannot exceed 255 characters',
            'email.required' => 'The email is required',
            'email.email' => 'The email must be a valid email address',
            'email.max' => 'The email cannot exceed 255 characters',
            'country.required' => 'The country is required',
            'country.string' => 'The country must be a string',
            'country.max' => 'The country cannot exceed 100 characters',
            'billing_address.required' => 'The billing address is required',
            'billing_address.string' => 'The billing address must be a string',
            'billing_address.max' => 'The billing address cannot exceed 255 characters',
            'tax_id.required' => 'The tax ID is required',
            'tax_id.string' => 'The tax ID must be a string',
            'tax_id.max' => 'The tax ID cannot exceed 100 characters',
            'telephone.string' => 'The telephone must be a string',
            'telephone.max' => 'The telephone cannot exceed 50 characters',
            'street.string' => 'The street must be a string',
            'street.max' => 'The street cannot exceed 255 characters',
            'street_number.string' => 'The street number must be a string',
            'street_number.max' => 'The street number cannot exceed 50 characters',
            'city.string' => 'The city must be a string',
            'city.max' => 'The city cannot exceed 255 characters',
            'province.string' => 'The province must be a string',
            'province.max' => 'The province cannot exceed 255 characters',
            'postal_code.string' => 'The postal code must be a string',
            'postal_code.max' => 'The postal code cannot exceed 20 characters',
            'logo.string' => 'The logo must be a string',
            'logo.max' => 'The logo cannot exceed 255 characters',
            'industry.string' => 'The industry must be a string',
            'industry.max' => 'The industry cannot exceed 255 characters',
            'timezone.string' => 'The timezone must be a string',
            'timezone.max' => 'The timezone cannot exceed 100 characters',
            'status.string' => 'The status must be a string',
            'status.max' => 'The status cannot exceed 50 characters',
            'subscription_starts_at.date' => 'The subscription start date is not a valid date',
            'subscription_ends_at.date' => 'The subscription end date is not a valid date',
            'settings.array' => 'The settings must be an array',
            'created_by.integer' => 'The created by field must be an integer',
            'created_by.exists' => 'The selected creator is invalid',
            'remarks.string' => 'The remarks must be a string',
            'website.url' => 'The website must be a valid URL',
            'website.max' => 'The website cannot exceed 255 characters',
        ];
    }
}
