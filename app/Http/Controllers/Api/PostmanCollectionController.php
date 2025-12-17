<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PostmanCollection;
use App\Services\PostmanCollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostmanCollectionController extends Controller
{
    protected PostmanCollectionService $service;

    public function __construct(PostmanCollectionService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $collections = PostmanCollection::with('rootItems')->paginate(15);

        return response()->json($collections);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'json' => 'required_without:url|string',
            'url' => 'required_without:json|url',
        ]);

        try {
            if (isset($validated['url'])) {
                $collection = $this->service->importFromUrl($validated['url']);
            } else {
                $collection = $this->service->importFromJson($validated['json']);
            }

            return response()->json([
                'message' => 'Collection imported successfully',
                'collection' => $collection->load('items'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to import collection',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(PostmanCollection $postmanCollection): JsonResponse
    {
        return response()->json($postmanCollection->load(['items.endpoint', 'rootItems']));
    }

    public function update(Request $request, PostmanCollection $postmanCollection): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $postmanCollection->update($validated);

        return response()->json($postmanCollection);
    }

    public function destroy(PostmanCollection $postmanCollection): JsonResponse
    {
        $postmanCollection->delete();

        return response()->json(null, 204);
    }
}
