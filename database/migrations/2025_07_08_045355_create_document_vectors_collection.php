<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Client;
use MongoDB\Database;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use Atlas DSN only (no localhost fallback)
        $client = new Client(env('MONGODB_DSN'));
        $database = $client->selectDatabase(env('MONGODB_DATABASE', 'laravel'));

        // Create the document_vectors collection if it doesn't exist
        $collections = $database->listCollections();
        $exists = false;
        foreach ($collections as $coll) {
            if ($coll->getName() === 'document_vectors') {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $database->createCollection('document_vectors');
        }
        $collection = $database->selectCollection('document_vectors');

        // Create Atlas vector search index (if supported by your driver/Atlas)
        try {
            $collection->createSearchIndex([
                'fields' => [
                    [
                        'type' => 'vector',
                        'numDimensions' => 768, // Set this to your actual embedding size
                        'path' => 'vector_embedding', // The field storing your vectors
                        'similarity' => 'cosine' // Or 'dotProduct'
                    ],
                ],
            ], ['name' => 'vector_embedding_index', 'type' => 'vectorSearch']);
        } catch (\Exception $e) {
            // Log or ignore if not supported
            // echo "Could not create vector search index: " . $e->getMessage();
        }

        // Create indexes for efficient vector similarity search
        $collection->createIndex([
            'document_id' => 1
        ], [
            'unique' => true,
            'name' => 'document_id_unique'
        ]);

        $collection->createIndex([
            'document_type' => 1
        ], [
            'name' => 'document_type_index'
        ]);

        $collection->createIndex([
            'created_at' => -1
        ], [
            'name' => 'created_at_index'
        ]);

        $collection->createIndex([
            'updated_at' => -1
        ], [
            'name' => 'updated_at_index'
        ]);

        $collection->createIndex([
            'content' => 'text',
            'title' => 'text'
        ], [
            'name' => 'content_text_search',
            'weights' => [
                'content' => 3,
                'title' => 2
            ]
        ]);

        // NOTE: Atlas vector indexes must be created via the Atlas UI or API, not via PHP.
        // Please create a vector index named 'vector_embedding_index' on the 'vector_embedding' field in Atlas.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $client = new Client(env('MONGODB_DSN'));
        $database = $client->selectDatabase(env('MONGODB_DATABASE', 'laravel'));
        $database->dropCollection('document_vectors');
    }
};
