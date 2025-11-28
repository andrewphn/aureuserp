<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Webkul\Project\Models\Tag;

/**
 * Tag Selector Panel class
 *
 */
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

    public function getMostUsedTags(int $limit = 10): \Illuminate\Support\Collection
    {
        return Cache::remember('project_tags_most_used', 1800, function() use ($limit) {
            return Tag::select('projects_tags.*')
                ->selectRaw('COUNT(projects_project_tag.tag_id) as usage_count')
                ->leftJoin('projects_project_tag', 'projects_tags.id', '=', 'projects_project_tag.tag_id')
                ->groupBy('projects_tags.id')
                ->having('usage_count', '>', 0)
                ->orderByDesc('usage_count')
                ->limit($limit)
                ->get();
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
            'phase_discovery' => ['label' => 'Discovery', 'icon' => '🔍'],
            'phase_design' => ['label' => 'Design', 'icon' => '🎨'],
            'phase_sourcing' => ['label' => 'Sourcing', 'icon' => '📦'],
            'phase_production' => ['label' => 'Production', 'icon' => '⚙️'],
            'phase_delivery' => ['label' => 'Delivery', 'icon' => '🚚'],
            'special_status' => ['label' => 'Special Status', 'icon' => '⭐'],
            'lifecycle' => ['label' => 'Lifecycle', 'icon' => '🔄'],
        ];
    }

    public function getCategoryGroups(): array
    {
        return [
            'general' => [
                'label' => 'General',
                'icon' => '📋',
                'types' => ['priority', 'health', 'risk', 'complexity', 'work_scope'],
            ],
            'phases' => [
                'label' => 'Phases',
                'icon' => '🔄',
                'types' => ['phase_discovery', 'phase_design', 'phase_sourcing', 'phase_production', 'phase_delivery'],
            ],
            'other' => [
                'label' => 'Other',
                'icon' => '📌',
                'types' => ['special_status', 'lifecycle'],
            ],
        ];
    }

    /**
     * Search tags by name, type, or description with relevance ranking
     */
    public function searchTags(string $query): \Illuminate\Support\Collection
    {
        $query = strtolower(trim($query));

        if (empty($query)) {
            return collect();
        }

        return Tag::query()
            ->where(function($q) use ($query) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$query}%"])
                  ->orWhereRaw('LOWER(type) LIKE ?', ["%{$query}%"])
                  ->orWhereRaw('LOWER(COALESCE(description, \'\')) LIKE ?', ["%{$query}%"]);
            })
            ->orderByRaw("
                CASE
                    WHEN LOWER(name) LIKE ? THEN 1
                    WHEN LOWER(name) LIKE ? THEN 2
                    WHEN LOWER(type) LIKE ? THEN 3
                    ELSE 4
                END
            ", ["{$query}%", "%{$query}%", "%{$query}%"])
            ->limit(10)
            ->get();
    }

    /**
     * Get all tags formatted for JSON (for Alpine.js)
     */
    public function getAllTagsJson(): string
    {
        $typeLabels = $this->getTypeLabels();

        return Tag::all()->map(function($tag) use ($typeLabels) {
            $typeInfo = $typeLabels[$tag->type] ?? ['label' => ucwords(str_replace('_', ' ', $tag->type ?? '')), 'icon' => '📌'];
            return [
                'id' => $tag->id,
                'name' => $tag->name,
                'type' => $tag->type,
                'typeLabel' => $typeInfo['label'],
                'typeIcon' => $typeInfo['icon'],
                'color' => $tag->color,
                'description' => $tag->description,
            ];
        })->toJson();
    }

    /**
     * Get most used tags formatted for JSON
     */
    public function getMostUsedTagsJson(int $limit = 5): string
    {
        $typeLabels = $this->getTypeLabels();

        return $this->getMostUsedTags($limit)->map(function($tag) use ($typeLabels) {
            $typeInfo = $typeLabels[$tag->type] ?? ['label' => ucwords(str_replace('_', ' ', $tag->type ?? '')), 'icon' => '📌'];
            return [
                'id' => $tag->id,
                'name' => $tag->name,
                'type' => $tag->type,
                'typeLabel' => $typeInfo['label'],
                'typeIcon' => $typeInfo['icon'],
                'color' => $tag->color,
                'description' => $tag->description,
                'usageCount' => $tag->usage_count ?? 0,
            ];
        })->toJson();
    }
}
