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
        Schema::create('gps_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gps_location_id')->nullable()->constrained('gps_locations')->onDelete('cascade');
            $table->string('event_type');
            $table->timestamp('event_time');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->decimal('max_speed', 8, 2)->nullable();
            $table->decimal('acceleration', 8, 2)->nullable();
            $table->decimal('impact_force', 8, 2)->nullable();
            $table->text('description')->nullable();
            $table->json('event_data')->nullable();
            $table->boolean('notified')->default(false);
            $table->string('session_id')->nullable();
            $table->boolean('synced')->default(false);
            $table->timestamps();

            // Índices
            $table->index(['session_id', 'synced']);
            $table->index(['event_type', 'event_time']);
            $table->index('event_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gps_events');
    }
};
