<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseRequest extends FormRequest
{
    // if the user is authorized to make request
    public function authorize(): bool
    {
        // By default, authorize all
        // Override in child classes
        return true;
    }

    // validation rules
    abstract public function rules(): array;

    // custom messages for validator errors
    public function messages(): array
    {
        return [];
    }

    // custom attributes for validator errors
    public function attributes(): array
    {
        return [];
    }
}
