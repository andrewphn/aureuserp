# User Story: Project Gantt Chart & Kanban Board Module

## Overview
As a project manager at TCS Woodwork, I need visual tools to see all projects at a glance and manage their progress through our production workflow.

---

## User Stories

### US-1: View Projects on Kanban Board
**As a** project manager
**I want to** see all projects organized by production stage on a Kanban board
**So that** I can quickly understand which projects are in which phase and identify bottlenecks

**Acceptance Criteria:**
- [x] Kanban board displays columns for each production stage (Discovery, Design, Sourcing, Production, Delivery)
- [x] Each project appears as a card in its current stage column
- [x] Cards display: project name, customer name, days remaining, milestone progress
- [x] Cards show visual indicators for overdue projects (red text)
- [x] Empty columns display properly without breaking layout

---

### US-11: Filter Kanban Board by Customer and Date
**As a** project manager
**I want to** filter the Kanban board by customer, date range, and overdue status
**So that** I can focus on specific subsets of projects

**Acceptance Criteria:**
- [x] Customer dropdown filters projects by selected customer
- [x] "All Customers" option shows all projects
- [x] Date range inputs filter by desired completion date
- [x] "Overdue only" checkbox shows only overdue projects
- [x] Clear filters button resets all filters
- [x] Project count updates dynamically when filters change

---

### US-12: View Tasks on Kanban Cards
**As a** project manager
**I want to** see project tasks directly on Kanban cards with expandable details
**So that** I can quickly understand task progress without leaving the board

**Acceptance Criteria:**
- [x] Cards show task count with expandable toggle
- [x] Expanded section shows up to 5 top-level tasks
- [x] Tasks display state indicators (checkmark for done, pulsing dot for in_progress, circle for pending)
- [x] Done tasks show strikethrough text
- [x] In-progress tasks show "IN PROGRESS" badge
- [x] Alpine.js collapse animation for smooth expand/collapse

---

### US-13: Enhanced Kanban Card Design
**As a** project manager
**I want to** see rich project information on Kanban cards at a glance
**So that** I can make quick decisions without clicking into each project

**Acceptance Criteria:**
- [x] Stage badge displayed at top of card with stage color
- [x] OVERDUE badge shown for overdue projects
- [x] Customer name with building icon
- [x] Days remaining or days late displayed
- [x] Linear feet displayed with icon
- [x] Progress bar showing milestone completion (X/Y format)
- [x] Left border highlight in red for overdue projects

---

### US-14: WIP Limits on Kanban Columns
**As a** shop manager
**I want to** see work-in-progress limits on each production stage
**So that** I can identify capacity issues and prevent overloading

**Acceptance Criteria:**
- [x] WIP limit configurable per stage in database (wip_limit column)
- [x] Column header shows count/limit format (e.g., "5/4")
- [x] Visual warning (red ring) when stage exceeds WIP limit
- [x] Warning icon appears next to count when over limit
- [x] Default WIP limits set for Production (4) and Sourcing (3) stages

---

### US-15: Project Value Display on Cards
**As a** business owner
**I want to** see the monetary value of each project on Kanban cards
**So that** I can prioritize high-value projects and track revenue visually

**Acceptance Criteria:**
- [x] Project value displayed with dollar icon
- [x] Value pulled from linked sales order (amount_total)
- [x] Value formatted with commas (no decimals)
- [x] Green color to highlight financial information

---

### US-16: Priority Indicator on Cards
**As a** project manager
**I want to** see project priority indicators on Kanban cards
**So that** I can quickly identify high-priority projects needing attention

**Acceptance Criteria:**
- [x] Priority badge shown based on complexity score
- [x] HIGH priority (score >= 8) shown with fire icon in orange
- [x] MEDIUM priority (score 5-7) shown in yellow
- [x] LOW priority not displayed (cleaner UI)

---

### US-17: Blocked Project Indicator
**As a** production coordinator
**I want to** see which projects are blocked by stage gate requirements
**So that** I can identify and resolve blockers quickly

**Acceptance Criteria:**
- [x] BLOCKED badge shown when project has unmet stage gate requirements
- [x] Purple styling to distinguish from overdue (red)
- [x] Hover tooltip shows list of blockers
- [x] Blockers pulled from getStageGateStatus() method

---

### US-18: Today Marker on Gantt Chart
**As a** project manager
**I want to** see a clear "today" indicator on the Gantt timeline
**So that** I can quickly understand where we are relative to project schedules

**Acceptance Criteria:**
- [x] Today column highlighted with blue tint
- [x] Dashed vertical line marking today's date
- [x] "Today" button to scroll timeline to current date
- [x] Today marker visible in all view modes

---

### US-2: Move Projects Between Stages via Drag-Drop
**As a** project manager
**I want to** drag a project card from one stage column to another
**So that** I can quickly update a project's production stage without opening the edit form

**Acceptance Criteria:**
- [x] Can drag a project card from one column to another
- [x] Project's stage_id updates in the database after drop
- [x] Success notification appears confirming the move
- [x] Card appears in the new column after drop
- [x] Sort order is preserved within columns

---

### US-3: Navigate to Project from Kanban Card
**As a** project manager
**I want to** click on a project card to open that project's detail page
**So that** I can quickly access full project information from the Kanban view

**Acceptance Criteria:**
- [x] Clicking a project card navigates to the project view page
- [x] Navigation works correctly with the project ID

---

### US-4: View Projects on Gantt Timeline
**As a** project manager
**I want to** see all projects displayed on a timeline Gantt chart
**So that** I can visualize project schedules, identify overlaps, and plan resources

**Acceptance Criteria:**
- [x] Gantt chart displays projects as horizontal bars
- [x] Bar position reflects project start_date and desired_completion_date
- [x] Progress bar shows milestone completion percentage
- [x] Projects without dates are excluded from the chart
- [x] Empty state message appears when no projects have dates

---

### US-5: Filter Gantt Chart by Stage and Date Range
**As a** project manager
**I want to** filter the Gantt chart by production stage and date range
**So that** I can focus on specific subsets of projects

**Acceptance Criteria:**
- [x] Stage dropdown filters projects by selected stage
- [x] "All Stages" option shows all projects
- [x] Date range inputs filter projects within the selected period
- [x] Chart updates automatically when filters change

---

### US-6: Change Gantt View Mode
**As a** project manager
**I want to** switch between different time scales (Day, Week, Month, Quarter, Year)
**So that** I can zoom in/out on the timeline as needed

**Acceptance Criteria:**
- [x] View mode buttons appear above the chart
- [x] Clicking a view mode changes the timeline scale
- [x] Current view mode is visually highlighted
- [x] Chart re-renders with new scale

---

### US-7: Update Project Dates via Gantt Drag
**As a** project manager
**I want to** drag a project bar on the Gantt chart to change its dates
**So that** I can quickly reschedule projects visually

**Acceptance Criteria:**
- [x] Can drag a project bar left/right to change dates
- [x] Can drag bar edges to extend/shorten duration
- [x] Database updates with new start_date and desired_completion_date
- [x] Success notification confirms the update

---

### US-8: View Project Dependencies on Gantt
**As a** project manager
**I want to** see dependency lines between related projects
**So that** I can understand which projects must complete before others can start

**Acceptance Criteria:**
- [x] Dependency lines connect dependent projects
- [x] Lines are drawn from dependency end to dependent start
- [x] Dependencies are stored in projects_project_dependencies table

---

### US-9: Navigate to Project from Gantt Chart
**As a** project manager
**I want to** click on a project bar to open that project's detail page
**So that** I can quickly access full project information from the Gantt view

**Acceptance Criteria:**
- [x] Clicking a project bar shows a popup with project details
- [x] Popup includes link/action to navigate to project
- [x] Clicking navigates to the project view page

---

### US-10: View Stage Color Legend
**As a** project manager
**I want to** see a color legend explaining which colors represent which stages
**So that** I can quickly identify project stages by color

**Acceptance Criteria:**
- [x] Legend appears below the Gantt chart
- [x] Each production stage has a distinct color (from database)
- [x] Colors match the bar colors in the chart

---

## Implementation Status: COMPLETE (Enhanced Dec 2025)

### Files Created
| File | Purpose |
|------|---------|
| `plugins/webkul/projects/database/migrations/2025_12_20_000001_create_projects_project_dependencies_table.php` | Project dependencies pivot table |
| `plugins/webkul/projects/src/Filament/Pages/ProjectKanban.php` | Kanban board Filament page |
| `plugins/webkul/projects/src/Filament/Pages/GanttChart.php` | Gantt chart Filament page |
| `plugins/webkul/projects/src/Livewire/ProjectGanttChart.php` | Gantt Livewire component |
| `plugins/webkul/projects/src/Livewire/ProjectKanbanBoard.php` | Kanban Livewire component |
| `plugins/webkul/projects/resources/views/livewire/project-gantt-chart.blade.php` | Gantt template |
| `plugins/webkul/projects/resources/views/livewire/project-kanban-board.blade.php` | Kanban template |
| `plugins/webkul/projects/resources/views/filament/pages/gantt-chart.blade.php` | Page wrapper |
| `plugins/webkul/projects/resources/views/filament/pages/project-kanban.blade.php` | Page wrapper |

### Files Modified
| File | Changes |
|------|---------|
| `plugins/webkul/projects/src/Models/Project.php` | Added `dependsOn()` and `dependents()` relationships |
| `plugins/webkul/projects/src/ProjectServiceProvider.php` | Registered Livewire components, added migration |

### Technical Notes

#### Navigation
- Kanban Board: `/admin/project/kanban`
- Gantt Chart: `/admin/project/gantt`

#### Database
- New table: `projects_project_dependencies` (project_id, depends_on_id, dependency_type, lag_days)
- New relationships on Project model: `dependsOn()`, `dependents()`

#### Key Features
- **Dynamic colors**: Stage colors loaded from database, not hardcoded
- **Dynamic legend**: Stage names and colors from `projects_project_stages` table
- **Frappe Gantt**: Uses UMD build via CDN for timeline visualization
- **Custom Kanban**: Built with Livewire + Alpine.js (not using filament-kanban package due to compatibility issues)

#### Kanban Enhancements (Dec 2025)
- **Filters**: Customer dropdown, date range, overdue toggle with reactive updates
- **Enhanced Cards**: Stage badges, OVERDUE indicators, progress bars, linear feet
- **Expandable Tasks**: Alpine.js x-collapse for task list expansion
- **Task States**: Visual indicators (checkmark, pulsing dot, circle) for done/in_progress/pending
- **Eager Loading**: Optimized queries with task constraints (top-level, limit 5)

#### Kanban MVP Features (Dec 2025 - Phase 2)
- **WIP Limits**: Capacity warnings per stage with visual indicators (red ring, warning icon)
- **Project Value**: Dollar amount from linked sales orders displayed on cards
- **Priority Badges**: HIGH/MED indicators based on complexity_score field
- **Blocked Indicator**: BLOCKED badge when stage gate requirements unmet
- **Stage Gate Integration**: Blockers pulled from Project::getStageGateStatus()

#### Gantt MVP Features (Dec 2025 - Phase 2)
- **Today Button**: Quick scroll to current date on timeline
- **Enhanced Today Marker**: Blue tint highlight with dashed vertical line
- **Visible Today Indicator**: Custom SVG line element for better visibility

#### Database Changes (Dec 2025 - Phase 2)
- Migration: `2025_12_21_000001_add_wip_limit_to_projects_project_stages_table.php`
- New column: `projects_project_stages.wip_limit` (unsigned integer, nullable)
- Default limits: Production=4, Sourcing=3

#### Dependencies
- frappe-gantt (CDN: https://cdn.jsdelivr.net/npm/frappe-gantt@1.0.0)
- Alpine.js collapse plugin (bundled with Filament)
