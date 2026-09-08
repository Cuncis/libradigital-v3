<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Idempotency log for incoming Mayar webhook deliveries. Mayar can retry a
 * delivery; every processed event is recorded here first so a retry is a
 * no-op instead of double-activating a subscription.
 */
class MayarWebhookEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_key',
        'event',
        'payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
