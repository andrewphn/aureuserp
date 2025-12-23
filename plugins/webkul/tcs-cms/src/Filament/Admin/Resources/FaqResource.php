<?php

namespace Webkul\TcsCms\Filament\Admin\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Webkul\TcsCms\Filament\Admin\Resources\FaqResource\Pages;
use Webkul\TcsCms\Models\Faq;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $slug = 'cms/faqs';

    protected static ?string $recordTitleAttribute = 'question';

    protected static ?int $navigationSort = 50;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-question-mark-circle';
    }

    public static function getNavigationLabel(): string
    {
        return 'FAQs';
    }

    public static function getNavigationGroup(): string
    {
        return 'TCS CMS';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('FAQ Details')
                    ->schema([
                        TextInput::make('question')
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull(),
                        RichEditor::make('answer')
                            ->required()
                            ->columnSpanFull(),
                        Select::make('category')
                            ->options(Faq::CATEGORIES)
                            ->default('general')
                            ->required(),
                        TextInput::make('position')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower = higher priority'),
                    ])
                    ->columns(2),

                Section::make('Status')
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Published')
                            ->default(true),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'draft' => 'Draft',
                                'archived' => 'Archived',
                            ])
                            ->default('active'),
                    ])
                    ->columns(2),

                Section::make('Statistics')
                    ->schema([
                        TextInput::make('view_count')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        TextInput::make('helpful_count')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->hidden(fn ($record) => $record === null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')
                    ->searchable()
                    ->sortable()
                    ->limit(60),
                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Faq::CATEGORIES[$state] ?? $state)
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
                TextColumn::make('position')
                    ->sortable(),
                TextColumn::make('view_count')
                    ->label('Views')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('helpful_count')
                    ->label('Helpful')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(Faq::CATEGORIES),
                TernaryFilter::make('is_published')
                    ->label('Published'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('position')
            ->reorderable('position');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'view' => Pages\ViewFaq::route('/{record}'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
