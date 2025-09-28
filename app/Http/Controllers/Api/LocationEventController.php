<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class LocationEventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = LocationEvent::query();

        // Filtros opcionales
        if ($request->has('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->has('start_date')) {
            $query->where('event_time', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('event_time', '<=', $request->end_date);
        }

        if ($request->has('notified')) {
            $query->where('notified', $request->boolean('notified'));
        }

        if ($request->has('synced')) {
            $query->where('synced', $request->boolean('synced'));
        }

        $limit = $request->get('limit', 50);
        $events = $query->orderBy('event_time', 'desc')->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_type' => 'required|string|in:speeding,harsh_acceleration,harsh_braking,crash',
            'event_time' => 'required|date',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed' => 'nullable|numeric|min:0',
            'max_speed' => 'nullable|numeric|min:0',
            'acceleration' => 'nullable|numeric',
            'impact_force' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'event_data' => 'nullable|array'
        ]);

        $event = LocationEvent::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Evento de ubicación guardado correctamente',
            'data' => $event
        ], 201);
    }

    /**
     * Store multiple events (batch insert).
     */
    public function batchStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'events' => 'required|array|min:1|max:500',
            'events.*.event_type' => 'required|string|in:speeding,harsh_acceleration,harsh_braking,crash',
            'events.*.event_time' => 'required|date',
            'events.*.latitude' => 'required|numeric|between:-90,90',
            'events.*.longitude' => 'required|numeric|between:-180,180',
            'events.*.speed' => 'nullable|numeric|min:0',
            'events.*.max_speed' => 'nullable|numeric|min:0',
            'events.*.acceleration' => 'nullable|numeric',
            'events.*.impact_force' => 'nullable|numeric|min:0',
            'events.*.description' => 'nullable|string|max:500',
            'events.*.event_data' => 'nullable|array'
        ]);

        $events = [];
        foreach ($validated['events'] as $event) {
            $events[] = [
                'event_type' => $event['event_type'],
                'event_time' => $event['event_time'],
                'latitude' => $event['latitude'],
                'longitude' => $event['longitude'],
                'speed' => $event['speed'] ?? null,
                'max_speed' => $event['max_speed'] ?? null,
                'acceleration' => $event['acceleration'] ?? null,
                'impact_force' => $event['impact_force'] ?? null,
                'description' => $event['description'] ?? null,
                'event_data' => $event['event_data'] ?? null,
                'synced' => true,
                'notified' => false,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        LocationEvent::insert($events);

        return response()->json([
            'success' => true,
            'message' => count($events) . ' eventos guardados correctamente'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(LocationEvent $locationEvent): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $locationEvent
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LocationEvent $locationEvent): JsonResponse
    {
        $validated = $request->validate([
            'event_type' => 'sometimes|required|string|in:speeding,harsh_acceleration,harsh_braking,crash',
            'event_time' => 'sometimes|required|date',
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
            'speed' => 'sometimes|nullable|numeric|min:0',
            'max_speed' => 'sometimes|nullable|numeric|min:0',
            'acceleration' => 'sometimes|nullable|numeric',
            'impact_force' => 'sometimes|nullable|numeric|min:0',
            'description' => 'sometimes|nullable|string|max:500',
            'event_data' => 'sometimes|nullable|array',
            'notified' => 'sometimes|boolean',
            'synced' => 'sometimes|boolean'
        ]);

        $locationEvent->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Evento actualizado correctamente',
            'data' => $locationEvent
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LocationEvent $locationEvent): JsonResponse
    {
        $locationEvent->delete();

        return response()->json([
            'success' => true,
            'message' => 'Evento eliminado correctamente'
        ]);
    }

    /**
     * Get events by type.
     */
    public function getByType(string $eventType, Request $request): JsonResponse
    {
        $validTypes = ['speeding', 'harsh_acceleration', 'harsh_braking', 'crash'];

        if (!in_array($eventType, $validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de evento no válido'
            ], 400);
        }

        $query = LocationEvent::byType($eventType);

        if ($request->has('start_date')) {
            $query->where('event_time', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('event_time', '<=', $request->end_date);
        }

        $limit = $request->get('limit', 50);
        $events = $query->orderBy('event_time', 'desc')->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * Get unnotified events.
     */
    public function getUnnotified(): JsonResponse
    {
        $unnotifiedEvents = LocationEvent::unnotified()
            ->orderBy('event_time', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $unnotifiedEvents
        ]);
    }

    /**
     * Mark events as notified.
     */
    public function markAsNotified(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:location_events,id'
        ]);

        LocationEvent::whereIn('id', $validated['ids'])->update(['notified' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Eventos marcados como notificados'
        ]);
    }

    /**
     * Get event statistics.
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', now()->startOfDay());
        $endDate = $request->get('end_date', now()->endOfDay());

        $statistics = LocationEvent::whereBetween('event_time', [$startDate, $endDate])
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->get()
            ->pluck('count', 'event_type');

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate
                ],
                'statistics' => $statistics,
                'total' => $statistics->sum()
            ]
        ]);
    }
}
