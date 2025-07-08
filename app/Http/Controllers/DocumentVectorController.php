<?php

namespace App\Http\Controllers;

use App\Models\DocumentVector;
use App\Services\VectorEmbeddingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Log;

class DocumentVectorController extends Controller
{
    protected $vectorService;
    protected $modelName;
    protected $transformer;

    public function __construct(VectorEmbeddingService $vectorService)
    {
        $this->vectorService = $vectorService;
    }

    /**
     * Store a new document with vector embedding
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
            $documentVector = $this->vectorService->storeDocumentVector($documentData);

            return response()->json([
                'success' => true,
                'message' => 'Document vector stored successfully',
                'data' => $documentVector
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to store document vector',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search for similar documents
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string',
                'limit' => 'integer|min:1|max:100',
                'threshold' => 'numeric|min:0|max:1',
            ]);

            $query = $request->input('query');
            $limit = $request->input('limit', 10);
            $threshold = $request->input('threshold', 0.7);

            $similarDocuments = $this->vectorService->findSimilarDocuments($query, $limit, $threshold);

            return response()->json([
                'success' => true,
                'data' => $similarDocuments,
                'query' => $query,
                'total_results' => count($similarDocuments)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search for similar documents using MongoDB native vector search
     */
    public function searchMongo(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string',
                'limit' => 'integer|min:1|max:100',
                'threshold' => 'numeric|min:0|max:1',
            ]);

            $query = $request->input('query');
            $limit = $request->input('limit', 10);
            $threshold = $request->input('threshold', 0.7);

            $results = $this->vectorService->searchWithMongoVector($query, $limit, $threshold);

            return response()->json([
                'success' => true,
                'data' => $results,
                'query' => $query,
                'total_results' => count($results)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search documents (MongoDB vector search)',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all document vectors
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 20);
            $offset = $request->input('offset', 0);
            $documentType = $request->input('document_type');

            $query = DocumentVector::query();

            if ($documentType) {
                $query->byType($documentType);
            }

            $documents = $query->skip($offset)
                ->limit($limit)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $documents,
                'total' => $query->count()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific document vector
     */
    public function show(string $documentId): JsonResponse
    {
        try {
            $document = DocumentVector::where('document_id', $documentId)->first();

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $document
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a document vector
     */
    public function update(Request $request, string $documentId): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'string|max:255',
                'content' => 'string',
                'document_type' => 'string|max:100',
                'metadata' => 'array',
            ]);

            $updatedDocument = $this->vectorService->updateDocumentVector($documentId, $request->all());

            if (!$updatedDocument) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully',
                'data' => $updatedDocument
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a document vector
     */
    public function destroy(string $documentId): JsonResponse
    {
        try {
            $deleted = $this->vectorService->deleteDocumentVector($documentId);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate embedding for a text without storing
     */
    public function generateEmbedding(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'text' => 'required|string',
            ]);

            $text = $request->input('text');
            $embedding = $this->vectorService->generateEmbedding($text);

            return response()->json([
                'success' => true,
                'data' => [
                    'text' => $text,
                    'embedding' => $embedding,
                    'embedding_size' => count($embedding)
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate embedding',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics about the vector database
     */
    public function stats(): JsonResponse
    {
        try {
            $totalDocuments = DocumentVector::count();
            $documentTypes = DocumentVector::distinct('document_type')->pluck('document_type');
            $embeddingModels = DocumentVector::distinct('embedding_model')->pluck('embedding_model');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_documents' => $totalDocuments,
                    'document_types' => $documentTypes,
                    'embedding_models' => $embeddingModels,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
