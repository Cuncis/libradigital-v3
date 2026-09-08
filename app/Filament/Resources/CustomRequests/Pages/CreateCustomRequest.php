<?php

namespace App\Filament\Resources\CustomRequests\Pages;

use App\Filament\Resources\CustomRequests\CustomRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomRequest extends CreateRecord
{
    protected static string $resource = CustomRequestResource::class;
}
