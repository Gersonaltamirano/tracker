<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationData;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class LocationDataController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = LocationData::query();

        // Filtros opcionales
        if ($request->has('start_date')) {
            $query->where('recorded_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('recorded_at', '<=', $request->end_date);
        }

        if ($request->has('synced')) {
            $query->where('synced', $request->boolean('synced'));
        }

        $limit = $request->get('limit', 100);
        $locationData = $query->orderBy('recorded_at', 'desc')->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $locationData
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recorded_at' => 'required|date',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed' => 'nullable|numeric|min:0',
            'accuracy' => 'nullable|numeric|min:0',
            'altitude' => 'nullable|numeric',
            'heading' => 'nullable|numeric|between:0,360',
            'device_info' => 'nullable|array'
        ]);

        $locationData = LocationData::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Datos de ubicación guardados correctamente',
            'data' => $locationData
        ], 201);
    }

    /**
     * Store multiple location data records (batch insert).
     */
    public function batchStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locations' => 'required|array|min:1|max:1000',
            'locations.*.recorded_at' => 'required|date',
            'locations.*.latitude' => 'required|numeric|between:-90,90',
            'locations.*.longitude' => 'required|numeric|between:-180,180',
            'locations.*.speed' => 'nullable|numeric|min:0',
            'locations.*.accuracy' => 'nullable|numeric|min:0',
            'locations.*.altitude' => 'nullable|numeric',
            'locations.*.heading' => 'nullable|numeric|between:0,360',
            'locations.*.device_info' => 'nullable|array'
        ]);

        $locations = [];
        foreach ($validated['locations'] as $location) {
            $locations[] = [
                'recorded_at' => $location['recorded_at'],
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'speed' => $location['speed'] ?? null,
                'accuracy' => $location['accuracy'] ?? null,
                'altitude' => $location['altitude'] ?? null,
                'heading' => $location['heading'] ?? null,
                'device_info' => $location['device_info'] ?? null,
                'synced' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        LocationData::insert($locations);

        return response()->json([
            'success' => true,
            'message' => count($locations) . ' registros de ubicación guardados correctamente'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(LocationData $locationData): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $locationData
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LocationData $locationData): JsonResponse
    {
        $validated = $request->validate([
            'recorded_at' => 'sometimes|required|date',
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
            'speed' => 'sometimes|nullable|numeric|min:0',
            'accuracy' => 'sometimes|nullable|numeric|min:0',
            'altitude' => 'sometimes|nullable|numeric',
            'heading' => 'sometimes|nullable|numeric|between:0,360',
            'device_info' => 'sometimes|nullable|array',
            'synced' => 'sometimes|boolean'
        ]);

        $locationData->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Datos de ubicación actualizados correctamente',
            'data' => $locationData
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LocationData $locationData): JsonResponse
    {
        $locationData->delete();

        return response()->json([
            'success' => true,
            'message' => 'Datos de ubicación eliminados correctamente'
        ]);
    }

    /**
     * Get unsynced records for mobile app.
     */
    public function getUnsynced(): JsonResponse
    {
        $unsyncedData = LocationData::unsynced()
            ->orderBy('recorded_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $unsyncedData
        ]);
    }

    /**
     * Mark records as synced.
     */
    public function markAsSynced(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:location_data,id'
        ]);

        LocationData::whereIn('id', $validated['ids'])->update(['synced' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Registros marcados como sincronizados'
        ]);
    }
}
