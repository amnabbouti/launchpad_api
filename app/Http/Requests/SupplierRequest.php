<?php

namespace App\Http\Requests;

class SupplierRequest extends BaseRequest
{
    /**
     * Validation rules.
     */
    public function rules(): array
    {
        $supplierId = $this->route('supplier')?->id ?? $this->supplier_id ?? null;
        $operation = $this->route()->getActionMethod();

        $rules = [];

        // Supplier specific validation
        if ($this->isSupplierOperation()) {
            $rules = array_merge($rules, [
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:50|unique:suppliers,code,'.$supplierId.',id,org_id,'.auth()->user()->org_id,
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
                'org_id' => 'nullable|exists:organizations,id',
            ]);
        }

        // Item supplier relationship validation
        if ($this->isItemSupplierOperation()) {
            $rules = array_merge($rules, [
                'item_id' => 'required|exists:items,id',
                'supplier_id' => 'required|exists:suppliers,id',
                'supplier_part_number' => 'nullable|string|max:100',
                'price' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|size:3',
                'lead_time_days' => 'nullable|integer|min:0',
                'is_preferred' => 'boolean',
            ]);
        }

        return $rules;
    }

    /**
     * Error messages.
     */
    public function messages(): array
    {
        return [
            // Supplier messages
            'name.required' => 'The supplier name is required',
            'code.unique' => 'This supplier code is already used in your organization.',
            'email.email' => 'Please enter a valid email address',
            'website.url' => 'Please enter a valid website URL',

            // Item Supplier relationship messages
            'item_id.required' => 'The item is required',
            'item_id.exists' => 'The selected item is invalid',
            'supplier_id.required' => 'The supplier is required',
            'supplier_id.exists' => 'The selected supplier is invalid',
            'price.min' => 'The price cannot be negative',
            'lead_time_days.min' => 'The lead time cannot be negative',            'currency.size' => 'Currency code must be exactly 3 characters',
        ];
    }

    /**
     * Check if this is a supplier operation.
     */
    private function isSupplierOperation(): bool
    {
        // Check for relationship type parameter
        $type = $this->get('type');

        return $type !== 'relationship' && ! $this->has('item_id');
    }

    /**
     * Check if this is an item supplier relationship operation.
     */
    private function isItemSupplierOperation(): bool
    {
        // Check for relationship type parameter or item_id presence
        $type = $this->get('type');

        return $type === 'relationship' || $this->has('item_id');
    }
}
