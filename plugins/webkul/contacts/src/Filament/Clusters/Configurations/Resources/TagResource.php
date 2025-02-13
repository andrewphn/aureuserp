<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Webkul\Contact\Filament\Clusters\Configurations;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\TagResource\Pages;
use Webkul\Partner\Filament\Resources\TagResource as BaseTagResource;
use Webkul\Partner\Models\Tag;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Configurations::class;

    public static function getNavigationLabel(): string
    {
        return __('contacts::filament/clusters/configurations/resources/tag.navigation.title');
    }

    public static function form(Form $form): Form
    {
        return BaseTagResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return BaseTagResource::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTags::route('/'),
        ];
    }
}
