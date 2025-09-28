<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('location_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // 'speeding', 'harsh_acceleration', 'harsh_braking', 'crash'
            $table->timestamp('event_time');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('speed', 8, 2)->nullable(); // velocidad en el momento del evento
            $table->decimal('max_speed', 8, 2)->nullable(); // velocidad máxima permitida
            $table->decimal('acceleration', 8, 2)->nullable(); // aceleración en m/s²
            $table->decimal('impact_force', 8, 2)->nullable(); // fuerza de impacto para choques
            $table->text('description')->nullable(); // descripción del evento
            $table->json('event_data')->nullable(); // datos adicionales del evento
            $table->boolean('notified')->default(false); // indica si ya se notificó al usuario
            $table->boolean('synced')->default(false); // indica si ya se sincronizó con el servidor
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_events');
    }
};
