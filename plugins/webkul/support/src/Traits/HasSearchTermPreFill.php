<?php

namespace Webkul\Support\Traits;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;

/**
 * Trait for pre-filling create option forms with search terms
 *
 * This trait enables any Filament page/resource to automatically pre-fill
 * the "name" field (or custom field) in create option modals with whatever
 * the user typed in the search box.
 *
 * Works with both simple single-field modals (e.g., Project Type) and
 * complex multi-field modals (e.g., Customer, Warehouse).
 *
 * Usage:
 * 1. Add `use HasSearchTermPreFill;` to your Filament Page class
 * 2. Use the helper methods when building Select fields:
 *
 * Simple usage (pre-fills 'name' field):
 * ```php
 * Select::make('partner_id')
 *     ->searchable()
 *     ->getSearchResultsUsing($this->trackSearchTerm('partner_id', function ($search) {
 *         return Partner::where('name', 'like', "%{$search}%")->pluck('name', 'id')->toArray();
 *     }))
 *     ->createOptionForm([...])
 *     ->createOptionAction($this->withSearchTermPreFill('partner_id'))
 * ```
 *
 * Custom field pre-fill:
 * ```php
 * ->createOptionAction($this->withSearchTermPreFill('warehouse_id', 'warehouse_name'))
 * ```
 *
 * @package Webkul\Support\Traits
 */
trait HasSearchTermPreFill
{
    /**
     * Stores the last search term for each field
     * Key = field name, Value = search term
     *
     * @var array<string, string|null>
     */
    public array $searchTerms = [];

    /**
     * Track the search term for a specific field
     *
     * Wraps your search callback to automatically capture the search term
     * before executing your search logic.
     *
     * @param string $fieldName The Select field name (e.g., 'partner_id')
     * @param callable $searchCallback Your search function that receives $search and returns results
     * @return callable The wrapped callback for getSearchResultsUsing
     */
    public function trackSearchTerm(string $fieldName, callable $searchCallback): callable
    {
        return function (string $search) use ($fieldName, $searchCallback): array {
            // Store the search term for this field
            $this->searchTerms[$fieldName] = $search;

            // Execute the original search callback
            return $searchCallback($search);
        };
    }

    /**
     * Get the stored search term for a field
     *
     * @param string $fieldName The Select field name
     * @return string|null The last search term, or null if none
     */
    public function getSearchTerm(string $fieldName): ?string
    {
        return $this->searchTerms[$fieldName] ?? null;
    }

    /**
     * Clear the search term for a field (call after successful creation)
     *
     * @param string $fieldName The Select field name
     * @return void
     */
    public function clearSearchTerm(string $fieldName): void
    {
        unset($this->searchTerms[$fieldName]);
    }

    /**
     * Create a createOptionAction callback that pre-fills the form with the search term
     *
     * @param string $fieldName The Select field name to get the search term from
     * @param string $targetField The form field to pre-fill (default: 'name')
     * @param array $additionalConfig Additional Action configuration (slideOver, modalWidth, etc.)
     * @return callable Callback for createOptionAction
     */
    public function withSearchTermPreFill(
        string $fieldName,
        string $targetField = 'name',
        array $additionalConfig = []
    ): callable {
        return function (Action $action) use ($fieldName, $targetField, $additionalConfig): Action {
            // Apply default slide-over style
            $action = $action
                ->slideOver()
                ->modalWidth($additionalConfig['modalWidth'] ?? 'lg');

            // Apply optional heading/description
            if (isset($additionalConfig['modalHeading'])) {
                $action = $action->modalHeading($additionalConfig['modalHeading']);
            }
            if (isset($additionalConfig['modalDescription'])) {
                $action = $action->modalDescription($additionalConfig['modalDescription']);
            }

            // Pre-fill the form with the search term
            $action = $action->fillForm(fn (): array => $this->getSearchTerm($fieldName)
                ? [$targetField => $this->getSearchTerm($fieldName)]
                : []
            );

            return $action;
        };
    }

    /**
     * Create a createOptionUsing callback that clears the search term after creation
     *
     * Wraps your creation callback to automatically clear the search term
     * after successful record creation.
     *
     * @param string $fieldName The Select field name
     * @param callable $createCallback Your creation function that receives $data and returns the new record's key
     * @return callable The wrapped callback for createOptionUsing
     */
    public function withSearchTermClear(string $fieldName, callable $createCallback): callable
    {
        return function (array $data) use ($fieldName, $createCallback): int|string {
            // Execute the creation callback
            $result = $createCallback($data);

            // Clear the search term after successful creation
            $this->clearSearchTerm($fieldName);

            return $result;
        };
    }

    /**
     * Configure a Select field with search term pre-fill in one method call
     *
     * This is a convenience method that configures all the necessary callbacks
     * for a Select field to support search term pre-fill.
     *
     * @param Select $select The Select component to configure
     * @param string $fieldName The field name for tracking (usually same as Select::make name)
     * @param callable $searchCallback Function to search records: fn($search) => ['id' => 'label', ...]
     * @param callable $createCallback Function to create record: fn($data) => $newId
     * @param array $options Additional options:
     *   - targetField: string (default: 'name') - which form field to pre-fill
     *   - modalHeading: string - custom modal heading
     *   - modalDescription: string - custom modal description
     *   - modalWidth: string (default: 'lg') - modal width
     * @return Select The configured Select component
     */
    public function configureSelectWithPreFill(
        Select $select,
        string $fieldName,
        callable $searchCallback,
        callable $createCallback,
        array $options = []
    ): Select {
        $targetField = $options['targetField'] ?? 'name';

        return $select
            ->getSearchResultsUsing($this->trackSearchTerm($fieldName, $searchCallback))
            ->createOptionUsing($this->withSearchTermClear($fieldName, $createCallback))
            ->createOptionAction($this->withSearchTermPreFill($fieldName, $targetField, $options));
    }
}
