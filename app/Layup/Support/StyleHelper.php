<?php

namespace App\Layup\Support;

use Crumbls\Layup\View\BaseView;

/**
 * Every widget's shared "Design" tab (BaseView::getDesignFormSchema())
 * exposes per-side padding/margin pickers (SpacingPicker::advanced()), but
 * BaseView::buildInlineStyles() — the helper all ~90 widget views call to
 * turn that tab's data into CSS — never actually reads `padding`/`margin`
 * at all, so those two fields have zero effect on rendering no matter what
 * a user sets them to. Wraps the vendor helper and adds the missing CSS;
 * every published widget view calls this instead (swapped in by a
 * project-wide find/replace, since the vendor call site can't be
 * subclassed — it's a static method called by fully-qualified name).
 */
class StyleHelper
{
    public static function buildInlineStyles(array $data): string
    {
        $styles = trim(BaseView::buildInlineStyles($data));

        foreach (['padding', 'margin'] as $property) {
            $spacing = static::spacingCss($property, $data[$property] ?? null);

            if ($spacing !== '') {
                $styles = trim($styles.' '.$spacing);
            }
        }

        return $styles;
    }

    /**
     * @param  mixed  $value  the SpacingPicker::advanced() shape:
     *                        {unit: 'px'|'rem'|'em'|'%', top, right, bottom, left}
     */
    protected static function spacingCss(string $property, mixed $value): string
    {
        if (! is_array($value)) {
            return '';
        }

        $unit = $value['unit'] ?? 'px';
        $declarations = [];

        foreach (['top', 'right', 'bottom', 'left'] as $side) {
            $amount = $value[$side] ?? null;

            // Deliberately not empty()/!empty() here — 0 is a valid,
            // meaningful value ("no padding on this side") and empty()
            // treats 0 as unset, which would silently ignore it.
            if ($amount === null || $amount === '') {
                continue;
            }

            $declarations[] = "{$property}-{$side}: {$amount}{$unit};";
        }

        return implode(' ', $declarations);
    }
}
