<?php

namespace Webkul\TcsCms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class WoodSpeciesBlock
{
    public static function make(): Block
    {
        return Block::make('wood_species')
            ->label('Wood Species Showcase')
            ->icon('heroicon-o-cube')
            ->schema([
                Section::make('Species Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('species_name')
                                    ->label('Species Name')
                                    ->placeholder('Red Oak, Cherry, Walnut, etc.')
                                    ->required(),

                                TextInput::make('scientific_name')
                                    ->label('Scientific Name')
                                    ->placeholder('Quercus rubra'),

                                Select::make('wood_type')
                                    ->label('Wood Type')
                                    ->options([
                                        'hardwood' => 'Hardwood',
                                        'softwood' => 'Softwood',
                                        'exotic' => 'Exotic',
                                    ])
                                    ->required(),

                                TextInput::make('origin')
                                    ->label('Origin/Region')
                                    ->placeholder('North America'),
                            ]),

                        RichEditor::make('description')
                            ->label('Species Description')
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList'])
                            ->columnSpanFull(),
                    ]),

                Section::make('Wood Properties')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('hardness_rating')
                                    ->label('Janka Hardness (lbf)')
                                    ->numeric()
                                    ->placeholder('1290'),

                                TextInput::make('grain_pattern')
                                    ->label('Grain Pattern')
                                    ->placeholder('Straight, Interlocked'),

                                TextInput::make('density')
                                    ->label('Density (lbs/ft³)')
                                    ->numeric(),

                                Select::make('durability')
                                    ->label('Durability Rating')
                                    ->options([
                                        'very_durable' => 'Very Durable',
                                        'durable' => 'Durable',
                                        'moderately_durable' => 'Moderately Durable',
                                        'perishable' => 'Perishable',
                                    ]),

                                Select::make('workability')
                                    ->label('Workability')
                                    ->options([
                                        'excellent' => 'Excellent',
                                        'good' => 'Good',
                                        'moderate' => 'Moderate',
                                        'difficult' => 'Difficult',
                                    ]),

                                TextInput::make('color_description')
                                    ->label('Color Description')
                                    ->placeholder('Light brown heartwood'),
                            ]),
                    ]),

                Section::make('Visual Elements')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('wood_sample_image')
                                    ->label('Wood Sample Image')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('tcs-cms/wood-species')
                                    ->helperText('Image showing wood grain and color'),

                                FileUpload::make('finished_piece_image')
                                    ->label('Finished Piece Image')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('tcs-cms/wood-species'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Toggle::make('show_properties')
                                    ->label('Display Properties Chart')
                                    ->default(true),

                                Toggle::make('show_comparison')
                                    ->label('Show Species Comparison')
                                    ->default(false),

                                Toggle::make('include_sourcing_info')
                                    ->label('Include Sourcing Info')
                                    ->default(false),
                            ]),
                    ]),

                Section::make('Usage & Applications')
                    ->schema([
                        RichEditor::make('common_uses')
                            ->label('Common Uses')
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList']),

                        RichEditor::make('working_notes')
                            ->label('Working Notes')
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList']),

                        TextInput::make('price_range')
                            ->label('Price Range')
                            ->placeholder('$$$ - Premium'),
                    ])
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }

    public static function mutateData(array $data): array
    {
        $data['display_name'] = $data['species_name'] ?? 'Unknown Species';

        $data['properties'] = [];

        if (isset($data['hardness_rating'])) {
            $data['properties']['Hardness'] = $data['hardness_rating'].' lbf';
        }

        if (isset($data['density'])) {
            $data['properties']['Density'] = $data['density'].' lbs/ft³';
        }

        if (isset($data['grain_pattern'])) {
            $data['properties']['Grain'] = $data['grain_pattern'];
        }

        if (isset($data['durability'])) {
            $data['properties']['Durability'] = ucfirst(str_replace('_', ' ', $data['durability']));
        }

        if (isset($data['workability'])) {
            $data['properties']['Workability'] = ucfirst($data['workability']);
        }

        // Process images
        if (isset($data['wood_sample_image'])) {
            $data['sample_image_url'] = asset('storage/'.$data['wood_sample_image']);
        }

        if (isset($data['finished_piece_image'])) {
            $data['finished_image_url'] = asset('storage/'.$data['finished_piece_image']);
        }

        $classes = ['wood-species'];
        if (isset($data['wood_type'])) {
            $classes[] = 'type-'.$data['wood_type'];
        }
        $data['css_classes'] = implode(' ', $classes);

        return $data;
    }
}
