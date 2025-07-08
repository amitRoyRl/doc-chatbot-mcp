<?php

namespace App\Services;

use App\Models\DocumentVector;
use Illuminate\Support\Facades\Log;
use Exception;
use Textualization\SentenceTransphormers\SentenceRopherta;

class VectorEmbeddingService
{
    protected $transformer;
    protected $modelName;

    public function __construct()
    {
        $this->modelName = 'SentenceRopherta';
        try {
            $this->transformer = new SentenceRopherta();
        } catch (Exception $e) {
            Log::error('Failed to initialize sentence transformer: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate vector embedding for a text
     */
    public function generateEmbedding(string $text): array
    {
        try {
            $embedding = $this->transformer->embeddings($text);
            return $embedding;
        } catch (Exception $e) {
            Log::error('Failed to generate embedding: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Store document with vector embedding in MongoDB
     */
    public function storeDocumentVector(array $documentData): DocumentVector
    {
        try {
            // Generate embedding for the content
            $content = $documentData['content'] ?? '';
            $vectorEmbedding = $this->generateEmbedding($content);

            // Create document vector record
            $documentVector = new DocumentVector([
                'document_id' => $documentData['document_id'] ?? uniqid(),
                'title' => $documentData['title'] ?? '',
                'content' => $content,
                'document_type' => $documentData['document_type'] ?? 'text',
                'vector_embedding' => $vectorEmbedding,
                'embedding_model' => $this->modelName,
                'metadata' => $documentData['metadata'] ?? [],
                'file_path' => $documentData['file_path'] ?? null,
                'file_size' => $documentData['file_size'] ?? null,
                'mime_type' => $documentData['mime_type'] ?? null,
            ]);

            $documentVector->save();

            Log::info('Document vector stored successfully', [
                'document_id' => $documentVector->document_id,
                'embedding_size' => count($vectorEmbedding)
            ]);

            return $documentVector;

        } catch (Exception $e) {
            Log::error('Failed to store document vector: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Store multiple documents with vector embeddings
     */
    public function storeMultipleDocumentVectors(array $documents): array
    {
        $results = [];

        foreach ($documents as $document) {
            try {
                $results[] = $this->storeDocumentVector($document);
            } catch (Exception $e) {
                Log::error('Failed to store document: ' . $e->getMessage(), [
                    'document_id' => $document['document_id'] ?? 'unknown'
                ]);
                $results[] = null;
            }
        }

        return array_filter($results);
    }

    /**
     * Find similar documents using vector similarity
     */
    public function findSimilarDocuments(string $query, int $limit = 10, float $threshold = 0.7): array
    {
        try {
            // Generate embedding for the query
            $queryEmbedding = $this->generateEmbedding($query);

            // For now, we'll use a simple approach
            // In production, you'd want to use MongoDB's vector search capabilities
            $documents = DocumentVector::where('vector_embedding', 'exists', true)
                ->limit($limit)
                ->get();

            Log::info('Found documents', ['count' => $documents->count()]);

            // Calculate cosine similarity and filter by threshold
            $similarDocuments = [];
            foreach ($documents as $document) {
                $similarity = $this->calculateCosineSimilarity($queryEmbedding, $document->vector_embedding);
                Log::info('Similarity', ['similarity' => $similarity, 'doc_id' => $document->document_id]);
                if ($similarity >= $threshold) {
                    $similarDocuments[] = [
                        'document' => $document,
                        'similarity' => $similarity
                    ];
                }
            }

            // Sort by similarity score
            usort($similarDocuments, function($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });

            return $similarDocuments;

        } catch (Exception $e) {
            Log::error('Failed to find similar documents: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find similar documents using MongoDB's native vector search (if available)
     */
    public function searchWithMongoVector(string $query, int $limit = 10, float $threshold = 0.7): array
    {
        $embedding = $this->generateEmbedding($query);

        // Use the Eloquent vectorSearch method
        $results = DocumentVector::vectorSearch(
            index: 'vector_embedding_index',
            path: 'vector_embedding',
            queryVector: $embedding,
            limit: $limit,
            numCandidates: 100
        );

        // Optionally filter by score if your driver returns it
        if ($threshold > 0) {
            $results = collect($results)->filter(function ($doc) use ($threshold) {
                return isset($doc['score']) ? $doc['score'] >= $threshold : true;
            })->values()->all();
        }

        return $results;
    }

    /**
     * Calculate cosine similarity between two vectors
     */
    public function calculateCosineSimilarity(array $vectorA, array $vectorB): float
    {
        if (count($vectorA) !== count($vectorB)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < count($vectorA); $i++) {
            $dotProduct += $vectorA[$i] * $vectorB[$i];
            $normA += $vectorA[$i] * $vectorA[$i];
            $normB += $vectorB[$i] * $vectorB[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        return $dotProduct / ($normA * $normB);
    }

    /**
     * Update document vector embedding
     */
    public function updateDocumentVector(string $documentId, array $newData): ?DocumentVector
    {
        try {
            $documentVector = DocumentVector::where('document_id', $documentId)->first();

            if (!$documentVector) {
                return null;
            }

            // If content has changed, regenerate embedding
            if (isset($newData['content']) && $newData['content'] !== $documentVector->content) {
                $newData['vector_embedding'] = $this->generateEmbedding($newData['content']);
            }

            $documentVector->update($newData);

            return $documentVector;

        } catch (Exception $e) {
            Log::error('Failed to update document vector: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete document vector
     */
    public function deleteDocumentVector(string $documentId): bool
    {
        try {
            $documentVector = DocumentVector::where('document_id', $documentId)->first();

            if ($documentVector) {
                $documentVector->delete();
                return true;
            }

            return false;

        } catch (Exception $e) {
            Log::error('Failed to delete document vector: ' . $e->getMessage());
            throw $e;
        }
    }
}
