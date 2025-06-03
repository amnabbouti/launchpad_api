<?php

namespace App\Http\Requests;

class AttachmentRequest extends BaseRequest
{
    /**
     * validation rules.
     */
    public function rules(): array
    {
        $isUpdate = $this->getMethod() === 'PUT' || $this->getMethod() === 'PATCH';
        $attachmentId = $this->route('attachment')?->id ?? null;

        return [
            'file' => $isUpdate ? 'sometimes|file|max:10240' : 'required|file|max:10240',
            'filename' => 'sometimes|string|max:255',
            'original_filename' => 'sometimes|string|max:255',
            'file_type' => 'sometimes|string|max:255',
            'extension' => 'nullable|string|max:10',
            'size' => 'sometimes|integer|min:0',
            'file_path' => 'sometimes|string',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:255',
            'user_id' => 'sometimes|exists:users,id',
            'org_id' => 'required|exists:organizations,id',
            'attachmentable_type' => 'sometimes|string|max:100',
            'attachmentable_id' => 'sometimes|integer|min:1',
        ];
    }

    /**
     * error messages.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'A file is required for upload.',
            'file.file' => 'The uploaded file is not valid.',
            'file.max' => 'The file size cannot exceed 10MB.',
            'filename.string' => 'The filename must be a string.',
            'filename.max' => 'The filename cannot exceed 255 characters.',
            'original_filename.string' => 'The original filename must be a string.',
            'original_filename.max' => 'The original filename cannot exceed 255 characters.',
            'file_type.string' => 'The file type must be a string.',
            'file_type.max' => 'The file type cannot exceed 255 characters.',
            'extension.string' => 'The file extension must be a string.',
            'extension.max' => 'The file extension cannot exceed 10 characters.',
            'size.integer' => 'The file size must be a number.',
            'size.min' => 'The file size cannot be negative.',
            'file_path.string' => 'The file path must be a string.',
            'description.string' => 'The description must be a string.',
            'description.max' => 'The description cannot exceed 1000 characters.',
            'category.string' => 'The category must be a string.',
            'category.max' => 'The category cannot exceed 255 characters.',
            'user_id.exists' => 'The selected user does not exist.',
            'org_id.required' => 'Organization ID is required.',
            'org_id.exists' => 'The selected organization does not exist.',
            'attachmentable_type.string' => 'The attachmentable type must be a string.',
            'attachmentable_type.max' => 'The attachmentable type cannot exceed 100 characters.',
            'attachmentable_id.integer' => 'The attachmentable ID must be a number.',
            'attachmentable_id.min' => 'The attachmentable ID must be positive.',
        ];
    }
}
