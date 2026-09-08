<?php

namespace App\Filament\Resources\Invitations\Schemas;

use App\Models\Theme;
use App\UserRole;
use Crumbls\Layup\Forms\Components\LayupBuilder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class InvitationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Details')
                ->columns(2)
                ->schema([
                    Select::make('user_id')
                        ->label('Owner')
                        ->relationship('user', 'name', fn ($query) => $query->where('role', UserRole::Customer))
                        ->searchable(['name', 'email'])
                        ->required()
                        ->columnSpanFull(),

                    Select::make('theme_id')
                        ->label('Start from a theme (optional)')
                        ->relationship('theme', 'name', fn ($query) => $query->active())
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set): void {
                            $set('content', $state ? Theme::query()->find($state)?->content : ['rows' => []]);
                        })
                        ->hidden(fn (string $operation): bool => $operation !== 'create')
                        ->columnSpanFull(),

                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Set $set, string $operation): void {
                            if ($operation === 'create') {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),

                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(table: 'invitations', ignoreRecord: true)
                        ->helperText('Used in the public invitation link.'),

                    TextInput::make('host_name')
                        ->label('Host name(s)')
                        ->maxLength(255),

                    TextInput::make('venue')
                        ->maxLength(255),

                    DateTimePicker::make('event_date'),

                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'scheduled' => 'Scheduled',
                            'published' => 'Published',
                        ])
                        ->default('draft')
                        ->required(),

                    DateTimePicker::make('published_at')
                        ->label('Publish at')
                        ->helperText('Leave blank to publish immediately when status is Published.'),

                    Toggle::make('is_custom_build')
                        ->label('Custom-built by staff')
                        ->helperText('Set automatically when built from a custom request.')
                        ->columnSpanFull(),
                ]),

            LayupBuilder::make('content')
                ->columnSpanFull(),
        ]);
    }
}
