<?php

namespace App\Filament\Resources\CustomRequests\Tables;

use App\CustomRequestStatus;
use App\Filament\Resources\Invitations\InvitationResource;
use App\Models\CustomRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Requested by')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('event_type')
                    ->badge(),

                TextColumn::make('event_date')
                    ->date()
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('budget')
                    ->money('IDR')
                    ->placeholder('—'),

                SelectColumn::make('status')
                    ->options([
                        CustomRequestStatus::New->value => 'New',
                        CustomRequestStatus::InReview->value => 'In review',
                        CustomRequestStatus::InProgress->value => 'In progress',
                        CustomRequestStatus::Delivered->value => 'Delivered',
                        CustomRequestStatus::Cancelled->value => 'Cancelled',
                    ]),

                TextColumn::make('assignedAdmin.name')
                    ->label('Assigned to')
                    ->placeholder('Unassigned'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        CustomRequestStatus::New->value => 'New',
                        CustomRequestStatus::InReview->value => 'In review',
                        CustomRequestStatus::InProgress->value => 'In progress',
                        CustomRequestStatus::Delivered->value => 'Delivered',
                        CustomRequestStatus::Cancelled->value => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                Action::make('assignToMe')
                    ->label('Assign to me')
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->visible(fn (CustomRequest $record): bool => $record->assigned_admin_id !== auth()->id())
                    ->action(fn (CustomRequest $record) => $record->update(['assigned_admin_id' => auth()->id()])),

                Action::make('build')
                    ->label(fn (CustomRequest $record): string => $record->invitation_id ? 'Open invitation' : 'Build this')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (CustomRequest $record): string => $record->invitation_id
                        ? InvitationResource::getUrl('edit', ['record' => $record->invitation_id])
                        : route('admin.custom-requests.build', $record)),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
