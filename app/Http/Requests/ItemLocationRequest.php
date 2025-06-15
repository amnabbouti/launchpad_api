<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ItemLocationRequest extends BaseRequest
{
    /**
     * validation rules.
     */
    public function rules(): array
    {
        $itemLocationId = $this->route('id') ?? null;
        $isMove = $this->isMethod('post') && str_contains($this->path(), 'move');
        $isUpdateQuantity = $this->isMethod('put') && str_contains($this->path(), 'update-quantity');
        $orgId = auth()->user()->org_id;

        if ($isMove) {
            return [
                'org_id' => 'required|exists:organizations,id',
                'item_id' => 'required|exists:items,id',
                'from_location_id' => 'required|exists:locations,id',
                'to_location_id' => 'required|exists:locations,id|different:from_location_id',
                'quantity' => 'required|numeric|min:0',
                'moved_date' => 'nullable|date',
                'notes' => 'nullable|string|max:65535',
            ];
        }

        if ($isUpdateQuantity) {
            return [
                'org_id' => 'required|exists:organizations,id',
                'item_id' => 'required|exists:items,id',
                'location_id' => 'required|exists:locations,id',
                'quantity' => 'required|numeric|min:0',
                'moved_date' => 'nullable|date',
                'notes' => 'nullable|string|max:65535',
            ];
        }

        return [
            'org_id' => 'required|exists:organizations,id',
            'item_id' => 'required|exists:items,id',
            'location_id' => [
                'required',
                'exists:locations,id',
                Rule::unique('item_locations')
                    ->where('org_id', $orgId)
                    ->where('item_id', $this->item_id)
                    ->ignore($itemLocationId),
            ],
            'quantity' => 'required|numeric|min:0',
            'moved_date' => 'nullable|date',
            'notes' => 'nullable|string|max:65535',
        ];
    }

    /**
     * error messages.
     */
    public function messages(): array
    {
        return [
            'org_id.required' => 'Organization ID is required',
            'org_id.exists' => 'The selected organization is invalid',
            'item_id.required' => 'The item is required',
            'item_id.exists' => 'The selected item is invalid',
            'location_id.required' => 'The location is required',
            'location_id.exists' => 'The selected location is invalid',
            'location_id.unique' => 'This item is already assigned to this location for the organization',
            'from_location_id.required' => 'The source location is required',
            'from_location_id.exists' => 'The selected source location is invalid',
            'to_location_id.required' => 'The destination location is required',
            'to_location_id.exists' => 'The selected destination location is invalid',
            'to_location_id.different' => 'The destination location must be different from the source location',
            'quantity.required' => 'The quantity is required',
            'quantity.min' => 'The quantity cannot be negative',
            'notes.max' => 'The notes field is too long',
        ];
    }
}
