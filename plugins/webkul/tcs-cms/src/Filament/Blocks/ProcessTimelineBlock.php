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

class ProcessTimelineBlock
{
    public static function make(): Block
    {
        return Block::make('process_timeline')
            ->label('Process Timeline')
            ->icon('heroicon-o-clock')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('timeline_title')
                            ->label('Timeline Title')
                            ->placeholder('Our 5-Phase Woodworking Process')
                            ->required(),

                        Select::make('timeline_style')
                            ->label('Timeline Style')
                            ->options([
                                'vertical' => 'Vertical Timeline',
                                'horizontal' => 'Horizontal Steps',
                                'circular' => 'Circular Process',
                                'numbered' => 'Numbered List',
                                'cards' => 'Process Cards',
                            ])
                            ->default('vertical')
                            ->required(),
                    ]),

                RichEditor::make('timeline_introduction')
                    ->label('Timeline Introduction')
                    ->toolbarButtons(['bold', 'italic', 'bulletList'])
                    ->columnSpanFull(),

                Repeater::make('process_steps')
                    ->label('Process Steps')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('step_number')
                                    ->label('Step Number')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('step_title')
                                    ->label('Step Title')
                                    ->placeholder('Discovery & Planning')
                                    ->required(),

                                TextInput::make('duration')
                                    ->label('Duration')
                                    ->placeholder('1-2 weeks'),
                            ]),

                        RichEditor::make('step_description')
                            ->label('Step Description')
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link'])
                            ->required()
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                FileUpload::make('step_image')
                                    ->label('Step Image')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('tcs-cms/process-timeline'),

                                Grid::make(1)
                                    ->schema([
                                        TextInput::make('key_deliverables')
                                            ->label('Key Deliverables')
                                            ->helperText('Comma-separated'),

                                        TextInput::make('tools_used')
                                            ->label('Tools/Equipment')
                                            ->helperText('Comma-separated'),

                                        Select::make('complexity_level')
                                            ->label('Complexity Level')
                                            ->options([
                                                'simple' => 'Simple',
                                                'moderate' => 'Moderate',
                                                'complex' => 'Complex',
                                                'expert' => 'Expert Level',
                                            ]),
                                    ]),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Toggle::make('is_critical_path')
                                    ->label('Critical Path Step'),

                                Toggle::make('requires_client_approval')
                                    ->label('Requires Client Approval'),

                                Toggle::make('weather_dependent')
                                    ->label('Weather Dependent'),
                            ]),
                    ])
                    ->defaultItems(1)
                    ->reorderable()
                    ->cloneable()
                    ->collapsible()
                    ->itemLabel(fn(array $state): ?string => $state['step_title'] ?? 'Untitled Step')
                    ->addActionLabel('Add Process Step')
                    ->columnSpanFull(),

                Grid::make(3)
                    ->schema([
                        Toggle::make('show_duration')
                            ->label('Show Step Duration')
                            ->default(true),

                        Toggle::make('show_deliverables')
                            ->label('Show Key Deliverables')
                            ->default(true),

                        Toggle::make('show_tools')
                            ->label('Show Tools Used')
                            ->default(false),

                        Toggle::make('interactive_timeline')
                            ->label('Interactive Timeline')
                            ->default(false),

                        Select::make('animation_style')
                            ->label('Animation Style')
                            ->options([
                                'none' => 'No Animation',
                                'fade_in' => 'Fade In',
                                'slide_in' => 'Slide In',
                                'progressive' => 'Progressive Reveal',
                            ])
                            ->default('fade_in'),
                    ]),
            ]);
    }

    public static function mutateData(array $data): array
    {
        if (isset($data['process_steps'])) {
            usort($data['process_steps'], function ($a, $b) {
                return ($a['step_number'] ?? 0) - ($b['step_number'] ?? 0);
            });

            foreach ($data['process_steps'] as &$step) {
                if (isset($step['key_deliverables'])) {
                    $step['deliverables_array'] = array_map('trim', explode(',', $step['key_deliverables']));
                }

                if (isset($step['tools_used'])) {
                    $step['tools_array'] = array_map('trim', explode(',', $step['tools_used']));
                }

                $classes = ['timeline-step'];
                if ($step['is_critical_path'] ?? false) {
                    $classes[] = 'critical-path';
                }
                if ($step['requires_client_approval'] ?? false) {
                    $classes[] = 'requires-approval';
                }
                if (isset($step['complexity_level'])) {
                    $classes[] = 'complexity-'.$step['complexity_level'];
                }

                $step['css_classes'] = implode(' ', $classes);

                if (isset($step['step_image'])) {
                    $step['image_url'] = asset('storage/'.$step['step_image']);
                }
            }
        }

        $classes = ['process-timeline', 'style-'.str_replace('_', '-', $data['timeline_style'] ?? 'vertical')];

        if ($data['interactive_timeline'] ?? false) {
            $classes[] = 'interactive';
        }

        if (isset($data['animation_style']) && $data['animation_style'] !== 'none') {
            $classes[] = 'animated';
            $classes[] = 'animation-'.str_replace('_', '-', $data['animation_style']);
        }

        $data['timeline_css_classes'] = implode(' ', $classes);

        return $data;
    }
}
