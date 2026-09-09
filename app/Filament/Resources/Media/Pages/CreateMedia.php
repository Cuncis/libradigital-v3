<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use Awcodes\Curator\Resources\Media\Pages\CreateMedia as CuratorCreateMedia;

class CreateMedia extends CuratorCreateMedia
{
    protected static string $resource = MediaResource::class;
}
