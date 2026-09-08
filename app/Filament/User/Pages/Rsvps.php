<?php

namespace App\Filament\User\Pages;

use App\Models\Guest;
use App\RsvpStatus;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Attributes\Computed;

class Rsvps extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.user.pages.rsvps';

    #[Computed]
    public function attendingCount(): int
    {
        return (int) Guest::query()
            ->whereHas('invitation', fn ($query) => $query->where('user_id', auth()->id()))
            ->where('attending', RsvpStatus::Attending)
            ->sum('party_size');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Guest::query()->whereHas('invitation', fn ($query) => $query->where('user_id', auth()->id()))
            )
            ->columns([
                TextColumn::make('invitation.title')
                    ->label('Invitation'),

                TextColumn::make('name'),

                TextColumn::make('attending')
                    ->badge()
                    ->color(fn (?RsvpStatus $state): string => match ($state) {
                        RsvpStatus::Attending => 'success',
                        RsvpStatus::NotAttending => 'danger',
                        RsvpStatus::Maybe => 'warning',
                        null => 'gray',
                    }),

                TextColumn::make('party_size'),

                TextColumn::make('message')
                    ->limit(40)
                    ->placeholder('—'),

                TextColumn::make('responded_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Export CSV')
                    ->url(route('rsvps.export')),
            ])
            ->defaultSort('responded_at', 'desc');
    }
}
