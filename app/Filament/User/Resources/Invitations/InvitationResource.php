<?php

namespace App\Filament\User\Resources\Invitations;

use App\Filament\RelationManagers\RevisionsRelationManager;
use App\Filament\User\Resources\Invitations\Pages\CreateInvitation;
use App\Filament\User\Resources\Invitations\Pages\EditInvitation;
use App\Filament\User\Resources\Invitations\Pages\ListInvitations;
use App\Filament\User\Resources\Invitations\Schemas\InvitationForm;
use App\Filament\User\Resources\Invitations\Tables\InvitationsTable;
use App\Models\Invitation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Js;

class InvitationResource extends Resource
{
    protected static ?string $model = Invitation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    public static function form(Schema $schema): Schema
    {
        return InvitationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvitationsTable::configure($table);
    }

    /**
     * One combined action: opens a modal to customize the slug (the
     * customizable part of the public /i/{slug} link, pre-filled from the
     * record) and copies the resulting link to the clipboard on submit —
     * merged from two previously separate actions (a read-only Copy Link
     * and a slug-editing Customize Link) per explicit request to make them
     * one. Saving happens first via ->action(), then the clipboard write
     * runs client-side via Livewire's $livewire->js() (a real browser API,
     * not something the server round trip itself can do) using the
     * now-current slug.
     */
    public static function copyLinkAction(): Action
    {
        return Action::make('copyLink')
            ->label('Copy Link')
            ->icon(Heroicon::OutlinedLink)
            ->color('gray')
            ->modalHeading('Invitation Link')
            ->modalDescription('Customize the link below, then copy it to share with guests.')
            ->modalSubmitActionLabel('Copy Link')
            ->schema([
                TextInput::make('slug')
                    ->label('Link')
                    ->prefix(url('/i').'/')
                    ->required()
                    ->maxLength(255)
                    ->unique(table: 'invitations', ignoreRecord: true)
                    ->helperText('Editing this changes the public link.'),
            ])
            ->fillForm(fn (Invitation $record): array => ['slug' => $record->slug])
            ->action(function (Invitation $record, array $data, $livewire): void {
                $record->update(['slug' => $data['slug']]);

                // route('invitations.show', $record) would embed the model's
                // route key (id) instead of its slug — the /i/{slug} route
                // parameter isn't named "invitation", so Laravel's URL
                // generator doesn't know to substitute the slug column, it
                // just calls $record->getRouteKey() (id) regardless. Passing
                // the slug explicitly is the only way to get the real link.
                $urlJs = Js::from(route('invitations.show', ['slug' => $record->slug]));
                $livewire->js("window.navigator.clipboard.writeText({$urlJs})");

                Notification::make()
                    ->title('Link copied')
                    ->success()
                    ->send();
            });
    }

    public static function getRelations(): array
    {
        return [
            RevisionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvitations::route('/'),
            'create' => CreateInvitation::route('/create'),
            'edit' => EditInvitation::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Manual per-owner scoping (this panel's chosen convention over Filament
     * tenancy — see plan.md Phase 3) — a customer only ever sees their own
     * invitations here, never another customer's.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }
}
