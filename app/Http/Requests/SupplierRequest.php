<?php

declare(strict_types = 1);

namespace App\Http\Requests;

use App\Constants\AppConstants;

class SupplierRequest extends BaseRequest {
    /**
     * Error messages - PURE VALIDATION MESSAGES ONLY
     */
    public function messages(): array {
        return [
            'name.required'      => 'The supplier name is required',
            'email.email'        => 'Please enter a valid email address',
            'website.url'        => 'Please enter a valid website URL',
            'item_id.exists'     => 'The selected item is invalid',
            'supplier_id.exists' => 'The selected supplier is invalid',
            'price.min'          => 'The price cannot be negative',
            'lead_time_days.min' => 'The lead time cannot be negative',
            'currency.size'      => 'Currency code must be exactly 3 characters',
            'org_id.exists'      => 'The selected organization is invalid',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void {
        $validator->after(static function ($validator): void {
            $data = $validator->getData();

            // If this is a relationship request, validate relationship fields
            if (isset($data['type']) && $data['type'] === 'relationship') {
                if (empty($data['item_id'])) {
                    $validator->errors()->add('item_id', 'The item ID is required for relationships.');
                }
                if (empty($data['supplier_id'])) {
                    $validator->errors()->add('supplier_id', 'The supplier ID is required for relationships.');
                }
            } else {
                // If this is a supplier request, validate supplier fields
                if (empty($data['name'])) {
                    $validator->errors()->add('name', 'The supplier name is required.');
                }
            }
        });
    }

    /**
     * Validation rules - PURE VALIDATION ONLY
     * Business logic handled in SupplierService
     */
    protected function getValidationRules(): array {
        return [
            // Supplier fields - basic validation only
            'name'         => 'nullable|string|max:255',
            'code'         => 'nullable|string|max:50',
            'contact_name' => 'nullable|string|max:255',
            'email'        => 'nullable|email|max:' . AppConstants::EMAIL_MAX_LENGTH,
            'phone'        => 'nullable|string|max:50',
            'address'      => 'nullable|string|max:255',
            'city'         => 'nullable|string|max:100',
            'state'        => 'nullable|string|max:100',
            'postal_code'  => 'nullable|string|max:' . AppConstants::POSTAL_CODE_MAX_LENGTH,
            'country'      => 'nullable|string|max:100',
            'website'      => 'nullable|url|max:255',
            'tax_id'       => 'nullable|string|max:50',
            'notes'        => 'nullable|string',
            'is_active'    => 'boolean',
            'org_id'       => 'nullable|string|exists:organizations,id',

            // Item-Supplier relationship fields
            'item_id'              => 'nullable|string|exists:items,id',
            'supplier_id'          => 'nullable|string|exists:suppliers,id',
            'supplier_part_number' => 'nullable|string|max:100',
            'price'                => 'nullable|numeric|min:0',
            'currency'             => 'nullable|string|size:3',
            'lead_time_days'       => 'nullable|integer|min:0',
            'is_preferred'         => 'boolean',

            // Operation type (for service to handle business logic)
            'type' => 'nullable|string|in:supplier,relationship',
        ];
    }
}
