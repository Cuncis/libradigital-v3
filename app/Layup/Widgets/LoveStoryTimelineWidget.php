<?php

declare(strict_types=1);

namespace App\Layup\Widgets;

use Crumbls\Layup\View\TimelineWidget;

/**
 * A themed preset of Layup's built-in Timeline widget, pre-filled with a
 * couple's-story shape instead of a generic company timeline. Reuses the
 * parent's form schema and Blade view unchanged — only the type/label/icon
 * and default placeholder content differ.
 */
class LoveStoryTimelineWidget extends TimelineWidget
{
    public static function getType(): string
    {
        return 'love-story-timeline';
    }

    public static function getLabel(): string
    {
        return 'Love Story Timeline';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-heart';
    }

    public static function getDefaultData(): array
    {
        return [
            'events' => [
                ['date' => '', 'title' => 'How We Met', 'description' => ''],
                ['date' => '', 'title' => 'First Date', 'description' => ''],
                ['date' => '', 'title' => 'The Proposal', 'description' => ''],
            ],
            'line_color' => null,
        ];
    }

    public static function getPreview(array $data): string
    {
        $count = count($data['events'] ?? []);

        return "Love Story ({$count} moments)";
    }
}
