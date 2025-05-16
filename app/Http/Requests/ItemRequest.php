<?php

namespace App\Http\Requests;

class ItemRequest extends BaseRequest
{
    // Rules
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:items,code,'.$this->item,
            'description' => 'nullable|string',
            'quantity' => 'numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'category_id' => 'required|exists:categories,id',
            'user_id' => 'required|exists:users,id',
            'stock_id' => 'required|exists:stocks,id',
            'is_active' => 'boolean',
            'specifications' => 'nullable|json',
        ];
    }

    // Messages
    public function messages(): array
    {
        return [
            'name.required' => 'The item name is required',
            'code.unique' => 'This item code is already in use',
            'category_id.exists' => 'The selected category does not exist',
            'user_id.exists' => 'The selected user does not exist',
            'stock_id.exists' => 'The selected stock does not exist',
        ];
    }
}
