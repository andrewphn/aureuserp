<?php

namespace Webkul\Product\Filament\Resources\ProductResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Webkul\Product\Filament\Resources\AttributeResource;
use Webkul\Product\Filament\Resources\ProductResource;
use Webkul\Product\Filament\Resources\ProductResource\Actions\GenerateVariantsAction;
use Webkul\Product\Models\Attribute;
use Webkul\Product\Models\ProductAttribute;

/**
 * Manage Attributes class
 *
 * @see \Filament\Resources\Resource
 */
class ManageAttributes extends ManageRelatedRecords
{
    protected static string $resource = ProductResource::class;

    protected static string $relationship = 'attributes';

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    public static function getNavigationLabel(): string
    {
        return __('products::filament/resources/product/pages/manage-attributes.title');
    }

    /**
     * Define the form schema
     *
     * @param Schema $schema
     * @return Schema
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('attribute_id')
                    ->label(__('products::filament/resources/product/pages/manage-attributes.form.attribute'))
                    ->required()
                    ->relationship(
                        'attribute',
                        'name',
                        modifyQueryUsing: fn (Builder $query) => $query->withTrashed(),
                    )
                    ->getOptionLabelFromRecordUsing(function ($record): string {
                        $label = $record->name;
                        if ($record->isNumeric() && $record->unit_symbol) {
                            $label .= " ({$record->unit_symbol})";
                        }
                        if ($record->trashed()) {
                            $label .= ' (Deleted)';
                        }
                        return $label;
                    })
                    ->disableOptionWhen(function (string $value) {
                        return $this->getOwnerRecord()->attributes->contains('attribute_id', $value);
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->disabledOn('edit')
                    ->createOptionForm(fn (Schema $schema): Schema => AttributeResource::form($schema))
                    ->afterStateUpdated(function ($state, Set $set) {
                        $set('options', []);
                        $set('numeric_value', null);
                    }),

                // Options selector (for RADIO/SELECT/COLOR types)
                Select::make('options')
                    ->label(__('products::filament/resources/product/pages/manage-attributes.form.values'))
                    ->required()
                    ->relationship(
                        name: 'options',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Get $get, Builder $query) => $query->where('products_attribute_options.attribute_id', $get('attribute_id')),
                    )
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->visible(function (Get $get): bool {
                        $attributeId = $get('attribute_id');
                        if (! $attributeId) {
                            return true; // Show by default until attribute is selected
                        }
                        $attribute = Attribute::find($attributeId);
                        return $attribute && $attribute->requiresOptions();
                    }),

                // Numeric value input (for NUMBER/DIMENSION types)
                TextInput::make('numeric_value')
                    ->label(function (Get $get): string {
                        $attributeId = $get('attribute_id');
                        if (! $attributeId) {
                            return 'Value';
                        }
                        $attribute = Attribute::find($attributeId);
                        return $attribute?->getLabelWithUnit() ?? 'Value';
                    })
                    ->numeric()
                    ->required()
                    ->step(0.0001)
                    ->minValue(function (Get $get): ?float {
                        $attributeId = $get('attribute_id');
                        if (! $attributeId) {
                            return null;
                        }
                        $attribute = Attribute::find($attributeId);
                        return $attribute?->min_value;
                    })
                    ->maxValue(function (Get $get): ?float {
                        $attributeId = $get('attribute_id');
                        if (! $attributeId) {
                            return null;
                        }
                        $attribute = Attribute::find($attributeId);
                        return $attribute?->max_value;
                    })
                    ->helperText(function (Get $get): ?string {
                        $attributeId = $get('attribute_id');
                        if (! $attributeId) {
                            return null;
                        }
                        $attribute = Attribute::find($attributeId);
                        if (! $attribute) {
                            return null;
                        }
                        $hints = [];
                        if ($attribute->min_value !== null) {
                            $hints[] = "Min: {$attribute->min_value}";
                        }
                        if ($attribute->max_value !== null) {
                            $hints[] = "Max: {$attribute->max_value}";
                        }
                        return $hints ? implode(', ', $hints) : null;
                    })
                    ->visible(function (Get $get): bool {
                        $attributeId = $get('attribute_id');
                        if (! $attributeId) {
                            return false;
                        }
                        $attribute = Attribute::find($attributeId);
                        return $attribute && $attribute->isNumeric();
                    }),
            ])
            ->columns(1);
    }

    /**
     * Define the table schema
     *
     * @param Table $table
     * @return Table
     */
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->description(__('products::filament/resources/product/pages/manage-attributes.table.description'))
            ->columns([
                TextColumn::make('attribute.name')
                    ->label(__('products::filament/resources/product/pages/manage-attributes.table.columns.attribute'))
                    ->description(fn ($record) => $record->attribute?->unit_symbol ? "({$record->attribute->unit_symbol})" : null),
                TextColumn::make('values.attributeOption.name')
                    ->label(__('products::filament/resources/product/pages/manage-attributes.table.columns.values'))
                    ->badge()
                    ->visible(fn ($record) => $record->attribute && $record->attribute->requiresOptions()),
                TextColumn::make('numeric_value_display')
                    ->label('Value')
                    ->state(function ($record): ?string {
                        if (! $record->attribute?->isNumeric()) {
                            return null;
                        }
                        // Get the first value's numeric_value
                        $value = $record->values->first();
                        return $value?->getFormattedValue();
                    })
                    ->visible(fn ($record) => $record->attribute && $record->attribute->isNumeric()),
            ])
            ->headerActions([
                GenerateVariantsAction::make(),
                CreateAction::make()
                    ->label(__('products::filament/resources/product/pages/manage-attributes.table.header-actions.create.label'))
                    ->icon('heroicon-o-plus-circle')
                    ->mutateDataUsing(function (array $data): array {
                        $data['creator_id'] = Auth::id();

                        return $data;
                    })
                    ->after(function (ProductAttribute $record, array $data) {
                        // For numeric attributes, create a ProductAttributeValue with the numeric value
                        if ($record->attribute->isNumeric() && isset($data['numeric_value'])) {
                            $record->values()->create([
                                'product_id'     => $record->product_id,
                                'attribute_id'   => $record->attribute_id,
                                'numeric_value'  => $data['numeric_value'],
                            ]);
                        } else {
                            $this->updateOrCreateVariants($record);
                        }
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('products::filament/resources/product/pages/manage-attributes.table.header-actions.create.notification.title'))
                            ->body(__('products::filament/resources/product/pages/manage-attributes.table.header-actions.create.notification.body')),
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, ProductAttribute $record): array {
                        // Load numeric value for editing
                        if ($record->attribute->isNumeric()) {
                            $value = $record->values->first();
                            $data['numeric_value'] = $value?->numeric_value;
                        }
                        return $data;
                    })
                    ->after(function (ProductAttribute $record, array $data) {
                        // For numeric attributes, update the ProductAttributeValue
                        if ($record->attribute->isNumeric() && isset($data['numeric_value'])) {
                            $record->values()->updateOrCreate(
                                ['product_attribute_id' => $record->id],
                                [
                                    'product_id'    => $record->product_id,
                                    'attribute_id'  => $record->attribute_id,
                                    'numeric_value' => $data['numeric_value'],
                                ]
                            );
                        } else {
                            $this->updateOrCreateVariants($record);
                        }
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('products::filament/resources/product/pages/manage-attributes.table.actions.edit.notification.title'))
                            ->body(__('products::filament/resources/product/pages/manage-attributes.table.actions.edit.notification.body')),
                    ),
                DeleteAction::make()
                    ->after(function (ProductAttribute $record) {
                        $this->updateOrCreateVariants($record);
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('products::filament/resources/product/pages/manage-attributes.table.actions.delete.notification.title'))
                            ->body(__('products::filament/resources/product/pages/manage-attributes.table.actions.delete.notification.body')),
                    ),
            ])
            ->paginated(false);
    }

    /**
     * Update Or Create Variants
     *
     * @param ProductAttribute $record The model record
     * @return void
     */
    protected function updateOrCreateVariants(ProductAttribute $record): void
    {
        $record->values->each(function ($value) use ($record) {
            $value->update([
                'extra_price'  => $value->attributeOption->extra_price,
                'attribute_id' => $record->attribute_id,
                'product_id'   => $record->product_id,
            ]);
        });

        $this->replaceMountedTableAction('products.generate.variants');
    }
}
