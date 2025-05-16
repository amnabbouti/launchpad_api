<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseRequest extends FormRequest
{
    // Authorization
    public function authorize(): bool
    {
        return true;
    }

    // Rules
    abstract public function rules(): array;

    // Messages
    public function messages(): array
    {
        return [];
    }

    // Attributes
    public function attributes(): array
    {
        return [];
    }
}
