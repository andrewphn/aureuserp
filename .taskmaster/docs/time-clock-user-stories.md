# Time Clock System - User Stories

## Overview

Simple time clock system for TCS Newburgh Shop (Mon-Thu 8am-5pm) allowing employees to:
- Clock in/out
- Record lunch duration
- Track time to projects/tasks
- Export reports

## What Already Exists

**Database:**
- `analytic_records` table with: id, type, name, date, unit_amount (hours), user_id, project_id, task_id
- `employees_work_locations` table with GPS coordinates
- `employees_calendars` and `employees_calendar_attendances` for schedules
- TCS Newburgh Shop calendar (Mon-Thu 8am-5pm) already created

**Models:**
- Timesheet model extends Record, auto-updates task hours
- WorkLocation model with location_type enum
- Employee model linked to calendar and work_location

**Filament UI:**
- TimesheetResource with form (date, employee, project, task, description, hours)
- Grouping by date/employee/project/task
- Date range filtering

**What's MISSING (to build):**
- Clock in/out timestamps (clock_in_time, clock_out_time columns)
- Break duration tracking (break_duration_minutes column)
- Entry type tracking (clock vs manual entry)
- Kiosk interface for shop floor
- PIN-based employee login
- Dashboard widgets for owner
- AI assistant integration for time clock

---

## Epic 1: Basic Clock In/Out (All Employees)

### US-1.1: Simple Clock In
**As** Ben (Apprentice) or Maria (Journey-Level Woodworker)
**I want to** clock in when I arrive at the shop
**So that** my work hours are tracked accurately

**Acceptance Criteria:**
- [ ] Single-tap "Clock In" button on shop kiosk or mobile
- [ ] Shows current time and confirms clock-in
- [ ] Records: employee_id, clock_in_time, date, work_location
- [ ] Prevents double clock-in (already clocked in today)
- [ ] Works offline and syncs when connection restored

**Ben's Perspective:** "I just want to tap one button when I get here. Don't make me think about it."

---

### US-1.2: Simple Clock Out
**As** Ben (Apprentice) or Maria (Journey-Level Woodworker)
**I want to** clock out when I leave the shop
**So that** my total hours are calculated correctly

**Acceptance Criteria:**
- [ ] Single-tap "Clock Out" button
- [ ] Prompts for lunch duration (pre-filled with 60 min default)
- [ ] Shows summary: Clock in time, Clock out time, Lunch, Total hours
- [ ] Calculates: `total_hours = (clock_out - clock_in) - lunch_duration`
- [ ] Confirms and saves timesheet entry

**Maria's Perspective:** "I want to see my hours for the day before I confirm. Makes sure nothing's wrong."

---

### US-1.3: Enter Lunch Duration
**As** any shop employee
**I want to** enter how long my lunch break was
**So that** my working hours are accurate (not including unpaid lunch)

**Acceptance Criteria:**
- [ ] Quick-select buttons: 30min, 45min, 60min (default), 90min
- [ ] Custom entry option (numeric input)
- [ ] Default to 60 minutes (TCS standard)
- [ ] Lunch duration stored in `break_duration_minutes`
- [ ] Working hours = Total time - Lunch time

**Ben's Perspective:** "Sometimes I take a shorter lunch to leave early. Need to track that."

---

### US-1.4: View Today's Status
**As** any shop employee
**I want to** see my current clock status
**So that** I know if I'm clocked in and how long I've been working

**Acceptance Criteria:**
- [ ] Shows: "Clocked In at 8:02 AM" or "Not Clocked In"
- [ ] Running timer showing current hours (if clocked in)
- [ ] Quick view of week's hours so far
- [ ] Visible on shop kiosk home screen

---

## Epic 2: Project/Task Time Tracking

### US-2.1: Assign Time to Project
**As** Maria (Skilled Woodworker)
**I want to** record what project I worked on today
**So that** job costing is accurate

**Acceptance Criteria:**
- [ ] At clock-out, option to assign hours to project(s)
- [ ] Searchable project list (by project number or client name)
- [ ] Can split time across multiple projects
- [ ] Default: all hours to single selected project
- [ ] Creates `analytic_records` entries with project_id

**Maria's Perspective:** "Most days I work on one project. But sometimes I split between two."

---

### US-2.2: Assign Time to Task (Optional)
**As** Maria (Skilled Woodworker)
**I want to** optionally record the specific task I worked on
**So that** we can track time spent on different phases

**Acceptance Criteria:**
- [ ] After selecting project, optionally select task
- [ ] Task list filtered by selected project
- [ ] Can leave blank (general project time)
- [ ] Common task presets: "Cutting", "Assembly", "Finishing", "Cleanup"

---

### US-2.3: Quick Time Entry (No Clock In/Out)
**As** any employee
**I want to** manually enter time for a past day
**So that** I can fix missed clock-ins or record off-site work

**Acceptance Criteria:**
- [ ] Select date, enter hours, select project
- [ ] Requires supervisor approval if > 24 hours ago
- [ ] Flagged as "manual entry" for audit
- [ ] Cannot exceed shop open hours (8am-5pm) without note

---

## Epic 3: Owner Reports & Dashboard

### US-3.1: Daily Hours Summary
**As** Bryan (Owner)
**I want to** see today's attendance at a glance
**So that** I know who's in the shop without walking around

**Acceptance Criteria:**
- [ ] Dashboard widget showing: Who's clocked in, When they arrived
- [ ] Visual indicator for late arrivals (after 8:15am)
- [ ] Shows who's NOT clocked in yet
- [ ] Auto-refreshes every 5 minutes
- [ ] ADHD-friendly: Clean, visual, no clutter

**Bryan's Perspective:** "I need to see at a glance who's here. Don't make me dig for it."

---

### US-3.2: Weekly Hours Report
**As** Bryan (Owner)
**I want to** see total hours worked per employee per week
**So that** I can verify payroll and identify overtime

**Acceptance Criteria:**
- [ ] Table: Employee | Mon | Tue | Wed | Thu | Total | Overtime
- [ ] Highlight overtime (>8 hrs/day or >32 hrs/week)
- [ ] Filterable by date range
- [ ] Exportable to CSV/Excel for payroll
- [ ] Shows missing punches (clocked in but never out)

---

### US-3.3: Project Hours Report
**As** Bryan (Owner)
**I want to** see total hours spent on each project
**So that** I can track labor costs and project profitability

**Acceptance Criteria:**
- [ ] Report by project showing: Total hours, By employee, By task
- [ ] Compare actual vs. estimated hours
- [ ] Date range filter
- [ ] Drill-down to individual time entries
- [ ] Exportable for job costing analysis

**Bryan's Perspective:** "I need to know if we're over budget on labor BEFORE the project ends."

---

### US-3.4: Export Hours for Payroll
**As** Bryan (Owner)
**I want to** export timesheet data for payroll processing
**So that** I can pay employees accurately

**Acceptance Criteria:**
- [ ] Export formats: CSV, Excel, PDF
- [ ] Select date range (default: current pay period)
- [ ] Includes: Employee name, Date, Clock in/out, Lunch, Total hours
- [ ] Separate regular and overtime hours
- [ ] Compatible with common payroll systems

---

## Epic 4: Apprentice-Specific Features

### US-4.1: Training Time Tracking
**As** Ben (Apprentice)
**I want to** log time spent on training vs. production
**So that** my learning progress can be tracked

**Acceptance Criteria:**
- [ ] Option to mark time as "Training" or "Production"
- [ ] Training categories: Safety, Tool Use, Technique, Observation
- [ ] Mentor can view apprentice's training log
- [ ] Reports show training vs. production ratio over time

**Ben's Perspective:** "Want to show I'm learning, not just sweeping floors."

---

### US-4.2: Mentor Check-In
**As** Ben (Apprentice)
**I want to** record daily check-ins with my mentor
**So that** feedback is documented

**Acceptance Criteria:**
- [ ] End-of-day prompt: "Did you check in with Miguel today?"
- [ ] Quick note field for learnings or questions
- [ ] Visible to mentor and owner in reports
- [ ] Optional - can skip if not applicable

---

## Epic 5: Error Handling & Edge Cases

### US-5.1: Forgot to Clock Out
**As** any employee
**I want to** be notified if I forgot to clock out
**So that** my hours are still recorded correctly

**Acceptance Criteria:**
- [ ] Auto-notification at 5:30pm if still clocked in
- [ ] Next-day prompt to correct previous day's time
- [ ] Supervisor notified of missed punches
- [ ] Option to manually enter clock-out time (with approval)

---

### US-5.2: Forgot to Clock In
**As** any employee
**I want to** add a missed clock-in
**So that** my hours aren't lost

**Acceptance Criteria:**
- [ ] "Add Missing Punch" option on kiosk
- [ ] Enter estimated arrival time
- [ ] Requires supervisor approval
- [ ] Flagged in reports as "manual entry"

---

### US-5.3: Early/Late Arrival Tracking
**As** Bryan (Owner)
**I want to** see patterns in early/late arrivals
**So that** I can address attendance issues

**Acceptance Criteria:**
- [ ] Define "on-time" window (e.g., 7:45am - 8:15am)
- [ ] Flag arrivals outside window
- [ ] Weekly report showing punctuality by employee
- [ ] Don't auto-penalize - just visibility for conversation

---

## Epic 6: Shop Kiosk Interface

### US-6.1: Touch-Friendly Kiosk UI
**As** any shop employee
**I want to** use a simple touch screen to clock in/out
**So that** I don't need to type or use a mouse

**Acceptance Criteria:**
- [ ] Large buttons (minimum 60px touch targets)
- [ ] PIN-based employee login (4-digit)
- [ ] Clock in/out in 3 taps or less
- [ ] Works with gloves on (large touch areas)
- [ ] High contrast for visibility in shop lighting

**Ben's Perspective:** "My hands are dirty. I need big buttons I can tap fast."

---

### US-6.2: Employee Selection
**As** any shop employee
**I want to** quickly select myself from a list
**So that** I don't have to type my name

**Acceptance Criteria:**
- [ ] Photo tiles of all employees (face recognition optional)
- [ ] Or: Enter 4-digit PIN
- [ ] Or: Scan badge (if hardware available)
- [ ] Remember last employee for 30 seconds (quick re-entry)

---

## Data Model Updates Required

### New/Modified Tables

```
analytic_records (existing - add columns):
  + clock_in_time (TIME)
  + clock_out_time (TIME)
  + break_duration_minutes (INTEGER, default 60)
  + entry_type (ENUM: 'clock', 'manual', 'adjusted')
  + approved_by (FK users, nullable)
  + work_location_id (FK employees_work_locations)
```

### Calculated Fields
- `working_hours = (clock_out_time - clock_in_time) - (break_duration_minutes / 60)`
- `overtime_hours = MAX(0, working_hours - 8)` per day
- `weekly_overtime = MAX(0, SUM(working_hours) - 32)` per week

---

## Technical Requirements

1. **Real-time sync**: Clock entries sync immediately when online
2. **Offline support**: Queue entries when offline, sync when back online
3. **Audit trail**: All changes logged with timestamp and user
4. **Data validation**: Prevent impossible entries (>24 hours, negative time)
5. **Timezone handling**: All times in `America/New_York`

---

## Priority Order

1. **Phase 1 (MVP)**: US-1.1, US-1.2, US-1.3, US-1.4 - Basic clock in/out
2. **Phase 2**: US-2.1, US-3.1, US-3.2 - Project tracking + basic reports
3. **Phase 3**: US-3.3, US-3.4 - Full reporting + export
4. **Phase 4**: US-4.x, US-5.x, US-6.x - Advanced features

---

## Test Data Employees

| Employee | Role | PIN | Typical Hours |
|----------|------|-----|---------------|
| Bryan Patton | Owner | 1234 | Variable (often in shop) |
| Aedan Ciganek | Inventory Manager | 5678 | 8am-5pm Mon-Thu |
| Ben Carter | Apprentice | 1111 | 8am-5pm Mon-Thu |
| Maria Garcia | Journey Woodworker | 2222 | 8am-5pm Mon-Thu |

---

## Epic 7: AI Assistant Time Clock Interactions

### US-7.1: Clock In via AI
**As** Maria (Skilled Woodworker)
**I want to** tell the AI assistant "I'm here" or "clock me in"
**So that** I can clock in hands-free while setting up my station

**Example Conversations:**
```
Maria: "Hey, clock me in"
AI: "Got it, Maria! Clocked you in at 7:58 AM at TCS Newburgh Shop. Have a great day!"

Maria: "I'm at the shop"
AI: "Good morning Maria! I've clocked you in at 8:02 AM. You're working on the Miller Kitchen project today, right?"
```

**Acceptance Criteria:**
- [ ] AI recognizes clock-in intent: "clock in", "I'm here", "arrived", "starting work"
- [ ] Confirms employee identity (from logged-in user or asks)
- [ ] Records clock-in and confirms time
- [ ] Suggests current project if assigned

---

### US-7.2: Clock Out via AI
**As** Ben (Apprentice)
**I want to** tell the AI "I'm leaving" and have it ask about my lunch
**So that** I don't forget to log my break time

**Example Conversations:**
```
Ben: "Heading out"
AI: "Clocking you out at 5:03 PM, Ben. How long was your lunch today?"
Ben: "45 minutes"
AI: "Got it! Your total for today: 8.25 hours (8:00 AM - 5:03 PM, minus 45 min lunch). See you tomorrow!"

Ben: "Done for the day"
AI: "Great work today! I'll clock you out at 4:58 PM. Did you take your usual 1-hour lunch?"
Ben: "Yeah"
AI: "Perfect. You worked 8 hours today. That's 32 hours for the week - right on target!"
```

**Acceptance Criteria:**
- [ ] AI recognizes clock-out intent: "leaving", "heading out", "done", "clock out"
- [ ] Prompts for lunch duration if not provided
- [ ] Shows daily summary: in time, out time, lunch, total hours
- [ ] Shows weekly progress toward 32 hours

---

### US-7.3: Ask About Hours via AI
**As** Bryan (Owner)
**I want to** ask the AI "How many hours did Ben work this week?"
**So that** I can check attendance without opening reports

**Example Conversations:**
```
Bryan: "How many hours has the team worked this week?"
AI: "Here's this week's hours (Mon-Wed):
     • Maria Garcia: 24.5 hours (on track)
     • Ben Carter: 23 hours (on track)
     • Aedan Ciganek: 16 hours (half week, as expected)

     Everyone's on pace for 32 hours by Thursday."

Bryan: "What about Ben specifically?"
AI: "Ben has worked 23 hours so far:
     • Monday: 8 hrs (Miller Kitchen - assembly)
     • Tuesday: 7.5 hrs (Training - safety + Miller Kitchen)
     • Wednesday: 7.5 hrs (Miller Kitchen - finishing)

     He needs 9 more hours by end of day Thursday."
```

**Acceptance Criteria:**
- [ ] AI understands: "hours worked", "time logged", "attendance"
- [ ] Can filter by employee name
- [ ] Shows breakdown by day and project
- [ ] Calculates remaining hours needed

---

### US-7.4: Log Time to Project via AI
**As** Maria (Skilled Woodworker)
**I want to** tell the AI "I worked on Miller Kitchen all day"
**So that** my time is automatically assigned to the right project

**Example Conversations:**
```
Maria: "I worked on the Miller job today"
AI: "Got it! I'll assign all 8 hours to the Miller Kitchen project. Want me to break it down by task?"
Maria: "Yeah, 3 hours cutting, 5 hours assembly"
AI: "Updated! Miller Kitchen: 3 hrs cutting, 5 hrs assembly. Total 8 hours logged."

Maria: "Split my time between Miller and Johnson today"
AI: "Sure! How many hours on each?"
Maria: "4 and 4"
AI: "Done. 4 hours to Miller Kitchen, 4 hours to Johnson Bathroom. Both logged!"
```

**Acceptance Criteria:**
- [ ] AI matches project names fuzzy ("Miller" → "Miller Kitchen Renovation")
- [ ] Allows splitting time across projects
- [ ] Can optionally assign to tasks within project
- [ ] Confirms assignments before saving

---

### US-7.5: Report Missing Clock via AI
**As** Ben (Apprentice)
**I want to** tell the AI "I forgot to clock in yesterday"
**So that** I can fix my hours easily

**Example Conversations:**
```
Ben: "I forgot to clock in yesterday"
AI: "No problem, Ben. What time did you arrive yesterday (Tuesday)?"
Ben: "Around 8"
AI: "And what time did you leave?"
Ben: "5"
AI: "Got it. I'll add an entry for Tuesday: 8:00 AM - 5:00 PM with 1-hour lunch (8 hours total).
     This needs supervisor approval since it's a past entry. I'll flag it for Bryan."

Ben: "Actually I took a short lunch, only 30 minutes"
AI: "Updated to 30-minute lunch. That's 8.5 hours for Tuesday. Flagged for approval."
```

**Acceptance Criteria:**
- [ ] AI guides through missing punch entry
- [ ] Asks for: date, arrival time, departure time, lunch duration
- [ ] Flags entry as "manual" requiring approval
- [ ] Notifies supervisor for entries > 24 hours old

---

### US-7.6: Weekly Summary Request via AI
**As** any employee
**I want to** ask "How many hours do I have this week?"
**So that** I know if I'm on track for my 32 hours

**Example Conversations:**
```
Ben: "How many hours do I have?"
AI: "You've worked 16 hours so far this week (Mon-Tue).
     You need 16 more hours by end of Thursday to hit your 32-hour goal.
     At your current pace, you're right on track!"

Maria: "What's my time looking like?"
AI: "Hey Maria! You have 24.5 hours logged through Wednesday:
     • Monday: 8 hrs
     • Tuesday: 8.5 hrs (30 min overtime)
     • Wednesday: 8 hrs

     Just 7.5 more hours tomorrow and you're at 32. Nice work!"
```

**Acceptance Criteria:**
- [ ] AI shows hours logged vs. target (32 hrs/week)
- [ ] Highlights overtime if any
- [ ] Shows daily breakdown
- [ ] Calculates remaining hours needed

---

### US-7.7: Project Hours Query via AI
**As** Bryan (Owner)
**I want to** ask "How much time have we spent on the Miller project?"
**So that** I can track labor costs in real-time

**Example Conversations:**
```
Bryan: "How much labor is into the Miller kitchen?"
AI: "Miller Kitchen project labor summary:
     • Total hours: 47.5 hours
     • Maria Garcia: 28 hrs (cutting, assembly)
     • Ben Carter: 15.5 hrs (sanding, finishing assist)
     • Miguel Rodriguez: 4 hrs (QC, setup)

     Estimated budget was 60 hours - you're at 79% with assembly complete."

Bryan: "Are we over budget?"
AI: "Not yet! You have 12.5 hours of budget remaining.
     Based on the task list, you'll need about 8-10 more hours for finishing.
     Looks like you'll come in slightly under budget."
```

**Acceptance Criteria:**
- [ ] AI retrieves project labor totals
- [ ] Shows breakdown by employee and task
- [ ] Compares to budget/estimate if available
- [ ] Projects completion based on remaining tasks

---

### US-7.8: AI Proactive Reminders
**As** Ben (Apprentice)
**I want** the AI to remind me to clock out if I forget
**So that** I don't lose my hours

**Example Conversations:**
```
AI (at 5:15 PM): "Hey Ben, you're still clocked in from 8:02 AM.
                  Heading out soon? Just say 'clock me out' when you're ready."

AI (next morning): "Good morning Ben! Looks like you forgot to clock out yesterday.
                   What time did you leave? I'll fix your timesheet."
```

**Acceptance Criteria:**
- [ ] AI sends reminder at shop close time (5:00 PM) + 15 min
- [ ] Next-day prompt if employee never clocked out
- [ ] Non-intrusive notification style
- [ ] Can be dismissed/snoozed

---

### US-7.9: Apprentice Training Log via AI
**As** Ben (Apprentice)
**I want to** tell the AI what I learned today
**So that** my training progress is documented

**Example Conversations:**
```
Ben: "I learned how to use the router table today"
AI: "That's great progress, Ben! I'll log that as router table training.
     Who showed you - Miguel?"
Ben: "Yeah"
AI: "Perfect. Added to your training log:
     📚 Router Table Operation (2 hours, trained by Miguel Rodriguez)

     You're building up your skills! Want me to show your training progress?"

Ben: "Show my training"
AI: "Here's your training log this month:
     • Table Saw Safety: 4 hrs ✓
     • Router Table: 2 hrs ✓
     • Finishing Basics: 3 hrs ✓
     • Material Handling: 6 hrs ✓

     Total training: 15 hours (47% of your time). Nice balance of learning and production!"
```

**Acceptance Criteria:**
- [ ] AI captures training activities with timestamps
- [ ] Associates mentor/trainer when mentioned
- [ ] Tracks training vs. production ratio
- [ ] Can display training summary on request

---

## AI Integration Technical Requirements

### Intent Recognition
The AI assistant should recognize these time clock intents:
- **Clock In**: "clock in", "I'm here", "arrived", "starting", "at the shop"
- **Clock Out**: "clock out", "leaving", "heading out", "done for the day", "going home"
- **Hours Query**: "how many hours", "my time", "hours this week", "attendance"
- **Project Time**: "worked on [project]", "log time to", "assign hours"
- **Missing Punch**: "forgot to clock", "missed punch", "fix my time"
- **Training Log**: "learned", "training", "practiced", "showed me"

### Context Awareness
- AI should know which employee is speaking (from logged-in user)
- AI should know current time, day of week, shop schedule
- AI should know employee's assigned projects
- AI should know if employee is currently clocked in or not

### Gemini Tool Definitions
```json
{
  "name": "clock_in_employee",
  "description": "Clock in an employee at the current time",
  "parameters": {
    "employee_id": "integer (optional, defaults to current user)",
    "work_location_id": "integer (optional, defaults to primary location)"
  }
}

{
  "name": "clock_out_employee",
  "description": "Clock out an employee with lunch duration",
  "parameters": {
    "employee_id": "integer",
    "lunch_duration_minutes": "integer (default 60)"
  }
}

{
  "name": "get_employee_hours",
  "description": "Get hours worked for an employee",
  "parameters": {
    "employee_id": "integer (optional)",
    "date_range": "string (today, week, month, custom)",
    "include_breakdown": "boolean"
  }
}

{
  "name": "assign_time_to_project",
  "description": "Assign logged hours to a project",
  "parameters": {
    "employee_id": "integer",
    "date": "date",
    "project_id": "integer",
    "hours": "decimal",
    "task_id": "integer (optional)"
  }
}

{
  "name": "add_manual_time_entry",
  "description": "Add a time entry for a past date",
  "parameters": {
    "employee_id": "integer",
    "date": "date",
    "clock_in_time": "time",
    "clock_out_time": "time",
    "lunch_duration_minutes": "integer",
    "notes": "string"
  }
}

{
  "name": "log_training_activity",
  "description": "Log an apprentice training activity",
  "parameters": {
    "employee_id": "integer",
    "activity": "string",
    "duration_hours": "decimal",
    "mentor_id": "integer (optional)",
    "notes": "string"
  }
}
```

---

*Generated: 2025-12-12*
*Based on personas from /Users/andrewphan/tcsadmin/docs/personas/*
