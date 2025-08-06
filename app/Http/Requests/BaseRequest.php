<?php

namespace App\Http\Requests;

use App\Services\PublicIdResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

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
     * Get validation rules - with global GET request handling.
     */
    public function rules(): array
    {
        // Global rule: GET requests don't need validation
        if ($this->isMethod('GET')) {
            return [];
        }

        return $this->getValidationRules();
    }

    /**
     * Get the actual validation rules (implemented by child classes).
     */
    abstract protected function getValidationRules(): array;

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
        if (! $this->has('org_id') && Auth::guard('api')->check() && Auth::guard('api')->user()->org_id) {
            $this->merge(['org_id' => Auth::guard('api')->user()->org_id]);
        }

        // Resolve public IDs to internal IDs before validation
        $this->resolvePublicIds();
    }

    /**
     * Resolve public IDs to internal IDs for validation.
     */
    protected function resolvePublicIds(): void
    {
        $data = $this->all();
        $resolvedData = PublicIdResolver::resolve($data);

        // Only merge if there were changes
        if ($resolvedData !== $data) {
            $this->replace($resolvedData);
        }
    }
}
