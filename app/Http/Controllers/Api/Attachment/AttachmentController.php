<?php

namespace App\Http\Controllers\Api\Attachment;

use App\Constants\HttpStatus;
use App\Constants\SuccessMessages;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\AttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttachmentController extends BaseController
{
    protected AttachmentService $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * Get attachments with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $this->attachmentService->processRequestParams($request->query());
        $attachments = $this->attachmentService->getFiltered($filters);

        // Determine appropriate message
        $message = $attachments->isEmpty() 
            ? 'No attachments found' 
            : SuccessMessages::RESOURCES_RETRIEVED;

        return $this->successResponse(
            AttachmentResource::collection($attachments),
            $message
        );
    }

    /**
     * Create a new attachment.
     */
    public function store(AttachmentRequest $request): JsonResponse
    {
        $attachment = $this->attachmentService->createAttachment($request->validated());

        return $this->successResponse(
            new AttachmentResource($attachment),
            SuccessMessages::RESOURCE_CREATED,
            HttpStatus::HTTP_CREATED,
        );
    }

    /**
     * Get a specific attachment.
     */
    public function show(int $id): JsonResponse
    {
        $with = array_filter(explode(',', request()->query('with', '')));
        $attachment = $this->attachmentService->find($id, $with);

        return $this->successResponse(new AttachmentResource($attachment));
    }

    /**
     * Update an attachment.
     */
    public function update(AttachmentRequest $request, int $id): JsonResponse
    {
        $updatedAttachment = $this->attachmentService->updateAttachment($id, $request->validated());

        return $this->successResponse(
            new AttachmentResource($updatedAttachment),
            SuccessMessages::RESOURCE_UPDATED,
        );
    }

    /**
     * Delete an attachment.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->attachmentService->deleteAttachment($id);

        return $this->successResponse(null, SuccessMessages::RESOURCE_DELETED);
    }

    /**
     * Get attachment type options for UI dropdowns.
     */
    public function getTypeOptions(): JsonResponse
    {
        $options = $this->attachmentService->getAttachmentTypeOptions();

        return $this->successResponse([
            'options' => $options,
            'instructions' => [
                'step_1' => 'Select an attachment type',
                'step_2' => 'Enter the specific entity ID (e.g., item ID, user ID)', 
                'step_3' => 'Upload'
            ]
        ], SuccessMessages::OPTIONS_RETRIEVED);
    }
}
