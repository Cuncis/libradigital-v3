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

    /**
     * Right-click cut/copy/duplicate/paste/delete menu, wired up from both
     * the canvas iframe and the structure panel.
     */
    public function test_the_create_page_wires_up_the_context_menu(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/admin/invitations/create');

        $response->assertOk();
        $response->assertSee('class="lyp-context-menu"', false);
        $response->assertSee('@click="contextCopy()"', false);
        $response->assertSee('@click="contextCut()"', false);
        $response->assertSee('@click="contextDuplicate()"', false);
        $response->assertSee('@click="contextPaste()"', false);
        $response->assertSee('@click="contextDelete()"', false);
        $response->assertSee(':disabled="!canPasteHere()"', false);

        // Triggered from the structure panel's row/column/widget nodes
        // (the iframe's own delegated listener, wired in onCanvasFrameLoad,
        // lives inside the Livewire @script block, which the raw HTTP
        // response HTML-escapes — not worth asserting against that form).
        $response->assertSee("openContextMenu(\$event, 'row', row.id, null, null)", false);
        $response->assertSee("openContextMenu(\$event, 'column', row.id, col.id, null)", false);
        $response->assertSee("openContextMenu(\$event, 'widget', row.id, col.id, widget.id)", false);
    }

    /**
     * Regression: deleting a row (from the row toolbar or the right-click
     * menu) opens Layup's own confirmation modal — on confirm, the server
     * removes it from `content.rows` and dispatches a `layup-row-deleted`
     * browser event, whose listener called rowDeleted(id) to filter the
     * row out of the client's `content.rows` too. Every sibling
     * layup-*-deleted/updated listener also calls pushHistory() (which
     * re-renders the WYSIWYG iframe canvas), but rowDeleted() didn't — so
     * the row genuinely was gone from state, yet stayed visible in the
     * canvas until some unrelated action happened to trigger a re-render.
     * From the user's seat: "right-click delete row, it doesn't delete."
     */
    public function test_row_deletion_triggers_a_canvas_re_render(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/admin/invitations/create');
        $response->assertOk();

        $html = $response->getContent();
        $start = strpos($html, 'rowDeleted: function(id) {');
        $this->assertNotFalse($start, 'rowDeleted() not found.');
        $body = substr($html, $start, 400);

        $this->assertStringContainsString('pushHistory()', $body);
    }

    /**
     * The structure panel used to be click-to-scroll-only. It now drives
     * the exact same drag state/methods the canvas iframe already uses
     * (rowDrag + onRowDragStart/Over/Drop for rows, drag + onDragStart/
     * onDragOverWidget/onDragOverCol/onDropCol for widgets and for
     * dropping a palette widget straight into a column) — a second set of
     * native HTML5 drag attributes on the light-DOM nodes, not a parallel
     * implementation, so dragging a row/widget in the structure panel
     * reorders it, and dragging a widget from the left palette onto a
     * structure panel row/column inserts it there too.
     */
    public function test_the_structure_panel_supports_drag_and_drop(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/admin/invitations/create');

        $response->assertOk();

        // Rows: draggable to reorder, and a drop target for both dragged
        // rows and (via the row-level onRowDragOver call underneath) a
        // palette drop landing directly on a row node.
        $response->assertSee('@dragstart="onRowDragStart($event, row.id, rowIndex)"', false);
        $response->assertSee('@dragend="onRowDragEnd()"', false);
        $response->assertSee('@dragover.prevent.stop="onRowDragOver($event, rowIndex)"', false);

        // Columns: drop target for a widget dragged from the palette or
        // moved from elsewhere in the canvas.
        $response->assertSee('@dragover.prevent="onDragOverCol($event, row.id, col.id)"', false);
        $response->assertSee('@drop.prevent="onDropCol($event, row.id, col.id)"', false);

        // Widgets: draggable to reorder/move between columns.
        $response->assertSee('@dragstart="onDragStart($event, row.id, col.id, widget.id, widgetIndex)"', false);
        $response->assertSee('@dragover.prevent.stop="onDragOverWidget($event, row.id, col.id, widgetIndex)"', false);

        $response->assertSee('class="lyp-structure-drop lyp-structure-drop--row"', false);
        $response->assertSee('class="lyp-structure-drop lyp-structure-drop--widget"', false);
    }

    /**
     * Regression: "I can't drag and drop into the canvas." Native HTML5
     * dragover/drop do not reliably cross an iframe boundary, even
     * same-origin, in either direction — a drag starting in the left
     * widget palette (or the structure panel) was silently failing to
     * register anywhere inside the canvas iframe, since the iframe's own
     * dragover/drop listeners only ever receive events for drags that
     * started inside that same document. The overlay sitting on top of
     * the iframe (light DOM, so it reliably receives the drag) only shows
     * for a drag that didn't start in the canvas itself — reordering
     * something already inside the canvas keeps using the iframe's own
     * listeners, which only ever worked because source and target share
     * one document.
     */
    public function test_the_canvas_has_a_light_dom_drag_overlay_for_drags_starting_outside_it(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/admin/invitations/create');

        $response->assertOk();
        $response->assertSee('class="lyp-canvas-drag-overlay"', false);
        $response->assertSee('@dragover.prevent="onCanvasOverlayDragOver($event)"', false);
        $response->assertSee('@drop.prevent="onCanvasOverlayDrop($event)"', false);
        $response->assertSee('!drag.sourceIsFrame', false);
        $response->assertSee('!rowDrag.sourceIsFrame', false);
    }
}
