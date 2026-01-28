# Gantt Chart User Guide

## Quick Start

The Project Gantt Chart provides a visual timeline of all your projects, making it easy to see schedules, dependencies, and progress at a glance.

**Access:** Navigate to `/admin/project/gantt` or click "Gantt Chart" in the Projects menu.

---

## Interface Overview

### Controls Bar (Top)

#### View Mode Buttons
Switch between different time scales:
- **Day** - Hourly detail (use `1` key)
- **Week** - Daily detail (use `2` key)
- **Month** - Weekly detail (use `3` key) ⭐ Default
- **Quarter** - Monthly detail (use `4` key)
- **Year** - Quarterly detail (use `5` key)

#### Today Button
- Click to jump to current date
- Keyboard shortcut: `T`
- Look for the pulsing blue dot marker

#### Export Button
- Download chart as SVG (vector) or PNG (image)
- Keyboard shortcut: `E`
- Perfect for presentations and reports

#### Print Button
- Print-optimized layout (landscape)
- Keyboard shortcut: `P`
- Includes project list and legend

#### Help Button
- Shows all keyboard shortcuts
- Keyboard shortcut: `?`
- Click outside to close

#### Stage Filter
- Show only projects in specific stage
- Select "All Stages" to see everything

#### Date Range
- Filter projects by start/end dates
- Adjust to focus on specific timeframe

### Project Sidebar (Left)

Shows key metrics for each project:
- **Project Name** - Click to view details
- **Customer** - Client/partner name
- **Value** - Order total (in thousands)
- **LF** - Linear feet estimate
- **Days** - Days until completion
  - Green: 7+ days remaining
  - Orange: < 7 days remaining
  - Red: Overdue (negative days)
- **Progress Bar** - Completion based on milestones
  - Shows completed/total milestones
  - Color matches project stage

### Timeline (Right)

Interactive Gantt chart:
- **Project Bars** - Duration and progress
  - Color indicates project stage
  - Length shows timeline
  - Fill shows completion percentage
- **Diamond Markers** - Project milestones
  - Filled: Completed
  - Hollow: Pending
- **Arrows** - Project dependencies
- **Today Line** - Vertical blue line with pulse animation

### Stage Legend (Bottom)

Shows color coding for all project stages:
- Each stage has a unique color
- Helps identify project status at a glance

---

## How to Use

### Navigate the Timeline

#### Using Mouse
- **Scroll horizontally** to move through time
- **Click and drag** timeline background to pan
- **Click project bar** to open project details
- **Hover over bar** to see project popup

#### Using Keyboard
- **←** - Scroll left (earlier dates)
- **→** - Scroll right (later dates)
- **T** - Jump to today
- **1-5** - Switch view modes

### Reschedule Projects

1. **Click and drag** a project bar
2. **Move left or right** to change dates
3. **Release** to save new dates
4. ✅ Notification confirms update
5. Database automatically updated

**Note:** Milestones (diamond markers) cannot be dragged from Gantt view. Edit milestone dates from project detail page.

### Filter Projects

#### By Stage
1. Click **Stage** dropdown
2. Select desired stage
3. Chart updates instantly

#### By Date Range
1. Set **Start Date** (left input)
2. Set **End Date** (right input)
3. Chart filters to range
4. Projects must overlap range to appear

**Tip:** Clear filters by selecting "All Stages" and adjusting date range to show more projects.

### Export Charts

#### SVG Export (Recommended)
1. Click **Export** button (or press `E`)
2. Select **SVG (Vector)**
3. File downloads automatically
4. **Best for:** Presentations, print materials, infinite scaling
5. **File size:** Small (~50-200KB)

#### PNG Export
1. Click **Export** button (or press `E`)
2. Select **PNG (Image)**
3. Wait for rendering (may take a few seconds)
4. File downloads automatically
5. **Best for:** Email attachments, quick sharing, compatibility
6. **File size:** Medium (~500KB-2MB)

**Export Filenames:** Auto-generated with current date (e.g., `gantt-chart-2026-01-28.svg`)

### Print Charts

1. Click **Print** button (or press `P`)
2. Print preview opens (landscape mode)
3. **Page 1:** Project list with metrics
4. **Page 2+:** Full timeline
5. Verify layout and click **Print**

**Tips:**
- Use landscape orientation (automatic)
- Print in color to preserve stage coding
- Consider print to PDF for digital archival

### View Project Details

**Method 1: Click Bar**
- Click any project bar on timeline
- Redirects to project detail page

**Method 2: Click Sidebar**
- Click project name in sidebar
- Opens project detail page

**Popup Preview:**
- Shows project name, customer, stage
- Displays linear feet, dates, progress
- Click "View project →" to open details

---

## Keyboard Shortcuts Reference

### Navigation
| Key | Action |
|-----|--------|
| `T` | Jump to today |
| `←` | Scroll left |
| `→` | Scroll right |

### View Modes
| Key | View Mode |
|-----|-----------|
| `1` | Day |
| `2` | Week |
| `3` | Month |
| `4` | Quarter |
| `5` | Year |

### Actions
| Key | Action |
|-----|--------|
| `E` | Export chart |
| `P` | Print chart |
| `?` | Show help |

**Note:** Shortcuts don't work when typing in filter inputs.

---

## Tips & Best Practices

### For Best Performance
- Filter by stage or date range for large project counts
- Use Month view (default) for overview
- Use Week/Day views for detailed scheduling

### For Professional Exports
- Use **SVG format** for presentations and reports
- Include stage legend in export frame
- Adjust view mode before exporting
- Filter to relevant projects only

### For Project Planning
- Set start and completion dates on all projects
- Create milestones for key project phases
- Use dependencies to show project relationships
- Update progress by completing milestones

### For Team Collaboration
- Export PNG for quick Slack/email sharing
- Print landscape for wall displays
- Use stage filters for team-specific views
- Share direct link: `/admin/project/gantt`

---

## Troubleshooting

### "No projects to display"
**Cause:** No projects have start and end dates set.
**Solution:** Edit projects to add `start_date` and `desired_completion_date` or `end_date`.

### Export button does nothing
**Cause:** No projects visible on timeline.
**Solution:** Adjust filters to show projects, then try export again.

### PNG export takes too long
**Cause:** Very large dataset (500+ projects).
**Solution:** Use SVG export instead, or filter to fewer projects.

### Print layout is wrong
**Cause:** Browser not using landscape mode.
**Solution:** In print dialog, select "Landscape" orientation manually.

### Keyboard shortcuts don't work
**Cause:** Typing in filter input field.
**Solution:** Click outside input fields first, then use shortcuts.

### Projects not updating after drag
**Cause:** Insufficient permissions.
**Solution:** Contact administrator for project edit permissions.

---

## Frequently Asked Questions

### Can I edit milestones from the Gantt chart?
No, milestone dates must be edited from the project detail page. The Gantt chart shows milestones but doesn't allow direct editing.

### How is progress calculated?
Progress is based on completed milestones. If a project has 4 milestones and 2 are complete, progress is 50%.

### Can I create dependencies visually?
Not yet. Dependencies must be set in project settings. Visual dependency creation is planned for Phase 2.

### Does the chart update in real-time?
The chart refreshes when you change filters or update project dates. For live collaboration, refresh the page to see others' changes.

### Can I export multiple views at once?
Not yet. You must export each view (Day, Week, Month, etc.) individually. Batch export is planned for a future update.

### How many projects can the chart handle?
- **Optimal:** < 200 projects
- **Good:** 200-500 projects
- **Slow:** 500+ projects (consider filtering)

---

## Advanced Features

### Understanding Colors
Each project stage has a unique color (configurable in stage settings):
- **Bar outline:** Stage color at full intensity
- **Bar fill:** Stage color at 20% opacity
- **Progress fill:** Stage color at full intensity

### Project Dependencies
Projects with dependencies show arrows between bars:
- Arrow points from prerequisite to dependent project
- Hover over arrow to highlight connection
- Dependencies are informational (no automatic scheduling yet)

### Milestone Types
- **Standard Milestone:** Hollow diamond when pending, filled when complete
- **Critical Milestone:** Red diamonds (planned for Phase 2)

### Days Remaining Logic
- **Positive number (green/orange):** Days until completion date
- **Negative number (red):** Days overdue
- **No number:** No completion date set

---

## Getting Help

### Resources
- **User Guide:** This document
- **Keyboard Shortcuts:** Press `?` on Gantt chart page
- **Video Tutorial:** [Coming soon]
- **Support:** Contact your system administrator

### Feedback
We're always improving! Share your feedback:
- What features would you like to see?
- What's confusing or difficult to use?
- How can we make the Gantt chart better?

### Upcoming Features (Roadmap)
**Phase 2:**
- Critical path highlighting
- Resource view (group by employee/team)
- Risk indicators (low progress near deadline)

**Phase 3:**
- Baseline comparison (planned vs actual)
- Visual dependency creation
- Resource loading chart
- Saved filter presets
- Smart notifications for overdue projects

---

**Last Updated:** 2026-01-28
**Version:** 1.0.0
**Questions?** Contact your TCS system administrator
