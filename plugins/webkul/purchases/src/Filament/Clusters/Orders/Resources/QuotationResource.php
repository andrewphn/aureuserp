<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources;

use Webkul\Purchase\Filament\Clusters\Orders;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\QuotationResource\Pages;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;

class QuotationResource extends OrderResource
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $recordTitleAttribute = 'name';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Orders::class;

    public static function getNavigationLabel(): string
    {
        return __('purchases::filament/clusters/orders/resources/quotation.navigation.title');
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewQuotation::class,
            Pages\EditQuotation::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'view'   => Pages\ViewQuotation::route('/{record}'),
            'edit'   => Pages\EditQuotation::route('/{record}/edit'),
        ];
    }
}
