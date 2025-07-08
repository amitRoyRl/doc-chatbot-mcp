# MongoDB Vector Storage Setup

This project implements a vector storage system using MongoDB and sentence-transformers for document similarity search and retrieval.

## Features

- Store documents with vector embeddings generated using sentence-transformers
- Search for similar documents using vector similarity
- Full CRUD operations for document vectors
- Support for multiple document types and metadata
- Efficient indexing for fast vector searches

## Prerequisites

- PHP 8.2+
- Laravel 12.0+
- MongoDB (running via Docker or local installation)
- Composer

## Installation

1. **Install Dependencies**
   ```bash
   composer install
   ```

2. **Environment Configuration**
   Add the following to your `.env` file:
   ```env
   MONGODB_DSN=mongodb://localhost:27017
   MONGODB_DATABASE=laravel
   MONGODB_AUTHENTICATION_DATABASE=admin
   ```

3. **Run MongoDB Migration**
   ```bash
   php artisan migrate
   ```

## Usage

### 1. Store a Document with Vector Embedding

```php
use App\Services\VectorEmbeddingService;

$vectorService = new VectorEmbeddingService();

$documentData = [
    'title' => 'Sample Document',
    'content' => 'This is the content of the document that will be vectorized.',
    'document_type' => 'text',
    'metadata' => [
        'author' => 'John Doe',
        'category' => 'technical'
    ]
];

$documentVector = $vectorService->storeDocumentVector($documentData);
```

### 2. Search for Similar Documents

```php
$similarDocuments = $vectorService->findSimilarDocuments(
    'search query here',
    10, // limit
    0.7 // similarity threshold
);

foreach ($similarDocuments as $result) {
    echo "Document: " . $result['document']->title;
    echo "Similarity: " . $result['similarity'];
}
```

### 3. API Endpoints

#### Store Document
```http
POST /api/documents
Content-Type: application/json

{
    "title": "Document Title",
    "content": "Document content for vectorization",
    "document_type": "text",
    "metadata": {
        "author": "Author Name",
        "category": "Category"
    }
}
```

#### Search Documents
```http
POST /api/documents/search
Content-Type: application/json

{
    "query": "search query",
    "limit": 10,
    "threshold": 0.7
}
```

#### Generate Embedding
```http
POST /api/documents/embedding
Content-Type: application/json

{
    "text": "Text to generate embedding for"
}
```

#### Get All Documents
```http
GET /api/documents?limit=20&offset=0&document_type=text
```

#### Get Document Statistics
```http
GET /api/documents/stats
```

## Database Schema

The `document_vectors` collection in MongoDB stores the following fields:

- `document_id`: Unique identifier for the document
- `title`: Document title
- `content`: Original document content
- `document_type`: Type/category of the document
- `vector_embedding`: Array of float values representing the document embedding
- `embedding_model`: Name of the sentence transformer model used
- `metadata`: Additional document metadata (JSON object)
- `file_path`: Path to the original file (if applicable)
- `file_size`: Size of the original file in bytes
- `mime_type`: MIME type of the original file
- `created_at`: Timestamp when the document was created
- `updated_at`: Timestamp when the document was last updated

## Indexes

The following indexes are created for optimal performance:

- `document_id_unique`: Unique index on document_id
- `document_type_index`: Index on document_type for filtering
- `created_at_index`: Index on created_at for time-based queries
- `updated_at_index`: Index on updated_at for time-based queries
- `content_text_search`: Text index for full-text search on content and title

## Vector Similarity Search

The system uses cosine similarity to find similar documents. The similarity score ranges from 0 to 1, where:

- 1.0 = Identical documents
- 0.0 = Completely different documents
- 0.7+ = High similarity (recommended threshold)

## Configuration

### Sentence Transformer Models

You can configure different sentence transformer models by modifying the `VectorEmbeddingService` constructor:

```php
// Default model (all-MiniLM-L6-v2)
$vectorService = new VectorEmbeddingService();

// Custom model
$vectorService = new VectorEmbeddingService('all-mpnet-base-v2');
```

Available models include:
- `all-MiniLM-L6-v2` (default, fast, 384 dimensions)
- `all-mpnet-base-v2` (better quality, 768 dimensions)
- `all-MiniLM-L12-v2` (better quality, 384 dimensions)

### MongoDB Configuration

You can customize MongoDB settings in `config/database.php`:

```php
'mongodb' => [
    'driver' => 'mongodb',
    'dsn' => env('MONGODB_DSN', 'mongodb://localhost:27017'),
    'database' => env('MONGODB_DATABASE', 'laravel'),
    'options' => [
        'database' => env('MONGODB_AUTHENTICATION_DATABASE', 'admin'),
    ],
],
```

## Error Handling

The system includes comprehensive error handling:

- Invalid input validation
- MongoDB connection errors
- Sentence transformer initialization errors
- Vector generation failures

All errors are logged and appropriate HTTP status codes are returned.

## Performance Considerations

1. **Vector Size**: Larger models produce better embeddings but require more storage and processing time
2. **Indexing**: Ensure MongoDB indexes are created for optimal query performance
3. **Batch Processing**: Use `storeMultipleDocumentVectors()` for bulk operations
4. **Memory**: Large vector embeddings may require increased PHP memory limits

## Troubleshooting

### Common Issues

1. **MongoDB Connection Failed**
   - Check if MongoDB is running
   - Verify connection string in `.env`
   - Ensure MongoDB extension is installed

2. **Sentence Transformer Model Not Found**
   - Models are downloaded automatically on first use
   - Check internet connection
   - Verify model name is correct

3. **Memory Issues**
   - Increase PHP memory limit in `php.ini`
   - Use smaller sentence transformer models
   - Process documents in smaller batches

### Logs

Check Laravel logs for detailed error information:
```bash
tail -f storage/logs/laravel.log
```

## Example Use Cases

1. **Document Search**: Find similar documents based on content
2. **Content Recommendation**: Suggest related articles or documents
3. **Duplicate Detection**: Identify similar or duplicate content
4. **Semantic Search**: Search documents by meaning rather than exact keywords
5. **Content Clustering**: Group similar documents together

## Contributing

When contributing to this project:

1. Follow Laravel coding standards
2. Add tests for new features
3. Update documentation
4. Ensure MongoDB indexes are properly created
5. Test with different sentence transformer models 
