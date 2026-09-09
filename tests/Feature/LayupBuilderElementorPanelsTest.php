<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the Elementor-style 3-panel layout added on top
 * of Layup's builder (resources/views/vendor/layup/forms/components/layup-builder.blade.php):
 * a persistent left widget palette, the existing center canvas, and a new
 * right structure/outline panel — restructured from Layup's original
 * single-column canvas + modal widget picker.
 */
class LayupBuilderElementorPanelsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_create_page_renders_the_widget_palette_and_structure_panels(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/admin/invitations/create');

        $response->assertOk();
        $response->assertSee('class="lyp-sidebar-left"', false);
        $response->assertSee('class="lyp-sidebar-right"', false);
        $response->assertSee('lyp-structure-body', false);
        $response->assertSee('onPickerDragStart', false);
        $response->assertSee('quickAddWidget', false);

        // Positions of the actual panel elements, not the CSS selectors of
        // the same name in the <style> block emitted earlier in the page.
        $html = $response->getContent();
        $leftPos = strpos($html, 'x-show="leftPanelOpen"');
        $canvasPos = strpos($html, 'class="lyp-canvas"');
        $rightPos = strpos($html, 'x-show="rightPanelOpen"');

        $this->assertNotFalse($leftPos);
        $this->assertNotFalse($canvasPos);
        $this->assertNotFalse($rightPos);
        $this->assertTrue($leftPos < $canvasPos, 'Widget palette must render before the canvas.');
        $this->assertTrue($canvasPos < $rightPos, 'Structure panel must render after the canvas.');
    }

    public function test_the_edit_page_marks_up_rows_columns_and_widgets_for_structure_panel_scrolling(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $invitation = Invitation::factory()->create([
            'content' => [
                'rows' => [[
                    'id' => 'row_1',
                    'settings' => [],
                    'columns' => [[
                        'id' => 'col_1',
                        'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                        'settings' => [],
                        'widgets' => [[
                            'id' => 'widget_1',
                            'type' => 'heading',
                            'data' => ['content' => 'Structure Panel Target', 'level' => 'h2'],
                        ]],
                    ]],
                ]],
            ],
        ]);

        $response = $this->actingAs($admin)->get("/admin/invitations/{$invitation->id}/edit");

        $response->assertOk();
        $response->assertSee(':data-row-id="row.id"', false);
        $response->assertSee(':data-col-id="col.id"', false);
        $response->assertSee(':data-widget-id="widget.id"', false);
        $response->assertSee('scrollToWidget', false);
    }
}
