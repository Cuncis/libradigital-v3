<?php

namespace App\Layup\Forms\Components;

use Crumbls\Layup\Forms\Components\LayupBuilder as BaseLayupBuilder;
use Crumbls\Layup\Support\Concerns\RegistersWidgets;
use Crumbls\Layup\Support\PageLayout;
use Crumbls\Layup\Support\WidgetRegistry;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Renderless;
use Throwable;

/**
 * Extends Layup's builder field with a WYSIWYG canvas: the public
 * invitation page uses the real widget views + real stylesheet, but the
 * builder canvas only live-renders ~30 widgets that opt into
 * supportsLivePreview() and otherwise falls back to plain-text summaries.
 * This makes every widget type render for real, and adds
 * renderCanvasFrame() to build the HTML shown inside the editor's
 * same-origin iframe (see resources/views/filament/layup/canvas-frame.blade.php).
 */
class LayupBuilder extends BaseLayupBuilder
{
    // The base field relies on LayupPlugin having already registered
    // widgets when a Filament panel boots. renderCanvasFrame() can be
    // called (and unit-tested) outside that context, so — same as the
    // public-facing AbstractController — it registers for itself too;
    // registration is idempotent (checks $registry->has() first).
    use RegistersWidgets;

    protected function renderLivePreviewHtml(WidgetRegistry $registry, string $type, array $data): string
    {
        return $this->renderWidgetHtml($registry, $type, $data);
    }

    protected function renderWidgetHtml(WidgetRegistry $registry, string $type, array $data): string
    {
        $this->ensureWidgetsRegistered();

        $class = $registry->get($type);

        if (! $class) {
            return '';
        }

        try {
            $rendered = $class::make($class::prepareForRender($data))->render();
        } catch (Throwable) {
            return '';
        }

        if ($rendered instanceof View) {
            return $rendered->render();
        }

        if ($rendered instanceof Htmlable) {
            return $rendered->toHtml();
        }

        return (string) $rendered;
    }

    /**
     * Render the in-progress (possibly unsaved) builder content for the
     * editable iframe canvas. A dedicated editor-only template — it never
     * touches layup::components.row / layup::components.column, so this
     * can't affect the live public page's rendering.
     *
     * @param  array<string, mixed>  $content
     */
    #[Renderless]
    #[ExposedLivewireMethod]
    public function renderCanvasFrame(array $content): string
    {
        $registry = app(WidgetRegistry::class);
        $rows = is_array($content['rows'] ?? null) ? $content['rows'] : [];

        $prepared = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $columns = [];

            foreach ((is_array($row['columns'] ?? null) ? $row['columns'] : []) as $column) {
                if (! is_array($column)) {
                    continue;
                }

                $widgets = [];

                foreach ((is_array($column['widgets'] ?? null) ? $column['widgets'] : []) as $widget) {
                    if (! is_array($widget)) {
                        continue;
                    }

                    $type = $widget['type'] ?? null;

                    if (! is_string($type) || $type === '') {
                        continue;
                    }

                    $widgets[] = [
                        'id' => (string) ($widget['id'] ?? ''),
                        'html' => $this->renderWidgetHtml($registry, $type, is_array($widget['data'] ?? null) ? $widget['data'] : []),
                    ];
                }

                $columns[] = [
                    'id' => (string) ($column['id'] ?? ''),
                    'span' => is_array($column['span'] ?? null) ? $column['span'] : ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                    'widgets' => $widgets,
                ];
            }

            $prepared[] = [
                'id' => (string) ($row['id'] ?? ''),
                'columns' => $columns,
            ];
        }

        return view('filament.layup.canvas-frame', [
            'rows' => $prepared,
            'containerClass' => PageLayout::resolve(),
        ])->render();
    }
}
