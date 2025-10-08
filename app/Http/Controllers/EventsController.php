<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Requests\EventsRequest;
use App\Http\Resources\ItemHistoryEventResource;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;

class EventsController extends BaseController {
    public function __construct(
        private readonly EventService $eventService,
    ) {}

    /**
     * Get all events with filtering.
     */
    public function index(EventsRequest $request): JsonResponse {
        $validated = $request->validated();

        $query  = $this->eventService->getItemEventHistory(null, $validated['event_types'] ?? [], $validated);
        $events = $this->paginated($query, $request);

        return ApiResponseMiddleware::listResponse(
            ItemHistoryEventResource::collection($events),
            'events',
            $events->total(),
        );
    }

    /**
     * Get all events for a specific item with filtering.
     */
    public function itemAllEvents(EventsRequest $request, string $itemId): JsonResponse {
        $validated = $request->validated();

        $query = $this->eventService->getItemEventHistory(
            $itemId,
            $validated['event_types'] ?? [],
            $validated,
        );
        $events = $this->paginated($query, $request);

        return ApiResponseMiddleware::listResponse(
            ItemHistoryEventResource::collection($events),
            'events',
            $events->total(),
        );
    }

    /**
     * Get check-in/out events for a specific item.
     */
    public function itemCheckInOut(EventsRequest $request, string $itemId): JsonResponse {
        $validated = $request->validated();
        $query     = $this->eventService->getItemEventHistory($itemId, ['check_in', 'check_out'], $validated);
        $events    = $this->paginated($query, $request);

        return ApiResponseMiddleware::listResponse(
            ItemHistoryEventResource::collection($events),
            'checkinout_events',
            $events->total(),
        );
    }

    /**
     * Get event history for a specific item.
     */
    public function itemHistory(EventsRequest $request, string $itemId): JsonResponse {
        $validated  = $request->validated();
        $eventTypes = isset($validated['event_type']) ? [$validated['event_type']] : [];

        $query  = $this->eventService->getItemEventHistory($itemId, $eventTypes, $validated);
        $events = $this->paginated($query, $request);

        return ApiResponseMiddleware::listResponse(
            ItemHistoryEventResource::collection($events),
            'events',
            $events->total(),
        );
    }

    /**
     * Get maintenance events for a specific item.
     */
    public function itemMaintenance(EventsRequest $request, string $itemId): JsonResponse {
        $validated = $request->validated();
        $query     = $this->eventService->getItemEventHistory($itemId, ['maintenance_start', 'maintenance_end'], $validated);
        $events    = $this->paginated($query, $request);

        return ApiResponseMiddleware::listResponse(
            ItemHistoryEventResource::collection($events),
            'maintenance_events',
            $events->total(),
        );
    }

    /**
     * Get movement events for a specific item.
     */
    public function itemMovements(EventsRequest $request, string $itemId): JsonResponse {
        $validated = $request->validated();
        $query     = $this->eventService->getItemEventHistory($itemId, ['movement'], $validated);
        $events    = $this->paginated($query, $request);

        return ApiResponseMiddleware::listResponse(
            ItemHistoryEventResource::collection($events),
            'movement_events',
            $events->total(),
        );
    }

    /**
     * Get system-wide events with filtering (admin/reporting).
     */
    public function systemEvents(EventsRequest $request): JsonResponse {
        $validated = $request->validated();

        $query  = $this->eventService->getItemEventHistory(null, $validated['event_types'] ?? [], $validated);
        $events = $this->paginated($query, $request);

        return ApiResponseMiddleware::listResponse(
            ItemHistoryEventResource::collection($events),
            'system_events',
            $events->total(),
        );
    }
}
