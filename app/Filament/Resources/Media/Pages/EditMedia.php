<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use Awcodes\Curator\Resources\Media\Pages\EditMedia as CuratorEditMedia;

class EditMedia extends CuratorEditMedia
{
    protected static string $resource = MediaResource::class;
}
