<?php

namespace App\Models;

use Crumbls\Layup\Models\Page;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invitation extends Page
{
    protected $fillable = [
        'title',
        'slug',
        'parent_id',
        'path',
        'content',
        'status',
        'meta',
        'author',
        'published_at',
        'featured_image',
        'user_id',
        'theme_id',
        'host_name',
        'venue',
        'event_date',
        'is_custom_build',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'event_date' => 'datetime',
            'is_custom_build' => 'boolean',
        ]);
    }

    protected static function newFactory(): InvitationFactory
    {
        return InvitationFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function customRequest(): HasOne
    {
        return $this->hasOne(CustomRequest::class);
    }
}
