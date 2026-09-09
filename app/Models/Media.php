<?php

namespace App\Models;

use App\Observers\MediaOwnerObserver;
use Awcodes\Curator\Models\Media as CuratorMedia;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(MediaOwnerObserver::class)]
class Media extends CuratorMedia
{
    // The base Curator model's $fillable doesn't include user_id (it has
    // no concept of ownership) — without appending it here, mass
    // assignment silently drops the field, and MediaOwnerObserver only
    // fills it in from the current auth session as a fallback for the
    // normal upload flow (which is otherwise the only path that sets it).
    protected $fillable = [
        'disk',
        'directory',
        'visibility',
        'name',
        'path',
        'width',
        'height',
        'size',
        'type',
        'ext',
        'alt',
        'title',
        'description',
        'caption',
        'exif',
        'curations',
        'file',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
