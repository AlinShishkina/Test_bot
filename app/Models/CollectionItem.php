<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionItem extends Model
{
    protected $fillable = [
        'postman_collection_id',
        'api_endpoint_id',
        'parent_id',
        'name',
        'type',
        'order',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(PostmanCollection::class, 'postman_collection_id');
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(ApiEndpoint::class, 'api_endpoint_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CollectionItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CollectionItem::class, 'parent_id');
    }

    public function scopeFolders($query)
    {
        return $query->where('type', 'folder');
    }

    public function scopeRequests($query)
    {
        return $query->where('type', 'request');
    }

    public function isFolder(): bool
    {
        return $this->type === 'folder';
    }

    public function isRequest(): bool
    {
        return $this->type === 'request';
    }
}
