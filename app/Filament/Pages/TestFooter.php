<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

/**
 * Test Footer class
 *
 * @see \Filament\Resources\Resource
 */
class TestFooter extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected string $view = 'filament.pages.test-footer';

    protected static ?string $navigationLabel = 'Test Footer';

    protected static ?string $title = 'Test Footer';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 98;
}
