<?php

declare(strict_types=1);

namespace App\Layup\Widgets;

use Crumbls\Layup\View\BaseWidget;
use Filament\Forms\Components\TextInput;

class RsvpFormWidget extends BaseWidget
{
    public static function getType(): string
    {
        return 'rsvp-form';
    }

    public static function getLabel(): string
    {
        return 'RSVP Form';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-envelope-open';
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
                ->default('Will you be attending?'),

            TextInput::make('submit_label')
                ->label('Button text')
                ->default('Send RSVP'),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'title' => 'Will you be attending?',
            'submit_label' => 'Send RSVP',
        ];
    }

    public static function getPreview(array $data): string
    {
        return $data['title'] ?? 'RSVP Form';
    }

    /**
     * The generator scaffolds this as `public static`, which fatals: the
     * parent (BaseBladeWidget) declares it as a non-static instance method,
     * so a static override is an incompatible signature. Also needed here
     * regardless, since the default `layup::components.{type}` convention
     * resolves against the vendor package's own view namespace — not where
     * `layup:make-widget` actually puts a custom widget's view file.
     */
    protected function getViewName(): string
    {
        return 'components.layup.rsvp-form';
    }
}
