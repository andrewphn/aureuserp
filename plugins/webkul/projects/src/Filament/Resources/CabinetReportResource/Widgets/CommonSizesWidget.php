<?php

namespace Webkul\Project\Filament\Resources\CabinetReportResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
use Webkul\Project\Models\Cabinet;

/**
 * Common Sizes Widget Filament widget
 *
 * @see \Filament\Resources\Resource
 */
class CommonSizesWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    /**
     * Define the table schema
     *
     * @param Table $table
     * @return Table
     */
    public function table(Table $table): Table
    {
        return $table
            ->heading('Most Common Cabinet Sizes')
            ->description('Top 10 most frequently built configurations')
            ->query(
                Cabinet::query()
                    ->selectRaw('
                        CONCAT(length_inches, "-", width_inches, "-", depth_inches, "-", height_inches) as id,
                        length_inches,
                        width_inches,
                        depth_inches,
                        height_inches,
                        SUM(quantity) as total_quantity,
                        COUNT(*) as order_count,
                        AVG(total_price / NULLIF(quantity, 0)) as avg_price_each,
                        CASE
                            WHEN linear_feet <= 1.5 THEN "small"
                            WHEN linear_feet <= 3.0 THEN "medium"
                            WHEN linear_feet <= 4.0 THEN "large"
                            ELSE "extra-large"
                        END as size_range
                    ')
                    ->groupBy(['length_inches', 'width_inches', 'depth_inches', 'height_inches'])
                    ->orderByDesc('total_quantity')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('dimensions')
                    ->label('Dimensions (L × W × D × H)')
                    ->getStateUsing(fn ($record) =>
                        "{$record->length_inches}\" × {$record->width_inches}\" × {$record->depth_inches}\" × {$record->height_inches}\""
                    )
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('size_range')
                    ->label('Size Range')
                    ->badge()
                    ->colors([
                        'success' => 'small',
                        'info' => 'medium',
                        'warning' => 'large',
                        'danger' => 'extra-large',
                    ]),

                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Total Built')
                    ->numeric()
                    ->sortable()
                    ->icon('heroicon-m-cube')
                    ->iconColor('primary'),

                Tables\Columns\TextColumn::make('order_count')
                    ->label('# Orders')
                    ->numeric()
                    ->sortable()
                    ->icon('heroicon-m-shopping-cart')
                    ->iconColor('warning'),

                Tables\Columns\TextColumn::make('avg_price_each')
                    ->label('Avg Price Each')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('actions')
                    ->label('Actions')
                    ->view('webkul-project::filament.common-size-actions'),
            ])
            ->paginated(false);
    }
}
