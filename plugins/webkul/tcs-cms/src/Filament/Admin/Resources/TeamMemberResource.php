<?php

namespace Webkul\TcsCms\Filament\Admin\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Webkul\TcsCms\Filament\Admin\Resources\TeamMemberResource\Pages;
use Webkul\TcsCms\Models\TeamMember;

class TeamMemberResource extends Resource
{
    protected static ?string $model = TeamMember::class;

    protected static ?string $slug = 'cms/team-members';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 60;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-user-group';
    }

    public static function getNavigationLabel(): string
    {
        return 'Team Members';
    }

    public static function getNavigationGroup(): string
    {
        return 'TCS CMS';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Basic Information')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->maxLength(255)
                                    ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(TeamMember::class, 'slug', ignoreRecord: true),
                                TextInput::make('title')
                                    ->placeholder('e.g., Master Craftsman')
                                    ->maxLength(255),
                                Select::make('role')
                                    ->options([
                                        'owner' => 'Owner',
                                        'lead_craftsman' => 'Lead Craftsman',
                                        'craftsman' => 'Craftsman',
                                        'apprentice' => 'Apprentice',
                                        'designer' => 'Designer',
                                        'project_manager' => 'Project Manager',
                                        'office' => 'Office Staff',
                                    ]),
                                TextInput::make('years_experience')
                                    ->numeric()
                                    ->suffix('years'),
                                DatePicker::make('start_date')
                                    ->label('Started at TCS'),
                                Textarea::make('bio')
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->helperText('Brief bio for listings')
                                    ->columnSpanFull(),
                                RichEditor::make('full_bio')
                                    ->label('Full Biography')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Section::make('Skills & Certifications')
                            ->schema([
                                Repeater::make('skills')
                                    ->simple(TextInput::make('skill'))
                                    ->defaultItems(3),
                                Repeater::make('certifications')
                                    ->simple(TextInput::make('certification'))
                                    ->defaultItems(0),
                            ])
                            ->columns(2),

                        Section::make('Contact Information')
                            ->schema([
                                TextInput::make('email')
                                    ->email(),
                                TextInput::make('phone')
                                    ->tel(),
                                KeyValue::make('social_links')
                                    ->keyLabel('Platform')
                                    ->valueLabel('URL')
                                    ->keyPlaceholder('e.g., linkedin')
                                    ->valuePlaceholder('https://...')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Photo')
                            ->schema([
                                FileUpload::make('photo')
                                    ->image()
                                    ->directory('team')
                                    ->imageEditor(),
                            ]),

                        Section::make('Status')
                            ->schema([
                                Toggle::make('is_published')
                                    ->label('Published')
                                    ->default(false),
                                Toggle::make('featured')
                                    ->label('Featured Team Member')
                                    ->default(false),
                                TextInput::make('position')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Lower = higher priority'),
                            ]),

                        Section::make('Link to Employee')
                            ->schema([
                                Select::make('employee_id')
                                    ->relationship('employee', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Optional: Link to ERP employee record'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')
                    ->label('Photo')
                    ->circular(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('role')
                    ->badge()
                    ->sortable(),
                TextColumn::make('years_experience')
                    ->label('Experience')
                    ->suffix(' yrs')
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
                IconColumn::make('featured')
                    ->boolean(),
                TextColumn::make('position')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'owner' => 'Owner',
                        'lead_craftsman' => 'Lead Craftsman',
                        'craftsman' => 'Craftsman',
                        'apprentice' => 'Apprentice',
                        'designer' => 'Designer',
                        'project_manager' => 'Project Manager',
                        'office' => 'Office Staff',
                    ]),
                TernaryFilter::make('is_published')
                    ->label('Published'),
                TernaryFilter::make('featured'),
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
            'index' => Pages\ListTeamMembers::route('/'),
            'create' => Pages\CreateTeamMember::route('/create'),
            'view' => Pages\ViewTeamMember::route('/{record}'),
            'edit' => Pages\EditTeamMember::route('/{record}/edit'),
        ];
    }
}
