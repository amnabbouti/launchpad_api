<?php

namespace App\Http\Controllers\Api\Attachment;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\AttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;

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
    public function index(): JsonResponse
    {
        $rawParams = request()->query();
        $processed = $this->attachmentService->processRequestParams($rawParams);
        $attachments = $this->attachmentService->getFiltered($processed);

        return $this->successResponse(AttachmentResource::collection($attachments));
    }

    /**
     * Create a new attachment.
     */
    public function store(AttachmentRequest $request): JsonResponse
    {
        $attachment = $this->attachmentService->createAttachment($request->validated());

        return $this->successResponse(
            new AttachmentResource($attachment),
            'Attachment created successfully',
            self::HTTP_CREATED,
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
            'Attachment updated successfully',
        );
    }

    /**
     * Delete an attachment.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->attachmentService->deleteAttachment($id);

        return $this->successResponse(null, 'Attachment deleted successfully');
    }
}
