<?php

namespace Webkul\TcsCms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ProjectGalleryBlock
{
    public static function make(): Block
    {
        return Block::make('project_gallery')
            ->label('Project Gallery')
            ->icon('heroicon-o-photo')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('gallery_title')
                            ->label('Gallery Title')
                            ->placeholder('Project Gallery')
                            ->required(),

                        Select::make('gallery_layout')
                            ->label('Gallery Layout')
                            ->options([
                                'masonry' => 'Masonry Grid',
                                'grid' => 'Equal Grid',
                                'carousel' => 'Carousel',
                                'lightbox' => 'Lightbox Grid',
                                'mosaic' => 'Mosaic Layout',
                                'timeline' => 'Progress Timeline',
                            ])
                            ->default('masonry')
                            ->required(),
                    ]),

                RichEditor::make('gallery_description')
                    ->label('Gallery Description')
                    ->toolbarButtons(['bold', 'italic', 'bulletList'])
                    ->columnSpanFull(),

                Repeater::make('gallery_images')
                    ->label('Gallery Images')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('image')
                                    ->label('Image')
                                    ->image()
                                    ->imageEditor()
                                    ->required()
                                    ->directory('tcs-cms/project-gallery'),

                                Grid::make(1)
                                    ->schema([
                                        TextInput::make('image_title')
                                            ->label('Image Title')
                                            ->required(),

                                        RichEditor::make('image_caption')
                                            ->label('Image Caption')
                                            ->toolbarButtons(['bold', 'italic'])
                                            ->required(),

                                        Select::make('project_phase')
                                            ->label('Project Phase')
                                            ->options([
                                                'planning' => 'Planning & Design',
                                                'preparation' => 'Material Preparation',
                                                'construction' => 'Construction',
                                                'assembly' => 'Assembly',
                                                'finishing' => 'Finishing',
                                                'installation' => 'Installation',
                                                'completion' => 'Completion',
                                                'detail' => 'Detail Shot',
                                                'context' => 'In Context',
                                            ])
                                            ->default('construction'),
                                    ]),
                            ]),

                        Grid::make(4)
                            ->schema([
                                TextInput::make('photographer_credit')
                                    ->label('Photographer'),

                                TextInput::make('date_taken')
                                    ->label('Date Taken'),

                                Select::make('image_orientation')
                                    ->label('Orientation')
                                    ->options([
                                        'landscape' => 'Landscape',
                                        'portrait' => 'Portrait',
                                        'square' => 'Square',
                                        'panoramic' => 'Panoramic',
                                    ])
                                    ->default('landscape'),

                                Select::make('image_quality')
                                    ->label('Image Type')
                                    ->options([
                                        'hero' => 'Hero Image',
                                        'detail' => 'Detail Shot',
                                        'progress' => 'Progress Photo',
                                        'finished' => 'Finished Result',
                                        'process' => 'Process Documentation',
                                        'context' => 'Contextual Shot',
                                    ])
                                    ->default('progress'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Toggle::make('is_featured')
                                    ->label('Featured Image'),

                                Toggle::make('is_before_after')
                                    ->label('Before/After Image'),

                                Toggle::make('show_technical_details')
                                    ->label('Show Technical Details'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('technical_details')
                                    ->label('Technical Details')
                                    ->visible(fn(callable $get) => $get('show_technical_details')),

                                TextInput::make('tools_visible')
                                    ->label('Tools/Equipment Visible'),
                            ]),
                    ])
                    ->defaultItems(1)
                    ->reorderable()
                    ->cloneable()
                    ->collapsible()
                    ->itemLabel(fn(array $state): ?string => $state['image_title'] ?? 'Untitled Image')
                    ->addActionLabel('Add Gallery Image')
                    ->columnSpanFull(),

                Grid::make(4)
                    ->schema([
                        Toggle::make('show_image_numbers')
                            ->label('Show Image Numbers')
                            ->default(true),

                        Toggle::make('show_phase_filters')
                            ->label('Show Phase Filters')
                            ->default(true),

                        Toggle::make('enable_fullscreen')
                            ->label('Enable Fullscreen View')
                            ->default(true),

                        Toggle::make('show_image_details')
                            ->label('Show Image Details')
                            ->default(true),

                        Toggle::make('auto_play_slideshow')
                            ->label('Auto-play Slideshow')
                            ->default(false)
                            ->visible(fn(callable $get) => $get('gallery_layout') === 'carousel'),

                        Select::make('columns_desktop')
                            ->label('Desktop Columns')
                            ->options([
                                '2' => '2 Columns',
                                '3' => '3 Columns',
                                '4' => '4 Columns',
                                '5' => '5 Columns',
                            ])
                            ->default('3'),

                        Select::make('columns_mobile')
                            ->label('Mobile Columns')
                            ->options([
                                '1' => '1 Column',
                                '2' => '2 Columns',
                            ])
                            ->default('2'),

                        Select::make('image_aspect_ratio')
                            ->label('Aspect Ratio')
                            ->options([
                                'original' => 'Original',
                                'square' => 'Square (1:1)',
                                'landscape' => 'Landscape (4:3)',
                                'wide' => 'Wide (16:9)',
                            ])
                            ->default('original'),
                    ]),

                Grid::make(2)
                    ->schema([
                        Select::make('sort_images_by')
                            ->label('Sort Images By')
                            ->options([
                                'order' => 'Manual Order',
                                'phase' => 'Project Phase',
                                'featured' => 'Featured First',
                                'quality' => 'Image Quality',
                            ])
                            ->default('order'),

                        Select::make('image_loading')
                            ->label('Image Loading')
                            ->options([
                                'lazy' => 'Lazy Loading',
                                'eager' => 'Immediate Loading',
                                'progressive' => 'Progressive Loading',
                            ])
                            ->default('lazy'),
                    ]),
            ]);
    }

    public static function mutateData(array $data): array
    {
        if (isset($data['gallery_images'])) {
            $sortBy = $data['sort_images_by'] ?? 'order';

            switch ($sortBy) {
                case 'featured':
                    usort($data['gallery_images'], function ($a, $b) {
                        return ($b['is_featured'] ?? false) <=> ($a['is_featured'] ?? false);
                    });
                    break;

                case 'phase':
                    $phaseOrder = [
                        'planning' => 1, 'preparation' => 2, 'construction' => 3,
                        'assembly' => 4, 'finishing' => 5, 'installation' => 6,
                        'completion' => 7, 'detail' => 8, 'context' => 9,
                    ];
                    usort($data['gallery_images'], function ($a, $b) use ($phaseOrder) {
                        $aPhase = $phaseOrder[$a['project_phase'] ?? 'construction'] ?? 999;
                        $bPhase = $phaseOrder[$b['project_phase'] ?? 'construction'] ?? 999;

                        return $aPhase - $bPhase;
                    });
                    break;
            }

            foreach ($data['gallery_images'] as $index => &$image) {
                if (isset($image['image'])) {
                    $image['image_url'] = asset('storage/'.$image['image']);
                }

                $image['display_index'] = $index + 1;

                if (isset($image['tools_visible'])) {
                    $image['tools_array'] = array_map('trim', explode(',', $image['tools_visible']));
                }

                $classes = ['gallery-item'];
                if ($image['is_featured'] ?? false) {
                    $classes[] = 'featured';
                }
                if ($image['is_before_after'] ?? false) {
                    $classes[] = 'before-after';
                }
                if (isset($image['project_phase'])) {
                    $classes[] = 'phase-'.str_replace('_', '-', $image['project_phase']);
                }
                if (isset($image['image_orientation'])) {
                    $classes[] = 'orientation-'.$image['image_orientation'];
                }

                $image['css_classes'] = implode(' ', $classes);
            }
        }

        $classes = ['project-gallery', 'layout-'.str_replace('_', '-', $data['gallery_layout'] ?? 'masonry')];

        if (isset($data['columns_desktop'])) {
            $classes[] = 'cols-desktop-'.$data['columns_desktop'];
        }

        if (isset($data['columns_mobile'])) {
            $classes[] = 'cols-mobile-'.$data['columns_mobile'];
        }

        if (isset($data['image_aspect_ratio'])) {
            $classes[] = 'aspect-'.str_replace('_', '-', $data['image_aspect_ratio']);
        }

        if ($data['enable_fullscreen'] ?? true) {
            $classes[] = 'fullscreen-enabled';
        }

        $data['gallery_css_classes'] = implode(' ', $classes);
        $data['gallery_id'] = 'gallery-'.uniqid();

        // Create phase filter data
        if ($data['show_phase_filters'] ?? true) {
            $phases = [];
            foreach ($data['gallery_images'] ?? [] as $image) {
                if (isset($image['project_phase'])) {
                    $phases[$image['project_phase']] = ucfirst(str_replace('_', ' ', $image['project_phase']));
                }
            }
            $data['phase_filters'] = $phases;
        }

        // Gallery statistics
        $data['gallery_stats'] = [
            'total_images' => count($data['gallery_images'] ?? []),
            'featured_count' => count(array_filter($data['gallery_images'] ?? [], fn($img) => $img['is_featured'] ?? false)),
            'phases_covered' => count(array_unique(array_column($data['gallery_images'] ?? [], 'project_phase'))),
        ];

        return $data;
    }
}
