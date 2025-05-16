<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemLocationController extends Controller
{
    // List all item-location assignments (optional, for admin)
    public function index(): JsonResponse
    {
        $itemLocations = ItemLocation::with(['item', 'location'])->get();

        $result = $itemLocations->map(function ($il) {
            return [
                'id' => $il->id,
                'item_id' => $il->item_id,
                'item_code' => $il->item->code ?? null,
                'location_id' => $il->location_id,
                'location_name' => $il->location->name ?? null,
                'quantity' => $il->quantity,
                'created_at' => $il->created_at,
                'updated_at' => $il->updated_at,
                'deleted_at' => $il->deleted_at,
            ];
        });

        return response()->json($result);
    }

    // Assign an item to a location
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_id' => 'required|exists:items,id',
            'location_id' => 'required|exists:locations,id',
            'quantity' => 'required|numeric|min:0',
        ]);
        $itemLocation = ItemLocation::create($data);

        return response()->json($itemLocation, 201);
    }

    // Get a specific item-location assignment
    public function show($id): JsonResponse
    {
        $il = ItemLocation::with(['item', 'location'])->findOrFail($id);

        return response()->json([
            'id' => $il->id,
            'item_id' => $il->item_id,
            'item_code' => $il->item->code ?? null,
            'location_id' => $il->location_id,
            'location_name' => $il->location->name ?? null,
            'quantity' => $il->quantity,
            'created_at' => $il->created_at,
            'updated_at' => $il->updated_at,
            'deleted_at' => $il->deleted_at,
        ]);
    }

    // Update an item-location assignment (e.g., quantity)
    public function update(Request $request, $id): JsonResponse
    {
        $itemLocation = ItemLocation::findOrFail($id);
        $data = $request->validate([
            'quantity' => 'required|numeric|min:0',
        ]);
        $itemLocation->update($data);

        return response()->json($itemLocation);
    }

    // Remove an item from a location
    public function destroy($id): JsonResponse
    {
        $itemLocation = ItemLocation::findOrFail($id);
        $itemLocation->delete();

        return response()->json(null, 204);
    }
}
