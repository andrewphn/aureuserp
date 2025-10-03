# PRD: FilamentPHP Project Creation Wizard

## Overview
Implement a multi-step wizard for project creation in TCS Woodwork ERP that guides users through the project setup process with live summary updates.

## Current State
- Project creation uses a standard form with all fields visible at once
- Uses a ProgressStepper component to show project stages (Discovery, Design, Sourcing, Production, Delivery)
- No live summary or preview of entered data
- Form is defined in `ProjectResource.php`

## Goals
1. Create a step-by-step wizard interface for project creation
2. Add a live summary panel that updates as users fill in fields
3. Organize fields logically by project stage
4. Maintain compatibility with existing FilamentPHP v4 architecture
5. Preserve all existing functionality and custom fields

## Technical Requirements

### Architecture
- **Must use**: Standard FilamentPHP v4 wizard with `HasWizard` trait
- **Must preserve**: Current form schema defined in `ProjectResource.php`
- **Must integrate**: Livewire v3 components for reactivity
- **Must maintain**: Custom fields system via `HasCustomFields` trait

### Wizard Steps
The wizard should have 5 steps matching the current project stages:

#### Step 1: Discovery
**Purpose**: Capture essential project information and customer details

**Field Order** (based on screenshot and user requirements):

1. **Live Summary Panel** (NEW) - Livewire component at top
   - Component: `App\Livewire\ProjectSummaryPanel`
   - Position: `columnSpanFull()` at very top of step
   - Displays: Project Number, Location, Customer, Company, Type
   - Updates in real-time as fields below are filled

2. **Company** (First field - determines context)
   - Field: `Select::make('company_id')`
   - Relationship: 'company', 'name'
   - Searchable and preloadable
   - Create option: Opens CompanyResource::form()
   - **WHY FIRST**: Company determines the context for customer, pricing, and project numbering

3. **Customer** (Second field - depends on company)
   - Field: `Select::make('partner_id')`
   - Relationship: 'partner', 'name'
   - Searchable and preloadable
   - Create option: Opens PartnerResource::form()
   - Edit option: Opens PartnerResource::form()
   - **DEPENDENCY**: May filter by company_id if applicable

4. **Project Name** (required)
   - Field: `TextInput::make('name')`
   - Validation: required, maxLength(255)
   - Style: Large font (1.5rem), height 3rem
   - Placeholder: Translation key from general.fields.name-placeholder

5. **Project Address Section** (NEW - if different from customer address)
   - **Use Customer Address** (Toggle/Checkbox)
     - Default: checked (use customer's address)
     - When unchecked: Show project address fields below

   - **Project Address Fields** (conditional - shown when NOT using customer address):
     - Field: `TextInput::make('project_address.street1')` - Street Address 1
     - Field: `TextInput::make('project_address.street2')` - Street Address 2
     - Field: `TextInput::make('project_address.city')` - City
     - Field: `TextInput::make('project_address.state')` - State
     - Field: `TextInput::make('project_address.zip')` - ZIP Code
     - Field: `TextInput::make('project_address.country')` - Country
     - **NOTE**: These create a record in `projects_project_addresses` table

6. **Project Number** (Auto-generated - READ ONLY)
   - Field: `TextInput::make('project_number')`
   - Read-only/disabled
   - Placeholder: "Will be generated on save"
   - **AUTO-GENERATION LOGIC**:
     - Format: Based on company prefix + sequential number
     - Example: "TCS-2025-001"
     - Generated in `mutateFormDataBeforeCreate()` method
     - Uses project address city/location if available

7. **Description**
   - Field: `RichEditor::make('description')`
   - Optional field
   - Full rich text editing capabilities

8. **Project Manager**
   - Field: `Select::make('user_id')`
   - Relationship: 'user', 'name'
   - Searchable and preloadable
   - Create option: Opens UserResource::form()
   - Default: Could be current user or auto-assigned

9. **Project Type** (NEW field for summary panel)
   - Field: `Select::make('project_type')`
   - Options: "Residential", "Commercial", "Custom", "Other"
   - When "Other" selected: Show text input for custom type
   - **PURPOSE**: Displays in Live Summary Panel

**Layout Structure**:
```php
Section::make('Project Information')
    ->schema([
        Livewire::make(ProjectSummaryPanel::class)->columnSpanFull(),

        Grid::make(2)->schema([
            Select::make('company_id'),  // Left column
            Select::make('partner_id'),  // Right column
        ]),

        TextInput::make('name')->columnSpanFull(),

        // Project Address Section
        Section::make('Project Location')
            ->schema([
                Toggle::make('use_customer_address')->default(true),
                Grid::make(2)
                    ->schema([
                        TextInput::make('project_address.street1'),
                        TextInput::make('project_address.street2'),
                        TextInput::make('project_address.city'),
                        TextInput::make('project_address.state'),
                        TextInput::make('project_address.zip'),
                        TextInput::make('project_address.country'),
                    ])
                    ->visible(fn (Get $get) => !$get('use_customer_address')),
            ])
            ->collapsible(),

        TextInput::make('project_number')
            ->disabled()
            ->dehydrated(false),

        RichEditor::make('description')->columnSpanFull(),

        Grid::make(2)->schema([
            Select::make('user_id'),     // Project Manager
            Select::make('project_type'), // Project Type
        ]),
    ])
```

**Key Implementation Notes**:
- Company MUST be first to establish context
- Project address is optional (can use customer address)
- Project number is auto-generated based on company + sequence
- All fields feed into Live Summary Panel for real-time preview

#### Step 2: Design
**Purpose**: Define project timeline and resource allocation

**Fields** (from ProjectResource.php lines 148-173):
- **Start Date**
  - Field: `DatePicker::make('start_date')`
  - Native: false (uses FilamentPHP date picker)
  - Format: Date only

- **End Date**
  - Field: `DatePicker::make('end_date')`
  - Native: false (uses FilamentPHP date picker)
  - Format: Date only

- **Allocated Hours**
  - Field: `TextInput::make('allocated_hours')`
  - Type: numeric
  - Step: 0.5
  - Min: 0
  - Suffix: Translation key
  - Helper text: "In hours (Eg. 1.5 hours means 1 hour 30 minutes)"

- **Tags**
  - Field: `Select::make('tags')`
  - Relationship: belongsToMany
  - Multiple: true
  - Searchable and preloadable
  - Create option: Opens TagResource::form()

**Layout**: Two-column grid for date fields, full width for others

#### Step 3: Sourcing
**Purpose**: Vendor and company information

**Fields** (from ProjectResource.php lines 174-185):
- **Company**
  - Field: `Select::make('company_id')`
  - Relationship: 'company', 'name'
  - Searchable and preloadable
  - Create option: Opens CompanyResource::form()

- **Custom Fields for Sourcing**
  - Merged via `HasCustomFields` trait
  - Method: `static::mergeCustomFormFields()`
  - Context: 'sourcing' or general project context

**Layout**: Section wrapper titled "Sourcing Information"

#### Step 4: Production
**Purpose**: Production and task management settings

**Fields** (from ProjectResource.php lines 186-220):
- **Task Management Section**
  - Title: "Task Management"
  - **Allow Milestones** (Toggle)
    - Field: `Toggle::make('allow_milestones')`
    - Default: true (checked)
    - Helper: "Monitor key milestones that are essential for achieving success."
    - Visibility: Conditional on TaskSettings::enable_milestones

- **Custom Fields for Production**
  - Merged via `HasCustomFields` trait
  - Method: `static::mergeCustomFormFields()`
  - Context: 'production' or general project context

**Layout**: Fieldset or Section wrapper for task management

#### Step 5: Delivery
**Purpose**: Final settings and permissions

**Fields** (from ProjectResource.php lines 186-220):
- **Settings Section**
  - Title: "Settings"

- **Visibility** (required)
  - Field: `Radio::make('visibility')`
  - Options:
    - `ProjectVisibility::Private->value` - "Private: Invited internal users only."
    - `ProjectVisibility::Internal->value` - "Internal: All internal users can see." (default)
    - `ProjectVisibility::Public->value` - "Public: Invited portal users and all internal users."
  - Default: ProjectVisibility::Internal
  - Descriptions: Include both title and helper text

- **Time Management Section**
  - Title: "Time Management"
  - **Allow Timesheets** (Toggle)
    - Field: `Toggle::make('allow_timesheets')`
    - Default: false
    - Helper: "Log time on tasks and track progress"
    - Visibility: Conditional on TimeSettings::enable_timesheets

**Layout**: Radio options in vertical list, sections for different setting groups

### Field Mapping from Current Form
All fields from `ProjectResource::form()` method (lines 104-220) must be distributed across wizard steps:

**Currently in ProjectResource.php**:
- Lines 110-119: ProgressStepper (REMOVE - replaced by wizard steps)
- Lines 120-131: General section → Step 1 (Discovery)
- Lines 133-147: Additional Information → Step 1 (Discovery)
- Lines 148-173: Dates/Hours/Tags → Step 2 (Design)
- Lines 174-185: Company → Step 3 (Sourcing)
- Lines 186-220: Settings → Steps 4 & 5 (Production/Delivery)

**Custom Fields Integration**:
- Method: `static::mergeCustomFormFields()` from HasCustomFields trait
- Must be preserved in appropriate wizard steps
- Should be placed after standard fields in each step

### Live Summary Panel
- **Location**: Top of Discovery step (first step)
- **Technology**: Livewire v3 component with Alpine.js reactivity
- **Update behavior**: Client-side updates without server roundtrips
- **Fields displayed**:
  - Project Number (shows "Will be assigned on save")
  - Location (from project name or address)
  - Customer (from partner_id selection)
  - Company (from company_id selection)
  - Type (from project_type field)

### Component Architecture
- `ProjectSummaryPanel.php` - Livewire component (already exists)
- `project-summary-panel.blade.php` - Blade template (already exists)
- Embedded using `Filament\Schemas\Components\Livewire::make()`

## Database Schema Requirements

### New Fields Required

#### `projects_projects` table additions:
- `project_type` - VARCHAR(50), nullable - Stores project type (Residential, Commercial, etc.)
- `project_type_other` - VARCHAR(255), nullable - Custom type when "Other" is selected
- `use_customer_address` - BOOLEAN, default true - Flag for using customer address vs custom

#### `projects_project_addresses` table (already exists):
- Migration file: `2025_10_01_155056_create_projects_project_addresses_table.php`
- Model: `Webkul\Project\Models\ProjectAddress`
- Relationship: `projects` hasOne `projectAddress`
- Fields:
  - `project_id` - Foreign key to projects_projects
  - `street1` - VARCHAR(255)
  - `street2` - VARCHAR(255), nullable
  - `city` - VARCHAR(100)
  - `state` - VARCHAR(100)
  - `zip` - VARCHAR(20)
  - `country` - VARCHAR(100)
  - Standard timestamps

### Project Number Auto-Generation Logic

**Implementation Location**: `CreateProject::mutateFormDataBeforeCreate()`

**Format**: `{COMPANY_ACRONYM}{UNIQUE_ID}-{STREET_NUMBER}-{STREET_NAME}`

**Generation Rules**:

1. **Company Acronym**: 2-4 letter company code
   - Get company by `$data['company_id']`
   - Use company `code` field if exists (e.g., "TCS", "ABC")
   - Fallback: First 3 letters of company name uppercase
   - Example: "TCS"

2. **Unique Project ID**: Auto-incrementing project record ID
   - Use the project's database primary key (`id`)
   - No padding, raw integer
   - Generated AFTER record is created
   - Example: "147"

3. **Street Number**: Extracted from project address
   - Parse `project_address.street1` field
   - Extract leading digits from street address
   - Remove any non-numeric characters
   - Example: From "123 Main Street" → "123"
   - If no street number: Use "0"

4. **Street Name**: Extracted and cleaned from project address
   - Parse `project_address.street1` field
   - Remove street number prefix
   - Remove street type suffix (Street, St, Avenue, Ave, Road, Rd, etc.)
   - Convert to uppercase
   - Remove spaces and special characters
   - Limit to first 15 characters
   - Example: From "123 Main Street" → "MAIN"

**Complete Examples**:
- `TCS147-123-MAIN` (TCS Woodwork, Project ID 147, 123 Main Street)
- `ABC89-456-BROADWAY` (ABC Company, Project ID 89, 456 Broadway)
- `TCS201-0-SHOPFLOOR` (Project with no street number, using shop floor name)

**Special Cases**:
- **Using Customer Address**: Still generate project number from customer's address
- **No Street Address**: Use `UNNAMED` as street name, `0` as street number
- **Project Name as Location**: If project has location in name, extract from there

**Implementation Code**:
```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    $data['creator_id'] = Auth::id();

    // Project number will be generated AFTER save in afterCreate hook
    // because we need the project ID

    return $data;
}

protected function afterCreate(): void
{
    $record = $this->getRecord();

    // Generate project number using record ID
    $projectNumber = $this->generateProjectNumber($record);

    // Update the record with generated project number
    $record->update(['project_number' => $projectNumber]);
}

protected function generateProjectNumber($project): string
{
    // 1. Company acronym
    $company = $project->company;
    $acronym = $company?->code ?? strtoupper(substr($company?->name ?? 'PRJ', 0, 3));

    // 2. Unique project ID (database primary key)
    $projectId = $project->id;

    // 3. Get address - either project address or customer address
    $address = null;
    if (!$project->use_customer_address && $project->projectAddress) {
        $address = $project->projectAddress->street1;
    } elseif ($project->partner && $project->partner->address) {
        $address = $project->partner->address->street1 ?? $project->partner->address;
    }

    // 4. Parse street number and name
    if ($address) {
        // Extract street number (leading digits)
        preg_match('/^(\d+)/', $address, $numberMatch);
        $streetNumber = $numberMatch[1] ?? '0';

        // Extract street name (remove number, clean up)
        $streetName = preg_replace('/^\d+\s*/', '', $address); // Remove leading number
        $streetName = preg_replace('/\s+(street|st|avenue|ave|road|rd|boulevard|blvd|drive|dr|lane|ln|court|ct|way|circle|cir)\.?$/i', '', $streetName); // Remove street type
        $streetName = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $streetName)); // Remove special chars
        $streetName = substr($streetName, 0, 15); // Limit length
        $streetName = $streetName ?: 'UNNAMED';
    } else {
        $streetNumber = '0';
        $streetName = 'UNNAMED';
    }

    return "{$acronym}{$projectId}-{$streetNumber}-{$streetName}";
}
```

**Why This Format**:
- **Unique**: Project ID ensures no duplicates
- **Descriptive**: Street address provides context
- **Sortable**: Can sort by company then ID
- **Recognizable**: Easy to reference in conversation ("TCS147")
- **Physical Location**: Street info helps identify project site

## Implementation Approach

### Phase 1: Database Setup
1. **Add migration** for new columns to `projects_projects`:
   - `project_type` VARCHAR(50) nullable
   - `project_type_other` VARCHAR(255) nullable
   - `use_customer_address` BOOLEAN default true

2. **Verify** `projects_project_addresses` table exists (already done)

3. **Update** `Project` model:
   - Add fillable fields: `project_type`, `project_type_other`, `use_customer_address`
   - Add relationship: `projectAddress()` hasOne
   - Add accessor for display project number

4. **Update** `ProjectAddress` model:
   - Verify relationship: `project()` belongsTo

### Phase 2: Wizard Structure
1. **Add** `HasWizard` trait to `CreateProject.php`
2. **Implement** `getSteps()` method returning 5 wizard steps
3. **Move** form fields from `ProjectResource::form()` to appropriate wizard steps
4. **Ensure** proper field grouping and layout per step
5. **Implement** project number auto-generation in `mutateFormDataBeforeCreate()`

### Phase 3: Live Summary Panel
1. **Embed** existing `ProjectSummaryPanel` component in Discovery step (top position)
2. **Update** `ProjectSummaryPanel` formatters to handle new fields:
   - Company (from company_id)
   - Customer (from partner_id)
   - Project Type (from project_type)
   - Location (from project_address or customer address)
3. **Configure** Alpine.js to watch all relevant form fields
4. **Test** real-time updates as users type/select

### Phase 4: Project Address Integration
1. **Implement** conditional address fields based on `use_customer_address` toggle
2. **Add** reactive visibility using `visible(fn (Get $get) => !$get('use_customer_address'))`
3. **Handle** address saving in `mutateFormDataBeforeCreate()`:
   - If `use_customer_address = true`: No project address record
   - If `use_customer_address = false`: Create ProjectAddress record
4. **Update** Live Summary to show correct address source

### Phase 5: Testing & Refinement
1. **Test** wizard navigation (next/previous/jump to step)
2. **Verify** all fields save correctly with proper relationships
3. **Test** validation at each step
4. **Verify** project number generation with different scenarios
5. **Ensure** custom fields render properly in all steps
6. **Test** address toggle behavior
7. **Verify** Live Summary updates correctly
8. **Check** mobile responsiveness

## User Experience

### Navigation
- Next/Previous buttons at bottom of each step
- Progress indicator showing current step
- Ability to jump to completed steps
- Final "Create" button on last step

### Validation
- Field-level validation on blur
- Step-level validation before proceeding to next step
- Clear error messages
- Scroll to first error on validation failure

### Summary Panel
- Collapsible section at top of Discovery step
- Updates immediately as user types
- Shows formatted/friendly values (not raw IDs)
- Visual indicators for empty fields

## Success Criteria
1. ✅ Wizard loads without errors
2. ✅ All 5 steps are accessible and functional
3. ✅ Live summary panel updates in real-time
4. ✅ All existing fields are present and functional
5. ✅ Custom fields render correctly
6. ✅ Project saves successfully with all data
7. ✅ No regressions in existing functionality

## Technical Constraints
- Must work with FilamentPHP v4 (not v3)
- Must use Laravel 11 and PHP 8.2+
- Must integrate with AureusERP plugin architecture
- Must preserve existing database schema
- Cannot break existing project edit/view pages

## Files to Modify
1. `plugins/webkul/projects/src/Filament/Resources/ProjectResource/Pages/CreateProject.php` - Add wizard
2. `plugins/webkul/projects/src/Filament/Resources/ProjectResource.php` - Refactor form schema
3. `app/Livewire/ProjectSummaryPanel.php` - Already exists, may need adjustments
4. `resources/views/livewire/project-summary-panel.blade.php` - Already exists

## Non-Goals
- Redesigning the overall project management system
- Changing database schema
- Modifying project edit page
- Adding new project fields (beyond what exists)
- Multi-language support (use existing translation system)
