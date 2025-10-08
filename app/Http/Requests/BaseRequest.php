<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\AuthorizationHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

abstract class BaseRequest extends FormRequest
{
    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Get validation rules - with global GET request handling.
     */
    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return [];
        }

        return $this->getValidationRules();
    }

    /**
     * Get the actual validation rules (implemented by child classes).
     */
    abstract protected function getValidationRules(): array;

    protected function prepareForValidation(): void
    {
        if (!$this->has('org_id') || !$this->input('org_id')) {
            $tempModel = new class extends Model {
                public $org_id = null;
            };
            AuthorizationHelper::autoAssignOrganization($tempModel);
            if ($tempModel->org_id) {
                $this->merge(['org_id' => $tempModel->org_id]);
            }
        }
    }
}
