<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
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

    // A full-screen takeover — no sidebar, no topbar — the same way
    // Elementor's own editor replaces wp-admin's chrome entirely rather
    // than living inside it. See the layout file's own docblock for how
    // this stays lightweight (it only removes chrome, adds nothing).
    protected static string $layout = 'filament.layouts.full-screen-editor';

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview')
                ->color('gray')
                ->icon(Heroicon::OutlinedEye)
                ->url(fn (Invitation $record): string => route('invitations.preview', $record))
                ->openUrlInNewTab(),
            InvitationResource::copyLinkAction(),
            // Not getSaveFormAction() — that renders a native type="submit"
            // button wired to the <form>'s wire:submit, which only works
            // while the button lives inside that <form>. Header actions
            // render outside it, so the button did nothing when clicked.
            // ->action() calls the same save() method directly instead,
            // the same way every other header action (Delete, Restore...)
            // already works regardless of where it renders.
            Action::make('save')
                ->label('Save changes')
                ->color('primary')
                ->icon(Heroicon::OutlinedCheck)
                ->action('save')
                ->keyBindings(['mod+s']),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    // Save moved into the header (between Preview and Delete) above, so the
    // default sticky bottom bar would otherwise just duplicate it.
    protected function getFormActions(): array
    {
        return [];
    }
}
