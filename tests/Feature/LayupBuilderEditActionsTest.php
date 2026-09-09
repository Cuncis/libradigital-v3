<?php

namespace Tests\Feature;

use App\Layup\Forms\Components\LayupBuilder;
use Filament\Support\Enums\SlideOverPosition;
use Filament\Support\Enums\Width;
use Tests\TestCase;

/**
 * Elementor opens a section/column/widget's settings in the same left
 * panel the widget library normally occupies (sliding over it), rather
 * than a right-hand drawer. rowEditAction()/columnEditAction()/
 * widgetEditAction() override Layup's own (which already used slideOver(),
 * just anchored to the default/right edge) to anchor to the start (left)
 * edge instead and narrow the width to roughly match the sidebar it's
 * covering — same actions, same forms, same everything Layup already
 * built, just repositioned.
 */
class LayupBuilderEditActionsTest extends TestCase
{
    public function test_row_edit_slides_over_from_the_left(): void
    {
        $action = LayupBuilder::make('content')->rowEditAction();

        $this->assertTrue($action->isModalSlideOver());
        $this->assertSame(SlideOverPosition::Start, $action->getModalSlideOverPosition());
        $this->assertSame(Width::Small, $action->getModalWidth());
    }

    public function test_column_edit_slides_over_from_the_left(): void
    {
        $action = LayupBuilder::make('content')->columnEditAction();

        $this->assertTrue($action->isModalSlideOver());
        $this->assertSame(SlideOverPosition::Start, $action->getModalSlideOverPosition());
        $this->assertSame(Width::Small, $action->getModalWidth());
    }

    public function test_widget_edit_slides_over_from_the_left(): void
    {
        $action = LayupBuilder::make('content')->widgetEditAction();

        $this->assertTrue($action->isModalSlideOver());
        $this->assertSame(SlideOverPosition::Start, $action->getModalSlideOverPosition());
        $this->assertSame(Width::Small, $action->getModalWidth());
    }
}
