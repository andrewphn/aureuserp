<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Support\Facades\Cache;
use Webkul\Project\Models\Tag;

class TagSelectorPanel extends Field
{
    protected string $view = 'forms.components.tag-selector-panel';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);
    }

    public function getTagsByType(): \Illuminate\Support\Collection
    {
        return Cache::remember('project_tags_grouped', 3600, function() {
            return Tag::all()->groupBy('type');
        });
    }

    public function getTypeLabels(): array
    {
        return [
            'priority' => ['label' => 'Priority', 'icon' => '🎯'],
            'health' => ['label' => 'Health Status', 'icon' => '💚'],
            'risk' => ['label' => 'Risk Factors', 'icon' => '⚠️'],
            'complexity' => ['label' => 'Complexity', 'icon' => '📊'],
            'work_scope' => ['label' => 'Work Scope', 'icon' => '🔨'],
            'phase_discovery' => ['label' => 'Discovery Phase', 'icon' => '🔍'],
            'phase_design' => ['label' => 'Design Phase', 'icon' => '🎨'],
            'phase_sourcing' => ['label' => 'Sourcing Phase', 'icon' => '📦'],
            'phase_production' => ['label' => 'Production Phase', 'icon' => '⚙️'],
            'phase_delivery' => ['label' => 'Delivery Phase', 'icon' => '🚚'],
            'special_status' => ['label' => 'Special Status', 'icon' => '⭐'],
            'lifecycle' => ['label' => 'Lifecycle', 'icon' => '🔄'],
        ];
    }
}
