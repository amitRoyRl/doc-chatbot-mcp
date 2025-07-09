<?php

namespace App\Services;

use App\Models\GeminiEmbedding;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class GeminiEmbeddingService
{
    protected $apiKey;
    protected $endpoint;
    protected $modelName;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->endpoint = config('services.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta/models/embedding-001:embedContent');
        $this->modelName = 'google-gemini-embedding-001';
    }

    /**
     * Generate vector embedding for a text using Gemini API, supporting taskType and title.
     * @param string $text
     * @param string $taskType (RETRIEVAL_DOCUMENT or RETRIEVAL_QUERY)
     * @param string|null $title (optional, for documents)
     * @return array
     */
    public function generateEmbedding(string $text, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array
    {
        $endpoint = config('services.gemini.embedding_endpoint', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-exp-03-07:embedContent');
        $model = config('services.gemini.embedding_model', 'models/gemini-embedding-exp-03-07');
        $body = [
            'content' => [
                'parts' => [
                    ['text' => $text]
                ]
            ],
            'taskType' => $taskType,
        ];
        if ($taskType === 'RETRIEVAL_DOCUMENT' && $title) {
            $body['title'] = $title;
        }
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $this->apiKey,
        ])->post($endpoint, $body);
        if (!$response->successful()) {
            \Log::error('Gemini embedding API error', ['response' => $response->body()]);
            throw new \Exception('Failed to get embedding from Gemini API');
        }
        $data = $response->json();
        if (isset($data['embedding']['values'])) {
            return $data['embedding']['values'];
        }
        throw new \Exception('Invalid Gemini API response');
    }

    /**
     * Generate vector embedding for a user query using Gemini API (taskType=RETRIEVAL_QUERY)
     * @param string $query
     * @return array
     */
    public function generateQueryEmbedding(string $query): array
    {
        return $this->generateEmbedding($query, 'RETRIEVAL_QUERY');
    }

    /**
     * Generate a Gemini completion using context, following official Gemini API docs:
     * https://ai.google.dev/gemini-api/docs/text-generation?hl=en
     *
     * @param string $query
     * @param array $contextDocs
     * @param array $generationConfig (optional)
     * @return string
     */
    public function generateCompletionWithContext(string $query, array $contextDocs, array $generationConfig = []): string | array
    {
        $apiKey = $this->apiKey;
        $endpoint = config('services.gemini.query_endpoint', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent');

        // Build the contents array as per Gemini 2.5 API best practices:
        // Each context chunk is a separate 'user' message, then the query as the final 'user' message.
        $contents = collect($contextDocs)->map(function($doc) {
            $content = $doc['content'] ?? (is_object($doc) ? $doc->content : '');
            if (is_array($content)) {
                $content = implode(",", $content);
            }
            return [
                'role' => 'user',
                'parts' => [
                    ['text' => "Here is some context:\n".$content]
                ]
            ];
        })->values()->all();
        // Add the user query as the final message
        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $query]
            ]
        ];

        // return $contents;
        $defaultConfig = [
            'temperature' => 1,
            'maxOutputTokens' => 10240,
        ];
        $generationConfig = array_merge($defaultConfig, $generationConfig);

        try {
            $body = [
                'contents' => $contents,
                'generationConfig' => $generationConfig
            ];
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $apiKey,
            ])->post($endpoint, $body);
            if (!$response->successful()) {
                \Log::error('Gemini completion API error', [
                    'response' => $response->body(),
                    'request_body' => $body
                ]);
                throw new \Exception('Failed to get completion from Gemini API');
            }
            $data = $response->json();
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }
            \Log::error('Invalid Gemini completion API response', ['response' => $data]);
            throw new \Exception('Invalid Gemini completion API response');
        } catch (\Exception $e) {
            \Log::error('Failed to generate Gemini completion: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Store document with Gemini embedding in MongoDB
     */
    public function storeGeminiEmbedding(array $documentData): GeminiEmbedding
    {
        try {
            $content = $documentData['content'] ?? '';
            $vectorEmbedding = $this->generateEmbedding($content);

            $geminiEmbedding = new GeminiEmbedding([
                'document_id' => $documentData['document_id'] ?? uniqid(),
                'title' => $documentData['title'] ?? '',
                'content' => $content, // Always store the original text
                'document_type' => $documentData['document_type'] ?? 'text',
                'vector_embedding' => $vectorEmbedding,
                'embedding_model' => $this->modelName,
                'metadata' => $documentData['metadata'] ?? [],
                'file_path' => $documentData['file_path'] ?? null,
                'file_size' => $documentData['file_size'] ?? null,
                'mime_type' => $documentData['mime_type'] ?? null,
            ]);

            $geminiEmbedding->save();

            Log::info('Gemini embedding stored successfully', [
                'document_id' => $geminiEmbedding->document_id,
                'embedding_size' => count($vectorEmbedding)
            ]);

            return $geminiEmbedding;
        } catch (Exception $e) {
            Log::error('Failed to store Gemini embedding: ' . $e->getMessage());
            throw $e;
        }
    }
}
