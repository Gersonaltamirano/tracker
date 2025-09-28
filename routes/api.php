<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LocationDataController;
use App\Http\Controllers\Api\LocationEventController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas para datos de ubicación
Route::prefix('location-data')->group(function () {
    Route::get('/', [LocationDataController::class, 'index']);
    Route::post('/', [LocationDataController::class, 'store']);
    Route::post('/batch', [LocationDataController::class, 'batchStore']);
    Route::get('/unsynced', [LocationDataController::class, 'getUnsynced']);
    Route::post('/mark-synced', [LocationDataController::class, 'markAsSynced']);
    Route::get('/{locationData}', [LocationDataController::class, 'show']);
    Route::put('/{locationData}', [LocationDataController::class, 'update']);
    Route::delete('/{locationData}', [LocationDataController::class, 'destroy']);
});

// Rutas para eventos de ubicación
Route::prefix('location-events')->group(function () {
    Route::get('/', [LocationEventController::class, 'index']);
    Route::post('/', [LocationEventController::class, 'store']);
    Route::post('/batch', [LocationEventController::class, 'batchStore']);
    Route::get('/unnotified', [LocationEventController::class, 'getUnnotified']);
    Route::post('/mark-notified', [LocationEventController::class, 'markAsNotified']);
    Route::get('/statistics', [LocationEventController::class, 'getStatistics']);
    Route::get('/type/{eventType}', [LocationEventController::class, 'getByType']);
    Route::get('/{locationEvent}', [LocationEventController::class, 'show']);
    Route::put('/{locationEvent}', [LocationEventController::class, 'update']);
    Route::delete('/{locationEvent}', [LocationEventController::class, 'destroy']);
});