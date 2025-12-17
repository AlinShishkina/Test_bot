<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiEndpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiEndpointController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ApiEndpoint::query();

        if ($request->has('source')) {
            $query->bySource($request->source);
        }

        if ($request->has('active')) {
            $request->boolean('active') ? $query->active() : $query->where('is_active', false);
        }

        $endpoints = $query->with('responses')->paginate($request->get('per_page', 15));

        return response()->json($endpoints);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'method' => 'required|string|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            'url' => 'required|url',
            'headers' => 'nullable|array',
            'query_params' => 'nullable|array',
            'body' => 'nullable|array',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['source'] = 'manual';

        $endpoint = ApiEndpoint::create($validated);

        return response()->json($endpoint, 201);
    }

    public function show(ApiEndpoint $apiEndpoint): JsonResponse
    {
        return response()->json($apiEndpoint->load(['responses' => function ($query) {
            $query->latest('collected_at')->limit(10);
        }]));
    }

    public function update(Request $request, ApiEndpoint $apiEndpoint): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'method' => 'sometimes|string|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            'url' => 'sometimes|url',
            'headers' => 'nullable|array',
            'query_params' => 'nullable|array',
            'body' => 'nullable|array',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $apiEndpoint->update($validated);

        return response()->json($apiEndpoint);
    }

    public function destroy(ApiEndpoint $apiEndpoint): JsonResponse
    {
        $apiEndpoint->delete();

        return response()->json(null, 204);
    }
}
