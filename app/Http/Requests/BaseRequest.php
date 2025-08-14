<?php

declare(strict_types = 1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseRequest extends FormRequest {
    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array {
        return [];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array {
        return [];
    }

    /**
     * Get validation rules - with global GET request handling.
     */
    public function rules(): array {
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
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void {}
}
