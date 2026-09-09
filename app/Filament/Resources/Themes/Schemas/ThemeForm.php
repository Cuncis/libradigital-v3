<?php

namespace App\Filament\Resources\Themes\Schemas;

use App\Layup\Forms\Components\LayupBuilder;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ThemeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Details')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Select::make('category')
                        ->options([
                            'wedding' => 'Wedding',
                            'birthday' => 'Birthday',
                            'corporate' => 'Corporate',
                            'general' => 'General',
                        ]),

                    Textarea::make('description')
                        ->columnSpanFull(),

                    // Not a relationship — this stores the picked Media
                    // record's id directly in the plain `preview_image`
                    // string column (see ThemesTable's ImageColumn, which
                    // resolves that id back to a URL). A real belongsTo
                    // would need a dedicated FK column; not worth a
                    // migration for a single admin-only preview image.
                    CuratorPicker::make('preview_image')
                        ->columnSpanFull(),

                    Toggle::make('is_active')
                        ->default(true)
                        ->columnSpanFull(),
                ]),

            LayupBuilder::make('content')
                ->columnSpanFull(),
        ]);
    }
}
