<?php

use App\Http\Controllers\Api\ApiEndpointController;
use App\Http\Controllers\Api\DataCollectorController;
use App\Http\Controllers\Api\PostmanCollectionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API Endpoints Management
Route::apiResource('endpoints', ApiEndpointController::class)
    ->parameters(['endpoints' => 'apiEndpoint']);

// Postman Collections Management
Route::apiResource('collections', PostmanCollectionController::class)
    ->parameters(['collections' => 'postmanCollection']);

// Data Collection
Route::prefix('collect')->group(function () {
    Route::post('/all', [DataCollectorController::class, 'collectAll']);
    Route::post('/endpoint/{apiEndpoint}', [DataCollectorController::class, 'collectFromEndpoint']);
    Route::post('/github', [DataCollectorController::class, 'importFromGitHub']);
});

// Responses & Statistics
Route::get('/responses', [DataCollectorController::class, 'getResponses']);
Route::get('/stats', [DataCollectorController::class, 'getStats']);
