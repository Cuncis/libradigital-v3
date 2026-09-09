<?php

declare(strict_types=1);
use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Media\Pages\CreateMedia;
use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Models\Media;
use Awcodes\Curator\Enums\PreviewableExtensions;
use Awcodes\Curator\Providers\GlideUrlProvider;
use Awcodes\Curator\Resources\Media\Schemas\MediaForm;
use Awcodes\Curator\Resources\Media\Tables\MediaTable;

return [
    'curation_formats' => PreviewableExtensions::toArray(),
    'default_disk' => env('CURATOR_DEFAULT_DISK', env('FILESYSTEM_DISK', 'public')),
    'default_directory' => null,
    'default_visibility' => 'public',
    'features' => [
        'curations' => true,
        'file_swap' => true,
        'directory_restriction' => false,
        'preserve_file_names' => false,
        // Curator's own tenancy scoping requires Filament's panel-level
        // tenancy (Filament::hasTenancy()), which this app deliberately
        // doesn't use (flat /user path, manual query scoping elsewhere —
        // see InvitationResource/ThemeResource). Per-user media scoping is
        // instead done by hand in App\Filament\Resources\Media\MediaResource
        // below. Also fixes a bug in the installer's generated config: the
        // unquoted `user` here is parsed as an undefined PHP constant,
        // which throws the moment this file is loaded.
        'tenancy' => [
            'enabled' => false,
            'relationship_name' => 'user',
        ],
    ],
    'glide_token' => env('CURATOR_GLIDE_TOKEN'),
    'model' => Media::class,
    'path_generator' => null,
    'resource' => [
        'label' => 'Media',
        'plural_label' => 'Media',
        'default_layout' => 'grid',
        'navigation' => [
            'group' => null,
            'icon' => 'heroicon-o-photo',
            'sort' => null,
            'should_register' => true,
            'should_show_badge' => false,
        ],
        'resource' => MediaResource::class,
        'pages' => [
            'create' => CreateMedia::class,
            'edit' => EditMedia::class,
            'index' => ListMedia::class,
        ],
        'schemas' => [
            'form' => MediaForm::class,
        ],
        'tables' => [
            'table' => MediaTable::class,
        ],
    ],
    'url_provider' => GlideUrlProvider::class,
];
