<?php

namespace App\Filament\User\Resources\Invitations\Pages;

use App\Filament\User\Resources\Invitations\InvitationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvitation extends CreateRecord
{
    protected static string $resource = InvitationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['is_custom_build'] = false;

        return $data;
    }
}
