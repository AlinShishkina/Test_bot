<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiEndpoint extends Model
{
    protected $fillable = [
        'name',
        'method',
        'url',
        'headers',
        'query_params',
        'body',
        'source',
        'source_reference',
        'description',
        'is_active',
    ];

    protected $casts = [
        'headers' => 'array',
        'query_params' => 'array',
        'body' => 'array',
        'is_active' => 'boolean',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(ApiResponse::class);
    }

    public function collectionItems(): HasMany
    {
        return $this->hasMany(CollectionItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }
}
