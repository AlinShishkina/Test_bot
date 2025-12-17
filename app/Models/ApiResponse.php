<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiResponse extends Model
{
    protected $fillable = [
        'api_endpoint_id',
        'status_code',
        'response_headers',
        'response_body',
        'response_time',
        'collected_at',
        'is_successful',
        'error_message',
    ];

    protected $casts = [
        'response_headers' => 'array',
        'collected_at' => 'datetime',
        'is_successful' => 'boolean',
        'response_time' => 'float',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(ApiEndpoint::class, 'api_endpoint_id');
    }

    public function scopeSuccessful($query)
    {
        return $query->where('is_successful', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('is_successful', false);
    }

    public function scopeByStatusCode($query, int $code)
    {
        return $query->where('status_code', $code);
    }
}
