<?php

namespace App\Http\Controllers;

use App\Services\GeminiEmbeddingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Log;

class GeminiEmbeddingController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiEmbeddingService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Store a new document with Gemini embedding
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'document_type' => 'string|max:100',
                'metadata' => 'array',
                'file_path' => 'string|max:500',
                'file_size' => 'integer',
                'mime_type' => 'string|max:100',
            ]);

            $documentData = $request->all();
            $embedding = $this->geminiService->storeGeminiEmbedding($documentData);

            return response()->json([
                'success' => true,
                'message' => 'Gemini embedding stored successfully',
                'data' => $embedding
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to store Gemini embedding',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a Gemini response using local context and Gemini API
     */
    public function chatWithContext(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string',
                'limit' => 'integer|min:1|max:10',
                'threshold' => 'numeric|min:0|max:1',
            ]);
            $query = $request->input('query');
            $limit = $request->input('limit', 5);
            $threshold = $request->input('threshold', 0.7);

            // Use the existing DocumentVector Mongo search for context
            $vectorService = app(\App\Services\VectorEmbeddingService::class);
            $contextDocs = $vectorService->searchWithMongoVector($query, $limit, $threshold);

            $response = $this->geminiService->generateCompletionWithContext($query, $contextDocs);

            return response()->json([
                'success' => true,
                'response' => $response,
                // 'context' => $contextDocs,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Gemini response',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a Gemini response using Gemini API-generated embeddings as context
     */
    public function chatWithGeminiEmbeddings(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string',
                'limit' => 'integer|min:1|max:10',
                'threshold' => 'numeric|min:0|max:1',
            ]);
            $query = $request->input('query');
            $limit = $request->input('limit', 5);
            $threshold = $request->input('threshold', 0.7);

            // Use Gemini API to generate embedding for the query
            $geminiService = $this->geminiService;
            $queryEmbedding = $geminiService->generateQueryEmbedding($query);

            // Use MongoDB vector search for Gemini embeddings
            $results = \App\Models\GeminiEmbedding::vectorSearch(
                index: 'vector_embedding_index',
                path: 'vector_embedding',
                queryVector: $queryEmbedding,
                limit: $limit,
                numCandidates: 100
            );

            // Optionally filter by score if your driver returns it
            if ($threshold > 0) {
                $results = collect($results)->filter(function ($doc) use ($threshold) {
                    return isset($doc['score']) ? $doc['score'] >= $threshold : true;
                })->values()->all();
            }
            $topDocs = array_slice($results, 0, $limit);

            $response = $geminiService->generateCompletionWithContext($query, $topDocs);

            return response()->json([
                'success' => true,
                'response' => $response,
                // 'context' => $topDocs,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Gemini response (Gemini embeddings)',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate cosine similarity between two vectors
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) return 0.0;
        $dot = 0.0; $normA = 0.0; $normB = 0.0;
        for ($i = 0; $i < count($a); $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }
        if ($normA == 0 || $normB == 0) return 0.0;
        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
