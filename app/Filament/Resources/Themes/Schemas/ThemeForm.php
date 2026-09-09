<?php

namespace App\Filament\Resources\Themes\Schemas;

use App\Layup\Forms\Components\LayupBuilder;
use Filament\Forms\Components\FileUpload;
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

                    FileUpload::make('preview_image')
                        ->image()
                        ->disk('r2')
                        ->directory('theme-previews')
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
