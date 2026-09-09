<?php

namespace App\Filament\Resources\Themes\Tables;

use App\Models\Theme;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ThemesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('preview_image')
                    ->disk('r2')
                    ->label(''),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->badge(),

                IconColumn::make('is_active')
                    ->boolean(),

                TextColumn::make('invitations_count')
                    ->label('Used by')
                    ->counts('invitations'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'wedding' => 'Wedding',
                        'birthday' => 'Birthday',
                        'corporate' => 'Corporate',
                        'general' => 'General',
                    ]),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('Preview')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Theme $record): string => route('themes.preview', $record))
                    ->openUrlInNewTab(),
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
