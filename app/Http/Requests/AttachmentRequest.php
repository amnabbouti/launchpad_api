<?php

declare(strict_types = 1);

namespace App\Http\Requests;

use App\Constants\AppConstants;

class AttachmentRequest extends BaseRequest {
    /**
     * Error messages
     */
    public function messages(): array {
        $maxSizeMB         = AppConstants::MAX_UPLOAD_SIZE / 1024 / 1024;
        $allowedExtensions = implode(', ', AppConstants::SUPPORTED_ATTACHMENT_EXTENSIONS);

        return [
            'file.file'                    => 'The uploaded file is not valid',
            'file.max'                     => "The file size cannot exceed {$maxSizeMB}MB",
            'file.mimes'                   => "The file must be one of the following types: {$allowedExtensions}",
            'filename.string'              => 'The filename must be a string',
            'filename.max'                 => 'The filename cannot exceed ' . AppConstants::NAME_MAX_LENGTH . ' characters',
            'original_filename.string'     => 'The original filename must be a string',
            'original_filename.max'        => 'The original filename cannot exceed ' . AppConstants::NAME_MAX_LENGTH . ' characters',
            'file_type.string'             => 'The file type must be a string',
            'file_type.max'                => 'The file type cannot exceed ' . AppConstants::NAME_MAX_LENGTH . ' characters',
            'extension.string'             => 'The file extension must be a string',
            'extension.max'                => 'The file extension cannot exceed 10 characters',
            'extension.regex'              => 'The file extension can only contain letters and numbers',
            'extension.in'                 => "The file extension must be one of: {$allowedExtensions}",
            'size.integer'                 => 'The file size must be a number',
            'size.min'                     => 'The file size cannot be negative',
            'size.max'                     => "The file size cannot exceed {$maxSizeMB}MB (" . AppConstants::MAX_UPLOAD_SIZE . ' bytes)',
            'file_path.string'             => 'The file path must be a string',
            'description.string'           => 'The description must be a string',
            'description.max'              => 'The description cannot exceed ' . AppConstants::DESCRIPTION_MAX_LENGTH . ' characters',
            'category.string'              => 'The category must be a string',
            'category.max'                 => 'The category cannot exceed ' . AppConstants::NAME_MAX_LENGTH . ' characters',
            'user_id.exists'               => 'The selected user does not exist',
            'org_id.required'              => 'Organization ID is required',
            'org_id.exists'                => 'The selected organization does not exist',
            'attachmentable_type.required' => 'You must specify what entity this attachment belongs to',
            'attachmentable_type.string'   => 'The entity type must be a string',
            'attachmentable_type.max'      => 'The entity type cannot exceed ' . AppConstants::NAME_MAX_LENGTH . ' characters',
            'attachmentable_id.required'   => 'You must specify which specific entity this attachment belongs to',
            'attachmentable_id.string'     => 'The entity ID must be a string',
            'attachmentable_id.min'        => 'The entity ID must be positive',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void {
        $validator->after(function ($validator): void {
            $data     = $validator->getData();
            $isUpdate = $this->route('attachment') !== null;

            // These fields are required for creation but optional for updates
            if (! $isUpdate) {
                if (empty($data['org_id'])) {
                    $validator->errors()->add('org_id', 'Organization ID is required.');
                }
                if (empty($data['attachmentable_type'])) {
                    $validator->errors()->add('attachmentable_type', 'You must specify what entity this attachment belongs to.');
                }
                if (empty($data['attachmentable_id'])) {
                    $validator->errors()->add('attachmentable_id', 'You must specify which specific entity this attachment belongs to.');
                }
            }
        });
    }

    /**
     * Validation rules
     */
    protected function getValidationRules(): array {
        $allowedExtensions = implode(',', AppConstants::SUPPORTED_ATTACHMENT_EXTENSIONS);
        $maxSizeInKb       = AppConstants::MAX_UPLOAD_SIZE / 1024;

        return [
            'file'                => "nullable|file|max:{$maxSizeInKb}|mimes:{$allowedExtensions}",
            'filename'            => 'nullable|string|max:' . AppConstants::NAME_MAX_LENGTH,
            'original_filename'   => 'nullable|string|max:' . AppConstants::NAME_MAX_LENGTH,
            'file_type'           => 'nullable|string|max:' . AppConstants::NAME_MAX_LENGTH,
            'extension'           => 'nullable|string|max:10|regex:/^[a-zA-Z0-9]+$/|in:' . $allowedExtensions,
            'size'                => 'nullable|integer|min:0|max:' . AppConstants::MAX_UPLOAD_SIZE,
            'file_path'           => 'nullable|string',
            'description'         => 'nullable|string|max:' . AppConstants::DESCRIPTION_MAX_LENGTH,
            'category'            => 'nullable|string|max:' . AppConstants::NAME_MAX_LENGTH,
            'user_id'             => 'nullable|exists:users,id',
            'org_id'              => 'nullable|string|exists:organizations,id',
            'attachmentable_type' => 'nullable|string|max:' . AppConstants::NAME_MAX_LENGTH,
            'attachmentable_id'   => 'nullable|string|min:1',
        ];
    }
}
