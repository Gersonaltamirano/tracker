<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpsLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'latitude',
        'longitude',
        'speed',
        'accuracy',
        'altitude',
        'heading',
        'recorded_at',
        'device_info',
        'user_agent',
        'session_id'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'speed' => 'decimal:2',
        'accuracy' => 'decimal:2',
        'altitude' => 'decimal:2',
        'heading' => 'decimal:2',
        'recorded_at' => 'datetime',
        'device_info' => 'array'
    ];

    public function events()
    {
        return $this->hasMany(GpsEvent::class);
    }
}