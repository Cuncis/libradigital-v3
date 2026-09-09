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
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
