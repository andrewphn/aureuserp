<?php

namespace App\Filament\Components;

use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

/**
 * Live Summary Panel class
 *
 * @see \Filament\Resources\Resource
 */
abstract class LiveSummaryPanel extends Widget
{
    protected string $view = 'filament.widgets.live-summary-panel';

    /**
     * Define the fields to display in the summary panel.
     * Each field should have: label, key, default, icon (optional), formatter (optional)
     */
    abstract protected function getSummaryFields(): array;

    /**
     * Column span for the widget
     */
    protected int | string | array $columnSpan = 'full';

    /**
     * Whether the panel should be collapsed by default
     */
    public bool $collapsed = true;

    /**
     * Panel heading
     */
    public string $heading = 'Summary';

    /**
     * Panel description
     */
    public ?string $description = 'Live preview of your form data';

    /**
     * Grid columns configuration for the fields
     */
    public string $gridCols = 'grid-cols-2 md:grid-cols-3 lg:grid-cols-5';

    /**
     * Get the formatted fields for the Alpine.js template
     */
    public function getFormattedFields(): array
    {
        return collect($this->getSummaryFields())->map(function ($field) {
            return [
                'label' => $field['label'],
                'key' => $field['key'],
                'default' => $field['default'] ?? 'Not selected',
                'icon' => $field['icon'] ?? null,
                'formatter' => $field['formatter'] ?? null,
                'isHtml' => $field['isHtml'] ?? false,
            ];
        })->all();
    }

    /**
     * Render
     *
     * @return View
     */
    public function render(): View
    {
        return view($this->view, [
            'fields' => $this->getFormattedFields(),
        ]);
    }
}
