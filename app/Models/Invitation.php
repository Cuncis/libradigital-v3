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

    /**
     * Layup's tree-builder (HasLayupContent::buildRowTree) has no error
     * handling for a malformed row/column/widget entry — unlike individual
     * widgets, which are already try/caught — so a single non-array entry
     * anywhere in this shape throws a hard TypeError and crashes the whole
     * page. Layup's own ContentValidator deliberately warns rather than
     * blocks malformed content from being saved (see Page::booted()), so
     * the render path has to tolerate it. Drop anything malformed rather
     * than crash on it — this only affects rendering, not the builder's
     * own edit-form data (Filament binds directly to the raw `content`
     * column), so a customer can still open the builder and fix it.
     */
    protected function getLayupContent(): array
    {
        $content = parent::getLayupContent();

        $content['rows'] = $this->sanitizeLayupRows($content['rows'] ?? []);

        if (isset($content['sections']) && is_array($content['sections'])) {
            $content['sections'] = array_map(
                fn ($section) => is_array($section)
                    ? [...$section, 'rows' => $this->sanitizeLayupRows($section['rows'] ?? [])]
                    : $section,
                $content['sections']
            );
        }

        return $content;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function sanitizeLayupRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($row) {
            if (! is_array($row)) {
                return null;
            }

            $row['columns'] = array_values(array_filter(array_map(function ($column) {
                if (! is_array($column)) {
                    return null;
                }

                $column['widgets'] = array_values(array_filter($column['widgets'] ?? [], 'is_array'));

                return $column;
            }, $row['columns'] ?? [])));

            return $row;
        }, $rows)));
    }
}
