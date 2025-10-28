# TCS Workflow Test Infrastructure - What Already Exists

## Existing Seeders

### TcsServiceProductsSeeder.php ✅
- **Cabinet Pricing Levels 1-5**: $138, $168, $192, $210, $225 /LF
- References: CAB-L1 through CAB-L5
- Includes descriptions and pricing details
- **Already has what we need for testing**

### PdfDocumentSeeder.php ✅
- Creates sample PDF documents with pages
- Includes annotations, activities
- Creates realistic test data

### Other Seeders
- TcsSkillsSeeder, TcsWorkLocationSeeder, TcsWorkScheduleSeeder
- FooterTemplateSeeder, CurrencySeeder
- ShieldSeeder (security/permissions)

## Existing E2E Tests

### test-25-friendship-complete-workflow.mjs ✅
- **Complete PDF annotation workflow**
- Phases 1-6: PDF capture → classification → room creation → cabinet runs
- Screenshot capabilities
- Workflow state tracking

### test-cabinet-runs-e2e.mjs ✅
- Tests Cabinet Runs relation manager
- Create, edit, delete cabinet runs
- Room location selection
- Linear feet tracking

### Other Playwright Tests
- test-annotation-*.mjs (multiple annotation tests)
- test-*-debug.mjs (various debugging scripts)
- create-7-kitchens-workflow.mjs

## Existing PHP Tests

### Feature Tests
- PdfAnnotationEndToEndTest.php
- AnnotationSystemIntegrationTest.php
- Phase6IntegrationTest.php

### Integration Tests
- ModuleIntegrationTest.php
- PdfAnnotationModalIntegrationTest.php

## What's MISSING for Alert/Chatter Testing

### Not Found:
1. ❌ Test for ReviewPdfAndPrice page workflow
2. ❌ Test for sales order creation with alerts
3. ❌ Test for Level 4/5 complexity alerts → Levi
4. ❌ Test for Ferry delivery alerts → JG  
5. ❌ Test for Premium materials alerts → Purchasing
6. ❌ Test for Chatter activity logging
7. ❌ Validation that notifications appear for correct users

## Existing Factories ✅

**All factories exist - can use for testing:**
- `plugins/webkul/projects/database/factories/ProjectFactory.php`
- `plugins/webkul/partners/database/factories/PartnerFactory.php`
- `plugins/webkul/employees/database/factories/EmployeeFactory.php`
- `database/factories/UserFactory.php`
- `database/factories/PdfDocumentFactory.php`
- Plus 20+ other factories in plugins

**Usage Pattern from existing tests:**
```php
$project = \Webkul\Project\Models\Project::factory()->create();
$partner = \Webkul\Partner\Models\Partner::factory()->create();
$employee = \Webkul\Employee\Models\Employee::factory()->create();
```

## Recommended Approach

✅ **DO:**
1. Use TcsServiceProductsSeeder (run once: `php artisan db:seed --class=TcsServiceProductsSeeder`)
2. Use factories for test data generation
3. Follow pattern from `test-cabinet-runs-e2e.mjs` for Playwright tests
4. Follow pattern from `PdfAnnotationEndToEndTest.php` for PHP tests

❌ **DON'T:**
1. Create new seeders - TcsServiceProductsSeeder already has Cabinet Levels
2. Duplicate existing test infrastructure
3. Hardcode test data - use factories

## Next Steps for Alert/Chatter Testing

1. **Run existing seeder** (if not already run):
   ```bash
   DB_CONNECTION=mysql php artisan db:seed --class=TcsServiceProductsSeeder
   ```

2. **Create PHP Feature Test** for backend validation:
   - Test ReviewPdfAndPrice::createSalesOrder() method
   - Verify alerts sent to correct employees
   - Verify Chatter messages created
   - Use factories for test data

3. **Create Playwright E2E Test** (optional, for UI validation):
   - Reuse login pattern from test-cabinet-runs-e2e.mjs
   - Navigate to ReviewPdfAndPrice
   - Verify UI shows alerts/chatter

4. **No new seeders needed** - everything already exists!
