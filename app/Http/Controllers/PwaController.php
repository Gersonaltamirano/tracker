<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\GpsLocation;
use App\Models\GpsEvent;

class PwaController extends Controller
{
    public function index()
    {
        return view('pwa.index');
    }

    public function saveLocationData(Request $request)
    {
        try {
            Log::info('PWA: Saving location data', $request->all());

            $location = GpsLocation::create([
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'speed' => $request->input('speed', 0),
                'accuracy' => $request->input('accuracy'),
                'altitude' => $request->input('altitude'),
                'heading' => $request->input('heading'),
                'recorded_at' => now(),
                'device_info' => $request->input('device_info', []),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId()
            ]);

            Log::info('PWA: Location saved successfully', ['id' => $location->id]);

            return response()->json([
                'success' => true,
                'id' => $location->id,
                'message' => 'Ubicación guardada correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('PWA: Error saving location', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error guardando ubicación: ' . $e->getMessage()
            ], 500);
        }
    }

    public function saveEventData(Request $request)
    {
        try {
            Log::info('PWA: Saving event data', $request->all());

            $event = GpsEvent::create([
                'event_type' => $request->input('event_type'),
                'event_time' => now(),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'speed' => $request->input('speed'),
                'max_speed' => $request->input('max_speed'),
                'acceleration' => $request->input('acceleration'),
                'impact_force' => $request->input('impact_force'),
                'description' => $request->input('description'),
                'event_data' => $request->all(),
                'notified' => false,
                'session_id' => session()->getId()
            ]);

            Log::info('PWA: Event saved successfully', ['id' => $event->id]);

            return response()->json([
                'success' => true,
                'id' => $event->id,
                'message' => 'Evento guardado correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('PWA: Error saving event', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error guardando evento: ' . $e->getMessage()
            ], 500);
        }
    }

    public function batchLocationData(Request $request)
    {
        try {
            $locations = $request->input('locations', []);
            Log::info('PWA: Batch saving locations', ['count' => count($locations)]);

            $savedLocations = [];
            foreach ($locations as $locationData) {
                $location = GpsLocation::create([
                    'latitude' => $locationData['latitude'],
                    'longitude' => $locationData['longitude'],
                    'speed' => $locationData['speed'] ?? 0,
                    'accuracy' => $locationData['accuracy'] ?? null,
                    'altitude' => $locationData['altitude'] ?? null,
                    'heading' => $locationData['heading'] ?? null,
                    'recorded_at' => $locationData['recorded_at'] ?? now(),
                    'device_info' => $locationData['device_info'] ?? [],
                    'user_agent' => $request->userAgent(),
                    'session_id' => session()->getId()
                ]);
                $savedLocations[] = $location;
            }

            Log::info('PWA: Batch locations saved', ['count' => count($savedLocations)]);

            return response()->json([
                'success' => true,
                'saved_count' => count($savedLocations),
                'message' => 'Ubicaciones guardadas correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('PWA: Error batch saving locations', [
                'error' => $e->getMessage(),
                'count' => count($request->input('locations', []))
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error guardando ubicaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    public function batchEventData(Request $request)
    {
        try {
            $events = $request->input('events', []);
            Log::info('PWA: Batch saving events', ['count' => count($events)]);

            $savedEvents = [];
            foreach ($events as $eventData) {
                $event = GpsEvent::create([
                    'event_type' => $eventData['event_type'],
                    'event_time' => $eventData['event_time'] ?? now(),
                    'latitude' => $eventData['latitude'] ?? null,
                    'longitude' => $eventData['longitude'] ?? null,
                    'speed' => $eventData['speed'] ?? null,
                    'max_speed' => $eventData['max_speed'] ?? null,
                    'acceleration' => $eventData['acceleration'] ?? null,
                    'impact_force' => $eventData['impact_force'] ?? null,
                    'description' => $eventData['description'] ?? '',
                    'event_data' => $eventData,
                    'notified' => false,
                    'session_id' => session()->getId()
                ]);
                $savedEvents[] = $event;
            }

            Log::info('PWA: Batch events saved', ['count' => count($savedEvents)]);

            return response()->json([
                'success' => true,
                'saved_count' => count($savedEvents),
                'message' => 'Eventos guardados correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('PWA: Error batch saving events', [
                'error' => $e->getMessage(),
                'count' => count($request->input('events', []))
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error guardando eventos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getConfiguration(Request $request)
    {
        $config = session('gpsTrackerConfig', [
            'maxSpeed' => 95,
            'interval' => 60,
            'sensitivity' => 'medium',
            'notifications' => true,
            'autoStart' => true
        ]);

        return response()->json([
            'success' => true,
            'config' => $config
        ]);
    }

    public function saveConfiguration(Request $request)
    {
        try {
            $config = $request->validate([
                'maxSpeed' => 'integer|min:1|max:200',
                'interval' => 'integer|min:5|max:300',
                'sensitivity' => 'in:low,medium,high',
                'notifications' => 'boolean',
                'autoStart' => 'boolean'
            ]);

            session(['gpsTrackerConfig' => $config]);

            Log::info('PWA: Configuration saved', $config);

            return response()->json([
                'success' => true,
                'config' => $config,
                'message' => 'Configuración guardada correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('PWA: Error saving configuration', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error guardando configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    public function manifest()
    {
        $manifest = [
            "name" => "GPS Tracker - Sistema de Monitoreo",
            "short_name" => "GPS Tracker",
            "description" => "Aplicación para monitorear velocidad y ubicación GPS",
            "start_url" => "/pwa/",
            "display" => "standalone",
            "background_color" => "#ffffff",
            "theme_color" => "#007bff",
            "orientation" => "portrait-primary",
            "categories" => ["navigation", "utilities"],
            "lang" => "es",
            "version" => "1.0.0",
            "icons" => [
                [
                    "src" => "/pwa/icons/icon-72x72.png",
                    "sizes" => "72x72",
                    "type" => "image/png",
                    "purpose" => "any maskable"
                ],
                [
                    "src" => "/pwa/icons/icon-96x96.png",
                    "sizes" => "96x96",
                    "type" => "image/png",
                    "purpose" => "any maskable"
                ],
                [
                    "src" => "/pwa/icons/icon-128x128.png",
                    "sizes" => "128x128",
                    "type" => "image/png",
                    "purpose" => "any maskable"
                ],
                [
                    "src" => "/pwa/icons/icon-144x144.png",
                    "sizes" => "144x144",
                    "type" => "image/png",
                    "purpose" => "any maskable"
                ],
                [
                    "src" => "/pwa/icons/icon-152x152.png",
                    "sizes" => "152x152",
                    "type" => "image/png",
                    "purpose" => "any maskable"
                ],
                [
                    "src" => "/pwa/icons/icon-192x192.png",
                    "sizes" => "192x192",
                    "type" => "image/png",
                    "purpose" => "any maskable"
                ],
                [
                    "src" => "/pwa/icons/icon-384x384.png",
                    "sizes" => "384x384",
                    "type" => "image/png",
                    "purpose" => "any maskable"
                ],
                [
                    "src" => "/pwa/icons/icon-512x512.png",
                    "sizes" => "512x512",
                    "type" => "image/png",
                    "purpose" => "any maskable"
                ]
            ]
        ];

        return response()->json($manifest);
    }
}