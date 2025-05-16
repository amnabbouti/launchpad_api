<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaintenanceController extends Controller
{
    // All
    public function index()
    {
        $maintenances = Maintenance::all();
        return response()->json($maintenances);
    }

    // Show
    public function show($id)
    {
        $maintenance = Maintenance::findOrFail($id);
        return response()->json($maintenance);
    }

    // Create
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,id',
            'user_id' => 'required|exists:users,id',
            'status' => 'required|string',
            'description' => 'nullable|string',
            'started_at' => 'required|date',
            'ended_at' => 'nullable|date|after_or_equal:started_at',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check active maintenance
        if ($request->status === 'active') {
            $active = Maintenance::where('item_id', $request->item_id)
                ->where('status', 'active')
                ->exists();
            if ($active) {
                return response()->json(['error' => 'This item already has an active maintenance.'], 409);
            }
        }

        $maintenance = Maintenance::create($request->all());

        return response()->json($maintenance, 201);
    }

    // Update
    public function update(Request $request, $id)
    {
        $maintenance = Maintenance::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'item_id' => 'sometimes|exists:items,id',
            'user_id' => 'sometimes|exists:users,id',
            'status' => 'sometimes|string',
            'description' => 'nullable|string',
            'started_at' => 'sometimes|date',
            'ended_at' => 'nullable|date|after_or_equal:started_at',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check active maintenance
        if ($request->has('status') && $request->status === 'active') {
            $active = Maintenance::where('item_id', $request->item_id ?? $maintenance->item_id)
                ->where('status', 'active')
                ->where('id', '!=', $maintenance->id)
                ->exists();
            if ($active) {
                return response()->json(['error' => 'This item already has an active maintenance.'], 409);
            }
        }

        $maintenance->update($request->all());

        return response()->json($maintenance);
    }

    // Delete
    public function destroy($id)
    {
        $maintenance = Maintenance::findOrFail($id);
        $maintenance->delete();

        return response()->json(['message' => 'Maintenance deleted successfully.']);
    }
}
