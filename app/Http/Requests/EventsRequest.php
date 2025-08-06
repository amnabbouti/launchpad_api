<?php

declare(strict_types=1);

namespace App\Http\Requests;

class EventsRequest extends BaseRequest
{
    /**
     * Get validation rules for events filtering.
     */
    protected function getValidationRules(): array
    {
        return [
            'event_types' => 'nullable|array',
            'event_types.*' => 'string|in:movement,check_in,check_out,maintenance_start,maintenance_end,status_change,quantity_adjustment,initial_placement,transfer,system_update',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'user_id' => 'nullable|string',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'event_types.array' => 'Event types must be an array',
            'event_types.*.string' => 'Each event type must be a string',
            'event_types.*.in' => 'Invalid event type. Allowed types: movement, check_in, check_out, maintenance_start, maintenance_end, status_change, quantity_adjustment, initial_placement, transfer, system_update',
            'date_from.date' => 'Start date must be a valid date',
            'date_to.date' => 'End date must be a valid date',
            'date_to.after_or_equal' => 'End date must be after or equal to start date',
            'user_id.string' => 'User ID must be a string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'event_types' => 'event types',
            'date_from' => 'start date',
            'date_to' => 'end date',
            'user_id' => 'user ID',
        ];
    }
}
