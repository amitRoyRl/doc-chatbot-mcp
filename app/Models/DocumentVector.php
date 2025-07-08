<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\HasMany;

class DocumentVector extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'document_vectors';

    protected $fillable = [
        'document_id',
        'title',
        'content',
        'document_type',
        'vector_embedding',
        'embedding_model',
        'metadata',
        'file_path',
        'file_size',
        'mime_type',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'file_size' => 'integer'
    ];

    /**
     * Get the document ID
     */
    public function getDocumentIdAttribute($value)
    {
        return $value;
    }

    /**
     * Set the document ID
     */
    public function setDocumentIdAttribute($value)
    {
        $this->attributes['document_id'] = $value;
    }

    /**
     * Get the vector embedding
     */
    public function getVectorEmbeddingAttribute($value)
    {
        return is_string($value) ? json_decode($value, true) : $value;
    }

    /**
     * Set the vector embedding
     */
    public function setVectorEmbeddingAttribute($value)
    {
        $this->attributes['vector_embedding'] = is_array($value) ? $value : json_decode($value, true);
    }

    /**
     * Get the metadata
     */
    public function getMetadataAttribute($value)
    {
        return is_string($value) ? json_decode($value, true) : $value;
    }

    /**
     * Set the metadata
     */
    public function setMetadataAttribute($value)
    {
        $this->attributes['metadata'] = is_array($value) ? $value : json_decode($value, true);
    }

    /**
     * Scope to filter by document type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope to filter by embedding model
     */
    public function scopeByModel($query, $model)
    {
        return $query->where('embedding_model', $model);
    }

    /**
     * Find similar documents using vector similarity
     */
    public static function findSimilar($vector, $limit = 10, $threshold = 0.7)
    {
        // This would typically use MongoDB's $vectorSearch aggregation
        // For now, we'll return a basic query that can be enhanced with vector search
        return static::where('vector_embedding', 'exists', true)
            ->limit($limit)
            ->get();
    }
}
