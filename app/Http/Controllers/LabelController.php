<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Requests\LabelGenerateRequest;
use App\Services\LabelService;
use Illuminate\Http\JsonResponse;

final class LabelController extends BaseController {
    public function __construct(
        private readonly LabelService $labelService,
    ) {}

    public function generate(LabelGenerateRequest $request): JsonResponse {
        $validated  = $request->validated();
        $entityType = $validated['entity_type'];
        $entityIds  = $validated['entity_ids'];
        $options    = (array) ($validated['options'] ?? []);

        $format        = mb_strtolower((string) ($validated['format'] ?? 'zpl'));
        $payload       = $this->labelService->generate($format, $entityType, $entityIds, $options);
        $artifact      = null;
        $artifactPaths = null;

        return response()->json([
            'status'  => 'success',
            'message' => 'succ.label.generated',
            'data'    => [
                'format'         => $format,
                'payload'        => $payload,
                'artifact_path'  => $artifact,
                'artifact_paths' => $artifactPaths,
            ],
        ]);
    }
}
