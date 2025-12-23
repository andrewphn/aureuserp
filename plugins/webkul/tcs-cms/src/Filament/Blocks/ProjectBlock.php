<?php

namespace Webkul\TcsCms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Webkul\TcsCms\Models\PortfolioProject;

class ProjectBlock
{
    public static function make(): Block
    {
        return Block::make('project')
            ->label('Portfolio Projects')
            ->icon('heroicon-o-rectangle-group')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('section_title')
                            ->label('Section Title')
                            ->placeholder('Featured Projects')
                            ->required()
                            ->columnSpan(1),

                        Select::make('display_type')
                            ->label('Display Type')
                            ->options([
                                'featured' => 'Featured Projects Only',
                                'recent' => 'Most Recent',
                                'category' => 'By Category',
                                'custom' => 'Custom Selection',
                            ])
                            ->default('featured')
                            ->live()
                            ->columnSpan(1),
                    ]),

                RichEditor::make('section_intro')
                    ->label('Section Introduction')
                    ->toolbarButtons(['bold', 'italic', 'bulletList'])
                    ->columnSpanFull(),

                Grid::make(3)
                    ->schema([
                        Select::make('category_filter')
                            ->label('Filter by Category')
                            ->options([
                                'all' => 'All Categories',
                                'kitchen' => 'Kitchen Cabinetry',
                                'bathroom' => 'Bathroom Vanities',
                                'living' => 'Living Spaces',
                                'office' => 'Home Office',
                                'outdoor' => 'Outdoor Structures',
                                'commercial' => 'Commercial Projects',
                                'restoration' => 'Restoration',
                            ])
                            ->default('all')
                            ->visible(fn(callable $get) => $get('display_type') === 'category'),

                        TextInput::make('project_count')
                            ->label('Number of Projects')
                            ->numeric()
                            ->default(6)
                            ->minValue(1)
                            ->maxValue(24),

                        Select::make('layout_style')
                            ->label('Layout Style')
                            ->options([
                                'grid' => 'Grid Layout',
                                'masonry' => 'Masonry Grid',
                                'carousel' => 'Carousel Slider',
                                'featured_hero' => 'Featured + Grid',
                            ])
                            ->default('grid'),
                    ]),

                Select::make('selected_projects')
                    ->label('Select Specific Projects')
                    ->options(fn() => PortfolioProject::query()
                        ->where('is_published', true)
                        ->pluck('title', 'id')
                        ->toArray())
                    ->multiple()
                    ->searchable()
                    ->visible(fn(callable $get) => $get('display_type') === 'custom')
                    ->columnSpanFull(),

                Grid::make(4)
                    ->schema([
                        Toggle::make('show_category_filters')
                            ->label('Show Category Filters')
                            ->default(true),

                        Toggle::make('show_project_details')
                            ->label('Show Project Details')
                            ->default(true),

                        Toggle::make('enable_lightbox')
                            ->label('Enable Lightbox')
                            ->default(true),

                        Toggle::make('show_view_all_link')
                            ->label('Show View All Link')
                            ->default(true),

                        Select::make('columns_desktop')
                            ->label('Desktop Columns')
                            ->options([
                                '2' => '2 Columns',
                                '3' => '3 Columns',
                                '4' => '4 Columns',
                            ])
                            ->default('3'),

                        Select::make('columns_mobile')
                            ->label('Mobile Columns')
                            ->options([
                                '1' => '1 Column',
                                '2' => '2 Columns',
                            ])
                            ->default('1'),

                        Select::make('image_aspect_ratio')
                            ->label('Image Aspect Ratio')
                            ->options([
                                'square' => 'Square (1:1)',
                                'landscape' => 'Landscape (4:3)',
                                'wide' => 'Wide (16:9)',
                                'portrait' => 'Portrait (3:4)',
                            ])
                            ->default('landscape'),

                        Select::make('hover_effect')
                            ->label('Hover Effect')
                            ->options([
                                'none' => 'None',
                                'zoom' => 'Zoom',
                                'overlay' => 'Overlay Info',
                                'lift' => 'Lift Card',
                            ])
                            ->default('overlay'),
                    ]),
            ]);
    }

    public static function mutateData(array $data): array
    {
        $query = PortfolioProject::query()
            ->where('is_published', true);

        // Apply display type logic
        switch ($data['display_type'] ?? 'featured') {
            case 'featured':
                $query->where('featured', true)
                    ->orderBy('portfolio_order');
                break;
            case 'recent':
                $query->orderByDesc('published_at');
                break;
            case 'category':
                if (isset($data['category_filter']) && $data['category_filter'] !== 'all') {
                    $query->where('category', $data['category_filter']);
                }
                $query->orderBy('portfolio_order');
                break;
            case 'custom':
                if (! empty($data['selected_projects'])) {
                    $query->whereIn('id', $data['selected_projects']);
                }
                break;
        }

        $limit = $data['project_count'] ?? 6;
        $data['projects'] = $query->limit($limit)->get();

        // Generate CSS classes
        $classes = ['project-block'];
        $classes[] = 'layout-'.($data['layout_style'] ?? 'grid');
        $classes[] = 'cols-desktop-'.($data['columns_desktop'] ?? '3');
        $classes[] = 'cols-mobile-'.($data['columns_mobile'] ?? '1');
        $classes[] = 'aspect-'.($data['image_aspect_ratio'] ?? 'landscape');
        $classes[] = 'hover-'.($data['hover_effect'] ?? 'overlay');

        $data['css_classes'] = implode(' ', $classes);

        return $data;
    }
}
