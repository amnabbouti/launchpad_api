<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Attachment;

use App\Http\Controllers\Api\BaseController;
use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\AttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttachmentController extends BaseController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly AttachmentService $attachmentService,
    ) {}

    /**
     * Get attachments with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $this->attachmentService->processRequestParams($request->query());
        $query = $this->attachmentService->getFiltered($filters);
        $attachments = $this->paginated($query, $request);

        return ApiResponseMiddleware::listResponse(
            AttachmentResource::collection($attachments),
            'attachment',
            $attachments->total()
        );
    }

    /**
     * Create a new attachment.
     */
    public function store(AttachmentRequest $request): JsonResponse
    {
        $attachment = $this->attachmentService->createAttachment($request->validated());

        return ApiResponseMiddleware::createResponse(
            new AttachmentResource($attachment),
            'attachment',
            $attachment->toArray()
        );
    }

    /**
     * Get a specific attachment.
     */
    public function show(int $id): JsonResponse
    {
        $with = array_filter(explode(',', request()->query('with', '')));
        $attachment = $this->attachmentService->find($id, $with);

        return ApiResponseMiddleware::showResponse(
            new AttachmentResource($attachment),
            'attachment',
            $attachment->toArray()
        );
    }

    /**
     * Update an attachment.
     */
    public function update(AttachmentRequest $request, int $id): JsonResponse
    {
        $updatedAttachment = $this->attachmentService->updateAttachment($id, $request->validated());

        return ApiResponseMiddleware::updateResponse(
            new AttachmentResource($updatedAttachment),
            'attachment',
            $updatedAttachment->toArray()
        );
    }

    /**
     * Delete an attachment.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->attachmentService->deleteAttachment($id);

        return ApiResponseMiddleware::deleteResponse('attachment');
    }

    /**
     * Get attachment type options for UI dropdowns.
     */
    public function getTypeOptions(): JsonResponse
    {
        $options = $this->attachmentService->getAttachmentTypeOptions();

        return ApiResponseMiddleware::showResponse([
            'options' => $options,
            'instructions' => [
                'step_1' => 'Select an attachment type',
                'step_2' => 'Enter the specific entity ID (e.g., item ID, user ID)',
                'step_3' => 'Upload',
            ],
        ], 'attachment');
    }

    /**
     * Get attachment usage statistics.
     */
    public function getStats(int $id): JsonResponse
    {
        $stats = $this->attachmentService->getAttachmentStats($id);

        return ApiResponseMiddleware::showResponse([
            'stats' => $stats,
            'attachment_id' => $id,
        ], 'attachment');
    }
}
