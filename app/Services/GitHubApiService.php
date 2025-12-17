<?php

namespace App\Services;

use App\Models\ApiEndpoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubApiService
{
    protected ?string $token = null;
    protected bool $verbose = false;

    public function __construct()
    {
        $this->token = config('services.github.token');
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function setVerbose(bool $verbose): self
    {
        $this->verbose = $verbose;
        return $this;
    }

    public function importEndpointsFromRepository(string $owner, string $repo, ?string $path = null): array
    {
        $this->log("Searching for API definitions in repository: {$owner}/{$repo}");

        $endpoints = [];

        // Search for common API definition files
        $searchPatterns = [
            'openapi.json',
            'openapi.yaml',
            'openapi.yml',
            'swagger.json',
            'swagger.yaml',
            'api.json',
            'postman_collection.json',
        ];

        foreach ($searchPatterns as $pattern) {
            try {
                $files = $this->searchFiles($owner, $repo, $pattern);
                foreach ($files as $file) {
                    $content = $this->getFileContent($owner, $repo, $file['path']);
                    $importedEndpoints = $this->parseApiDefinition($content, $file['name'], "{$owner}/{$repo}");
                    $endpoints = array_merge($endpoints, $importedEndpoints);
                }
            } catch (\Exception $e) {
                $this->log("Error searching for {$pattern}: " . $e->getMessage(), 'warning');
            }
        }

        $this->log("Imported " . count($endpoints) . " endpoints from repository");

        return $endpoints;
    }

    public function importFromOpenApiSpec(string $owner, string $repo, string $filePath): array
    {
        $this->log("Importing OpenAPI spec from: {$owner}/{$repo}/{$filePath}");

        $content = $this->getFileContent($owner, $repo, $filePath);
        return $this->parseApiDefinition($content, $filePath, "{$owner}/{$repo}");
    }

    protected function searchFiles(string $owner, string $repo, string $filename): array
    {
        $response = $this->makeRequest("search/code", [
            'q' => "filename:{$filename} repo:{$owner}/{$repo}",
        ]);

        return $response['items'] ?? [];
    }

    protected function getFileContent(string $owner, string $repo, string $path): string
    {
        $response = $this->makeRequest("repos/{$owner}/{$repo}/contents/{$path}");

        if (isset($response['content'])) {
            return base64_decode($response['content']);
        }

        throw new \RuntimeException("Unable to fetch file content");
    }

    protected function parseApiDefinition(string $content, string $filename, string $sourceRepo): array
    {
        $endpoints = [];

        // Try JSON first
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try YAML if JSON fails
            if (function_exists('yaml_parse')) {
                $data = yaml_parse($content);
            } else {
                $this->log("YAML parsing not available, skipping YAML files");
                return [];
            }
        }

        if (!$data) {
            return [];
        }

        // Check if it's an OpenAPI/Swagger spec
        if (isset($data['openapi']) || isset($data['swagger'])) {
            return $this->parseOpenApiSpec($data, $sourceRepo);
        }

        // Check if it's a Postman collection
        if (isset($data['info']['schema']) && str_contains($data['info']['schema'], 'postman')) {
            $postmanService = new PostmanCollectionService();
            $postmanService->setVerbose($this->verbose);
            $collection = $postmanService->importFromJson($content, "github:{$sourceRepo}");

            return ApiEndpoint::where('source', 'postman')
                ->where('source_reference', 'LIKE', '%' . $collection->postman_id . '%')
                ->get()
                ->toArray();
        }

        return $endpoints;
    }

    protected function parseOpenApiSpec(array $spec, string $sourceRepo): array
    {
        $endpoints = [];
        $baseUrl = $this->extractBaseUrl($spec);

        $paths = $spec['paths'] ?? [];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $details) {
                if (!in_array(strtoupper($method), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'])) {
                    continue;
                }

                $endpoint = ApiEndpoint::create([
                    'name' => $details['summary'] ?? $details['operationId'] ?? "{$method} {$path}",
                    'method' => strtoupper($method),
                    'url' => $baseUrl . $path,
                    'headers' => null,
                    'query_params' => $this->extractQueryParams($details),
                    'body' => $this->extractRequestBody($details),
                    'source' => 'github',
                    'source_reference' => $sourceRepo,
                    'description' => $details['description'] ?? null,
                    'is_active' => true,
                ]);

                $endpoints[] = $endpoint;
                $this->log("Created endpoint: {$endpoint->name}");
            }
        }

        return $endpoints;
    }

    protected function extractBaseUrl(array $spec): string
    {
        // OpenAPI 3.x
        if (isset($spec['servers'][0]['url'])) {
            return rtrim($spec['servers'][0]['url'], '/');
        }

        // Swagger 2.x
        if (isset($spec['host'])) {
            $scheme = $spec['schemes'][0] ?? 'https';
            $basePath = $spec['basePath'] ?? '';
            return "{$scheme}://{$spec['host']}{$basePath}";
        }

        return '';
    }

    protected function extractQueryParams(array $details): ?array
    {
        $params = [];
        $parameters = $details['parameters'] ?? [];

        foreach ($parameters as $param) {
            if (($param['in'] ?? '') === 'query') {
                $params[$param['name']] = $param['schema']['default'] ?? '';
            }
        }

        return !empty($params) ? $params : null;
    }

    protected function extractRequestBody(array $details): ?array
    {
        if (isset($details['requestBody']['content']['application/json']['schema'])) {
            return ['schema' => $details['requestBody']['content']['application/json']['schema']];
        }

        return null;
    }

    protected function makeRequest(string $endpoint, array $query = []): array
    {
        $url = "https://api.github.com/{$endpoint}";

        $request = Http::withHeaders([
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'Laravel-API-Collector',
        ]);

        if ($this->token) {
            $request = $request->withToken($this->token);
        }

        $response = $request->get($url, $query);

        if (!$response->successful()) {
            throw new \RuntimeException("GitHub API error: {$response->status()} - {$response->body()}");
        }

        return $response->json();
    }

    protected function log(string $message, string $level = 'info'): void
    {
        if ($this->verbose) {
            echo "[" . date('Y-m-d H:i:s') . "] {$message}\n";
        }

        Log::$level("[GitHubApi] {$message}");
    }
}
