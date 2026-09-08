<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ThemeSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Theme::query()->create([
            'name' => 'Blank',
            'description' => 'Start from an empty canvas.',
            'category' => 'general',
            'content' => ['rows' => []],
            'is_active' => true,
        ]);

        Theme::query()->create([
            'name' => 'Elegant Wedding',
            'description' => 'Hero, countdown, gallery, and venue map for a classic wedding invitation.',
            'category' => 'wedding',
            'content' => $this->weddingContent(),
            'is_active' => true,
        ]);

        Theme::query()->create([
            'name' => 'Modern Birthday',
            'description' => 'Bold hero and countdown for a birthday celebration.',
            'category' => 'birthday',
            'content' => $this->birthdayContent(),
            'is_active' => true,
        ]);

        Theme::query()->create([
            'name' => 'Corporate Event',
            'description' => 'Clean hero, agenda heading, and venue map for a corporate event.',
            'category' => 'corporate',
            'content' => $this->corporateContent(),
            'is_active' => true,
        ]);
    }

    protected function weddingContent(): array
    {
        return [
            'rows' => [
                $this->row([
                    $this->widget('hero', [
                        'title' => 'The Wedding Of',
                        'subtitle' => 'Bride & Groom',
                    ]),
                ]),
                $this->row([
                    $this->widget('countdown', [
                        'target_date' => now()->addMonths(3)->toDateString(),
                    ]),
                ]),
                $this->row([
                    $this->widget('gallery', ['images' => []]),
                ]),
                $this->row([
                    $this->widget('testimonial', [
                        'content' => 'A love story worth celebrating.',
                    ]),
                ]),
                $this->row([
                    $this->widget('map', ['address' => '']),
                ]),
            ],
        ];
    }

    protected function birthdayContent(): array
    {
        return [
            'rows' => [
                $this->row([
                    $this->widget('hero', [
                        'title' => "You're Invited",
                        'subtitle' => 'Birthday Celebration',
                    ]),
                ]),
                $this->row([
                    $this->widget('countdown', [
                        'target_date' => now()->addMonth()->toDateString(),
                    ]),
                ]),
                $this->row([
                    $this->widget('gallery', ['images' => []]),
                ]),
            ],
        ];
    }

    protected function corporateContent(): array
    {
        return [
            'rows' => [
                $this->row([
                    $this->widget('hero', [
                        'title' => 'You Are Invited',
                        'subtitle' => 'Company Event',
                    ]),
                ]),
                $this->row([
                    $this->widget('heading', ['content' => 'Agenda', 'level' => 'h2']),
                    $this->widget('text', ['content' => 'Event schedule and details go here.']),
                ]),
                $this->row([
                    $this->widget('map', ['address' => '']),
                ]),
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $widgets
     * @return array<string, mixed>
     */
    protected function row(array $widgets): array
    {
        return [
            'id' => 'row_'.Str::random(8),
            'settings' => [],
            'columns' => [
                [
                    'id' => 'col_'.Str::random(8),
                    'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                    'settings' => [],
                    'widgets' => $widgets,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function widget(string $type, array $data): array
    {
        return [
            'id' => 'widget_'.Str::random(8),
            'type' => $type,
            'data' => $data,
        ];
    }
}
