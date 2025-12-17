<?php

namespace App\Services;

use App\Models\ApiEndpoint;
use App\Models\ApiResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ApiCollectorService
{
    protected bool $verbose = false;

    public function setVerbose(bool $verbose): self
    {
        $this->verbose = $verbose;
        return $this;
    }

    public function collectFromEndpoint(ApiEndpoint $endpoint): ?ApiResponse
    {
        $this->log("Starting collection from endpoint: {$endpoint->name}");

        $startTime = microtime(true);

        try {
            $response = $this->makeRequest($endpoint);
            $endTime = microtime(true);
            $responseTime = $endTime - $startTime;

            $apiResponse = ApiResponse::create([
                'api_endpoint_id' => $endpoint->id,
                'status_code' => $response->status(),
                'response_headers' => $response->headers(),
                'response_body' => $response->body(),
                'response_time' => $responseTime,
                'collected_at' => Carbon::now(),
                'is_successful' => $response->successful(),
                'error_message' => $response->successful() ? null : $response->reason(),
            ]);

            $this->log("Successfully collected data. Status: {$response->status()}, Time: {$responseTime}s");

            return $apiResponse;
        } catch (\Exception $e) {
            $this->log("Error collecting from endpoint: " . $e->getMessage(), 'error');

            return ApiResponse::create([
                'api_endpoint_id' => $endpoint->id,
                'status_code' => 0,
                'response_headers' => null,
                'response_body' => null,
                'response_time' => microtime(true) - $startTime,
                'collected_at' => Carbon::now(),
                'is_successful' => false,
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function collectFromAllActiveEndpoints(): array
    {
        $this->log("Starting collection from all active endpoints");

        $endpoints = ApiEndpoint::active()->get();
        $results = [];

        foreach ($endpoints as $endpoint) {
            $results[] = $this->collectFromEndpoint($endpoint);
        }

        $this->log("Completed collection from " . count($results) . " endpoints");

        return $results;
    }

    protected function makeRequest(ApiEndpoint $endpoint): \Illuminate\Http\Client\Response
    {
        $url = $endpoint->url;
        $method = strtolower($endpoint->method);
        $headers = $endpoint->headers ?? [];
        $queryParams = $endpoint->query_params ?? [];
        $body = $endpoint->body ?? [];

        $request = Http::withHeaders($headers)->timeout(30);

        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        $this->log("Making {$endpoint->method} request to: {$url}");

        return match ($method) {
            'get' => $request->get($url),
            'post' => $request->post($url, $body),
            'put' => $request->put($url, $body),
            'patch' => $request->patch($url, $body),
            'delete' => $request->delete($url, $body),
            default => $request->get($url),
        };
    }

    protected function log(string $message, string $level = 'info'): void
    {
        if ($this->verbose) {
            echo "[" . date('Y-m-d H:i:s') . "] {$message}\n";
        }

        Log::$level("[ApiCollector] {$message}");
    }
}
