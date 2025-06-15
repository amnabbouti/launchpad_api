<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UserRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Allow GET requests to skip validation for flexible filtering
        if ($this->isMethod('GET')) {
            return [];
        }

        $userId = $this->route('user');
        if (is_object($userId) && method_exists($userId, 'getKey')) {
            $userId = $userId->getKey();
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],
            'password' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'string',
                'min:8',
                'confirmed',
            ],
            'password_confirmation' => [
                $this->filled('password') ? 'required' : 'sometimes',
                'string',
            ],
            'role_id' => [
                'sometimes',
                'integer',
                Rule::exists('roles', 'id'),
            ],
            'org_id' => $this->getOrgIdValidationRules(),
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not be greater than 255 characters.',

            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'The email has already been taken.',
            'email.max' => 'The email may not be greater than 255 characters.',

            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',

            'role_id.required' => 'The role field is required.',
            'role_id.exists' => 'The selected role is invalid.',

            'org_id.exists' => 'The selected organization is invalid.',
        ];
    }

    /**
     * Get validated data for user creation/update.
     */
    public function validatedForUser(): array
    {
        $validated = $this->validated();

        // Remove password_confirmation as it's not needed for model
        unset($validated['password_confirmation']);

        return $validated;
    }

    /**
     * Get org_id validation rules.
     * Pure validation only - business logic handled in service layer.
     */
    protected function getOrgIdValidationRules(): array
    {
        // Simple validation - org_id can be nullable or integer with exists check
        // The business logic for whether it's required based on role is handled in UserService
        return [
            'nullable',
            'integer',
            Rule::exists('organizations', 'id')
        ];
    }


}
