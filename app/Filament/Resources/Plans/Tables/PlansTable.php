<?php

namespace App\Filament\Resources\Plans\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tier')
                    ->badge(),

                TextColumn::make('billing_interval'),

                TextColumn::make('price')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('invitation_limit')
                    ->placeholder('Unlimited'),

                TextColumn::make('mayar_tier_id')
                    ->label('Mayar tier ID')
                    ->placeholder('Not configured')
                    ->color(fn (?string $state): string => $state ? 'gray' : 'danger'),

                TextColumn::make('subscriptions_count')
                    ->label('Subscribers')
                    ->counts('subscriptions'),
            ])
            ->defaultSort('price')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
