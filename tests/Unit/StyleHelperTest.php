<?php

namespace Tests\Unit;

use App\Layup\Support\StyleHelper;
use Tests\TestCase;

/**
 * Every widget's shared "Design" tab (BaseView::getDesignFormSchema())
 * exposes padding/margin pickers, but BaseView::buildInlineStyles() — the
 * helper all widget views call to turn that tab's data into CSS — never
 * read `padding`/`margin` at all, so setting them had zero visible effect
 * no matter the value. StyleHelper::buildInlineStyles() wraps it and adds
 * the missing CSS (see resources/views/vendor/layup/components/*.blade.php,
 * all switched to call this instead of the vendor helper directly).
 */
class StyleHelperTest extends TestCase
{
    public function test_it_still_produces_the_vendor_helpers_styles(): void
    {
        $styles = StyleHelper::buildInlineStyles(['text_color' => '#ff0000']);

        $this->assertStringContainsString('color: #ff0000;', $styles);
    }

    /**
     * Regression: the reported bug. All-sides padding explicitly set to 0
     * must still emit `padding-*: 0px;` — a naive empty()/!empty() check
     * on the SpacingPicker value would treat 0 as "not set" and silently
     * drop it, which is exactly what was happening before this fix.
     */
    public function test_padding_set_to_zero_on_every_side_is_not_treated_as_unset(): void
    {
        $styles = StyleHelper::buildInlineStyles([
            'padding' => ['unit' => 'px', 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0],
        ]);

        $this->assertStringContainsString('padding-top: 0px;', $styles);
        $this->assertStringContainsString('padding-right: 0px;', $styles);
        $this->assertStringContainsString('padding-bottom: 0px;', $styles);
        $this->assertStringContainsString('padding-left: 0px;', $styles);
    }

    public function test_margin_and_a_non_px_unit_are_supported(): void
    {
        $styles = StyleHelper::buildInlineStyles([
            'margin' => ['unit' => 'rem', 'top' => 2, 'left' => 1],
        ]);

        $this->assertStringContainsString('margin-top: 2rem;', $styles);
        $this->assertStringContainsString('margin-left: 1rem;', $styles);
        $this->assertStringNotContainsString('margin-right', $styles);
        $this->assertStringNotContainsString('margin-bottom', $styles);
    }

    public function test_a_side_left_blank_is_omitted_rather_than_defaulted_to_zero(): void
    {
        $styles = StyleHelper::buildInlineStyles([
            'padding' => ['unit' => 'px', 'top' => 10, 'right' => null, 'bottom' => '', 'left' => 10],
        ]);

        $this->assertStringContainsString('padding-top: 10px;', $styles);
        $this->assertStringContainsString('padding-left: 10px;', $styles);
        $this->assertStringNotContainsString('padding-right', $styles);
        $this->assertStringNotContainsString('padding-bottom', $styles);
    }

    public function test_missing_padding_and_margin_data_is_handled_without_error(): void
    {
        $this->assertSame('', StyleHelper::buildInlineStyles([]));
    }
}
