<?php

namespace App\Filament\Resources\CustomRequests\Schemas;

use App\CustomRequestStatus;
use App\UserRole;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Requested by')
                ->relationship('user', 'name')
                ->searchable(['name', 'email'])
                ->required(),

            Select::make('event_type')
                ->options([
                    'wedding' => 'Wedding',
                    'birthday' => 'Birthday',
                    'corporate' => 'Corporate',
                    'other' => 'Other',
                ])
                ->required(),

            DatePicker::make('event_date'),

            TextInput::make('budget')
                ->numeric()
                ->prefix('Rp'),

            Select::make('status')
                ->options([
                    CustomRequestStatus::New->value => 'New',
                    CustomRequestStatus::InReview->value => 'In review',
                    CustomRequestStatus::InProgress->value => 'In progress',
                    CustomRequestStatus::Delivered->value => 'Delivered',
                    CustomRequestStatus::Cancelled->value => 'Cancelled',
                ])
                ->required(),

            Select::make('assigned_admin_id')
                ->label('Assigned to')
                ->relationship('assignedAdmin', 'name', fn ($query) => $query->where('role', UserRole::Admin))
                ->searchable(),

            Textarea::make('style_notes')
                ->label('What they have in mind')
                ->columnSpanFull(),
        ]);
    }
}
