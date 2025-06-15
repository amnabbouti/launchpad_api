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
            'file' => $isUpdate 
                ? 'sometimes|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif,bmp,svg,zip,rar' 
                : 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif,bmp,svg,zip,rar',
            'filename' => 'sometimes|string|max:255',
            'original_filename' => 'sometimes|string|max:255',
            'file_type' => 'sometimes|string|max:255',
            'extension' => 'nullable|string|max:10|regex:/^[a-zA-Z0-9]+$/',
            'size' => 'sometimes|integer|min:0|max:10485760',
            'file_path' => 'sometimes|string',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:255',
            'user_id' => 'sometimes|exists:users,id',
            // Note: org_id is auto-injected from authenticated user in BaseRequest::prepareForValidation()
            'org_id' => 'required|exists:organizations,id',
            'attachmentable_type' => [
                'required',
                'string',
                'max:255',
            ],
            'attachmentable_id' => [
                'required',
                'integer',
                'min:1',
            ],
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
            'file.mimes' => 'The file must be a valid type: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, JPG, JPEG, PNG, GIF, BMP, SVG, ZIP, or RAR.',
            'filename.string' => 'The filename must be a string.',
            'filename.max' => 'The filename cannot exceed 255 characters.',
            'original_filename.string' => 'The original filename must be a string.',
            'original_filename.max' => 'The original filename cannot exceed 255 characters.',
            'file_type.string' => 'The file type must be a string.',
            'file_type.max' => 'The file type cannot exceed 255 characters.',
            'extension.string' => 'The file extension must be a string.',
            'extension.max' => 'The file extension cannot exceed 10 characters.',
            'extension.regex' => 'The file extension can only contain letters and numbers.',
            'size.integer' => 'The file size must be a number.',
            'size.min' => 'The file size cannot be negative.',
            'size.max' => 'The file size cannot exceed 10MB (10485760 bytes).',
            'file_path.string' => 'The file path must be a string.',
            'description.string' => 'The description must be a string.',
            'description.max' => 'The description cannot exceed 1000 characters.',
            'category.string' => 'The category must be a string.',
            'category.max' => 'The category cannot exceed 255 characters.',
            'user_id.exists' => 'The selected user does not exist.',
            'org_id.required' => 'Organization ID is required.',
            'org_id.exists' => 'The selected organization does not exist.',
            'attachmentable_type.required' => 'You must specify what entity this attachment belongs to. Use GET /api/attachments/supported-types to see available options.',
            'attachmentable_type.string' => 'The entity type must be a string.',
            'attachmentable_type.max' => 'The entity type cannot exceed 255 characters.',
            'attachmentable_id.required' => 'You must specify which specific entity this attachment belongs to.',
            'attachmentable_id.integer' => 'The entity ID must be a number.',
            'attachmentable_id.min' => 'The entity ID must be positive.',
        ];
    }
}
