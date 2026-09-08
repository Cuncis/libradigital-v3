<?php

namespace App\Filament\Resources\CustomRequests;

use App\Filament\Resources\CustomRequests\Pages\CreateCustomRequest;
use App\Filament\Resources\CustomRequests\Pages\EditCustomRequest;
use App\Filament\Resources\CustomRequests\Pages\ListCustomRequests;
use App\Filament\Resources\CustomRequests\Schemas\CustomRequestForm;
use App\Filament\Resources\CustomRequests\Tables\CustomRequestsTable;
use App\Models\CustomRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomRequestResource extends Resource
{
    protected static ?string $model = CustomRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CustomRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomRequests::route('/'),
            'create' => CreateCustomRequest::route('/create'),
            'edit' => EditCustomRequest::route('/{record}/edit'),
        ];
    }
}
