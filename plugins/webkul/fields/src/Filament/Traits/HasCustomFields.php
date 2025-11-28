<?php

namespace Webkul\Field\Filament\Traits;

use Webkul\Field\Filament\Forms\Components\CustomFields;
use Webkul\Field\Filament\Infolists\Components\CustomEntries;
use Webkul\Field\Filament\Tables\Columns\CustomColumns;
use Webkul\Field\Filament\Tables\Filters\CustomFilters;

/**
 * Has Custom Fields trait
 *
 */
trait HasCustomFields
{
    /**
     * Merge Custom Form Fields
     *
     * @param array $baseSchema
     * @param array $include
     * @param array $exclude
     * @return array
     */
    protected static function mergeCustomFormFields(array $baseSchema, array $include = [], array $exclude = []): array
    {
        return array_merge($baseSchema, static::getCustomFormFields($include, $exclude));
    }

    /**
     * Merge Custom Table Columns
     *
     * @param array $baseColumns
     * @param array $include
     * @param array $exclude
     * @return array
     */
    protected static function mergeCustomTableColumns(array $baseColumns, array $include = [], array $exclude = []): array
    {
        return array_merge($baseColumns, static::getCustomTableColumns($include, $exclude));
    }

    /**
     * Merge Custom Table Filters
     *
     * @param array $baseFilters
     * @param array $include
     * @param array $exclude
     * @return array
     */
    protected static function mergeCustomTableFilters(array $baseFilters, array $include = [], array $exclude = []): array
    {
        return array_merge($baseFilters, static::getCustomTableFilters($include, $exclude));
    }

    /**
     * Merge Custom Table Query Builder Constraints
     *
     * @param array $baseConstraints
     * @param array $include
     * @param array $exclude
     * @return array
     */
    protected static function mergeCustomTableQueryBuilderConstraints(array $baseConstraints, array $include = [], array $exclude = []): array
    {
        return array_merge($baseConstraints, static::getTableQueryBuilderConstraints($include, $exclude));
    }

    /**
     * Merge Custom Infolist Entries
     *
     * @param array $baseSchema
     * @param array $include
     * @param array $exclude
     * @return array
     */
    protected static function mergeCustomInfolistEntries(array $baseSchema, array $include = [], array $exclude = []): array
    {
        return array_merge($baseSchema, static::getCustomInfolistEntries($include, $exclude));
    }

    /**
     * Get Custom Form Fields
     *
     * @param array $include
     * @param array $exclude
     * @return array
     */
    protected static function getCustomFormFields(array $include = [], array $exclude = []): array
    {
        return CustomFields::make(static::class)
            ->include($include)
            ->exclude($exclude)
            ->getSchema();
    }

    /**
     * Get Custom Table Columns
     *
     * @param array $include
     * @param array $exclude
     * @return array
     */
    protected static function getCustomTableColumns(array $include = [], array $exclude = []): array
    {
        return CustomColumns::make(static::class)
            ->include($include)
            ->exclude($exclude)
            ->getColumns();
    }

    /**
     * Get Custom Table Filters
     *
     * @param array $include
     * @param array $exclude
     * @return array
     */
    protected static function getCustomTableFilters(array $include = [], array $exclude = []): array
    {
        return CustomFilters::make(static::class)
            ->include($include)
            ->exclude($exclude)
            ->getFilters();
    }

    /**
     * Get Table Query Builder Constraints
     *
     * @param array $include
     * @param array $exclude
     * @return array
     */
    protected static function getTableQueryBuilderConstraints(array $include = [], array $exclude = []): array
    {
        return CustomFilters::make(static::class)
            ->include($include)
            ->exclude($exclude)
            ->getQueryBuilderConstraints();
    }

    /**
     * Get Custom Infolist Entries
     *
     * @param array $include
     * @param array $exclude
     * @return array
     */
    protected static function getCustomInfolistEntries(array $include = [], array $exclude = []): array
    {
        return CustomEntries::make(static::class)
            ->include($include)
            ->exclude($exclude)
            ->getSchema();
    }
}
