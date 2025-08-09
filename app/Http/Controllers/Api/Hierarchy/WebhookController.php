<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hierarchy;

use App\Http\Controllers\Api\BaseController;
use App\Http\Middleware\ApiResponseMiddleware;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends BaseController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly WebhookService $webhookService,
    ) {}

    /**
     * Handle Stripe webhook events
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $eventType = $payload['type'] ?? '';

        $result = match ($eventType) {
            'invoice.payment_succeeded' => $this->webhookService->handlePaymentSucceeded($payload),
            'invoice.paid' => $this->webhookService->handlePaymentSucceeded($payload),
            'invoice.payment_failed' => $this->webhookService->handlePaymentFailed($payload),
            default => true
        };

        return ApiResponseMiddleware::success(
            ['processed' => $result],
            'webhook.processed'
        );
    }
}
