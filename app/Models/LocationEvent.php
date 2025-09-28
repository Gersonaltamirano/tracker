<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LocationEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'event_time',
        'latitude',
        'longitude',
        'speed',
        'max_speed',
        'acceleration',
        'impact_force',
        'description',
        'event_data',
        'notified',
        'synced'
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'speed' => 'decimal:2',
        'max_speed' => 'decimal:2',
        'acceleration' => 'decimal:2',
        'impact_force' => 'decimal:2',
        'event_data' => 'array',
        'notified' => 'boolean',
        'synced' => 'boolean'
    ];

    /**
     * Constantes para tipos de eventos
     */
    const TYPE_SPEEDING = 'speeding';
    const TYPE_HARSH_ACCELERATION = 'harsh_acceleration';
    const TYPE_HARSH_BRAKING = 'harsh_braking';
    const TYPE_CRASH = 'crash';

    /**
     * Scope para obtener solo eventos no sincronizados
     */
    public function scopeUnsynced($query)
    {
        return $query->where('synced', false);
    }

    /**
     * Scope para obtener eventos no notificados
     */
    public function scopeUnnotified($query)
    {
        return $query->where('notified', false);
    }

    /**
     * Scope para obtener eventos por tipo
     */
    public function scopeByType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope para obtener eventos en un rango de fechas
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('event_time', [$startDate, $endDate]);
    }
}
