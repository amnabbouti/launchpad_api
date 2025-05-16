<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UserRequest extends BaseRequest
{
    // Rules
    public function rules(): array
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user),
            ],
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
        ];

        // Password rules
        if ($this->isMethod('post')) {
            $rules['password'] = 'required|string|min:8|confirmed';
        } elseif ($this->filled('password')) {
            $rules['password'] = 'string|min:8|confirmed';
        }

        return $rules;
    }

    // Messages
    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already in use',
            'password.confirmed' => 'The password confirmation does not match',
        ];
    }
}
