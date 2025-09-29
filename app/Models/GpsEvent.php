<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpsEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'gps_location_id',
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
        'session_id'
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
        'notified' => 'boolean'
    ];

    public function location()
    {
        return $this->belongsTo(GpsLocation::class);
    }
}