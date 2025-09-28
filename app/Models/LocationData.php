<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LocationData extends Model
{
    use HasFactory;

    protected $fillable = [
        'recorded_at',
        'latitude',
        'longitude',
        'speed',
        'accuracy',
        'altitude',
        'heading',
        'device_info',
        'synced'
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'speed' => 'decimal:2',
        'accuracy' => 'decimal:2',
        'altitude' => 'decimal:2',
        'heading' => 'decimal:2',
        'device_info' => 'array',
        'synced' => 'boolean'
    ];

    /**
     * Scope para obtener solo registros no sincronizados
     */
    public function scopeUnsynced($query)
    {
        return $query->where('synced', false);
    }

    /**
     * Scope para obtener registros en un rango de fechas
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }
}
