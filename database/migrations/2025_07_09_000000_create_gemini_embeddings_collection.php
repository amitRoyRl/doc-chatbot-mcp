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
        $client = new Client(env('MONGODB_DSN'));
        $database = $client->selectDatabase(env('MONGODB_DATABASE', 'laravel'));

        // Create the gemini_embeddings collection if it doesn't exist
        $collections = $database->listCollections();
        $exists = false;
        foreach ($collections as $coll) {
            if ($coll->getName() === 'gemini_embeddings') {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $database->createCollection('gemini_embeddings');
        }
        $collection = $database->selectCollection('gemini_embeddings');

        // Create Atlas vector search index (if supported by your driver/Atlas)
        try {
            $collection->createSearchIndex([
                'fields' => [
                    [
                        'type' => 'vector',
                        'numDimensions' => 3072, // Gemini embedding size
                        'path' => 'vector_embedding',
                        'similarity' => 'cosine'
                    ],
                ],
            ], ['name' => 'vector_embedding_index', 'type' => 'vectorSearch']);
        } catch (\Exception $e) {
            // Log or ignore if not supported
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
            'file_path' => 1
        ], [
            'name' => 'file_path_index'
        ]);

        // Ensure 'content' field is present for storing original document text
        $collection->updateMany(
            [ 'content' => [ '$exists' => false ] ],
            [ '$set' => [ 'content' => '' ] ]
        ); // This is a no-op for schema-less Mongo, but documents should have this field for RAG

        // Vector index is handled by Atlas or driver
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $client = new Client(env('MONGODB_DSN'));
        $database = $client->selectDatabase(env('MONGODB_DATABASE', 'laravel'));
        $database->dropCollection('gemini_embeddings');
    }
};
