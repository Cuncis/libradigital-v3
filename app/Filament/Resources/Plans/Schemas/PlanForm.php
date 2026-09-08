<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Plan')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('tier')
                        ->required()
                        ->maxLength(255)
                        ->helperText('e.g. starter, plus, pro, organizer — pairs with billing_interval as the unique key.'),

                    TextInput::make('billing_interval')
                        ->required()
                        ->helperText('monthly or yearly.'),

                    TextInput::make('price')
                        ->numeric()
                        ->required()
                        ->prefix('Rp')
                        ->helperText('Plain Rupiah, no minor unit.'),

                    TextInput::make('currency')
                        ->default('IDR')
                        ->required()
                        ->maxLength(3),

                    TextInput::make('invitation_limit')
                        ->numeric()
                        ->helperText('Leave blank for unlimited.'),

                    TextInput::make('mayar_tier_id')
                        ->label('Mayar tier ID')
                        ->helperText('From the Mayar dashboard — shared across a tier\'s monthly and yearly rows.'),
                ]),

            Section::make('Features')
                ->columns(2)
                ->schema([
                    Toggle::make('features.remove_branding')->label('Remove branding'),
                    Toggle::make('features.custom_domain')->label('Custom domain'),
                    Toggle::make('features.guest_personalization')->label('Guest personalization'),
                    Toggle::make('features.priority_support')->label('Priority support'),
                    Toggle::make('features.white_label')->label('White-label'),
                    TextInput::make('features.rsvp_limit')
                        ->label('RSVP limit')
                        ->numeric()
                        ->helperText('Leave blank for unlimited.'),
                ]),
        ]);
    }
}
