<?php

namespace Webkul\TcsCms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class CraftsmanQuoteBlock
{
    public static function make(): Block
    {
        return Block::make('craftsman_quote')
            ->label('Craftsman Quote')
            ->icon('heroicon-o-chat-bubble-bottom-center-text')
            ->schema([
                RichEditor::make('quote_text')
                    ->label('Quote')
                    ->placeholder('Every piece of wood tells a story...')
                    ->toolbarButtons(['bold', 'italic'])
                    ->required()
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        TextInput::make('author_name')
                            ->label('Author Name')
                            ->placeholder('Bryan Patton')
                            ->required(),

                        TextInput::make('author_title')
                            ->label('Author Title/Role')
                            ->placeholder('Master Craftsman, TCS Woodworking'),
                    ]),

                Grid::make(2)
                    ->schema([
                        FileUpload::make('author_image')
                            ->label('Author Photo')
                            ->image()
                            ->imageEditor()
                            ->directory('tcs-cms/quotes')
                            ->helperText('Professional photo of the author'),

                        FileUpload::make('background_image')
                            ->label('Background Image')
                            ->image()
                            ->imageEditor()
                            ->directory('tcs-cms/quotes')
                            ->helperText('Optional background image'),
                    ]),

                Grid::make(4)
                    ->schema([
                        Select::make('quote_style')
                            ->label('Quote Style')
                            ->options([
                                'simple' => 'Simple',
                                'card' => 'Card Style',
                                'featured' => 'Featured Block',
                                'sidebar' => 'Sidebar Style',
                                'full_width' => 'Full Width Banner',
                            ])
                            ->default('card'),

                        Select::make('alignment')
                            ->label('Text Alignment')
                            ->options([
                                'left' => 'Left',
                                'center' => 'Center',
                                'right' => 'Right',
                            ])
                            ->default('center'),

                        Toggle::make('show_quotation_marks')
                            ->label('Show Quote Marks')
                            ->default(true),

                        Toggle::make('show_author_image')
                            ->label('Show Author Photo')
                            ->default(true),
                    ]),

                Grid::make(3)
                    ->schema([
                        Select::make('color_scheme')
                            ->label('Color Scheme')
                            ->options([
                                'default' => 'Default (Warm Wood)',
                                'light' => 'Light Background',
                                'dark' => 'Dark Background',
                                'brand' => 'Brand Colors',
                                'custom' => 'Custom',
                            ])
                            ->default('default'),

                        TextInput::make('custom_background_color')
                            ->label('Custom BG Color')
                            ->placeholder('#8B4513')
                            ->visible(fn(callable $get) => $get('color_scheme') === 'custom'),

                        TextInput::make('custom_text_color')
                            ->label('Custom Text Color')
                            ->placeholder('#FFFFFF')
                            ->visible(fn(callable $get) => $get('color_scheme') === 'custom'),
                    ]),
            ]);
    }

    public static function mutateData(array $data): array
    {
        $classes = ['craftsman-quote'];

        if (isset($data['quote_style'])) {
            $classes[] = 'style-'.str_replace('_', '-', $data['quote_style']);
        }

        if (isset($data['alignment'])) {
            $classes[] = 'align-'.$data['alignment'];
        }

        if (isset($data['color_scheme'])) {
            $classes[] = 'color-'.$data['color_scheme'];
        }

        if ($data['show_quotation_marks'] ?? true) {
            $classes[] = 'with-quotes';
        }

        $data['css_classes'] = implode(' ', $classes);

        // Process images
        if (isset($data['author_image'])) {
            $data['author_image_url'] = asset('storage/'.$data['author_image']);
        }

        if (isset($data['background_image'])) {
            $data['background_image_url'] = asset('storage/'.$data['background_image']);
        }

        return $data;
    }
}
