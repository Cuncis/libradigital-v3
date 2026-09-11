<?php

namespace App\Filament\Resources\Invitations;

use App\Filament\RelationManagers\RevisionsRelationManager;
use App\Filament\Resources\Invitations\Pages\CreateInvitation;
use App\Filament\Resources\Invitations\Pages\EditInvitation;
use App\Filament\Resources\Invitations\Pages\ListInvitations;
use App\Filament\Resources\Invitations\Schemas\InvitationForm;
use App\Filament\Resources\Invitations\Tables\InvitationsTable;
use App\Models\Invitation;
use BackedEnum;
use Filament\Actions\Action;
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
     * The real guest-facing link (route('invitations.show'), /i/{slug}) —
     * distinct from the "Preview" action next to it, which opens a
     * separate auth-gated route usable regardless of publish status.
     * Copying is a browser clipboard API, not something a server round
     * trip can do, so this has no ->action() — same alpineClickHandler()
     * pattern as the Media Library's Copy Link (MediaResource), with the
     * URL embedded directly since it's already known server-side.
     */
    public static function copyLinkAction(): Action
    {
        return Action::make('copyLink')
            ->label('Copy Link')
            ->icon(Heroicon::OutlinedLink)
            ->color('gray')
            ->alpineClickHandler(function (Invitation $record): string {
                // route('invitations.show', $record) would embed the model's
                // route key (id) instead of its slug — the /i/{slug} route
                // parameter isn't named "invitation", so Laravel's URL
                // generator doesn't know to substitute the slug column, it
                // just calls $record->getRouteKey() (id) regardless. Passing
                // the slug explicitly is the only way to get the real link.
                $urlJs = Js::from(route('invitations.show', ['slug' => $record->slug]));
                $messageJs = Js::from('Copied!');

                return <<<JS
                    window.navigator.clipboard.writeText({$urlJs})
                    \$tooltip({$messageJs}, {
                        theme: \$store.theme,
                        timeout: 2000,
                    })
                    JS;
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
}
