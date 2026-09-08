<?php

namespace App\Filament\RelationManagers;

use Crumbls\Layup\Models\PageRevision;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only: revisions are auto-saved by Layup whenever content changes
 * (see Crumbls\Layup\Models\Page::booted()). This manager only surfaces them
 * and offers a one-click restore — no create/edit/delete, those don't apply.
 *
 * Shared between the admin and customer InvitationResources (identical
 * behavior in both panels — access to the record itself is already gated
 * by each resource's own query scope/policy).
 */
class RevisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'revisions';

    protected static ?string $title = 'Version history';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('note')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Saved')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('author')
                    ->placeholder('—'),

                TextColumn::make('note')
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([
                Action::make('restore')
                    ->requiresConfirmation()
                    ->modalDescription('Replace the current content with this version? This creates a new revision of the restored state — nothing is lost.')
                    ->action(function (PageRevision $record): void {
                        $this->getOwnerRecord()->restoreRevision($record);

                        Notification::make()
                            ->title('Version restored')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([]);
    }
}
