<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiEndpoint;
use App\Models\ApiResponse;
use App\Services\ApiCollectorService;
use App\Services\GitHubApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataCollectorController extends Controller
{
    protected ApiCollectorService $collectorService;
    protected GitHubApiService $githubService;

    public function __construct(ApiCollectorService $collectorService, GitHubApiService $githubService)
    {
        $this->collectorService = $collectorService;
        $this->githubService = $githubService;
    }

    public function collectFromEndpoint(ApiEndpoint $apiEndpoint): JsonResponse
    {
        $response = $this->collectorService->collectFromEndpoint($apiEndpoint);

        return response()->json([
            'message' => 'Data collected successfully',
            'response' => $response,
        ]);
    }

    public function collectAll(): JsonResponse
    {
        $responses = $this->collectorService->collectFromAllActiveEndpoints();

        $successful = collect($responses)->filter(fn($r) => $r && $r->is_successful)->count();
        $failed = collect($responses)->filter(fn($r) => $r && !$r->is_successful)->count();

        return response()->json([
            'message' => 'Data collection completed',
            'total' => count($responses),
            'successful' => $successful,
            'failed' => $failed,
            'responses' => $responses,
        ]);
    }

    public function importFromGitHub(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'owner' => 'required|string',
            'repo' => 'required|string',
            'file_path' => 'nullable|string',
            'token' => 'nullable|string',
        ]);

        try {
            if (isset($validated['token'])) {
                $this->githubService->setToken($validated['token']);
            }

            if (isset($validated['file_path'])) {
                $endpoints = $this->githubService->importFromOpenApiSpec(
                    $validated['owner'],
                    $validated['repo'],
                    $validated['file_path']
                );
            } else {
                $endpoints = $this->githubService->importEndpointsFromRepository(
                    $validated['owner'],
                    $validated['repo']
                );
            }

            return response()->json([
                'message' => 'Endpoints imported from GitHub',
                'count' => count($endpoints),
                'endpoints' => $endpoints,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to import from GitHub',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function getResponses(Request $request): JsonResponse
    {
        $query = ApiResponse::with('endpoint');

        if ($request->has('endpoint_id')) {
            $query->where('api_endpoint_id', $request->endpoint_id);
        }

        if ($request->has('successful')) {
            $request->boolean('successful') ? $query->successful() : $query->failed();
        }

        if ($request->has('status_code')) {
            $query->byStatusCode($request->status_code);
        }

        $responses = $query->latest('collected_at')->paginate($request->get('per_page', 15));

        return response()->json($responses);
    }

    public function getStats(): JsonResponse
    {
        $totalEndpoints = ApiEndpoint::count();
        $activeEndpoints = ApiEndpoint::active()->count();
        $totalResponses = ApiResponse::count();
        $successfulResponses = ApiResponse::successful()->count();
        $failedResponses = ApiResponse::failed()->count();

        $avgResponseTime = ApiResponse::avg('response_time');

        $responsesByStatusCode = ApiResponse::selectRaw('status_code, count(*) as count')
            ->groupBy('status_code')
            ->get()
            ->pluck('count', 'status_code');

        $endpointsBySource = ApiEndpoint::selectRaw('source, count(*) as count')
            ->groupBy('source')
            ->get()
            ->pluck('count', 'source');

        return response()->json([
            'endpoints' => [
                'total' => $totalEndpoints,
                'active' => $activeEndpoints,
                'by_source' => $endpointsBySource,
            ],
            'responses' => [
                'total' => $totalResponses,
                'successful' => $successfulResponses,
                'failed' => $failedResponses,
                'avg_response_time' => round($avgResponseTime ?? 0, 3),
                'by_status_code' => $responsesByStatusCode,
            ],
        ]);
    }
}
