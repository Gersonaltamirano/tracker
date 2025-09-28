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
        Schema::create('location_data', function (Blueprint $table) {
            $table->id();
            $table->timestamp('recorded_at');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('speed', 8, 2)->nullable(); // velocidad en km/h
            $table->decimal('accuracy', 8, 2)->nullable(); // precisión en metros
            $table->decimal('altitude', 8, 2)->nullable(); // altitud en metros
            $table->decimal('heading', 5, 2)->nullable(); // dirección en grados
            $table->json('device_info')->nullable(); // información del dispositivo
            $table->boolean('synced')->default(false); // indica si ya se sincronizó con el servidor
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_data');
    }
};
