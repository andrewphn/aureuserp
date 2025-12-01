# HasSearchTermPreFill Trait

A reusable FilamentPHP trait for automatically pre-filling inline creation modals with search terms.

**Location:** `plugins/webkul/support/src/Traits/HasSearchTermPreFill.php`

**Namespace:** `Webkul\Support\Traits`

## Problem Solved

When using FilamentPHP's `Select::make()->createOptionForm()` for inline record creation, the search term typed by the user is lost when the modal opens. This trait captures the search term and pre-fills the modal form automatically.

**Before:** User types "Acme Construction" → clicks "Create" → modal opens with empty Name field
**After:** User types "Acme Construction" → clicks "Create" → modal opens with "Acme Construction" pre-filled

## Quick Start

```php
use Webkul\Support\Traits\HasSearchTermPreFill;

class CreateProject extends Page implements HasForms
{
    use InteractsWithForms;
    use HasSearchTermPreFill;  // Add the trait

    // ... your code
}
```

## Usage Patterns

### Pattern 1: Simple Pre-fill (Most Common)

Pre-fills the `name` field with the search term:

```php
Select::make('partner_id')
    ->label('Customer')
    ->searchable()
    ->getSearchResultsUsing($this->trackSearchTerm('partner_id', function (string $search): array {
        return Partner::where('name', 'like', "%{$search}%")
            ->pluck('name', 'id')
            ->toArray();
    }))
    ->createOptionForm([
        TextInput::make('name')->required(),
        TextInput::make('email'),
    ])
    ->createOptionUsing($this->withSearchTermClear('partner_id', function (array $data): int {
        return Partner::create($data)->getKey();
    }))
    ->createOptionAction($this->withSearchTermPreFill('partner_id'))
```

### Pattern 2: Custom Target Field

Pre-fill a field other than `name`:

```php
->createOptionAction($this->withSearchTermPreFill('warehouse_id', 'warehouse_name'))
```

### Pattern 3: With Modal Configuration

Add custom modal styling and headings:

```php
->createOptionAction($this->withSearchTermPreFill('partner_id', 'name', [
    'modalHeading' => 'Add New Customer',
    'modalDescription' => 'Quick customer entry - add more details later.',
    'modalWidth' => 'xl',  // sm, md, lg, xl, 2xl
]))
```

### Pattern 4: All-in-One Configuration

Use `configureSelectWithPreFill()` for a complete setup:

```php
$this->configureSelectWithPreFill(
    Select::make('category_id')
        ->label('Category')
        ->searchable()
        ->createOptionForm([
            TextInput::make('name')->required(),
        ]),
    'category_id',
    fn ($search) => Category::where('name', 'like', "%{$search}%")->pluck('name', 'id')->toArray(),
    fn ($data) => Category::create($data)->getKey(),
    ['modalHeading' => 'Create Category']
);
```

## API Reference

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `$searchTerms` | `array<string, string\|null>` | Stores search terms keyed by field name |

### Methods

#### `trackSearchTerm(string $fieldName, callable $searchCallback): callable`

Wraps your search callback to capture the search term before executing your search logic.

**Parameters:**
- `$fieldName` - The Select field name (e.g., 'partner_id')
- `$searchCallback` - Your search function: `fn($search) => ['id' => 'label', ...]`

**Returns:** A wrapped callback for `getSearchResultsUsing()`

#### `getSearchTerm(string $fieldName): ?string`

Get the stored search term for a field.

#### `clearSearchTerm(string $fieldName): void`

Clear the search term for a field (automatically called by `withSearchTermClear`).

#### `withSearchTermPreFill(string $fieldName, string $targetField = 'name', array $additionalConfig = []): callable`

Create a callback for `createOptionAction()` that pre-fills the form.

**Parameters:**
- `$fieldName` - The Select field name to get the search term from
- `$targetField` - The form field to pre-fill (default: 'name')
- `$additionalConfig` - Optional configuration:
  - `modalHeading` - Custom modal title
  - `modalDescription` - Custom modal subtitle
  - `modalWidth` - Modal width ('sm', 'md', 'lg', 'xl', '2xl')

**Returns:** A callback for `createOptionAction()`

#### `withSearchTermClear(string $fieldName, callable $createCallback): callable`

Wraps your creation callback to clear the search term after successful creation.

**Parameters:**
- `$fieldName` - The Select field name
- `$createCallback` - Your creation function: `fn($data) => $newRecordId`

**Returns:** A wrapped callback for `createOptionUsing()`

#### `configureSelectWithPreFill(Select $select, string $fieldName, callable $searchCallback, callable $createCallback, array $options = []): Select`

Convenience method to configure everything in one call.

## Complete Example

From `CreateProject.php`:

```php
<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Webkul\Partner\Models\Partner;
use Webkul\Support\Traits\HasSearchTermPreFill;

class CreateProject extends Page implements HasForms
{
    use InteractsWithForms;
    use HasSearchTermPreFill;

    protected function getFormSchema(): array
    {
        return [
            Select::make('partner_id')
                ->label('Customer')
                ->searchable()
                ->required()
                ->live(onBlur: true)
                // 1. Track the search term
                ->getSearchResultsUsing($this->trackSearchTerm('partner_id', function (string $search): array {
                    return Partner::where('sub_type', 'customer')
                        ->where('name', 'like', "%{$search}%")
                        ->orderBy('name')
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->toArray();
                }))
                ->getOptionLabelUsing(fn ($value): ?string => Partner::find($value)?->name)
                // 2. Define the creation form
                ->createOptionForm([
                    TextInput::make('name')
                        ->label('Customer Name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('Email')
                        ->email(),
                    TextInput::make('phone')
                        ->label('Phone')
                        ->tel(),
                ])
                // 3. Handle record creation and clear search term
                ->createOptionUsing($this->withSearchTermClear('partner_id', function (array $data): int {
                    $data['sub_type'] = 'customer';
                    $data['creator_id'] = Auth::id();

                    $partner = Partner::create($data);

                    Notification::make()
                        ->success()
                        ->title('Customer Created')
                        ->body("Customer '{$partner->name}' has been created.")
                        ->send();

                    return $partner->getKey();
                }))
                // 4. Pre-fill the modal with search term
                ->createOptionAction($this->withSearchTermPreFill('partner_id', 'name', [
                    'modalHeading' => 'Add New Customer',
                    'modalDescription' => 'Quick customer entry - you can add more details later.',
                ])),
        ];
    }
}
```

## Multiple Fields Support

The trait supports multiple Select fields independently. Each field tracks its own search term:

```php
class CreateOrder extends Page implements HasForms
{
    use HasSearchTermPreFill;

    protected function getFormSchema(): array
    {
        return [
            // Customer field
            Select::make('customer_id')
                ->searchable()
                ->getSearchResultsUsing($this->trackSearchTerm('customer_id', fn($search) => ...))
                ->createOptionAction($this->withSearchTermPreFill('customer_id')),

            // Product field (independent search term)
            Select::make('product_id')
                ->searchable()
                ->getSearchResultsUsing($this->trackSearchTerm('product_id', fn($search) => ...))
                ->createOptionAction($this->withSearchTermPreFill('product_id')),

            // Warehouse field (independent search term)
            Select::make('warehouse_id')
                ->searchable()
                ->getSearchResultsUsing($this->trackSearchTerm('warehouse_id', fn($search) => ...))
                ->createOptionAction($this->withSearchTermPreFill('warehouse_id', 'warehouse_name')),
        ];
    }
}
```

## How It Works

1. **Search Tracking:** When the user types in the Select's search box, `trackSearchTerm()` captures the search string and stores it in `$this->searchTerms['field_name']`.

2. **Modal Pre-fill:** When the user clicks "Create", `withSearchTermPreFill()` uses FilamentPHP's `fillForm()` callback to pre-populate the specified field with the stored search term.

3. **Cleanup:** After successful creation, `withSearchTermClear()` removes the search term from storage to prevent stale data.

## Testing

The trait is tested in:
- `plugins/webkul/projects/tests/Unit/CustomerCreationFormTest.php`
- `plugins/webkul/projects/tests/Feature/CustomerCreationModalIntegrationTest.php`

Run tests:
```bash
DB_CONNECTION=mysql vendor/bin/phpunit plugins/webkul/projects/tests/Unit/CustomerCreationFormTest.php
DB_CONNECTION=mysql vendor/bin/phpunit plugins/webkul/projects/tests/Feature/CustomerCreationModalIntegrationTest.php
```

## Why This Exists

FilamentPHP's Select component with `createOptionForm()` doesn't automatically pass the search term to the modal because:

1. The search is handled client-side by Alpine.js/Choices.js
2. The modal form is a separate Filament Action with its own state
3. There's no built-in mechanism to bridge the search input to the modal

This trait provides that bridge using Livewire's server-side state management.

## Related

- FilamentPHP Select documentation: https://filamentphp.com/docs/forms/select
- FilamentPHP Actions documentation: https://filamentphp.com/docs/actions
- Original discussion: https://github.com/filamentphp/filament/discussions/5379
