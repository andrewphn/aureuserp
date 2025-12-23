<?php

namespace Webkul\TcsCms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class FAQBlock
{
    public static function make(): Block
    {
        return Block::make('faq')
            ->label('FAQ Section')
            ->icon('heroicon-o-question-mark-circle')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('section_title')
                            ->label('Section Title')
                            ->placeholder('Frequently Asked Questions')
                            ->required()
                            ->columnSpan(1),

                        Select::make('faq_category')
                            ->label('FAQ Category')
                            ->options([
                                'general' => 'General Questions',
                                'services' => 'Our Services',
                                'process' => 'Our Process',
                                'materials' => 'Materials & Quality',
                                'pricing' => 'Pricing & Estimates',
                                'timeline' => 'Timeline & Delivery',
                                'care' => 'Care & Maintenance',
                                'warranty' => 'Warranty & Guarantee',
                            ])
                            ->default('general')
                            ->columnSpan(1),
                    ]),

                RichEditor::make('section_intro')
                    ->label('Section Introduction')
                    ->toolbarButtons(['bold', 'italic', 'bulletList'])
                    ->columnSpanFull(),

                Repeater::make('faqs')
                    ->label('FAQ Items')
                    ->schema([
                        TextInput::make('question')
                            ->label('Question')
                            ->placeholder('What types of custom cabinetry do you offer?')
                            ->required(),

                        RichEditor::make('answer')
                            ->label('Answer')
                            ->toolbarButtons([
                                'bold', 'italic', 'bulletList', 'orderedList', 'link',
                            ])
                            ->required(),

                        Grid::make(3)
                            ->schema([
                                Select::make('faq_priority')
                                    ->label('Priority')
                                    ->options([
                                        'high' => 'High (Show First)',
                                        'medium' => 'Medium',
                                        'low' => 'Low (Show Last)',
                                    ])
                                    ->default('medium'),

                                Select::make('faq_subcategory')
                                    ->label('Subcategory')
                                    ->options([
                                        'before_project' => 'Before Starting',
                                        'during_project' => 'During Project',
                                        'after_project' => 'After Completion',
                                        'technical' => 'Technical Details',
                                        'general' => 'General Info',
                                    ])
                                    ->default('general'),

                                Toggle::make('is_featured')
                                    ->label('Featured FAQ')
                                    ->helperText('Show in highlights'),
                            ]),
                    ])
                    ->defaultItems(1)
                    ->reorderable()
                    ->cloneable()
                    ->collapsible()
                    ->itemLabel(fn(array $state): ?string => $state['question'] ?? 'Untitled FAQ')
                    ->addActionLabel('Add FAQ')
                    ->columnSpanFull(),

                Grid::make(3)
                    ->schema([
                        Select::make('display_style')
                            ->label('Display Style')
                            ->options([
                                'accordion' => 'Accordion (Expandable)',
                                'list' => 'Visible List',
                                'cards' => 'Card Grid',
                                'tabs' => 'Tabbed by Category',
                            ])
                            ->default('accordion'),

                        Toggle::make('show_search')
                            ->label('Enable FAQ Search')
                            ->default(false),

                        Toggle::make('show_categories')
                            ->label('Show Category Filters')
                            ->default(false),

                        Select::make('sort_order')
                            ->label('Sort Order')
                            ->options([
                                'manual' => 'Manual Order',
                                'priority' => 'By Priority',
                                'alphabetical' => 'Alphabetical',
                            ])
                            ->default('manual'),
                    ]),
            ]);
    }

    public static function mutateData(array $data): array
    {
        if (isset($data['faqs'])) {
            $sortBy = $data['sort_order'] ?? 'manual';

            switch ($sortBy) {
                case 'priority':
                    $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];
                    usort($data['faqs'], function ($a, $b) use ($priorityOrder) {
                        $aPriority = $priorityOrder[$a['faq_priority'] ?? 'medium'] ?? 2;
                        $bPriority = $priorityOrder[$b['faq_priority'] ?? 'medium'] ?? 2;

                        return $aPriority - $bPriority;
                    });
                    break;

                case 'alphabetical':
                    usort($data['faqs'], function ($a, $b) {
                        return strcasecmp($a['question'] ?? '', $b['question'] ?? '');
                    });
                    break;
            }

            // Add CSS classes and data attributes
            foreach ($data['faqs'] as &$faq) {
                $classes = ['faq-item'];

                if ($faq['is_featured'] ?? false) {
                    $classes[] = 'featured';
                }

                if (isset($faq['faq_priority'])) {
                    $classes[] = 'priority-'.$faq['faq_priority'];
                }

                $faq['css_classes'] = implode(' ', $classes);
            }
        }

        return $data;
    }
}
