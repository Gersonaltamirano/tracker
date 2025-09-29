<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\GpsLocation;
use App\Models\GpsEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GpsTracker extends Component
{
    public $currentLocation = null;
    public $eventCounts = [
        'speeding' => 0,
        'acceleration' => 0,
        'braking' => 0,
        'crash' => 0
    ];
    public $recentEvents = [];
    public $isTracking = false;
    public $sessionId;

    // Configuración
    public $config = [
        'maxSpeed' => 95,
        'interval' => 60,
        'sensitivity' => 'medium',
        'notifications' => true,
        'autoStart' => true
    ];

    public function mount()
    {
        $this->sessionId = session()->getId();
        $this->loadConfiguration();
        $this->loadEventCounts();
        $this->loadRecentEvents();
    }

    public function loadConfiguration()
    {
        $savedConfig = session('gpsTrackerConfig');
        if ($savedConfig) {
            $this->config = array_merge($this->config, $savedConfig);
        }
    }

    public function loadEventCounts()
    {
        $this->eventCounts = [
            'speeding' => GpsEvent::where('session_id', $this->sessionId)
                                ->where('event_type', 'speeding')
                                ->count(),
            'acceleration' => GpsEvent::where('session_id', $this->sessionId)
                                    ->where('event_type', 'harsh_acceleration')
                                    ->count(),
            'braking' => GpsEvent::where('session_id', $this->sessionId)
                              ->where('event_type', 'harsh_braking')
                              ->count(),
            'crash' => GpsEvent::where('session_id', $this->sessionId)
                            ->where('event_type', 'crash')
                            ->count()
        ];
    }

    public function loadRecentEvents()
    {
        $this->recentEvents = GpsEvent::where('session_id', $this->sessionId)
            ->with('location')
            ->latest()
            ->limit(50)
            ->get()
            ->toArray();
    }

    public function saveLocationData($data)
    {
        try {
            Log::info('Saving location data:', $data);

            $location = GpsLocation::create([
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'speed' => $data['speed'] ?? 0,
                'accuracy' => $data['accuracy'] ?? null,
                'altitude' => $data['altitude'] ?? null,
                'heading' => $data['heading'] ?? null,
                'recorded_at' => $data['recorded_at'],
                'device_info' => $data['device_info'] ?? [],
                'user_agent' => request()->userAgent(),
                'session_id' => $this->sessionId
            ]);

            $this->currentLocation = $location->toArray();

            Log::info('Location saved successfully:', ['id' => $location->id]);

            // Emitir evento para actualizar la UI
            $this->dispatch('location-updated', $location->toArray());

            return $location;
        } catch (\Exception $e) {
            Log::error('Error saving location data:', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            $this->dispatch('error-occurred', 'Error guardando ubicación: ' . $e->getMessage());
        }
    }

    public function saveEventData($data)
    {
        try {
            Log::info('Saving event data:', $data);

            $event = GpsEvent::create([
                'event_type' => $data['event_type'],
                'event_time' => $data['event_time'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'speed' => $data['speed'] ?? null,
                'max_speed' => $data['max_speed'] ?? null,
                'acceleration' => $data['acceleration'] ?? null,
                'impact_force' => $data['impact_force'] ?? null,
                'description' => $data['description'],
                'event_data' => $data,
                'notified' => false,
                'session_id' => $this->sessionId
            ]);

            // Actualizar contador
            $this->eventCounts[$data['event_type']] = ($this->eventCounts[$data['event_type']] ?? 0) + 1;

            // Recargar eventos recientes
            $this->loadRecentEvents();

            Log::info('Event saved successfully:', ['id' => $event->id]);

            // Emitir evento para notificación
            $this->dispatch('event-created', $event->toArray());

            return $event;
        } catch (\Exception $e) {
            Log::error('Error saving event data:', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            $this->dispatch('error-occurred', 'Error guardando evento: ' . $e->getMessage());
        }
    }

    public function saveConfiguration($config)
    {
        try {
            $this->config = array_merge($this->config, $config);
            session(['gpsTrackerConfig' => $this->config]);

            Log::info('Configuration saved:', $this->config);

            $this->dispatch('config-saved', $this->config);
        } catch (\Exception $e) {
            Log::error('Error saving configuration:', $e->getMessage());
            $this->dispatch('error-occurred', 'Error guardando configuración');
        }
    }

    public function clearEvents()
    {
        try {
            GpsEvent::where('session_id', $this->sessionId)->delete();

            $this->eventCounts = [
                'speeding' => 0,
                'acceleration' => 0,
                'braking' => 0,
                'crash' => 0
            ];

            $this->recentEvents = [];

            Log::info('Events cleared for session:', $this->sessionId);

            $this->dispatch('events-cleared');
        } catch (\Exception $e) {
            Log::error('Error clearing events:', $e->getMessage());
            $this->dispatch('error-occurred', 'Error limpiando eventos');
        }
    }

    public function startTracking()
    {
        $this->isTracking = true;
        $this->dispatch('tracking-started');
    }

    public function stopTracking()
    {
        $this->isTracking = false;
        $this->dispatch('tracking-stopped');
    }

    public function render()
    {
        return view('livewire.gps-tracker');
    }
}