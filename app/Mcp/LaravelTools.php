<?php

namespace App\Mcp;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use PhpMcp\Server\Attributes\McpResource;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\McpPrompt;
use Psr\Log\LoggerInterface;
use App\Services\VectorEmbeddingService;

class LaravelTools
{
    public function __construct(private LoggerInterface $logger) {}

    /**
     * Search documents using SQL or MongoDB vector search
     */
    #[McpTool]
    public function searchDocuments(string $query, int $limit = 10, float $threshold = 0.7): array
    {
        // Otherwise, treat as semantic search and use MongoDB vector search
        try {
            $service = app(VectorEmbeddingService::class);
            $results = $service->searchWithMongoVector($query, $limit, $threshold);
            Log::info('Search results', ['results' => $results]);
            return [
                'query' => $query,
                'results' => $results,
                'count' => count($results)
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error in Mongo vector search: ' . $e->getMessage());
            return [
                'error' => 'Failed to search documents (MongoDB vector search): ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate Laravel development prompt
     */
    #[McpPrompt]
    public function laravelDevelopmentPrompt(): string
    {
        return "You are a Laravel development assistant. You can help with:
- Creating models, controllers, and migrations
- Writing Eloquent queries
- Implementing authentication and authorization
- Setting up routes and middleware
- Debugging application issues
- Optimizing database performance
- Following Laravel best practices

Current Laravel version: " . app()->version() . "
Environment: " . Config::get('app.env') . "

How can I help you with your Laravel project today?";
    }

}
