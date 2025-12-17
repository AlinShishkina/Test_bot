<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostmanCollection extends Model
{
    protected $fillable = [
        'name',
        'postman_id',
        'description',
        'schema_version',
        'variables',
        'auth',
        'raw_data',
        'source_url',
    ];

    protected $casts = [
        'variables' => 'array',
        'auth' => 'array',
        'raw_data' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CollectionItem::class);
    }

    public function rootItems(): HasMany
    {
        return $this->hasMany(CollectionItem::class)->whereNull('parent_id');
    }

    public function folders(): HasMany
    {
        return $this->hasMany(CollectionItem::class)->where('type', 'folder');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(CollectionItem::class)->where('type', 'request');
    }
}
