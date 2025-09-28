<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\LocationData;
use App\Models\LocationEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GPSTrackerController extends Controller
{
    /**
     * Display the GPS Tracker dashboard.
     */
    public function index(Request $request)
    {
        // Obtener parámetros de fecha
        $startDate = $request->get('start_date', now()->startOfDay());
        $endDate = $request->get('end_date', now()->endOfDay());

        // Estadísticas generales
        $totalLocations = LocationData::whereBetween('recorded_at', [$startDate, $endDate])->count();
        $totalEvents = LocationEvent::whereBetween('event_time', [$startDate, $endDate])->count();

        // Estadísticas por tipo de evento
        $eventStats = LocationEvent::whereBetween('event_time', [$startDate, $endDate])
            ->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->pluck('count', 'event_type');

        // Datos recientes de ubicación (últimas 1000 posiciones)
        $recentLocations = LocationData::whereBetween('recorded_at', [$startDate, $endDate])
            ->orderBy('recorded_at', 'desc')
            ->limit(1000)
            ->get();

        // Eventos recientes
        $recentEvents = LocationEvent::with('locationData')
            ->whereBetween('event_time', [$startDate, $endDate])
            ->orderBy('event_time', 'desc')
            ->limit(100)
            ->get();

        // Datos para gráficos
        $chartData = $this->getChartData($startDate, $endDate);

        // Calcular velocidad promedio y máxima
        $speedStats = LocationData::whereBetween('recorded_at', [$startDate, $endDate])
            ->whereNotNull('speed')
            ->select(
                DB::raw('AVG(speed) as avg_speed'),
                DB::raw('MAX(speed) as max_speed'),
                DB::raw('MIN(speed) as min_speed')
            )
            ->first();

        // Datos de ubicación para el mapa (últimas 24 horas)
        $mapLocations = LocationData::where('recorded_at', '>=', now()->subDay())
            ->orderBy('recorded_at', 'desc')
            ->limit(500)
            ->get(['latitude', 'longitude', 'speed', 'recorded_at']);

        return view('content.dashboard.gps-tracker', compact(
            'totalLocations',
            'totalEvents',
            'eventStats',
            'recentLocations',
            'recentEvents',
            'chartData',
            'speedStats',
            'mapLocations',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Get data for charts.
     */
    private function getChartData($startDate, $endDate)
    {
        // Datos de velocidad por hora
        $speedByHour = LocationData::whereBetween('recorded_at', [$startDate, $endDate])
            ->whereNotNull('speed')
            ->select(
                DB::raw('HOUR(recorded_at) as hour'),
                DB::raw('AVG(speed) as avg_speed'),
                DB::raw('MAX(speed) as max_speed')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Eventos por hora
        $eventsByHour = LocationEvent::whereBetween('event_time', [$startDate, $endDate])
            ->select(
                DB::raw('HOUR(event_time) as hour'),
                DB::raw('count(*) as count')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour');

        // Eventos por tipo
        $eventsByType = LocationEvent::whereBetween('event_time', [$startDate, $endDate])
            ->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->get();

        return [
            'speedByHour' => $speedByHour,
            'eventsByHour' => $eventsByHour,
            'eventsByType' => $eventsByType
        ];
    }

    /**
     * Get location data for API.
     */
    public function getLocationData(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfDay());
        $endDate = $request->get('end_date', now()->endOfDay());
        $limit = $request->get('limit', 1000);

        $locations = LocationData::whereBetween('recorded_at', [$startDate, $endDate])
            ->orderBy('recorded_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $locations
        ]);
    }

    /**
     * Get events data for API.
     */
    public function getEventsData(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfDay());
        $endDate = $request->get('end_date', now()->endOfDay());
        $eventType = $request->get('event_type');
        $limit = $request->get('limit', 100);

        $query = LocationEvent::whereBetween('event_time', [$startDate, $endDate]);

        if ($eventType) {
            $query->where('event_type', $eventType);
        }

        $events = $query->orderBy('event_time', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * Export data to CSV.
     */
    public function exportData(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfDay());
        $endDate = $request->get('end_date', now()->endOfDay());
        $dataType = $request->get('data_type', 'locations'); // locations or events

        $filename = "gps_{$dataType}_" . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($startDate, $endDate, $dataType) {
            $handle = fopen('php://output', 'w');

            if ($dataType === 'locations') {
                // Headers para datos de ubicación
                fputcsv($handle, ['ID', 'Fecha', 'Latitud', 'Longitud', 'Velocidad (km/h)', 'Precisión (m)', 'Altitud (m)']);

                $locations = LocationData::whereBetween('recorded_at', [$startDate, $endDate])
                    ->orderBy('recorded_at')
                    ->get();

                foreach ($locations as $location) {
                    fputcsv($handle, [
                        $location->id,
                        $location->recorded_at->format('Y-m-d H:i:s'),
                        $location->latitude,
                        $location->longitude,
                        $location->speed ?? '',
                        $location->accuracy ?? '',
                        $location->altitude ?? ''
                    ]);
                }
            } else {
                // Headers para eventos
                fputcsv($handle, ['ID', 'Tipo', 'Fecha', 'Latitud', 'Longitud', 'Velocidad (km/h)', 'Descripción']);

                $events = LocationEvent::whereBetween('event_time', [$startDate, $endDate])
                    ->orderBy('event_time')
                    ->get();

                foreach ($events as $event) {
                    fputcsv($handle, [
                        $event->id,
                        $event->event_type,
                        $event->event_time->format('Y-m-d H:i:s'),
                        $event->latitude,
                        $event->longitude,
                        $event->speed ?? '',
                        $event->description ?? ''
                    ]);
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get dashboard statistics.
     */
    public function getStatistics(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfDay());
        $endDate = $request->get('end_date', now()->endOfDay());

        // Estadísticas generales
        $stats = [
            'total_locations' => LocationData::whereBetween('recorded_at', [$startDate, $endDate])->count(),
            'total_events' => LocationEvent::whereBetween('event_time', [$startDate, $endDate])->count(),
            'speeding_events' => LocationEvent::whereBetween('event_time', [$startDate, $endDate])
                ->where('event_type', 'speeding')->count(),
            'acceleration_events' => LocationEvent::whereBetween('event_time', [$startDate, $endDate])
                ->where('event_type', 'harsh_acceleration')->count(),
            'braking_events' => LocationEvent::whereBetween('event_time', [$startDate, $endDate])
                ->where('event_type', 'harsh_braking')->count(),
            'crash_events' => LocationEvent::whereBetween('event_time', [$startDate, $endDate])
                ->where('event_type', 'crash')->count(),
        ];

        // Velocidad promedio y máxima
        $speedData = LocationData::whereBetween('recorded_at', [$startDate, $endDate])
            ->whereNotNull('speed')
            ->select(
                DB::raw('AVG(speed) as avg_speed'),
                DB::raw('MAX(speed) as max_speed')
            )
            ->first();

        $stats['avg_speed'] = round($speedData->avg_speed ?? 0, 2);
        $stats['max_speed'] = round($speedData->max_speed ?? 0, 2);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
