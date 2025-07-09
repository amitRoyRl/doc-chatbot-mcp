<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class GeminiEmbedding extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'gemini_embeddings';

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

    public function getDocumentIdAttribute($value)
    {
        return $value;
    }

    public function setDocumentIdAttribute($value)
    {
        $this->attributes['document_id'] = $value;
    }

    public function getVectorEmbeddingAttribute($value)
    {
        return is_string($value) ? json_decode($value, true) : $value;
    }

    public function setVectorEmbeddingAttribute($value)
    {
        $this->attributes['vector_embedding'] = is_array($value) ? $value : json_decode($value, true);
    }

    public function getMetadataAttribute($value)
    {
        return is_string($value) ? json_decode($value, true) : $value;
    }

    public function setMetadataAttribute($value)
    {
        $this->attributes['metadata'] = is_array($value) ? $value : json_decode($value, true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeByModel($query, $model)
    {
        return $query->where('embedding_model', $model);
    }
}
