<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * validation rules.
     */
    abstract public function rules(): array;

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('org_id') && auth()->check() && auth()->user()->org_id) {
            $this->merge(['org_id' => auth()->user()->org_id]);
        }

        // Resolve public IDs to internal IDs before validation
        $this->resolvePublicIds();
    }

    /**
     * Resolve public IDs to internal IDs for validation.
     */
    protected function resolvePublicIds(): void
    {
        $user = auth()->user();
        if (!$user || !$user->org_id) {
            return;
        }

        $data = $this->all();
        
        $foreignKeyMappings = [
            'item_id' => \App\Models\Item::class,
            'supplier_id' => \App\Models\Supplier::class,
            'parent_id' => \App\Models\Location::class,
            'location_id' => \App\Models\Location::class,
            'stock_id' => \App\Models\Stock::class,
            'stock_item_id' => \App\Models\StockItem::class,
            'status_id' => \App\Models\ItemStatus::class,  // For StockItems
            'unit_id' => \App\Models\UnitOfMeasure::class,
            'role_id' => \App\Models\Role::class,
            'general_status_id' => \App\Models\Status::class,  // For general statuses
            'item_status_id' => \App\Models\ItemStatus::class,
        ];

        $resolvedData = [];

        foreach ($foreignKeyMappings as $field => $modelClass) {
            if (isset($data[$field]) && is_string($data[$field]) && !is_numeric($data[$field])) {
                // resolve public ID to internal ID
                if (method_exists($modelClass, 'findByPublicId')) {
                    $model = $modelClass::findByPublicId($data[$field], $user->org_id);
                    if ($model) {
                        $resolvedData[$field] = $model->id;
                    }
                }
            }
        }

        if (!empty($resolvedData)) {
            $this->merge($resolvedData);
        }
    }
}
