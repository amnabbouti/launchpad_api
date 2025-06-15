<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class CheckInOutRequest extends BaseRequest
{
    /**
     * validation rules.
     */
    public function rules(): array
    {
        $checkInOutId = $this->route('id') ?? null;
        $rules = [
            'org_id' => 'required|exists:organizations,id',
        ];

        $route = $this->route()->getName();

        if (in_array($route, ['checks.out', 'checks.out-with-components'])) {
            $rules = array_merge($rules, [
                'user_id' => 'required|exists:users,id',
                'checkout_location_id' => 'required|string',
                'checkout_quantity' => 'required|numeric|min:1',
                'checkout_date' => 'nullable|date',
                'status_out_id' => 'nullable|exists:statuses,id',
                'expected_return_date' => 'nullable|date|after:today',
                'notes' => 'nullable|string|max:500',
                'include_components' => 'nullable|boolean',
                'reference' => [
                    'nullable',
                    'string',
                    'max:100',
                    Rule::unique('check_ins_outs')
                        ->where('org_id', $this->org_id)
                        ->ignore($checkInOutId),
                ],
            ]);
        } elseif ($route === 'checks.in') {
            $rules = array_merge($rules, [
                'checkin_user_id' => 'required',
                'checkin_location_id' => 'required',
                'checkin_quantity' => 'nullable|numeric|min:1',
                'checkin_date' => 'nullable|date',
                'status_in_id' => 'nullable|exists:statuses,public_id',
                'notes' => 'nullable|string|max:500',
            ]);
        }

        return $rules;
    }

    /**
     * error messages.
     */
    public function messages(): array
    {
        return [
            'org_id.required' => 'Organization ID is required',
            'org_id.exists' => 'The selected organization does not exist',
            'item_id.required' => 'The item is required',
            'item_id.exists' => 'The selected item does not exist',
            'user_id.required' => 'The user is required',
            'user_id.exists' => 'The selected user does not exist',
            'checkout_location_id.required' => 'The checkout location is required',
            'checkout_location_id.exists' => 'The selected checkout location does not exist',
            'checkin_user_id.required' => 'The check-in user is required',
            'checkin_user_id.exists' => 'The selected check-in user does not exist',
            'checkin_location_id.required' => 'The check-in location is required',
            'checkin_location_id.exists' => 'The selected check-in location does not exist',
            'checkout_quantity.required' => 'The checkout quantity is required',
            'checkout_quantity.numeric' => 'The checkout quantity must be a number',
            'checkout_quantity.min' => 'The checkout quantity must be at least 1',
            'checkin_quantity.numeric' => 'The checkin quantity must be a number',
            'checkin_quantity.min' => 'The checkin quantity must be at least 1',
            'checkout_date.date' => 'The checkout date must be a valid date',
            'checkin_date.date' => 'The check-in date must be a valid date',
            'expected_return_date.date' => 'The expected return date must be a valid date',
            'expected_return_date.after' => 'The expected return date must be after today',
            'reference.unique' => 'The reference is already used for this organization',
            'notes.max' => 'The notes field is too long',
        ];
    }
}
