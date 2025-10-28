# TCS Woodwork Complete Workflow System - Product Requirements Document
**Version:** 1.0
**Date:** October 2025
**Project:** End-to-End Business Workflow Implementation
**Platform:** AureusERP (Laravel 11 + FilamentPHP v4)

---

## Executive Summary

TCS Woodwork requires 25 integrated FilamentPHP panels to automate their complete business workflow from Lead Capture through Delivery. The system centers on **Linear Feet (LF)** as the universal measurement connecting all stages, with complexity multipliers adjusting pricing and resource allocation.

### Business Objectives
- **Eliminate Bryan bottleneck** through systematic automation
- **Professional architect credibility** via 24-hour professional responses
- **Thursday meeting efficiency** - cover 2x projects in same time
- **Zero missed details** through room-by-room systematic review
- **Delivery excellence** with proper logistics planning (NO installation)

### Core Principle
**Linear Feet + TCS Pricing Levels = Universal Business Language**

**TCS Pricing System (Existing)**:
- 5 Pricing Levels: $138-$225/LF (base labor)
- Material Upgrades: +$138-$185/LF
- Formula: `(Level Price + Material Upgrade) × Linear Feet = Total`
- Example: Level 3 ($192) + Stain Grade ($156) = $348/LF

**System Integration**:
- Sales quotes use existing PriceList
- Production schedules in LF
- Delivery planning in LF
- Profitability tracked from actual pricing

---

## Phase 1: Lead Capture & Discovery (5 Resources)

### 1. LeadInquiryResource
**Priority:** HIGH
**Complexity:** Medium
**Estimate:** 16 hours

**Purpose:** Mobile-friendly initial contact capture for Mark (Nantucket remote sales)
**Database:** Uses existing `partners_partners` for customers, `projects_projects` for projects - NO new tables needed

**Existing System Integration:**
- **Customers/Clients:** `partners_partners` (with partner type = customer)
- **Projects:** `projects_projects` (with stage tracking)
- **Companies:** `companies` table for company assignment

**Features:**
- Mobile-responsive form optimized for tablet/phone
- Contact information capture (name, email, phone, company) → creates/updates `partners_partners`
- Project location with ferry access flag
- Quick project type selection (kitchen, full house, commercial, etc.)
- Photo upload for site/architectural plans (multiple)
- Estimated Linear Feet slider (10-500 LF)
- Estimated budget dropdown
- Preferred timeline calendar
- Save draft functionality for incomplete submissions
- Auto-create Project in "Inquiry" stage on submission

**Technical Requirements:**
- FilamentPHP 4 Resource
- Mobile-first responsive design
- Media upload with cloud storage
- Voice transcription API integration
- Automatic Project model creation
- Email notification to Bryan on submission

**Acceptance Criteria:**
- Mark can submit inquiry in <3 minutes from mobile
- Photos and voice notes properly attached
- Project auto-created with "inquiry" stage
- Bryan receives notification with summary
- Draft save works offline, syncs when connected

---

### 2. ArchitectDrawingIntakeResource
**Priority:** CRITICAL
**Complexity:** High
**Estimate:** 32 hours

**Purpose:** Systematic PDF architectural plan review with AI-assisted room extraction and Linear Feet calculation (THE professional credibility solution)

**Features:**
- PDF upload with multi-page support
- AI extraction of room data (room type, dimensions, LF estimates)
- Manual verification/correction interface
- Room-by-room systematic review checklist
- Linear Feet calculation per room:
  - Upper cabinets LF
  - Lower cabinets LF
  - Tall/pantry units LF
  - Island/peninsula LF
- Complexity factor tagging per room:
  - Curved elements (1.5x multiplier)
  - Custom materials (1.3x multiplier)
  - High ceilings >9ft (1.2x multiplier)
  - Special finishes (1.4x multiplier)
  - Integration requirements
- Architect notes capture
- Total project LF aggregation
- Adjusted LF with complexity multipliers
- Project timeline estimation
- Similar project portfolio auto-selection
- Version control for plan revisions

**Technical Requirements:**
- PDF viewer integration (Nutrient PSPDFKit or similar)
- AI extraction service (GPT-4 Vision or AWS Textract)
- Room model creation/updating
- Linear Feet calculation engine
- Complexity multiplier system
- Project similarity matching algorithm

**Database Schema:**
```sql
-- projects_rooms table enhancement
ALTER TABLE projects_rooms ADD COLUMN total_linear_feet DECIMAL(10,2);
ALTER TABLE projects_rooms ADD COLUMN upper_cabinet_lf DECIMAL(10,2);
ALTER TABLE projects_rooms ADD COLUMN lower_cabinet_lf DECIMAL(10,2);
ALTER TABLE projects_rooms ADD COLUMN tall_unit_lf DECIMAL(10,2);
ALTER TABLE projects_rooms ADD COLUMN island_lf DECIMAL(10,2);

-- projects table enhancement for TCS pricing
ALTER TABLE projects_projects ADD COLUMN pricing_level_id BIGINT REFERENCES products_product_price_lists(id);
ALTER TABLE projects_projects ADD COLUMN material_category_id BIGINT REFERENCES products_categories(id);
ALTER TABLE projects_projects ADD COLUMN pricing_effective_date DATE;
ALTER TABLE projects_projects ADD COLUMN quoted_price_per_lf DECIMAL(10,2);

-- Use existing projects_room_locations.complexity_tier (already has 1-5 tiers)
-- No new table needed - complexity already tracked per location
```

**Acceptance Criteria:**
- PDF uploads and displays all pages
- AI extracts room data with >80% accuracy
- Manual corrections save properly
- LF calculations accurate to architect plans
- Complexity multipliers apply correctly
- Total adjusted LF matches manual calculation
- Professional package generates in <5 seconds

---

### 3. ThursdayMeetingDashboardResource
**Priority:** CRITICAL
**Complexity:** High
**Estimate:** 24 hours

**Purpose:** Structured 15-minute project review interface solving Bryan's meeting chaos (the Thursday meeting transformation)

**Features:**
- Meeting agenda auto-generated from pending projects
- Priority sorting (high-value, time-sensitive first)
- 15-minute timer per project with alerts
- Three-tier review structure:
  1. **Overview (2 min)** - Client, budget, LF, timeline, Go/No-Go decision
  2. **Scope Review (3 min)** - Complexity factors, resource requirements
  3. **Technical Details (5 min)** - Materials, hardware, special requirements
- Decision capture:
  - Go/No-Go with reasoning
  - Drawing hours allocation (0-8 hours)
  - Material procurement needs
  - Team assignments (JG: drawings, Levi: planning, etc.)
  - Timeline commitments
- Real-time notes taking
- Parking lot for off-topic items
- Meeting summary auto-generation
- Email recap to all attendees
- Action item tracking
- Mark's async notes access (Nantucket time zone)

**Technical Requirements:**
- FilamentPHP 4 Widget Dashboard
- Real-time timer with browser notifications
- Auto-save functionality
- Email notification service
- Meeting history tracking

**Database Schema:**
```sql
-- projects_meetings table (new)
CREATE TABLE projects_meetings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    meeting_date DATE NOT NULL,
    attendees JSON, -- user IDs
    agenda_items JSON, -- project IDs with order
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- projects_meeting_decisions table (new)
CREATE TABLE projects_meeting_decisions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    meeting_id BIGINT REFERENCES projects_meetings(id),
    project_id BIGINT REFERENCES projects_projects(id),
    decision_type ENUM('go', 'no_go', 'deferred'),
    reasoning TEXT,
    drawing_hours_allocated INT,
    assigned_to_user_id BIGINT,
    follow_up_date DATE,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Acceptance Criteria:**
- Meeting covers 2x more projects than before
- Timer enforces 15-minute discipline
- All decisions captured and searchable
- Action items assigned with accountability
- Meeting summary sent within 5 minutes of completion
- Mark can review async from Nantucket

---

### 4. PricingLevelSelectorResource
**Priority:** HIGH
**Complexity:** Medium
**Estimate:** 12 hours

**Purpose:** Systematic TCS pricing level and material selection using existing PriceList system

**Features:**
- Integration with existing `products_product_price_lists` table
- Visual pricing level selector (Level 1-5):
  - Level 1: $138/LF - Paint grade, open boxes only
  - Level 2: $168/LF - Paint grade, semi-European, flat/shaker doors
  - Level 3: $192/LF - Stain grade, semi-complicated
  - Level 4: $210/LF - Beaded frames, specialty doors, moldings
  - Level 5: $225/LF - Unique custom work, paneling, reeded, rattan
- Material category upgrade selector:
  - Paint Grade: +$138/LF (Hard Maple, Poplar)
  - Stain Grade: +$156/LF (Oak, Maple)
  - Premium: +$185/LF (Rifted White Oak, Black Walnut)
  - Custom/Exotic: TBD
- Real-time pricing preview: `(Level + Material) × LF`
- Alert triggers based on pricing level:
  - Level 4/5 → Alert Levi for production complexity review
  - Delivery challenges → Alert JG for logistics planning
  - Premium materials → Alert purchasing
- Historical pricing comparison
- Recommended drawing hours by level
- Profitability margin calculation

**Technical Requirements:**
- Integration with existing PriceList model
- Real-time pricing calculation
- Alert notification system
- Historical project pricing analysis

**Acceptance Criteria:**
- Pricing calculations match TCS price sheet exactly
- Level selection properly sets project pricing
- Material upgrades add correctly
- Alerts trigger for Level 4/5 projects
- Historical pricing data accurate

---

### 5. ProjectQualificationResource
**Priority:** MEDIUM
**Complexity:** Medium
**Estimate:** 12 hours

**Purpose:** Systematic Go/No-Go decision framework

**Features:**
- Automatic scoring criteria:
  - Project size (LF threshold: minimum 50 LF)
  - Estimated revenue (minimum $15k)
  - Profitability margin (target 35%+)
  - Strategic fit (architect relationship value)
  - Resource capacity (shop availability)
  - Geographic considerations (ferry complexity)
- Weighted scoring algorithm
- Go/No-Go recommendation
- Professional decline templates
- Referral tracking (when declining)
- Decision history and analytics

**Acceptance Criteria:**
- Scoring algorithm matches Bryan's intuition >85% of time
- Recommendations clear and justified
- Decline templates professional and relationship-preserving
- Referral tracking works for network development

---

## Phase 2: Quoting & Proposals (5 Resources)

### 6. QuotationResource (ENHANCE EXISTING)
**Priority:** HIGH
**Complexity:** Medium
**Estimate:** 16 hours

**Current State:** Basic quotation functionality exists (uses existing sales tables)
**Enhancement Scope:** Linear Feet pricing integration
**Database:** Uses existing `sales_orders` and related tables - NO new tables needed

**New Features:**
- Linear Feet + complexity multiplier pricing engine
- Room-by-room cost breakdown
- Automatic quote generation from project data
- Professional proposal package attachment
- Portfolio of similar projects auto-selection
- Quote version control
- Expiration date tracking
- Electronic signature integration
- Auto-convert to Order on acceptance

**Technical Enhancements:**
- Integration with LinearFeetPricingCalculatorResource
- PDF proposal generation
- Email delivery with tracking
- Signature capture workflow

**Acceptance Criteria:**
- Quotes generate in <2 minutes
- LF pricing accurate to manual calculation
- Professional PDF package impresses architects
- Similar project portfolio relevant >90% of time
- Signature workflow seamless

---

### 7. PricingIntegrationService
**Priority:** MEDIUM
**Complexity:** Low
**Estimate:** 8 hours

**Purpose:** Service layer for integrating existing TCS PriceList system with project workflow

**Features:**
- Integration with existing `products_product_price_lists` table
- Pricing calculation service:
  - Retrieve pricing level from PriceList
  - Retrieve material upgrade from PriceList
  - Calculate: `(level_price + material_price) × linear_feet`
- Pricing data storage in project:
  - Store selected pricing_level_id
  - Store selected material_category_id
  - Store calculated total
  - Store pricing_effective_date
- Historical pricing tracking:
  - Track pricing at time of quote
  - Compare to current pricing
  - Material cost variance analysis
- Margin calculation:
  - Actual costs vs quoted price
  - Target margin comparison (35%)
- Pricing tag integration:
  - Link to existing projects_tags system
  - Tag projects by pricing level
  - Tag by material category

**Technical Requirements:**
- Service class for pricing calculations
- Integration with PriceList model
- Project pricing data storage
- Historical comparison queries

**Acceptance Criteria:**
- Calculations use existing PriceList data
- No duplicate pricing tables created
- Pricing stored with project for history
- Margin tracking accurate
- Tag integration seamless

---

### 8. ProposalPackageGeneratorResource
**Priority:** CRITICAL
**Complexity:** High
**Estimate:** 24 hours

**Purpose:** Professional 24-hour response system (architect credibility solution)

**Features:**
- Auto-generated professional PDF package containing:
  1. **Executive Summary** - Project overview, total LF, timeline
  2. **Room-by-Room Breakdown** - Detailed specifications per room
  3. **Pricing Summary** - LF-based pricing with complexity breakdown
  4. **Timeline with Milestones** - Realistic schedule
  5. **Similar Project Portfolio** - 3-5 comparable projects with photos
  6. **Next Steps Document** - Clear action items
  7. **Company Credentials** - TCS capabilities and quality standards
- Template customization for different project types
- Brand-consistent styling
- Interactive timeline visualization
- Photo gallery integration
- Auto-send via email within 24 hours of Thursday meeting

**Technical Requirements:**
- PDF generation library (TCPDF or DomPDF)
- Template engine (Blade templates)
- Photo management system
- Email delivery service
- Version control for proposals

**Acceptance Criteria:**
- Professional PDF generates in <10 seconds
- Architect feedback: "This looks very professional"
- Similar projects relevant >90% of time
- Timeline accurate to production capacity
- Email delivery within 24 hours of approval

---

### 9. ScopeOfWorkResource
**Priority:** HIGH
**Complexity:** Medium
**Estimate:** 16 hours

**Purpose:** Detailed project scope documentation and change order management

**Features:**
- Room-by-room specifications:
  - Cabinet types and quantities
  - Door styles and materials
  - Finish specifications
  - Hardware selections
  - Special features (glass doors, lighting, etc.)
- Material selection tracking
- Hardware specification management
- Special requirements documentation
- Change order workflow:
  - Change request submission
  - Impact analysis (LF, cost, timeline)
  - Approval routing
  - Updated scope documentation
- Version control
- Client approval signatures

**Technical Requirements:**
- Structured data model for specifications
- Change order workflow engine
- Approval routing system
- Document version control

**Acceptance Criteria:**
- All scope details captured systematically
- Change orders track LF and cost impact
- Approval workflow prevents scope creep
- Historical changes auditable

---

### 10. ProfitMarginAnalyzerResource
**Priority:** MEDIUM
**Complexity:** Medium
**Estimate:** 12 hours

**Purpose:** Real-time profitability analysis and margin protection

**Features:**
- Material cost estimation from BOM
- Labor hour projections by Linear Feet
- Overhead allocation (shop, equipment, utilities)
- Target margin comparison (35% target)
- Actual vs estimated tracking during production
- Historical project comparison
- Profitability alerts (margin below threshold)
- What-if scenario analysis
- Cost variance reporting

**Technical Requirements:**
- Real-time cost aggregation
- Labor hour tracking integration
- Overhead allocation algorithm
- Variance analysis engine

**Acceptance Criteria:**
- Margin calculations accurate within 3%
- Real-time updates during project execution
- Alerts trigger when margin at risk
- Historical comparison valuable for future bidding

---

## Phase 3: Orders & Planning (5 Resources)

### 11. OrderResource (ENHANCE EXISTING)
**Priority:** HIGH
**Complexity:** Medium
**Estimate:** 16 hours

**Current State:** Basic order management exists (uses existing sales_orders, sales_order_lines)
**Enhancement Scope:** Quote conversion and LF tracking
**Database:** Uses existing `sales_orders`, `sales_order_lines`, `sales_order_line_items` - NO new tables needed

**New Features:**
- Auto-convert from accepted Quotation
- Linear Feet tracking and validation
- Order stage workflow automation
- Material procurement triggers
- Production schedule integration
- Delivery timeline coordination
- Invoice generation from order
- Client portal for order status

**Technical Enhancements:**
- Quote-to-Order conversion workflow
- Stage transition automation
- Integration with production systems
- Client notification system

**Acceptance Criteria:**
- Quote converts to Order in <30 seconds
- LF data carries through accurately
- Production triggers work automatically
- Client receives order confirmation immediately

---

### 12. ProjectResource (ENHANCE EXISTING)
**Priority:** HIGH
**Complexity:** High
**Estimate:** 24 hours

**Current State:** Project management exists
**Enhancement Scope:** Complete workflow integration

**New Features:**
- Workflow state machine (Inquiry → Quote → Order → Production → Delivery → Complete)
- Linear Feet rollup from all rooms
- Thursday meeting notes integration
- Team assignment automation based on project stage
- Timeline management with dependencies
- Resource capacity integration
- Document management (PDFs, photos, specs)
- Client communication history
- Profit tracking throughout lifecycle

**Technical Enhancements:**
- State machine implementation
- LF aggregation from rooms
- Resource allocation engine
- Timeline dependency management

**Acceptance Criteria:**
- Stage transitions logical and validated
- LF rollup accurate across all rooms
- Resource conflicts detected and flagged
- Timeline realistic and capacity-aware

---

### 13. CADDrawingWorkflowResource
**Priority:** HIGH
**Complexity:** Medium
**Estimate:** 16 hours

**Purpose:** JG/Carlos drawing hours planning and progress tracking

**Features:**
- Drawing hours estimation by room complexity:
  - Simple kitchen: 2-3 hours
  - Complex kitchen with island: 5-8 hours
  - Full house: 20-40 hours
- Assignment to JG or Carlos
- Progress tracking (% complete)
- Drawing approval workflow
- Revision management
- Version control
- Client review and feedback
- Final approval signatures
- Integration with CAD software (DWG/PDF export)

**Technical Requirements:**
- Hours estimation algorithm
- Task assignment system
- Progress tracking
- File version control
- PDF generation from CAD

**Acceptance Criteria:**
- Hours estimates within 20% of actual
- Progress tracking accurate
- Approval workflow prevents rework
- Version control maintains history

---

### 14. BillOfMaterialsResource (ENHANCE EXISTING)
**Priority:** HIGH
**Complexity:** Medium
**Estimate:** 16 hours

**Current State:** Basic BOM functionality exists (uses existing projects_bom)
**Enhancement Scope:** Auto-generation from LF calculations
**Database:** Uses existing `projects_bom` and `inventories_*` tables - NO new tables needed

**Existing Inventory System Integration:**
- `inventories_warehouses` - Warehouse locations (shop location)
- `inventories_locations` - Storage bins/areas within warehouse
- `inventories_product_quantities` - Real-time stock levels per product
- `inventories_moves` - Material movements (linked via `purchase_order_line_id`, `sale_order_line_id`)
- `inventories_operations` - Receiving/delivery operations (linked via `sale_order_id`)
- `inventories_operation_types` - Operation templates (receiving, delivery, internal transfer)

**New Features:**
- Auto-generation from Linear Feet and specifications:
  - Material quantities from LF and door style
  - Hardware counts from cabinet specifications
  - Finish materials from square footage
- Material cost rollup from `products_products` pricing
- Inventory availability check using `inventories_product_quantities`
- Supplier assignment from `partners_partners` (supplier type)
- Procurement workflow triggers (creates `purchases_orders` linked to `inventories_moves`)
- Waste factor calculations
- Alternative material suggestions
- Cost comparison by supplier

**Technical Enhancements:**
- LF-to-material conversion algorithms
- Inventory integration
- Supplier matching logic
- Cost optimization engine

**Acceptance Criteria:**
- BOM generation 90% accurate to manual
- Inventory integration real-time
- Supplier selection optimizes cost
- Waste factors realistic

---

### 15. MaterialProcurementResource
**Priority:** MEDIUM
**Complexity:** Medium
**Estimate:** 12 hours

**Purpose:** Purchasing workflow from BOM to receiving
**Database:** Uses existing `purchases_orders`, `purchases_order_lines`, `inventories_moves` - NO new tables needed

**Existing System Integration:**
- **Purchase Orders:** `purchases_orders`, `purchases_order_lines`
- **Suppliers:** `partners_partners` (with partner type = supplier)
- **Receiving:** `inventories_operations`, `inventories_moves` (linked via `purchase_order_line_id`)
- **Invoices:** `accounts_account_moves`, `accounts_account_move_lines` (vendor bills)
- **Payments:** `accounts_account_payments`, `accounts_payment_registers`
- **Companies:** `companies` table for multi-company support

**Features:**
- Purchase order generation from BOM (creates `purchases_orders`, `purchases_order_lines`)
- Supplier selection with pricing comparison (queries `partners_partners` where type=supplier)
- Delivery schedule coordination
- PO approval workflow ($5k+ requires Bryan approval)
- Order tracking
- Receiving workflow with quality inspection (creates `inventories_operations` + `inventories_moves`)
- Invoice matching (3-way: PO, Receipt, Invoice) using `accounts_account_moves`
- Payment tracking (uses `accounts_account_payments`)
- Supplier performance metrics (analyze `partners_partners` payment history)

**Technical Requirements:**
- PO generation system
- Supplier integration (future API)
- Receiving workflow
- Invoice matching logic

**Acceptance Criteria:**
- PO generation automatic from approved BOM
- Receiving process captures quality issues
- Invoice matching catches discrepancies
- Supplier performance tracked for future decisions

---

## Phase 4: Production & Scheduling (5 Resources)

### 16. ProductionScheduleResource
**Priority:** HIGH
**Complexity:** High
**Estimate:** 20 hours

**Purpose:** Shop capacity planning and project sequencing

**Features:**
- Linear Feet capacity by week (current capacity: ~150 LF/week)
- Project sequencing with dependencies
- Crew assignment by project phase
- Material availability checking before scheduling
- Timeline visualization (Gantt chart)
- Capacity utilization reporting
- Bottleneck identification
- What-if scenario planning
- Resource leveling
- Overtime planning

**Technical Requirements:**
- Capacity planning engine
- Gantt chart visualization
- Resource allocation algorithm
- Constraint satisfaction solver

**Database Schema:**
```sql
-- Use existing projects_production_estimates for capacity planning
-- Use existing projects_milestones for schedule milestones
-- Use existing projects_tasks for detailed scheduling
-- No new tables needed - functionality already exists

-- Enhance existing projects_milestones with production scheduling
ALTER TABLE projects_milestones ADD COLUMN IF NOT EXISTS allocated_lf DECIMAL(10,2);
ALTER TABLE projects_milestones ADD COLUMN IF NOT EXISTS actual_lf_completed DECIMAL(10,2);

-- Enhance existing projects_tasks for production tracking
ALTER TABLE projects_tasks ADD COLUMN IF NOT EXISTS allocated_lf DECIMAL(10,2);
```

**Acceptance Criteria:**
- Capacity calculations accurate to shop reality
- Schedule prevents over-allocation
- Bottlenecks identified proactively
- Timeline visualization clear and actionable

---

### 17. ProjectStageResource (ENHANCE EXISTING)
**Priority:** MEDIUM
**Complexity:** Low
**Estimate:** 8 hours

**Current State:** Stage configuration exists
**Enhancement Scope:** Automated transitions and checklists

**New Features:**
- Automated stage transitions based on completion criteria
- Stage-specific checklist requirements
- Notification triggers on stage change
- Stage-specific views and permissions
- Timeline tracking per stage
- Bottleneck detection

**Acceptance Criteria:**
- Stage transitions automatic when criteria met
- Checklists enforce quality gates
- Notifications keep team informed
- Stage durations tracked for future planning

---

### 18. QualityCheckpointResource
**Priority:** HIGH
**Complexity:** Medium
**Estimate:** 16 hours

**Purpose:** Levi's quality control tracking and issue management

**Features:**
- QC checklist by project phase:
  - Material inspection (receipt)
  - Pre-production setup
  - In-progress checks (doors, drawer boxes, assembly)
  - Pre-finishing inspection
  - Post-finishing quality check
  - Pre-delivery final inspection
- Photo documentation requirements
- Issue tracking with severity levels
- Rework management
- Root cause analysis
- Quality metrics dashboard
- Craftsman accountability

**Technical Requirements:**
- Checklist configuration system
- Photo upload and annotation
- Issue workflow engine
- Metrics aggregation

**Acceptance Criteria:**
- Checklists comprehensive for all phases
- Photo documentation mandatory for key points
- Issues tracked to resolution
- Quality metrics show improvement trends

---

### 19. WorkOrderResource
**Priority:** MEDIUM
**Complexity:** Medium
**Estimate:** 12 hours

**Purpose:** Daily shop floor task management and progress tracking

**Features:**
- Task assignment to specific craftsmen
- Priority sequencing
- Time tracking integration (start/stop)
- Material usage tracking
- Progress percentage updates
- Issue reporting (quality, material, equipment)
- Task completion signoff
- Daily production reporting

**Technical Requirements:**
- Task assignment system
- Time tracking integration
- Material consumption tracking
- Mobile-friendly interface for shop floor

**Acceptance Criteria:**
- Tasks clear and actionable for craftsmen
- Time tracking accurate for job costing
- Material usage data feeds BOM reconciliation
- Progress visibility for Levi and Bryan

---

### 20. TeamAssignmentResource
**Priority:** MEDIUM
**Complexity:** Medium
**Estimate:** 12 hours

**Purpose:** Crew scheduling, skill matching, and workload balancing

**Features:**
- Craftsman availability calendar
- Skill profile management
- Skill matching to project requirements
- Workload balancing across team
- Timesheet integration
- Performance tracking
- Training needs identification
- Vacation and time-off planning

**Technical Requirements:**
- Scheduling algorithm
- Skill matrix database
- Workload calculation engine
- Integration with timesheet system

**Acceptance Criteria:**
- Skills matched to project needs >85% of time
- Workload balanced to prevent burnout
- Performance metrics fair and constructive
- Time-off planning maintains capacity

---

## Phase 5: Delivery & Completion (5 Resources)

### 21. DeliveryScheduleResource
**Priority:** CRITICAL
**Complexity:** Medium
**Estimate:** 16 hours

**Purpose:** JG's delivery logistics planning and coordination (DELIVERY ONLY - NO INSTALLATION)

**Features:**
- Delivery calendar with capacity planning
- Route optimization for multiple deliveries
- Crew assignment (delivery team)
- Truck capacity planning by Linear Feet
- Client coordination and confirmation
- Delivery window scheduling
- Weather contingency planning
- Delivery notes and special instructions
- Photo documentation requirement
- Client signature capture

**Technical Requirements:**
- Calendar scheduling system
- Route optimization algorithm
- Capacity calculation by truck LF
- Mobile app for delivery team
- Signature capture

**Acceptance Criteria:**
- Delivery schedule optimizes routes
- Client confirmations reduce no-shows
- Truck capacity prevents overload
- Signature capture works on mobile
- Photo documentation enforced

---

### 22. SiteAccessPlanningResource
**Priority:** HIGH
**Complexity:** Medium
**Estimate:** 16 hours

**Purpose:** Nantucket ferry logistics and site access planning (UNIQUE to TCS business)

**Features:**
- Ferry schedule integration (Steamship Authority)
- Ferry booking management
- Truck size validation for ferry capacity
- Site access requirements:
  - Driveway width/clearance
  - Stairs/elevator considerations
  - Maximum piece dimensions
  - Access restrictions (time windows, noise, etc.)
- Staging area planning
- Equipment needs (dollies, straps, blankets)
- Weather considerations
- Backup plan documentation
- Island logistics coordination

**Technical Requirements:**
- Ferry schedule API integration (if available)
- Site access questionnaire
- Equipment checklist system
- Weather API integration

**Database Schema:**
```sql
-- site_access_plans table (new)
CREATE TABLE projects_site_access_plans (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT REFERENCES projects_projects(id),
    requires_ferry BOOLEAN DEFAULT false,
    ferry_booking_reference VARCHAR(100),
    ferry_departure_time TIME,
    driveway_width_inches INT,
    has_stairs BOOLEAN,
    has_elevator BOOLEAN,
    maximum_door_width_inches INT,
    staging_area_notes TEXT,
    access_restrictions TEXT,
    special_equipment_needed JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Acceptance Criteria:**
- Ferry bookings integrate with schedule
- Site access issues identified pre-delivery
- Truck/ferry capacity validated
- Equipment needs accurate
- Backup plans documented

---

### 23. LoadPlanningResource
**Priority:** MEDIUM
**Complexity:** Medium
**Estimate:** 12 hours

**Purpose:** Truck loading optimization by Linear Feet and protection requirements

**Features:**
- Linear Feet capacity calculation by truck type
- Load sequence planning (LIFO for multiple deliveries)
- Cabinet protection requirements:
  - Blanket wrapping
  - Corner protectors
  - Strapping/securing
- Weight distribution
- Fragile item identification
- Loading checklist
- Photo documentation before loading
- Pre-delivery inspection

**Technical Requirements:**
- Truck capacity database
- Load sequence algorithm
- Checklist system
- Photo upload

**Acceptance Criteria:**
- Load sequences optimize delivery route
- Protection prevents damage
- Weight distribution safe
- Checklists complete before departure

---

### 24. DeliveryChecklistResource
**Priority:** HIGH
**Complexity:** Low
**Estimate:** 8 hours

**Purpose:** Pre-delivery verification and quality assurance

**Features:**
- Quality final inspection checklist
- Completeness verification (all pieces, hardware, specs)
- Packaging quality check
- Documentation preparation:
  - Delivery receipt
  - Warranty information
  - Care and maintenance guide
  - Installation recommendations (for client's installer)
- Client notification (delivery reminder)
- Delivery team briefing
- Emergency contact information

**Acceptance Criteria:**
- Checklists prevent incomplete deliveries
- Documentation professional and complete
- Client notifications reduce surprises
- Team briefing improves execution

---

### 25. ProjectCompletionResource
**Priority:** MEDIUM
**Complexity:** Medium
**Estimate:** 12 hours

**Purpose:** Final handoff, documentation, and client relationship

**Features:**
- Delivery photo documentation (before/during/after)
- Client sign-off and satisfaction survey
- Warranty documentation generation
- Project archive:
  - All drawings
  - Photos throughout process
  - Material specifications
  - Invoice and payment records
- Lessons learned capture (for team improvement)
- Referral request workflow
- Follow-up scheduling (30/60/90 day check-ins)
- Testimonial request
- Portfolio addition (with client permission)

**Technical Requirements:**
- Photo management system
- Document archive storage
- Survey/feedback collection
- Automated follow-up scheduling

**Acceptance Criteria:**
- All projects have complete photo documentation
- Client satisfaction tracked and analyzed
- Referrals increase from systematic requests
- Lessons learned improve future projects
- Portfolio stays current

---

## Implementation Timeline

### Sprint 1: Critical Path - Professional Intake (Weeks 1-4)
**Goal:** Solve Bryan bottleneck and architect credibility

**Resources:** 5 resources totaling 108 hours
1. ArchitectDrawingIntakeResource (32 hours) - Week 1-2
2. ComplexityScoringResource (16 hours) - Week 2
3. ThursdayMeetingDashboardResource (24 hours) - Week 2-3
4. ProposalPackageGeneratorResource (24 hours) - Week 3
5. LeadInquiryResource (16 hours) - Week 4

**Success Metrics:**
- Thursday meetings cover 2x more projects
- 24-hour professional response achieved >90% of time
- Architect satisfaction improves (survey feedback)
- Zero "missed room" incidents

### Sprint 2: Quote to Order (Weeks 5-8)
**Goal:** Seamless quote-to-production workflow

**Resources:** 5 resources totaling 88 hours
6. LinearFeetPricingCalculatorResource (20 hours) - Week 5
7. ScopeOfWorkResource (16 hours) - Week 5-6
8. Enhance QuotationResource (16 hours) - Week 6
9. Enhance OrderResource (16 hours) - Week 7
10. ProfitMarginAnalyzerResource (12 hours) - Week 7-8

**Success Metrics:**
- Quote approval to production start <48 hours
- Pricing accuracy within 3% of actual
- Scope changes tracked and billed 100%
- Profit margins meet 35% target

### Sprint 3: Production Excellence (Weeks 9-12)
**Goal:** Shop efficiency and quality

**Resources:** 5 resources totaling 80 hours
11. CADDrawingWorkflowResource (16 hours) - Week 9
12. ProductionScheduleResource (20 hours) - Week 9-10
13. Enhance BillOfMaterialsResource (16 hours) - Week 10-11
14. QualityCheckpointResource (16 hours) - Week 11
15. WorkOrderResource (12 hours) - Week 12

**Success Metrics:**
- Zero "surprised by complexity" incidents
- Production schedule accuracy >90%
- Quality issues detected pre-finishing >95%
- Material waste <6% (from 8%)

### Sprint 4: Delivery Excellence (Weeks 13-16)
**Goal:** Flawless delivery logistics

**Resources:** 5 resources totaling 64 hours
16. DeliveryScheduleResource (16 hours) - Week 13
17. SiteAccessPlanningResource (16 hours) - Week 13-14
18. LoadPlanningResource (12 hours) - Week 14-15
19. DeliveryChecklistResource (8 hours) - Week 15
20. ProjectCompletionResource (12 hours) - Week 16

**Success Metrics:**
- Zero delivery damage incidents
- Ferry logistics smooth >95% of time
- Client satisfaction >9/10 on delivery
- Referral requests increase 50%

### Sprint 5: Complete Ecosystem (Weeks 17-20)
**Goal:** Full system integration

**Resources:** 5 resources totaling 88 hours
21. ProjectQualificationResource (12 hours) - Week 17
22. MaterialProcurementResource (12 hours) - Week 17-18
23. TeamAssignmentResource (12 hours) - Week 18-19
24. Enhance ProjectStageResource (8 hours) - Week 19
25. Enhance ProjectResource (24 hours) - Week 19-20

**Success Metrics:**
- Bryan spends 50% less time on project management
- Team empowerment: 80% of decisions made without Bryan
- End-to-end workflow automated >90%
- ROI positive within 6 months

---

## Success Metrics

### Operational Excellence
- **Thursday Meeting Efficiency:** Cover 2x more projects in same time
- **Response Time:** Professional package within 24 hours >90% of time
- **Quality:** Zero critical defects pre-delivery >95% of time
- **Delivery:** Zero damage incidents, >95% on-time

### Financial Performance
- **Profit Margins:** Maintain 35% target across all projects
- **Pricing Accuracy:** Within 3% of estimated costs
- **Material Waste:** Reduce from 8% to <6%
- **Revenue Growth:** 20% increase from improved professional credibility

### Team Effectiveness
- **Bryan's Time:** 50% reduction in project management overhead
- **Decision Empowerment:** 80% of decisions made without Bryan
- **Craftsman Utilization:** 90% productive time (from 75%)
- **Client Satisfaction:** >9/10 on delivery experience

### Professional Credibility
- **Architect Retention:** 100% repeat business from satisfied architects
- **Referrals:** 50% increase from systematic requesting
- **Competitive Wins:** Win rate increase 25% against larger shops
- **Brand Reputation:** "Professional and systematic" feedback

---

## Technical Architecture

### Technology Stack
- **Framework:** Laravel 11.x with PHP 8.2+
- **Admin Panel:** FilamentPHP v4.x
- **Database:** MySQL 8.0+
- **Frontend:** Livewire 3.x, Alpine.js 3.x, Tailwind CSS
- **PDF Processing:** Nutrient PSPDFKit or PDFTron
- **AI Services:** OpenAI GPT-4 Vision or AWS Textract
- **File Storage:** AWS S3 or local NAS
- **Email:** Laravel Mail with SendGrid/SES
- **Scheduling:** Laravel Queue with Redis

### Database Design Principles
- Extend existing AureusERP tables where possible
- Maintain compatibility with plugin architecture
- Linear Feet as core metric across all tables
- Audit trails on all financial transactions
- Soft deletes for data preservation

### Performance Requirements
- Page load <2 seconds for all views
- PDF generation <10 seconds for complex proposals
- AI extraction <30 seconds per architectural plan
- Real-time calculation <1 second for pricing changes
- Dashboard refresh <5 seconds

### Security Requirements
- Role-based permissions (FilamentShield)
- API authentication for integrations
- Data encryption for financial information
- Audit logging for all changes
- Backup and disaster recovery

---

## Risk Management

### Technical Risks
**Risk:** AI extraction accuracy below 80%
**Mitigation:** Manual verification interface, learning from corrections
**Impact:** Medium - Increases manual work but still faster than pure manual

**Risk:** PDF viewer performance with large files
**Mitigation:** Page-by-page loading, cloud processing for heavy files
**Impact:** Low - Affects user experience but not core functionality

**Risk:** Linear Feet calculation complexity
**Mitigation:** Extensive testing against historical projects, manual override capability
**Impact:** High - Core business logic must be accurate

### Business Risks
**Risk:** Team resistance to systematic processes
**Mitigation:** Gradual rollout, demonstrate time savings, involve team in design
**Impact:** High - Adoption critical to success

**Risk:** Architect adoption of professional packages
**Mitigation:** A/B test formats, collect feedback, iterate quickly
**Impact:** Medium - Goal is credibility, can refine approach

**Risk:** Ferry/delivery logistics complexity
**Mitigation:** Build flexibility, maintain manual override, document exceptions
**Impact:** Medium - Nantucket logistics inherently challenging

### Organizational Risks
**Risk:** Bryan still being pulled into details
**Mitigation:** Enforce discipline, empower team decisions, measure time savings
**Impact:** High - Defeats purpose if Bryan remains bottleneck

**Risk:** Mark's mobile adoption from Nantucket
**Mitigation:** Simple interface, offline capability, minimal data entry
**Impact:** Medium - Critical for remote sales effectiveness

---

## Success Definition

The system succeeds when:

1. **Bryan's time shifts from tactical to strategic** (50% reduction in project management)
2. **Architects view TCS as professional and systematic** (testimonial feedback)
3. **Thursday meetings are productive and efficient** (2x throughput)
4. **Zero critical details are missed** (room-by-room systematic review)
5. **Delivery is flawless** (zero damage, proper logistics)
6. **Team is empowered** (80% decisions without Bryan)
7. **Linear Feet is the universal language** (used across all stages)
8. **Profitability is protected** (35% margins maintained)

---

## Next Steps

1. **Parse this PRD into Task Master** - Break into trackable subtasks
2. **Design database schema changes** - Plan migrations
3. **Create wireframes for critical resources** - Visual design validation
4. **Prototype Sprint 1 resources** - Prove the concept
5. **User testing with team** - Gather feedback early
6. **Iterate and refine** - Continuous improvement

**Project Start Date:** Immediate
**First Delivery:** Sprint 1 (4 weeks) - Professional Intake System
**Full System Completion:** 20 weeks
**ROI Expected:** 6 months post-completion

---

**Document Owner:** TCS Woodwork Technology Team
**Reviewed By:** Bryan Patton (Owner), Mark O'Connell (Sales), Levi (Production Lead), JG (Delivery Lead)
**Approval Date:** [Pending]
**Next Review:** Weekly sprint retrospectives
