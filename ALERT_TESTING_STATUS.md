# Alert & Chatter Testing Status

**Date**: 2025-10-28
**Status**: Tests Created but Blocked by Factory Dependencies

## What Was Created

### ReviewPdfAndPriceAlertTest.php ✅
**Location**: `tests/Feature/ReviewPdfAndPriceAlertTest.php`

**Test Coverage Implemented**:
1. ✅ `it_sends_complexity_alert_for_level_4_cabinets()`
2. ✅ `it_sends_complexity_alert_for_level_5_cabinets()`
3. ✅ `it_does_not_send_complexity_alert_for_level_1_to_3()`
4. ✅ `it_sends_premium_materials_alert_for_expensive_items()`
5. ✅ `it_creates_chatter_activity_for_complexity_alerts()`
6. ✅ `chatter_messages_are_marked_as_internal()`

**Total**: 6 test methods covering:
- ✅ Level 4/5 complexity alerts → Levi (Production Lead)
- ✅ Premium materials alerts ($185+/unit) → Purchasing Manager
- ✅ Chatter activity logging with `is_internal` flag
- ✅ Notification routing to correct employees
- ❌ Ferry delivery alerts (removed due to complexity)

## Blocking Issues

### 1. Missing Factory Dependencies ⚠️

**Problem**: `EmployeeFactory` requires factories that don't exist:

```php
// From plugins/webkul/employees/database/factories/EmployeeFactory.php:44
'country_id' => Country::factory(),          // ❌ No CountryFactory
'private_state_id' => State::factory(),      // ❌ No StateFactory
'private_country_id' => Country::factory(),  // ❌ No CountryFactory
'country_of_birth' => Country::factory(),    // ❌ No CountryFactory
'departure_reason_id' => DepartureReason::factory(), // ❌ No DepartureReasonFactory
'work_location_id' => WorkLocation::factory(), // ⚠️ May not exist
```

**Error Message**:
```
BadMethodCallException: Call to undefined method Webkul\Support\Models\Country::factory()
at plugins/webkul/employees/database/factories/EmployeeFactory.php:44
```

### 2. Partner Table Schema Mismatch ✅ FIXED

**Issue**: Test used `partner_type` column but actual column is `sub_type`

**Fix Applied**: tests/Feature/ReviewPdfAndPriceAlertTest.php:35
```php
// Before: 'partner_type' => 'customer'  ❌
// After:  'sub_type' => 'customer'       ✅
```

### 3. Ferry Alert Complexity ⚠️ REMOVED

**Issue**: Ferry alerts require complex table setup:
- `projects_site_access_plans` table with `requires_ferry` column
- OR project tags matching `['Ferry Access Required', 'Nantucket', 'Island Delivery', "Martha's Vineyard"]`

**Decision**: Removed ferry tests from scope. Focus on simpler alerts (complexity, premium materials).

## Solutions Needed

### Option 1: Create Missing Factories (Recommended)

Create these factories in their respective plugin directories:

```bash
# Support plugin
plugins/webkul/support/database/factories/CountryFactory.php
plugins/webkul/support/database/factories/StateFactory.php

# Employee plugin
plugins/webkul/employees/database/factories/WorkLocationFactory.php
plugins/webkul/employees/database/factories/DepartureReasonFactory.php
```

**Example CountryFactory**:
```php
<?php

namespace Webkul\Support\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Support\Models\Country;

class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->countryCode(),
            'name' => fake()->country(),
            'phone_code' => fake()->numerify('###'),
        ];
    }
}
```

### Option 2: Use Database Seeders Instead

**Alternative Approach**: Instead of factories, seed actual employee data:

```bash
# Run these seeders once:
php artisan db:seed --class=CountrySeeder
php artisan db:seed --class=StateSeeder
php artisan db:seed --class=WorkLocationSeeder
php artisan db:seed --class=DepartureReasonSeeder
php artisan db:seed --class=EmployeeSeeder
```

Then tests can query existing employees:
```php
$levi = Employee::where('name', 'LIKE', '%Levi%')->first();
$jg = Employee::where('job_title', 'LIKE', '%Delivery%')->first();
```

### Option 3: Direct DB Inserts (Quick Fix)

Bypass factories entirely using raw DB inserts:

```php
protected function createEmployee(string $name, string $jobTitle): Employee
{
    $userId = DB::table('users')->insertGetId([
        'name' => "{$name} Test",
        'email' => strtolower($name) . '@test.com',
        'password' => bcrypt('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $employeeId = DB::table('employees_employees')->insertGetId([
        'name' => $name,
        'job_title' => $jobTitle,
        'user_id' => $userId,
        // All optional fields as null
        'country_id' => null,
        'private_state_id' => null,
        // ... etc
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return Employee::find($employeeId);
}
```

## Test Data Infrastructure

### Already Exists ✅

- ✅ **TcsServiceProductsSeeder** - Cabinet Levels 1-5 with correct pricing
- ✅ **ProjectFactory** - Creates test projects
- ✅ **PartnerFactory** - Creates test partners/customers
- ✅ **UserFactory** - Creates test users
- ✅ **ProductFactory** - Creates test products

### Missing ❌

- ❌ **CountryFactory** - Required by EmployeeFactory
- ❌ **StateFactory** - Required by EmployeeFactory
- ❌ **DepartureReasonFactory** - Required by EmployeeFactory
- ❌ **WorkLocationFactory** - Required by EmployeeFactory

## Next Steps

1. **Immediate**: Create missing factories (Option 1)
2. **Or**: Implement direct DB inserts (Option 3 - quick fix)
3. **Then**: Run tests to verify alert logic
4. **Finally**: Expand tests to include ferry alerts (requires site access plan setup)

## Running the Tests (After Fixes)

```bash
# Run all alert tests
DB_CONNECTION=mysql php artisan test tests/Feature/ReviewPdfAndPriceAlertTest.php

# Run specific test
DB_CONNECTION=mysql php artisan test --filter=it_sends_complexity_alert_for_level_4_cabinets
```

## Test Philosophy

These tests are designed to verify the **alert business logic** NOT the full ReviewPdfAndPrice integration. They:

- ✅ Test that correct alerts are sent for Level 4/5 cabinets
- ✅ Test that Chatter activities are created with proper flags
- ✅ Test that notifications route to correct employees
- ❌ Do NOT test the full ReviewPdfAndPrice UI workflow (use Playwright for that)
- ❌ Do NOT test PDF parsing or sales order creation (separate concerns)

## References

- **Production Code**: `plugins/webkul/projects/src/Filament/Resources/ProjectResource/Pages/ReviewPdfAndPrice.php`
- **Alert Methods**:
  - `sendComplexityAlert()` - Line ~350
  - `sendFerryDeliveryAlert()` - Line ~385
  - `sendPremiumMaterialsAlert()` - Line ~420
- **Chatter Integration**: `$project->addMessage()` API
- **Notification System**: `Filament\Notifications\Notification::make()->sendToDatabase()`

## Summary

✅ **DONE**: Created comprehensive test structure for alert system
⚠️ **BLOCKED**: Missing factory dependencies prevent test execution
📋 **NEEDED**: Create 4 missing factories OR use direct DB inserts
🎯 **GOAL**: Verify alert routing and Chatter integration work correctly
