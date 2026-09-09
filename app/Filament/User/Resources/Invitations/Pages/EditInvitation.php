<?php

namespace App\Filament\User\Resources\Invitations\Pages;

use App\Filament\User\Resources\Invitations\InvitationResource;
use App\Models\Invitation;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class EditInvitation extends EditRecord
{
    protected static string $resource = InvitationResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview')
                ->icon(Heroicon::OutlinedEye)
                ->url(fn (Invitation $record): string => route('invitations.preview', $record))
                ->openUrlInNewTab(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
