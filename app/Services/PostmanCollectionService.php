<?php

namespace App\Services;

use App\Models\ApiEndpoint;
use App\Models\CollectionItem;
use App\Models\PostmanCollection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PostmanCollectionService
{
    protected bool $verbose = false;

    public function setVerbose(bool $verbose): self
    {
        $this->verbose = $verbose;
        return $this;
    }

    public function importFromJson(string $json, ?string $sourceUrl = null): PostmanCollection
    {
        $this->log("Starting Postman collection import");

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        return $this->processCollectionData($data, $sourceUrl);
    }

    public function importFromUrl(string $url): PostmanCollection
    {
        $this->log("Fetching Postman collection from URL: {$url}");

        $response = Http::get($url);

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to fetch collection from URL: {$response->status()}");
        }

        return $this->importFromJson($response->body(), $url);
    }

    public function importFromFile(string $filePath): PostmanCollection
    {
        $this->log("Importing Postman collection from file: {$filePath}");

        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $json = file_get_contents($filePath);

        return $this->importFromJson($json);
    }

    protected function processCollectionData(array $data, ?string $sourceUrl = null): PostmanCollection
    {
        $info = $data['info'] ?? [];

        $collection = PostmanCollection::create([
            'name' => $info['name'] ?? 'Unnamed Collection',
            'postman_id' => $info['_postman_id'] ?? $info['id'] ?? null,
            'description' => $info['description'] ?? null,
            'schema_version' => $info['schema'] ?? null,
            'variables' => $data['variable'] ?? null,
            'auth' => $data['auth'] ?? null,
            'raw_data' => $data,
            'source_url' => $sourceUrl,
        ]);

        $this->log("Created collection: {$collection->name}");

        $items = $data['item'] ?? [];
        $this->processItems($collection, $items);

        return $collection;
    }

    protected function processItems(PostmanCollection $collection, array $items, ?int $parentId = null, int $order = 0): void
    {
        foreach ($items as $index => $item) {
            $order = $index;

            if (isset($item['item'])) {
                // This is a folder
                $collectionItem = CollectionItem::create([
                    'postman_collection_id' => $collection->id,
                    'parent_id' => $parentId,
                    'name' => $item['name'] ?? 'Unnamed Folder',
                    'type' => 'folder',
                    'order' => $order,
                    'raw_data' => $item,
                ]);

                $this->log("Created folder: {$collectionItem->name}");

                // Process nested items
                $this->processItems($collection, $item['item'], $collectionItem->id);
            } else {
                // This is a request
                $endpoint = $this->createEndpointFromRequest($item);

                CollectionItem::create([
                    'postman_collection_id' => $collection->id,
                    'api_endpoint_id' => $endpoint->id,
                    'parent_id' => $parentId,
                    'name' => $item['name'] ?? 'Unnamed Request',
                    'type' => 'request',
                    'order' => $order,
                    'raw_data' => $item,
                ]);

                $this->log("Created request: {$item['name']} -> {$endpoint->url}");
            }
        }
    }

    protected function createEndpointFromRequest(array $requestData): ApiEndpoint
    {
        $request = $requestData['request'] ?? [];
        $method = is_string($request['method'] ?? 'GET') ? $request['method'] : 'GET';

        // Handle URL (can be string or object)
        $url = $this->extractUrl($request['url'] ?? '');

        // Handle headers
        $headers = [];
        foreach ($request['header'] ?? [] as $header) {
            if (isset($header['key']) && isset($header['value'])) {
                $headers[$header['key']] = $header['value'];
            }
        }

        // Handle body
        $body = null;
        if (isset($request['body'])) {
            $bodyData = $request['body'];
            if (isset($bodyData['raw'])) {
                $body = ['raw' => $bodyData['raw'], 'mode' => $bodyData['mode'] ?? 'raw'];
            } elseif (isset($bodyData['formdata'])) {
                $body = ['formdata' => $bodyData['formdata'], 'mode' => 'formdata'];
            } elseif (isset($bodyData['urlencoded'])) {
                $body = ['urlencoded' => $bodyData['urlencoded'], 'mode' => 'urlencoded'];
            }
        }

        // Handle query params
        $queryParams = [];
        if (isset($request['url']['query'])) {
            foreach ($request['url']['query'] as $param) {
                if (isset($param['key'])) {
                    $queryParams[$param['key']] = $param['value'] ?? '';
                }
            }
        }

        return ApiEndpoint::create([
            'name' => $requestData['name'] ?? 'Unnamed Endpoint',
            'method' => $method,
            'url' => $url,
            'headers' => !empty($headers) ? $headers : null,
            'query_params' => !empty($queryParams) ? $queryParams : null,
            'body' => $body,
            'source' => 'postman',
            'source_reference' => $requestData['_postman_id'] ?? null,
            'description' => $request['description'] ?? null,
            'is_active' => true,
        ]);
    }

    protected function extractUrl($url): string
    {
        if (is_string($url)) {
            return $url;
        }

        if (is_array($url)) {
            if (isset($url['raw'])) {
                return $url['raw'];
            }

            // Build URL from parts
            $protocol = $url['protocol'] ?? 'https';
            $host = is_array($url['host'] ?? []) ? implode('.', $url['host']) : ($url['host'] ?? '');
            $path = is_array($url['path'] ?? []) ? implode('/', $url['path']) : ($url['path'] ?? '');
            $port = isset($url['port']) ? ':' . $url['port'] : '';

            return "{$protocol}://{$host}{$port}/{$path}";
        }

        return '';
    }

    protected function log(string $message, string $level = 'info'): void
    {
        if ($this->verbose) {
            echo "[" . date('Y-m-d H:i:s') . "] {$message}\n";
        }

        Log::$level("[PostmanCollection] {$message}");
    }
}
