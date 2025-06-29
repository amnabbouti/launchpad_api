<?php

namespace App\Http\Requests;

class ThreatDetectionRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    protected function getValidationRules(): array
    {
        return [
            'time_range' => 'nullable|integer|min:1|max:168',
            'severity' => 'nullable|in:low,medium,high,critical',
            'ip_address' => 'nullable|ip',
            'threat_type' => 'nullable|in:brute_force,endpoint_scanning,high_error_rate,auth_failure_spike,rate_limit_exceeded',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'time_range' => 'time range',
            'severity' => 'severity level',
            'ip_address' => 'IP address',
            'threat_type' => 'threat type',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'time_range.max' => 'Time range cannot exceed 1 week (168 hours)',
            'severity.in' => 'Severity must be one of: low, medium, high, critical',
            'threat_type.in' => 'Invalid threat type specified',
        ];
    }
} 