<?php

namespace Tests\Feature;

use App\Layup\Forms\Components\LayupBuilder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * The WYSIWYG iframe canvas (App\Layup\Forms\Components\LayupBuilder::
 * renderCanvasFrame()) renders the in-progress, possibly-unsaved builder
 * content using the real widget views + the real invitation stylesheet,
 * so the editor canvas looks like the actual public page instead of
 * Layup's generic placeholder boxes.
 */
class LayupCanvasFrameTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_renders_a_widget_that_never_opted_into_layups_live_preview(): void
    {
        // Hero never overrides supportsLivePreview() (defaults to false),
        // so Layup's own canvas would only ever show plain text for it.
        $html = LayupBuilder::make('content')->renderCanvasFrame([
            'rows' => [[
                'id' => 'row_1',
                'columns' => [[
                    'id' => 'col_1',
                    'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                    'widgets' => [[
                        'id' => 'widget_1',
                        'type' => 'hero',
                        'data' => ['heading' => 'Real Rendered Heading', 'subheading' => 'Real Rendered Sub'],
                    ]],
                ]],
            ]],
        ]);

        $this->assertStringContainsString('Real Rendered Heading', $html);
        $this->assertStringContainsString('Real Rendered Sub', $html);
        $this->assertStringContainsString('data-row-id="row_1"', $html);
        $this->assertStringContainsString('data-col-id="col_1"', $html);
        $this->assertStringContainsString('data-widget-id="widget_1"', $html);
    }

    public function test_it_loads_the_real_invitation_stylesheet(): void
    {
        $html = LayupBuilder::make('content')->renderCanvasFrame(['rows' => []]);

        $this->assertStringContainsString('invitation', $html);
        $this->assertStringContainsString('stylesheet', $html);
    }

    public function test_a_malformed_row_or_widget_is_skipped_instead_of_crashing(): void
    {
        $html = LayupBuilder::make('content')->renderCanvasFrame([
            'rows' => [
                'not-a-row',
                [
                    'id' => 'row_1',
                    'columns' => [
                        'not-a-column',
                        [
                            'id' => 'col_1',
                            'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                            'widgets' => [
                                'not-a-widget',
                                ['id' => 'widget_1', 'type' => 'heading', 'data' => ['content' => 'Still Renders', 'level' => 'h2']],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertStringContainsString('Still Renders', $html);
    }

    public function test_an_unregistered_widget_type_is_skipped_instead_of_crashing(): void
    {
        $html = LayupBuilder::make('content')->renderCanvasFrame([
            'rows' => [[
                'id' => 'row_1',
                'columns' => [[
                    'id' => 'col_1',
                    'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                    'widgets' => [
                        ['id' => 'widget_1', 'type' => 'does-not-exist', 'data' => []],
                        ['id' => 'widget_2', 'type' => 'heading', 'data' => ['content' => 'Still Renders', 'level' => 'h2']],
                    ],
                ]],
            ]],
        ]);

        $this->assertStringContainsString('data-widget-id="widget_1"', $html);
        $this->assertStringContainsString('Still Renders', $html);
    }

    public function test_empty_content_renders_the_empty_state_without_error(): void
    {
        $html = LayupBuilder::make('content')->renderCanvasFrame([]);

        $this->assertStringContainsString('No rows yet', $html);
    }

    public function test_it_renders_column_actions_disabling_move_at_the_edges(): void
    {
        $html = LayupBuilder::make('content')->renderCanvasFrame([
            'rows' => [[
                'id' => 'row_1',
                'columns' => [
                    ['id' => 'col_1', 'span' => ['sm' => 12, 'md' => 12, 'lg' => 6, 'xl' => 6], 'widgets' => []],
                    ['id' => 'col_2', 'span' => ['sm' => 12, 'md' => 12, 'lg' => 6, 'xl' => 6], 'widgets' => []],
                ],
            ]],
        ]);

        $this->assertMatchesRegularExpression(
            '/data-lyp-action="col-move-left" data-row-id="row_1" data-col-id="col_1" disabled/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/data-lyp-action="col-move-right" data-row-id="row_1" data-col-id="col_2" disabled/',
            $html,
        );
        $this->assertStringContainsString('data-lyp-action="col-delete" data-row-id="row_1" data-col-id="col_1"', $html);
    }
}
