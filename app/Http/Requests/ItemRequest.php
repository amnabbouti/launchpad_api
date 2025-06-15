<?php

namespace App\Http\Requests;

class ItemRequest extends BaseRequest
{
    /**
     * Get validation rules
     */
    public function rules(): array
    {
        $itemId = $this->route('item');
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $trackingMode = $this->input('tracking_mode');

        $rules = [
            'name' => $isUpdate ? 'sometimes|string|max:100' : 'required|string|max:100',
            'code' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:50',
                'unique:items,code,' . ($itemId ?: 'NULL') . ',id,org_id,' . $this->org_id,
            ],
            'barcode' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'tracking_mode' => $isUpdate ? 'sometimes|in:abstract,bulk,serialized' : 'required|in:abstract,bulk,serialized',
            'unit_id' => $isUpdate ? 'sometimes|exists:unit_of_measures,id' : 'required|exists:unit_of_measures,id',
            'price' => 'nullable|numeric|min:0|max:999999.99',
            'category_id' => 'nullable|exists:categories,id',
            'user_id' => 'nullable|exists:users,id',
            'specifications' => 'nullable|array',
            'is_active' => 'boolean',
            'org_id' => $isUpdate ? 'sometimes|exists:organizations,id' : 'required|exists:organizations,id',
            'parent_item_id' => 'nullable|exists:items,id',
            'item_relation_id' => 'nullable|exists:items,id',
            'serial_number' => 'nullable|string|max:255|unique:items,serial_number,' . ($itemId ?: 'NULL') . ',id,org_id,' . $this->org_id,
            'status_id' => 'nullable|exists:statuses,id',
            'notes' => 'nullable|string|max:1000',
            'locations' => 'nullable|array',
            'locations.*.id' => 'required_with:locations|string',
            'locations.*.quantity' => 'required_with:locations|numeric|min:0',
        ];

        // Tracking mode specific validation
        if ($trackingMode === 'serialized') {
            $rules['serial_number'] = str_replace('nullable', 'required', $rules['serial_number']);
        } elseif ($isUpdate && !$this->has('tracking_mode')) {
            // For updates where tracking_mode is not provided, don't enforce serial_number requirement
            // This allows partial updates without needing to know the tracking mode
            $rules['serial_number'] = 'nullable|string|max:255|unique:items,serial_number,' . ($itemId ?: 'NULL') . ',id,org_id,' . $this->org_id;
        }

        return $rules;
    }
       

    /**
     * error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The item name is required',
            'code.required' => 'The item code is required',
            'code.unique' => 'This item code is already used in your organization.',
            'tracking_mode.required' => 'The tracking mode is required',
            'tracking_mode.in' => 'The tracking mode must be: abstract, bulk, or serialized',
            'unit_id.required' => 'The unit of measure is required',
            'unit_id.exists' => 'The selected unit of measure is invalid.',
            'serial_number.required' => 'The serial number is required for serialized items',
            'serial_number.unique' => 'This serial number already exists in your organization.',
            'category_id.exists' => 'The selected category is invalid.',
            'user_id.exists' => 'The selected user is invalid.',
            'status_id.exists' => 'The selected status is invalid.',
            'org_id.required' => 'The organization ID is required',
            'org_id.exists' => 'The selected organization is invalid',
            'price.numeric' => 'The price must be a number',
            'price.min' => 'The price must be at least 0',
            'price.max' => 'The price cannot exceed 999,999.99',
            'barcode.max' => 'The barcode cannot exceed 255 characters',
            'description.max' => 'The description cannot exceed 1000 characters',
            'notes.max' => 'The notes cannot exceed 1000 characters',
            'parent_item_id.exists' => 'The selected parent item is invalid.',
            'item_relation_id.exists' => 'The selected related item is invalid.',
        ];
    }

    /**
     * Apply tracking mode constraints to validated data
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        
        if (is_array($data) && isset($data['tracking_mode'])) {
            $data = $this->applyTrackingModeConstraints($data);
        }
        
        return $data;
    }

    /**
     * Apply constraints based on tracking mode
     */
    private function applyTrackingModeConstraints(array $data): array
    {
        switch ($data['tracking_mode']) {
            case 'abstract':
                $data['serial_number'] = null;
                $data['status_id'] = null;
                $data['notes'] = null;
                break;
            case 'bulk':
                $data['serial_number'] = null;
                break;
            case 'serialized':
                // Serial number is already validated as required
                break;
        }

        return $data;
    }
}
