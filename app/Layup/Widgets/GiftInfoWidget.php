<?php

declare(strict_types=1);

namespace App\Layup\Widgets;

use Crumbls\Layup\View\BaseWidget;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class GiftInfoWidget extends BaseWidget
{
    public static function getType(): string
    {
        return 'gift-info';
    }

    public static function getLabel(): string
    {
        return 'Gift Info';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-gift';
    }

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getContentFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->label('Heading')
                ->default('Send a Gift'),

            Textarea::make('intro_text')
                ->label('Intro text')
                ->default('Your presence means the world to us. If you\'d like to send a gift, here are our details.'),

            Repeater::make('accounts')
                ->label('Bank / e-wallet accounts')
                ->schema([
                    TextInput::make('bank_name')->label('Bank / provider')->required(),
                    TextInput::make('account_number')->label('Account number')->required(),
                    TextInput::make('account_holder')->label('Account holder'),
                ])
                ->defaultItems(1)
                ->columnSpanFull(),

            TextInput::make('digital_link')
                ->label('Digital gift link (optional)')
                ->url(),

            TextInput::make('digital_link_label')
                ->label('Digital gift link button text')
                ->default('Send a Digital Gift'),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'title' => 'Send a Gift',
            'intro_text' => 'Your presence means the world to us. If you\'d like to send a gift, here are our details.',
            'accounts' => [],
            'digital_link' => '',
            'digital_link_label' => 'Send a Digital Gift',
        ];
    }

    public static function getPreview(array $data): string
    {
        $count = count($data['accounts'] ?? []);

        return "Gift Info ({$count} account".($count === 1 ? '' : 's').')';
    }

    protected function getViewName(): string
    {
        return 'components.layup.gift-info';
    }
}
