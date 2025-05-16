<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CheckInOutResource;
use App\Models\CheckInOut;
use App\Models\Item;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CheckInOutController extends Controller
{
    // Constants for status codes
    private const HTTP_CREATED = 201;
    private const HTTP_OK = 200;
    private const HTTP_CONFLICT = 409;
    private const HTTP_FORBIDDEN = 403;
    private const HTTP_SERVER_ERROR = 500;

    // Checkout
    public function checkout(Request $request, int $itemId): CheckInOutResource|JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'quantity' => 'required|numeric|min:1',
            'checkout_location_id' => 'required|exists:locations,id',
            'expected_return_date' => 'nullable|date',
            'status_out_id' => 'nullable|exists:stock_statuses,id',
            'notes' => 'nullable|string',
        ])->validate();

        $user = Auth::user();
        
        // Load active maintenances
        $item = Item::with(['maintenances' => function ($q) {
            $q->whereNull('date_back_from_maintenance');
        }])->findOrFail($itemId);

        // Verify checkout availability
        $activeCheckout = CheckInOut::where('item_id', $item->id)
            ->whereNull('checkin_date')
            ->first();
            
        if ($activeCheckout) {
            return $this->errorResponse('Item is already checked out', self::HTTP_CONFLICT);
        }
        
        if ($item->maintenances && $item->maintenances->count() > 0) {
            return $this->errorResponse('Item is in maintenance', self::HTTP_CONFLICT);
        }

        $checkInOut = CheckInOut::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'checkout_location_id' => $validated['checkout_location_id'],
            'checkout_date' => now(),
            'quantity' => $validated['quantity'],
            'status_out_id' => $validated['status_out_id'] ?? null,
            'expected_return_date' => $validated['expected_return_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
        ]);

        return new CheckInOutResource($checkInOut->fresh([
            'user', 'checkoutLocation', 'statusOut',
        ]));
    }

    // Checkin
    public function checkin(Request $request, int $itemId): CheckInOutResource|JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'checkin_location_id' => 'required|exists:locations,id',
            'checkin_quantity' => 'nullable|numeric|min:1',
            'status_in_id' => 'nullable|exists:stock_statuses,id',
            'notes' => 'nullable|string',
        ])->validate();

        $user = Auth::user();
        $item = Item::findOrFail($itemId);

        $activeCheckout = CheckInOut::where('item_id', $item->id)
            ->whereNull('checkin_date')
            ->first();
            
        if (! $activeCheckout) {
            return $this->errorResponse('Item is not checked out', self::HTTP_CONFLICT);
        }

        // Authorization check
        if ($activeCheckout->user_id !== $user->id && ! $user->is_admin) {
            return $this->errorResponse('Not authorized to check in this item', self::HTTP_FORBIDDEN);
        }

        $activeCheckout->update([
            'checkin_user_id' => $user->id,
            'checkin_location_id' => $validated['checkin_location_id'],
            'checkin_date' => now(),
            'checkin_quantity' => $validated['checkin_quantity'] ?? $activeCheckout->quantity,
            'status_in_id' => $validated['status_in_id'] ?? null,
            'notes' => $validated['notes'] ?? $activeCheckout->notes,
            'is_active' => false,
        ]);

        return new CheckInOutResource($activeCheckout->fresh([
            'user', 'checkinUser', 'checkinLocation', 'statusIn',
        ]));
    }

    // History
    public function history(Request $request, int $itemId): AnonymousResourceCollection|JsonResponse
    {
        try {
            $item = Item::findOrFail($itemId);

            // Query by item_id
            $history = CheckInOut::where('item_id', $item->id)
                ->with([
                    'user', 
                    'checkinUser', 
                    'checkoutLocation', 
                    'checkinLocation', 
                    'statusOut', 
                    'statusIn',
                ])
                ->orderByDesc('checkout_date')
                ->paginate($request->get('per_page', 15));

            return CheckInOutResource::collection($history);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while fetching checkout history', self::HTTP_SERVER_ERROR);
        }
    }

    // Error response helper
    private function errorResponse(string $message, int $statusCode): JsonResponse
    {
        return response()->json(['message' => $message], $statusCode);
    }
}
