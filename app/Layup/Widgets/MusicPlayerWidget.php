<?php

declare(strict_types=1);

namespace App\Layup\Widgets;

use App\Models\Media;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Crumbls\Layup\View\BaseWidget;
use Filament\Forms\Components\Toggle;

class MusicPlayerWidget extends BaseWidget
{
    public static function getType(): string
    {
        return 'music-player';
    }

    public static function getLabel(): string
    {
        return 'Background Music';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-musical-note';
    }

    public static function getCategory(): string
    {
        return 'interactive';
    }

    public static function getContentFormSchema(): array
    {
        return [
            // Stores the picked Media Library record's id, not a storage
            // path — see music-player.blade.php, which resolves it back
            // to a URL, and getPreview() below.
            CuratorPicker::make('audio_file')
                ->label('Audio file')
                ->acceptedFileTypes(['audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/wav'])
                ->maxSize(config('layup.uploads.max_size', 10240)),

            Toggle::make('autoplay')
                ->label('Try to autoplay')
                ->helperText('Most browsers block audio autoplay until the guest interacts with the page — this is a best-effort attempt, the floating button always works regardless.')
                ->default(false),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'audio_file' => '',
            'autoplay' => false,
        ];
    }

    public static function getPreview(array $data): string
    {
        if (empty($data['audio_file'])) {
            return '(no audio file)';
        }

        return Media::query()->find($data['audio_file'])?->pretty_name ?? '(audio file removed)';
    }

    protected function getViewName(): string
    {
        return 'components.layup.music-player';
    }
}
