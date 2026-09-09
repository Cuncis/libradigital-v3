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

        // Row/column/widget markup itself lives inside the WYSIWYG iframe
        // now (built server-side by renderCanvasFrame() — see
        // LayupCanvasFrameTest), not the outer page's light DOM. This just
        // proves the outer page wires the iframe up and the structure
        // panel's scroll-to-node methods target it correctly.
        $response->assertOk();
        $response->assertSee('x-ref="canvasFrame"', false);
        $response->assertSee('@load="onCanvasFrameLoad()"', false);
        $response->assertSee('scrollToWidget', false);
        $response->assertSee('canvasFrame?.contentDocument', false);
    }

    /**
     * Regression: the iframe used to auto-resize itself to its content's
     * scrollHeight on load (and on every ResizeObserver tick). Widgets
     * like Hero use `min-height: 70vh` (see hero.blade.php), which
     * resolves against the iframe's OWN viewport — so growing the iframe
     * to fit Hero, which then grows again because the iframe grew, is a
     * circular dependency. It doesn't run away to infinity (the vh
     * coefficient is < 1, so it's a converging series), but it converges
     * on a Hero several times taller than the real page, which is exactly
     * what "my hero is too long" looks like. Fixed by giving the iframe a
     * fixed viewport height instead and letting it scroll internally.
     */
    public function test_the_canvas_iframe_has_a_fixed_height_instead_of_auto_growing_to_its_content(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/admin/invitations/create');

        $response->assertOk();
        $response->assertSee('.lyp-canvas-frame', false);
        $response->assertSee('height: 75vh', false);
    }

    /**
     * Invitations are guest-facing links almost exclusively opened on a
     * phone, so the builder canvas only offers a mobile-width preview
     * (config/layup.php `breakpoints`/`default_breakpoint`) rather than
     * the desktop/tablet/mobile toggle Layup ships by default.
     */
    public function test_the_canvas_only_offers_a_mobile_breakpoint(): void
    {
        // The source of truth LayupBuilder::getBreakpointsProperty() reads
        // from directly. The icon names ('heroicon-o-device-tablet' etc.)
        // are static strings baked into the Alpine template regardless of
        // config, so asserting against rendered HTML would false-positive.
        $this->assertSame(['sm'], array_keys(config('layup.breakpoints')));
        $this->assertSame('sm', config('layup.default_breakpoint'));
        $this->assertSame(390, config('layup.breakpoints.sm.width'));
    }
}
