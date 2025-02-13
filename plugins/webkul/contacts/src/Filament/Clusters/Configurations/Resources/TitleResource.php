<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Webkul\Contact\Filament\Clusters\Configurations;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\TitleResource\Pages;
use Webkul\Partner\Filament\Resources\TitleResource as BaseTitleResource;
use Webkul\Partner\Models\Title;

class TitleResource extends Resource
{
    protected static ?string $model = Title::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Configurations::class;

    public static function form(Form $form): Form
    {
        return BaseTitleResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return BaseTitleResource::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTitles::route('/'),
        ];
    }
}
