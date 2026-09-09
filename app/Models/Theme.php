<?php

namespace App\Models;

use Crumbls\Layup\Concerns\HasLayupContent;
use Database\Factories\ThemeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    /** @use HasFactory<ThemeFactory> */
    use HasFactory, HasLayupContent;

    protected $fillable = [
        'name',
        'description',
        'preview_image',
        'content',
        'category',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Layup's default frontend view (layup::frontend.page) unconditionally
     * calls this on whatever record it's given — needed for ThemePreviewController
     * to reuse that view rather than forking it just for themes.
     */
    public function getMetaTitle(): string
    {
        return $this->name;
    }
}
