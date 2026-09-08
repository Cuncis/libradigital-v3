<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\SubscriptionStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('plan.name')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (SubscriptionStatus $state): string => match ($state) {
                        SubscriptionStatus::Active => 'success',
                        SubscriptionStatus::Pending => 'warning',
                        SubscriptionStatus::Expired, SubscriptionStatus::Cancelled => 'danger',
                    }),

                TextColumn::make('current_period_end')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        SubscriptionStatus::Pending->value => 'Pending',
                        SubscriptionStatus::Active->value => 'Active',
                        SubscriptionStatus::Expired->value => 'Expired',
                        SubscriptionStatus::Cancelled->value => 'Cancelled',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
