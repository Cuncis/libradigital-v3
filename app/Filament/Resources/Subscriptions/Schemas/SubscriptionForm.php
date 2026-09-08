<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\SubscriptionStatus;
use App\UserRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Customer')
                ->relationship('user', 'name', fn ($query) => $query->where('role', UserRole::Customer))
                ->searchable(['name', 'email'])
                ->required(),

            Select::make('plan_id')
                ->relationship('plan', 'name')
                ->required(),

            Select::make('status')
                ->options([
                    SubscriptionStatus::Pending->value => 'Pending',
                    SubscriptionStatus::Active->value => 'Active',
                    SubscriptionStatus::Expired->value => 'Expired',
                    SubscriptionStatus::Cancelled->value => 'Cancelled',
                ])
                ->required()
                ->helperText('Set to Active here to comp a subscription without going through Mayar.'),

            DateTimePicker::make('current_period_end'),

            TextInput::make('mayar_member_id')
                ->label('Mayar member ID'),

            TextInput::make('mayar_invoice_id')
                ->label('Mayar invoice ID'),
        ]);
    }
}
