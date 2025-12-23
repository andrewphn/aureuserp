<?php

namespace Webkul\TcsCms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class WorkshopTipBlock
{
    public static function make(): Block
    {
        return Block::make('workshop_tip')
            ->label('Workshop Tip')
            ->icon('heroicon-o-light-bulb')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('tip_title')
                            ->label('Tip Title')
                            ->placeholder('Pro Tip: Preventing Wood Movement')
                            ->required(),

                        Select::make('tip_category')
                            ->label('Tip Category')
                            ->options([
                                'safety' => 'Safety',
                                'technique' => 'Technique',
                                'tool_use' => 'Tool Use',
                                'material_handling' => 'Material Handling',
                                'finishing' => 'Finishing',
                                'measurement' => 'Measurement',
                                'joinery' => 'Joinery',
                                'troubleshooting' => 'Troubleshooting',
                                'efficiency' => 'Efficiency',
                                'maintenance' => 'Maintenance',
                                'general' => 'General',
                            ])
                            ->required()
                            ->default('general'),
                    ]),

                RichEditor::make('tip_content')
                    ->label('Tip Content')
                    ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link'])
                    ->required()
                    ->columnSpanFull(),

                Grid::make(3)
                    ->schema([
                        Select::make('difficulty_level')
                            ->label('Difficulty Level')
                            ->options([
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                                'expert' => 'Expert',
                                'all_levels' => 'All Levels',
                            ])
                            ->default('all_levels')
                            ->required(),

                        Select::make('tip_priority')
                            ->label('Tip Priority')
                            ->options([
                                'critical' => 'Critical (Safety/Must-Know)',
                                'important' => 'Important',
                                'helpful' => 'Helpful',
                                'advanced' => 'Advanced',
                            ])
                            ->default('helpful'),

                        TextInput::make('time_savings')
                            ->label('Time Savings')
                            ->placeholder('5-10 minutes'),
                    ]),

                Grid::make(2)
                    ->schema([
                        FileUpload::make('tip_image')
                            ->label('Illustration Image')
                            ->image()
                            ->imageEditor()
                            ->directory('tcs-cms/workshop-tips'),

                        Grid::make(1)
                            ->schema([
                                TextInput::make('tools_needed')
                                    ->label('Tools Needed')
                                    ->helperText('Comma-separated'),

                                TextInput::make('materials_needed')
                                    ->label('Materials Needed')
                                    ->helperText('Comma-separated'),

                                TextInput::make('estimated_time')
                                    ->label('Time Required')
                                    ->placeholder('2-3 minutes'),
                            ]),
                    ]),

                Grid::make(2)
                    ->schema([
                        RichEditor::make('common_mistakes')
                            ->label('Common Mistakes to Avoid')
                            ->toolbarButtons(['bold', 'italic', 'bulletList']),

                        RichEditor::make('related_tips')
                            ->label('Related Tips')
                            ->toolbarButtons(['bold', 'italic', 'bulletList']),
                    ]),

                Grid::make(4)
                    ->schema([
                        Select::make('tip_style')
                            ->label('Display Style')
                            ->options([
                                'callout' => 'Callout Box',
                                'sidebar' => 'Sidebar Note',
                                'highlight' => 'Highlighted Section',
                                'card' => 'Tip Card',
                                'inline' => 'Inline Note',
                            ])
                            ->default('callout'),

                        Select::make('color_scheme')
                            ->label('Color Scheme')
                            ->options([
                                'default' => 'Default (Blue)',
                                'safety' => 'Safety (Red)',
                                'success' => 'Success (Green)',
                                'warning' => 'Warning (Orange)',
                                'info' => 'Info (Light Blue)',
                                'neutral' => 'Neutral (Gray)',
                            ])
                            ->default('default'),

                        Toggle::make('show_icon')
                            ->label('Show Category Icon')
                            ->default(true),

                        Toggle::make('show_difficulty')
                            ->label('Show Difficulty Level')
                            ->default(true),
                    ]),

                Grid::make(3)
                    ->schema([
                        Toggle::make('expandable')
                            ->label('Make Expandable')
                            ->default(false),

                        Toggle::make('printable')
                            ->label('Include Print Version')
                            ->default(false),

                        Toggle::make('shareable')
                            ->label('Make Shareable')
                            ->default(false),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('author_credit')
                            ->label('Tip Author/Source')
                            ->placeholder('Bryan Patton, Master Craftsman'),

                        TextInput::make('learn_more_link')
                            ->label('Learn More URL')
                            ->url(),
                    ]),
            ]);
    }

    public static function mutateData(array $data): array
    {
        $classes = ['workshop-tip'];

        if (isset($data['tip_category'])) {
            $classes[] = 'category-'.str_replace('_', '-', $data['tip_category']);
        }

        if (isset($data['difficulty_level'])) {
            $classes[] = 'level-'.str_replace('_', '-', $data['difficulty_level']);
        }

        if (isset($data['tip_priority'])) {
            $classes[] = 'priority-'.$data['tip_priority'];
        }

        if (isset($data['tip_style'])) {
            $classes[] = 'style-'.str_replace('_', '-', $data['tip_style']);
        }

        if (isset($data['color_scheme'])) {
            $classes[] = 'color-'.str_replace('_', '-', $data['color_scheme']);
        }

        if ($data['expandable'] ?? false) {
            $classes[] = 'expandable';
        }

        if ($data['printable'] ?? false) {
            $classes[] = 'printable';
        }

        $data['css_classes'] = implode(' ', $classes);

        // Parse comma-separated lists
        if (isset($data['tools_needed'])) {
            $data['tools_array'] = array_map('trim', explode(',', $data['tools_needed']));
        }

        if (isset($data['materials_needed'])) {
            $data['materials_array'] = array_map('trim', explode(',', $data['materials_needed']));
        }

        // Category icons
        $categoryIcons = [
            'safety' => 'heroicon-o-exclamation-triangle',
            'technique' => 'heroicon-o-wrench-screwdriver',
            'tool_use' => 'heroicon-o-wrench',
            'material_handling' => 'heroicon-o-cube',
            'finishing' => 'heroicon-o-paint-brush',
            'measurement' => 'heroicon-o-scale',
            'joinery' => 'heroicon-o-link',
            'troubleshooting' => 'heroicon-o-bug-ant',
            'efficiency' => 'heroicon-o-clock',
            'maintenance' => 'heroicon-o-cog-6-tooth',
            'general' => 'heroicon-o-light-bulb',
        ];

        $data['category_icon'] = $categoryIcons[$data['tip_category'] ?? 'general'] ?? 'heroicon-o-light-bulb';

        // Difficulty colors
        $difficultyColors = [
            'beginner' => 'green',
            'intermediate' => 'blue',
            'advanced' => 'orange',
            'expert' => 'red',
            'all_levels' => 'gray',
        ];

        $data['difficulty_color'] = $difficultyColors[$data['difficulty_level'] ?? 'all_levels'] ?? 'gray';

        // Process image
        if (isset($data['tip_image'])) {
            $data['image_url'] = asset('storage/'.$data['tip_image']);
        }

        // Author
        $data['formatted_author'] = $data['author_credit'] ?? 'TCS Woodworking Workshop';

        if ($data['expandable'] ?? false) {
            $data['unique_id'] = 'tip-'.uniqid();
        }

        return $data;
    }
}
