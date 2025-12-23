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

class MaterialShowcaseBlock
{
    public static function make(): Block
    {
        return Block::make('material_showcase')
            ->label('Material Showcase')
            ->icon('heroicon-o-cube-transparent')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('showcase_title')
                            ->label('Showcase Title')
                            ->placeholder('Premium Materials We Use')
                            ->required(),

                        Select::make('showcase_layout')
                            ->label('Showcase Layout')
                            ->options([
                                'grid' => 'Grid Layout',
                                'carousel' => 'Carousel',
                                'masonry' => 'Masonry Grid',
                                'featured_list' => 'Featured List',
                                'comparison_table' => 'Comparison Table',
                            ])
                            ->default('grid')
                            ->required(),
                    ]),

                RichEditor::make('showcase_description')
                    ->label('Showcase Description')
                    ->toolbarButtons(['bold', 'italic', 'bulletList'])
                    ->columnSpanFull(),

                Repeater::make('materials')
                    ->label('Materials')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('material_name')
                                    ->label('Material Name')
                                    ->placeholder('Eastern White Pine')
                                    ->required(),

                                Select::make('material_category')
                                    ->label('Category')
                                    ->options([
                                        'wood' => 'Wood',
                                        'hardware' => 'Hardware',
                                        'finish' => 'Finish',
                                        'adhesive' => 'Adhesive',
                                        'fastener' => 'Fastener',
                                        'other' => 'Other',
                                    ])
                                    ->required(),

                                TextInput::make('brand_manufacturer')
                                    ->label('Brand/Manufacturer'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                FileUpload::make('material_image')
                                    ->label('Material Image')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('tcs-cms/materials'),

                                RichEditor::make('material_description')
                                    ->label('Description')
                                    ->toolbarButtons(['bold', 'italic', 'bulletList'])
                                    ->required(),
                            ]),

                        Grid::make(4)
                            ->schema([
                                TextInput::make('key_properties')
                                    ->label('Key Properties')
                                    ->helperText('Comma-separated'),

                                TextInput::make('typical_uses')
                                    ->label('Typical Uses')
                                    ->helperText('Comma-separated'),

                                TextInput::make('price_range')
                                    ->label('Price Range')
                                    ->placeholder('$$ - Moderate'),

                                Select::make('sustainability_rating')
                                    ->label('Sustainability')
                                    ->options([
                                        'standard' => 'Standard',
                                        'sustainable' => 'Sustainable',
                                        'certified' => 'Certified Sustainable',
                                        'reclaimed' => 'Reclaimed/Recycled',
                                    ])
                                    ->default('standard'),
                            ]),

                        Grid::make(4)
                            ->schema([
                                Toggle::make('is_featured')
                                    ->label('Featured'),

                                Toggle::make('is_specialty')
                                    ->label('Specialty Item'),

                                Toggle::make('eco_friendly')
                                    ->label('Eco-Friendly'),

                                Select::make('quality_grade')
                                    ->label('Quality Grade')
                                    ->options([
                                        'standard' => 'Standard',
                                        'premium' => 'Premium',
                                        'professional' => 'Professional',
                                        'luxury' => 'Luxury',
                                    ])
                                    ->default('standard'),
                            ]),
                    ])
                    ->defaultItems(1)
                    ->reorderable()
                    ->cloneable()
                    ->collapsible()
                    ->itemLabel(fn(array $state): ?string => $state['material_name'] ?? 'Untitled Material')
                    ->addActionLabel('Add Material')
                    ->columnSpanFull(),

                Grid::make(4)
                    ->schema([
                        Toggle::make('show_categories')
                            ->label('Show Category Filters')
                            ->default(true),

                        Toggle::make('show_pricing')
                            ->label('Show Price Ranges')
                            ->default(false),

                        Toggle::make('show_sustainability')
                            ->label('Show Sustainability Info')
                            ->default(true),

                        Select::make('sort_materials_by')
                            ->label('Sort By')
                            ->options([
                                'name' => 'Name',
                                'category' => 'Category',
                                'quality_grade' => 'Quality Grade',
                                'featured' => 'Featured First',
                            ])
                            ->default('featured'),

                        Select::make('grid_columns')
                            ->label('Grid Columns')
                            ->options([
                                '2' => '2 Columns',
                                '3' => '3 Columns',
                                '4' => '4 Columns',
                            ])
                            ->default('3'),
                    ]),
            ]);
    }

    public static function mutateData(array $data): array
    {
        if (isset($data['materials'])) {
            $sortBy = $data['sort_materials_by'] ?? 'featured';

            switch ($sortBy) {
                case 'featured':
                    usort($data['materials'], function ($a, $b) {
                        $aFeatured = $a['is_featured'] ?? false;
                        $bFeatured = $b['is_featured'] ?? false;

                        return $bFeatured <=> $aFeatured;
                    });
                    break;

                case 'category':
                    usort($data['materials'], function ($a, $b) {
                        return strcasecmp($a['material_category'] ?? '', $b['material_category'] ?? '');
                    });
                    break;

                case 'name':
                    usort($data['materials'], function ($a, $b) {
                        return strcasecmp($a['material_name'] ?? '', $b['material_name'] ?? '');
                    });
                    break;

                case 'quality_grade':
                    $gradeOrder = ['luxury' => 1, 'professional' => 2, 'premium' => 3, 'standard' => 4];
                    usort($data['materials'], function ($a, $b) use ($gradeOrder) {
                        $aGrade = $gradeOrder[$a['quality_grade'] ?? 'standard'] ?? 999;
                        $bGrade = $gradeOrder[$b['quality_grade'] ?? 'standard'] ?? 999;

                        return $aGrade - $bGrade;
                    });
                    break;
            }

            foreach ($data['materials'] as &$material) {
                if (isset($material['key_properties'])) {
                    $material['properties_array'] = array_map('trim', explode(',', $material['key_properties']));
                }

                if (isset($material['typical_uses'])) {
                    $material['uses_array'] = array_map('trim', explode(',', $material['typical_uses']));
                }

                $classes = ['material-item'];
                if ($material['is_featured'] ?? false) {
                    $classes[] = 'featured';
                }
                if ($material['eco_friendly'] ?? false) {
                    $classes[] = 'eco-friendly';
                }
                if (isset($material['quality_grade'])) {
                    $classes[] = 'grade-'.$material['quality_grade'];
                }

                $material['css_classes'] = implode(' ', $classes);

                if (isset($material['material_image'])) {
                    $material['image_url'] = asset('storage/'.$material['material_image']);
                }
            }
        }

        $classes = ['material-showcase', 'layout-'.str_replace('_', '-', $data['showcase_layout'] ?? 'grid')];
        $classes[] = 'columns-'.($data['grid_columns'] ?? '3');
        $data['showcase_css_classes'] = implode(' ', $classes);

        return $data;
    }
}
