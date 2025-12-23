<?php

namespace Webkul\TcsCms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class BeforeAfterBlock
{
    public static function make(): Block
    {
        return Block::make('before_after')
            ->label('Before & After Comparison')
            ->icon('heroicon-o-arrow-path')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('comparison_title')
                            ->label('Comparison Title')
                            ->placeholder('Kitchen Transformation')
                            ->required()
                            ->columnSpan(2),

                        RichEditor::make('project_description')
                            ->label('Project Description')
                            ->toolbarButtons(['bold', 'italic', 'bulletList'])
                            ->columnSpan(2),
                    ]),

                Grid::make(2)
                    ->schema([
                        FileUpload::make('before_image')
                            ->label('Before Image')
                            ->image()
                            ->imageEditor()
                            ->required()
                            ->directory('tcs-cms/before-after'),

                        FileUpload::make('after_image')
                            ->label('After Image')
                            ->image()
                            ->imageEditor()
                            ->required()
                            ->directory('tcs-cms/before-after'),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('before_caption')
                            ->label('Before Caption')
                            ->placeholder('Original 1980s kitchen'),

                        TextInput::make('after_caption')
                            ->label('After Caption')
                            ->placeholder('Modern custom kitchen'),
                    ]),

                Grid::make(3)
                    ->schema([
                        Select::make('comparison_style')
                            ->label('Comparison Style')
                            ->options([
                                'slider' => 'Interactive Slider',
                                'side_by_side' => 'Side by Side',
                                'overlay' => 'Overlay Transition',
                                'tabbed' => 'Tabbed View',
                                'hover_reveal' => 'Hover to Reveal',
                            ])
                            ->default('slider')
                            ->required(),

                        Select::make('layout_orientation')
                            ->label('Layout Orientation')
                            ->options([
                                'horizontal' => 'Horizontal',
                                'vertical' => 'Vertical',
                                'adaptive' => 'Adaptive',
                            ])
                            ->default('horizontal'),

                        Select::make('image_aspect_ratio')
                            ->label('Aspect Ratio')
                            ->options([
                                'original' => 'Original',
                                'square' => 'Square (1:1)',
                                'landscape' => 'Landscape (4:3)',
                                'widescreen' => 'Widescreen (16:9)',
                            ])
                            ->default('original'),
                    ]),

                Grid::make(2)
                    ->schema([
                        RichEditor::make('transformation_details')
                            ->label('Transformation Details')
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList']),

                        Grid::make(1)
                            ->schema([
                                TextInput::make('project_duration')
                                    ->label('Project Duration')
                                    ->placeholder('6 weeks'),

                                TextInput::make('project_year')
                                    ->label('Project Year')
                                    ->numeric(),

                                TextInput::make('project_location')
                                    ->label('Project Location')
                                    ->placeholder('Nantucket, MA'),

                                TextInput::make('square_footage')
                                    ->label('Square Footage')
                                    ->placeholder('250 sq ft'),
                            ]),
                    ]),

                Grid::make(4)
                    ->schema([
                        Toggle::make('show_project_details')
                            ->label('Show Project Details')
                            ->default(true),

                        Toggle::make('enable_zoom')
                            ->label('Enable Image Zoom')
                            ->default(false),

                        Toggle::make('show_image_captions')
                            ->label('Show Image Captions')
                            ->default(true),

                        Toggle::make('auto_play_slider')
                            ->label('Auto-play Slider')
                            ->default(false)
                            ->visible(fn(callable $get) => $get('comparison_style') === 'slider'),
                    ]),

                Grid::make(2)
                    ->schema([
                        Select::make('animation_effect')
                            ->label('Animation Effect')
                            ->options([
                                'none' => 'No Animation',
                                'fade' => 'Fade Transition',
                                'slide' => 'Slide Transition',
                                'wipe' => 'Wipe Effect',
                            ])
                            ->default('fade'),

                        TextInput::make('slider_handle_color')
                            ->label('Slider Handle Color')
                            ->placeholder('#8B4513')
                            ->visible(fn(callable $get) => $get('comparison_style') === 'slider'),
                    ]),
            ]);
    }

    public static function mutateData(array $data): array
    {
        $classes = ['before-after-block'];

        if (isset($data['comparison_style'])) {
            $classes[] = 'style-'.str_replace('_', '-', $data['comparison_style']);
        }

        if (isset($data['layout_orientation'])) {
            $classes[] = 'orientation-'.str_replace('_', '-', $data['layout_orientation']);
        }

        if (isset($data['image_aspect_ratio'])) {
            $classes[] = 'aspect-'.str_replace('_', '-', $data['image_aspect_ratio']);
        }

        if ($data['enable_zoom'] ?? false) {
            $classes[] = 'zoomable';
        }

        if (isset($data['animation_effect']) && $data['animation_effect'] !== 'none') {
            $classes[] = 'animated';
            $classes[] = 'animation-'.$data['animation_effect'];
        }

        $data['css_classes'] = implode(' ', $classes);
        $data['unique_id'] = 'before-after-'.uniqid();

        // Process images
        if (isset($data['before_image'])) {
            $data['before_image_url'] = asset('storage/'.$data['before_image']);
        }

        if (isset($data['after_image'])) {
            $data['after_image_url'] = asset('storage/'.$data['after_image']);
        }

        // Create project metadata
        $data['project_metadata'] = [];

        if (isset($data['project_duration'])) {
            $data['project_metadata']['Duration'] = $data['project_duration'];
        }

        if (isset($data['project_year'])) {
            $data['project_metadata']['Year'] = $data['project_year'];
        }

        if (isset($data['project_location'])) {
            $data['project_metadata']['Location'] = $data['project_location'];
        }

        if (isset($data['square_footage'])) {
            $data['project_metadata']['Size'] = $data['square_footage'];
        }

        // Slider configuration
        if (($data['comparison_style'] ?? '') === 'slider') {
            $data['slider_config'] = [
                'autoplay' => $data['auto_play_slider'] ?? false,
                'handleColor' => $data['slider_handle_color'] ?? '#8B4513',
            ];
        }

        return $data;
    }
}
