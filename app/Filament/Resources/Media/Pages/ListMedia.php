<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use Awcodes\Curator\Resources\Media\Pages\ListMedia as CuratorListMedia;

class ListMedia extends CuratorListMedia
{
    protected static string $resource = MediaResource::class;
}
