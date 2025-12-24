# Project Management Visual Tools - User Story
## TCS Woodwork Custom Cabinet Shop

**Author**: Bryan Patton, Owner
**Date**: December 20, 2025
**Purpose**: Define business requirements for integrated Gantt/Kanban project management visualization

---

## Executive Summary

As the owner of TCS Woodwork, I need visual project management tools that help me make better business decisions, not just track tasks. I'm juggling 8-12 custom cabinet projects at any given time, each worth $15,000-$85,000, and I need to see:

- **Where bottlenecks are forming** so I can reassign resources before we miss deadlines
- **Which projects are at risk** so I can communicate proactively with clients
- **How to sequence work** to maximize shop efficiency and hit revenue targets
- **When to take on new projects** based on actual capacity, not gut feeling

I don't need another task list. I need a **command center** that shows me the health of my business at a glance and helps me make smart decisions about scheduling, staffing, and client commitments.

---

## Background: Why Visual Tools Matter

### The Current Reality

Right now, I'm managing projects across:
- Email threads with clients (Sarah's kitchen remodel, Michael Lee's office buildout)
- Spreadsheets tracking linear footage and pricing
- Whiteboard with project stages
- Text messages with Trott about Nantucket orders
- Mental calculations about shop capacity

**The problem**: I can't see the forest for the trees. I know we're busy, but I can't tell you if we're *optimally* busy or just chaotically busy. When Frank Russo asks if we can take on a commercial job next month, I'm guessing based on feel, not data.

### What I Need to See

I need two complementary views of the same project data:

1. **Timeline View (Gantt Chart)**: "When is everything happening?"
   - Shows project deadlines on a calendar
   - Reveals scheduling conflicts and dependencies
   - Helps me plan around customer commitments

2. **Stage View (Kanban Board)**: "Where is everything stuck?"
   - Shows bottlenecks in our production workflow
   - Reveals capacity issues in each stage
   - Helps me balance workload across the shop

Both views together answer the critical question: **"What decisions do I need to make today to keep projects on track and hit our $84,900 monthly revenue target?"**

---

## User Story 1: Making Strategic Scheduling Decisions

### As a Business Owner...

**I want to** see all active projects on a timeline with their critical milestones
**So that** I can make informed decisions about new project commitments and resource allocation

### The Scenario

It's Monday morning. Frank Russo calls about a $45,000 commercial millwork job. Install deadline is 6 weeks out. Do I have capacity?

**What I need to see** (Gantt Chart View):

```
Timeline: Next 8 Weeks
├─ Week 1-2: Sarah's Kitchen (Design → Sourcing)
├─ Week 2-3: Michael Lee Office (Production → Delivery) [CRITICAL PATH]
├─ Week 3-4: Miller Residence (Sourcing → Production)
├─ Week 4-6: Nantucket Orders (Discovery → Design) [TROTT]
└─ Week 5-7: [OPEN CAPACITY?]
```

**Decision-making insights I need**:

1. **Critical Path Analysis**: Which projects have no scheduling slack?
   - Michael Lee's office buildout is on critical path (commercial deadline)
   - Can't slip without penalty clauses kicking in
   - Need to protect those dates

2. **Dependency Mapping**: What's blocking what?
   - Sarah's kitchen design approval gates the sourcing order
   - Can't start Miller production until Shane finishes CNC for Michael Lee
   - Eric's finishing schedule affects multiple delivery dates

3. **Capacity Windows**: Where are the gaps?
   - Week 5-7 shows potential capacity IF:
     - Nantucket orders stay in design phase (Trott often delays)
     - Michael Lee delivers on time (freeing up Shane and McKenna)
     - No major rework on Miller project

**The Business Decision**:

Looking at the Gantt chart, I can see:
- ✅ Frank's job could fit in Week 5-7 window
- ⚠️ High risk if Michael Lee slips or Nantucket accelerates
- 💡 **Decision**: Accept Frank's job BUT negotiate 7-week timeline (not 6) to give buffer
- 📞 **Action**: Call Frank, propose Week 5 start with Week 7 delivery, cite current production schedule

**Why this matters**: Without the visual timeline, I would have either:
- Overcommitted (taken 6-week deadline) → miss deadline → damage reputation
- Under-committed (said no) → miss $45K revenue → leave money on table

---

## User Story 2: Identifying Production Bottlenecks

### As a Shop Manager...

**I want to** see where projects are piling up in our workflow stages
**So that** I can reallocate resources and prevent delays before they cascade

### The Scenario

It's Wednesday afternoon. Levi mentions at the daily huddle that "we're getting backed up in production." I need to see the problem immediately, not dig through status reports.

**What I need to see** (Kanban Board View):

```
TCS Workflow Stages:

DISCOVERY          DESIGN            SOURCING          PRODUCTION        DELIVERY
─────────────────────────────────────────────────────────────────────────────────
[ 2 projects ]     [ 1 project ]     [ 1 project ]     [ 5 projects ]    [ 1 project ]
                                                        ⚠️ BOTTLENECK

Sarah Kitchen      Michael Lee       Miller Res.       Sarah Kitchen     Nantucket #1
Nantucket #2       (waiting client)  (waiting lumber)  Michael Lee       (JG installing)
                                                        Miller Cabinets
                                                        Nantucket #3
                                                        Frank Commercial
```

**Bottleneck Analysis I need**:

1. **Stage Capacity Limits**:
   - Production stage: 5 projects (normal max is 3-4)
   - Shane's CNC time is finite: 40 hrs/week
   - McKenna's post-processing is finite: 32 hrs/week
   - **Insight**: We're 25% over production capacity

2. **Upstream/Downstream Flow**:
   - Only 1 project in Sourcing (materials ready to flow in)
   - Only 1 project in Delivery (outflow is slow)
   - **Insight**: Bottleneck is IN production, not before/after

3. **Project Age in Stage**:
   - Sarah's kitchen: 8 days in Production (normal is 5-7)
   - Michael Lee office: 12 days in Production (should be 7-10)
   - **Insight**: Projects are dwelling too long, not flowing through

**The Business Decision**:

Looking at the Kanban board, I can see:
- 🚨 **Problem**: Production bottleneck is causing 20-40% schedule slip
- 🔍 **Root Cause**: Too many projects entered Production simultaneously
- ⚙️ **Options**:
  1. Add overtime for Shane/McKenna (cost: $28/hr × 1.5 × 10 hrs = $420/project)
  2. Delay Frank's commercial start by 1 week (reduce production queue)
  3. Fast-track Michael Lee (critical path) by reassigning senior workers

**My Decision Process**:

1. Cross-reference with Gantt chart → Michael Lee is critical path
2. Check margins → Michael Lee is $65K project (can absorb overtime)
3. **Decision**: Option 3 + Option 1
   - Pull 1 senior worker (SRS) onto Michael Lee full-time
   - Authorize 10 hrs overtime for Shane this week
   - Delay Frank commercial start by 1 week (call him today)

**Why this matters**: Without the Kanban view, I would have:
- Not seen the bottleneck until projects were already late
- Applied generic solutions (work harder!) instead of targeted interventions
- Missed the opportunity to protect the critical path project

---

## User Story 3: Proactive Client Communication

### As a Client Relationship Manager...

**I want to** see which projects are at risk of missing milestones
**So that** I can communicate proactively with clients before they call me upset

### The Scenario

It's Friday morning. I want to know which clients I need to call TODAY to manage expectations, not wait for them to call me asking why we're late.

**What I need to see** (Combined Gantt + Kanban View):

```
AT-RISK PROJECT DASHBOARD

Project             Stage         Days in Stage    Target Date    Status
─────────────────────────────────────────────────────────────────────────────
Michael Lee Office  Production    12 days          Jan 3          ⚠️ AMBER
  └─ Milestone: CNC Complete (Jan 2) - ON TRACK
  └─ Milestone: Finishing Done (Jan 5) - AT RISK (Eric backed up)

Miller Residence    Sourcing      6 days           Jan 10         🟢 GREEN
  └─ Milestone: Lumber Delivery (Dec 28) - ON TRACK

Sarah Kitchen       Production    8 days           Jan 15         🟡 YELLOW
  └─ Milestone: Cabinet Assembly (Jan 8) - SLIGHT DELAY

Nantucket #3        Design        14 days          Jan 20         🔴 RED
  └─ Milestone: Client Approval (Dec 20) - OVERDUE
  └─ BLOCKER: Waiting on Trott to get client sign-off
```

**Client Communication Insights**:

1. **Michael Lee (AMBER)**:
   - **What I know**: CNC on track, but finishing is bottlenecked
   - **Client impact**: Delivery might slip from Jan 10 → Jan 12
   - **My call strategy**:
     - ✅ Proactive call TODAY (Friday)
     - Message: "Michael, wanted to give you a heads up - we're on schedule for production completion, but our finishing shop is running 2 days behind. Your Jan 10 delivery might shift to Jan 12. Can you accommodate that, or is Jan 10 firm?"
     - **Why this works**: I'm calling BEFORE he expects a problem, shows I'm on top of it

2. **Nantucket #3 (RED)**:
   - **What I know**: Stuck in Design for 14 days, client approval overdue
   - **Client impact**: If not approved by Monday, will miss Jan 20 deadline
   - **My action strategy**:
     - 📞 Call Trott RIGHT NOW
     - Message: "Trott, Nantucket #3 has been waiting 14 days for client approval. If we don't get sign-off by Monday, we can't hit the Jan 20 deadline they requested. Can you follow up with them today?"
     - **Why this works**: Data-driven urgency, specific deadline impact

3. **Sarah Kitchen (YELLOW)**:
   - **What I know**: Slight production delay, but 3 weeks to deadline
   - **Client impact**: Low risk, monitor but don't escalate yet
   - **My action**: No call needed, but check status Monday

**The Business Decision**:

- ✅ Make 2 proactive calls TODAY (Michael Lee, Trott)
- 📊 Update internal forecast: Michael Lee delivery = Jan 12 (not Jan 10)
- 🔄 Adjust production schedule: Prioritize Nantucket #3 once design approved

**Why this matters**:
- **Client retention**: Proactive communication = trust, reactive communication = excuses
- **Reputation management**: I control the narrative, not the client
- **Stress reduction**: I'm calling them on MY timeline, not scrambling when they call upset

---

## User Story 4: Optimizing Revenue and Capacity Planning

### As a Business Strategist...

**I want to** see project pipeline value mapped against shop capacity
**So that** I can maximize revenue while maintaining quality and on-time delivery

### The Scenario

It's month-end review. I need to answer: **"Are we on track for $84,900 monthly revenue? Should I be selling more work, or will that hurt quality?"**

**What I need to see** (Gantt Chart with Financial Overlay):

```
REVENUE FORECAST - NEXT 30 DAYS

Week 1          Week 2          Week 3          Week 4          TOTAL
$18,200         $22,400         $21,100         $19,800         $81,500
────────────────────────────────────────────────────────────────────────
Sarah Kitchen   Michael Lee     Miller Res.     Nantucket #3
($15K deliv.)   ($28K deliv.)   ($18K deliv.)   ($16K deliv.)

STATUS: ⚠️ $3,400 SHORT OF TARGET ($84,900)
```

**Capacity Analysis I need**:

1. **Shop Utilization Metrics**:
   - Total available hours: 208 hrs/week (all staff)
   - Current utilization: 175 hrs/week (84%)
   - Remaining capacity: 33 hrs/week (16%)
   - **Insight**: We have capacity headroom

2. **Linear Footage Production**:
   - Current: 68 LF/week average
   - Target for $84,900: 98 LF/week (at $200/LF) OR 82 LF/week (at $240/LF)
   - Current gap: 30 LF/week OR 14 LF/week
   - **Insight**: Pricing increase is easier than 44% production increase

3. **Revenue Per Project Stage**:
   ```
   Discovery:  $45,000 (2 projects)  → potential, not committed
   Design:     $28,000 (1 project)   → 50% deposit collected
   Sourcing:   $18,000 (1 project)   → materials purchased, committed
   Production: $125,000 (5 projects) → in progress, delivery within 4 weeks
   Delivery:   $0 (completed)        → revenue already recognized
   ```

**The Business Decision**:

Looking at the data:
- 🚨 **Problem**: $3,400 short this month, $26K short monthly average
- 📊 **Capacity Reality**: 16% headroom = room for more work
- 💰 **Revenue Reality**: Need 14 more LF/week (manageable) OR 5% price increase

**My Strategic Options**:

1. **Aggressive Sales Push**:
   - Convert 1 Discovery project to Design this month
   - If Frank's $45K commercial job starts Week 3 → $81,500 + $15K deposit = $96,500
   - **Result**: Exceed target by $11,600
   - **Risk**: Capacity utilization → 95% (near max)

2. **Price Increase Strategy**:
   - Raise pricing from $200/LF → $220/LF (10% increase)
   - Current 68 LF/week × $220 = $14,960/week = $64,832/month
   - Still need more volume, but improves margins
   - **Result**: Combine with modest volume increase to 78 LF/week → $85,800/month
   - **Risk**: Client pushback on pricing

3. **Hybrid Approach** (MY CHOICE):
   - Close Frank's commercial job ($45K, starts Week 3)
   - Raise pricing 5% to $210/LF for NEW projects (grandfather existing)
   - Target 75 LF/week production (10% increase, very achievable)
   - **Result**: $84,000+/month with sustainable capacity (88% utilization)

**Why this matters**:
- Without visual project/capacity data, I'd be making revenue decisions blind
- I can now set specific, data-driven targets for David (sales) and Levi (production)
- I can answer "Should I sell more?" with confidence: YES, but with pricing increase

---

## User Story 5: Managing Project Dependencies

### As a Production Coordinator...

**I want to** see how projects depend on each other and shared resources
**So that** I can sequence work optimally and prevent cascade delays

### The Scenario

Shane (CNC operator) tells me he has 3 projects that need CNC work next week. Which one do I prioritize? They're all "important" according to clients.

**What I need to see** (Gantt Chart with Dependency Mapping):

```
DEPENDENCY CHAIN ANALYSIS

Project A: Sarah Kitchen
├─ CNC Work (Shane): Jan 2-3 (2 days)
├─ BLOCKS → Cabinet Assembly (Levi + SRS): Jan 4-6
├─ BLOCKS → Finishing (Eric): Jan 7-9
└─ BLOCKS → Delivery (JG): Jan 10
    └─ Client Deadline: Jan 15 (5 days slack) 🟢 LOW PRIORITY

Project B: Michael Lee Office
├─ CNC Work (Shane): Jan 2-4 (3 days)
├─ BLOCKS → Post-Processing (McKenna): Jan 5-6
├─ BLOCKS → Finishing (Eric): Jan 7-10
└─ BLOCKS → Delivery (JG): Jan 11
    └─ Client Deadline: Jan 12 (1 day slack) 🔴 HIGH PRIORITY

Project C: Miller Residence
├─ CNC Work (Shane): Jan 2-5 (4 days)
├─ BLOCKS → Assembly: Jan 6-8
└─ BLOCKS → Delivery: Jan 12
    └─ Client Deadline: Jan 20 (8 days slack) 🟢 LOW PRIORITY

SHARED RESOURCE: Shane's CNC (40 hrs/week available)
Total CNC needed: 2 + 3 + 4 = 9 days (72 hours) > 40 hours available ⚠️ CONFLICT
```

**Dependency Decision Framework**:

1. **Critical Path Priority**:
   - Michael Lee has only 1 day slack → CRITICAL
   - Sarah Kitchen has 5 days slack → MEDIUM
   - Miller Residence has 8 days slack → LOW
   - **Decision**: Shane prioritizes Michael Lee first

2. **Cascade Impact Analysis**:
   - If Michael Lee CNC slips 1 day → Finishing slips → Delivery slips → MISS DEADLINE
   - If Sarah CNC slips 2 days → Still hits deadline (has slack)
   - **Decision**: Protect Michael Lee schedule at all costs

3. **Resource Sequencing**:
   ```
   OPTIMAL SCHEDULE:
   Shane's Week:
   ├─ Mon-Wed: Michael Lee CNC (3 days) [CRITICAL PATH]
   ├─ Thu-Fri: Sarah Kitchen CNC (2 days) [HAS SLACK]
   └─ DEFER: Miller Residence to following week (has 8 days slack)

   Contingency:
   ├─ If Michael Lee finishes early → Start Miller
   ├─ If Michael Lee runs over → Sarah absorbs 1 day (still has 4 days slack)
   ```

**The Business Decision**:

- 📋 Shane's priority order: Michael Lee → Sarah → Miller
- 📞 Call Miller client: "We're moving your CNC to next week, still delivering by Jan 20"
- ⚙️ Authorize Shane for 5 hrs overtime if Michael Lee needs it (protect critical path)
- 📊 Update production board: Miller moves from "Sourcing" to "Sourcing - Scheduled CNC 1/9"

**Why this matters**:
- Without dependency visualization, Shane makes priority calls based on "who yells loudest"
- I can make data-driven decisions that optimize for on-time delivery across ALL projects
- I can see cascade effects BEFORE they happen, not after

---

## Core Metrics That Drive Decisions

### Real-Time Dashboard Metrics I Need

1. **Project Health Indicators**:
   - 🟢 On Track: Milestone dates achievable with current schedule
   - 🟡 At Risk: Less than 2 days slack remaining
   - 🔴 Critical: On critical path OR overdue milestone
   - ⚫ Blocked: Waiting on external dependency (client, vendor)

2. **Stage Capacity Metrics**:
   ```
   Discovery:   2/5 slots (40% capacity)
   Design:      1/3 slots (33% capacity)
   Sourcing:    1/2 slots (50% capacity)
   Production:  5/4 slots (125% capacity) ⚠️ OVER CAPACITY
   Delivery:    1/2 slots (50% capacity)
   ```

3. **Timeline Metrics**:
   - Days until next deadline: 8 days (Michael Lee)
   - Projects due this week: 2
   - Projects due next week: 1
   - Overdue projects: 0 ✅

4. **Financial Metrics**:
   - Revenue this month: $58,200 (of $84,900 target) - 69%
   - Revenue forecast (next 30 days): $81,500
   - Average project value: $26,750
   - Pipeline value (Discovery + Design): $73,000

5. **Resource Utilization**:
   - Shop capacity: 84% (175/208 hours)
   - Shane (CNC): 95% utilized ⚠️ BOTTLENECK
   - McKenna (Post-CNC): 88% utilized
   - Eric (Finishing): 78% utilized
   - Levi + Team: 80% utilized

---

## How Gantt and Kanban Complement Each Other

### When I Use Each View

**Gantt Chart (Timeline View)** - I use when asking:
- "Can we take on this new project?"
- "Which deadline is most at risk?"
- "What happens if this project slips 2 days?"
- "When should I schedule this client install?"
- "What's our capacity next month?"

**Kanban Board (Stage View)** - I use when asking:
- "Where are we bottlenecked right now?"
- "Why is this project taking so long?"
- "How many projects can we handle in production?"
- "Which stage needs more resources?"
- "What's blocking us from moving forward?"

**Combined Dashboard** - I use when asking:
- "What decisions do I need to make today?"
- "Which client do I need to call?"
- "Are we on track for monthly revenue?"
- "Should I hire another CNC operator?"
- "What's the health of my business right now?"

### The Power of Integration

The magic happens when both views show **THE SAME DATA**:

- **Scenario**: I see 5 projects in Production stage (Kanban)
- **Question**: Which one is most urgent?
- **Answer**: Switch to Gantt view → Michael Lee has tightest deadline
- **Decision**: Prioritize Michael Lee in production queue

OR:

- **Scenario**: I see Michael Lee slipping in Gantt chart
- **Question**: Why is it slipping?
- **Answer**: Switch to Kanban view → Production bottleneck (5 projects)
- **Decision**: Add overtime OR delay less critical project

---

## Real-World Workflow Integration

### Monday Morning Planning Session

**8:00 AM - Weekly Review with Levi**

1. Open **Gantt Chart**:
   - Review all deadlines this week
   - Identify critical path projects
   - Note any scheduling conflicts

2. Open **Kanban Board**:
   - Check stage capacity (are we overloaded anywhere?)
   - Review project age in each stage
   - Identify blocked projects

3. **Decision Time**:
   - Set priority list for Shane, McKenna, Eric
   - Identify which projects need attention today
   - Plan client communication for at-risk projects

### Wednesday Check-In

**2:00 PM - Mid-Week Pulse Check**

1. **Kanban Quick Scan**:
   - Any stages getting backed up since Monday?
   - Any projects dwelling too long?

2. **Gantt Quick Scan**:
   - Are we still on track for this week's deadlines?
   - Any new risks emerged?

3. **Adjustment Decisions**:
   - Reallocate resources if needed
   - Authorize overtime if critical path at risk
   - Update client communication plan

### Friday Afternoon Review

**4:00 PM - Week Close-Out**

1. **Move Projects on Kanban**:
   - What advanced stages this week?
   - What's ready to move to next stage Monday?

2. **Update Gantt Milestones**:
   - Mark completed milestones
   - Adjust forecasts based on actual progress

3. **Next Week Preview**:
   - What's coming up?
   - Any capacity concerns?
   - Client calls needed?

---

## Integration with Existing TCS Workflow

### How This Fits Our Current Process

**Current State**: We track projects in spreadsheets and whiteboard

**Future State**: Visual tools replace manual tracking

**Workflow Stages Mapped**:

```
KANBAN STAGES = TCS WORKFLOW STAGES

1. Discovery Stage
   ├─ Initial client consultation
   ├─ Site measurements
   ├─ Scope definition
   └─ Preliminary quote

2. Design Stage
   ├─ CAD design (Bryan or David)
   ├─ Client review and approval
   ├─ Final specifications
   └─ 50% deposit collected

3. Sourcing Stage
   ├─ Material ordering
   ├─ Hardware selection
   ├─ Delivery scheduling
   └─ Quality check (Shane receives)

4. Production Stage
   ├─ CNC programming and cutting (Shane)
   ├─ Post-processing (McKenna - edge band, holes)
   ├─ Assembly (Levi + team)
   └─ Quality checkpoints (5 major checks)

5. Finishing Stage (Eric's Shop)
   ├─ Staining
   ├─ Sealing
   ├─ Final finish
   └─ Drying time

6. Delivery Stage
   ├─ Final QC (Bryan + Levi)
   ├─ Delivery scheduling (JG)
   ├─ Installation (JG + team)
   └─ Client walkthrough and sign-off
```

**Gantt Milestones Mapped**:

```
PROJECT TIMELINE MILESTONES

Week 1:
└─ Discovery Complete → Design Start

Week 2:
├─ Design Complete → Client Approval
└─ 50% Deposit Received → Sourcing Start

Week 3:
├─ Materials Received → Production Start
└─ CNC Complete (Shane)

Week 4:
├─ Post-Processing Complete (McKenna)
├─ Assembly Complete (Levi)
└─ Finishing Start (Eric)

Week 5:
├─ Finishing Complete
└─ Final QC (Bryan)

Week 6:
├─ Delivery Scheduled
└─ Installation Complete (JG)
```

---

## Success Criteria: How I'll Know This Works

### Quantitative Metrics

1. **Revenue Target Achievement**:
   - ✅ Hit $84,900 monthly revenue consistently (currently at $58,888)
   - ✅ Reduce revenue variance month-to-month (currently unpredictable)

2. **On-Time Delivery**:
   - ✅ 95%+ of projects delivered by committed date (currently ~80%)
   - ✅ Zero missed critical path deadlines

3. **Capacity Optimization**:
   - ✅ Shop utilization 85-90% (currently 84%, but unmanaged)
   - ✅ No stage exceeding 110% capacity for more than 3 days

4. **Client Satisfaction**:
   - ✅ Proactive client communication on 100% of at-risk projects
   - ✅ Zero "surprised" client calls about delays

### Qualitative Metrics

1. **Decision Confidence**:
   - ✅ I can answer "Can we take this job?" in 5 minutes, not 5 days
   - ✅ I stop second-guessing project commitments

2. **Stress Reduction**:
   - ✅ I sleep better knowing I can see problems before they explode
   - ✅ I spend less time firefighting, more time on strategy

3. **Team Empowerment**:
   - ✅ Levi can make priority decisions without waiting for me
   - ✅ David can quote realistic timelines based on current capacity

---

## Technical Requirements for Development Team

### Must-Have Features (MVP)

1. **Gantt Chart View**:
   - Timeline spanning 90 days (past 30 + current + next 60)
   - Projects displayed as bars with start/end dates
   - Milestones shown as diamonds on timeline
   - Critical path highlighted in red
   - Dependency arrows connecting related tasks
   - Today marker (vertical line)
   - Drag-to-adjust dates (with confirmation)

2. **Kanban Board View**:
   - 6 columns matching TCS workflow stages
   - Project cards showing: name, client, days in stage, value, priority
   - Color coding: Green (on track), Yellow (at risk), Red (critical)
   - Drag-and-drop to move stages
   - WIP limits visible (e.g., "Production: 5/4 ⚠️")
   - Blocked indicator for waiting projects

3. **Unified Data Model**:
   - Same project data drives both views
   - Real-time sync (change in Gantt updates Kanban, vice versa)
   - Stage transitions auto-update timeline milestones

4. **Filter & Search**:
   - Filter by client, priority, stage, date range
   - Search by project name or client name
   - "Show only at-risk" toggle

5. **Mobile Responsive**:
   - I need to check status from my phone at client sites
   - Key metrics visible without horizontal scrolling

### Nice-to-Have Features (Future)

1. **Resource Allocation View**:
   - See Shane, McKenna, Eric, Levi, JG workload by week
   - Capacity heatmap (green = available, red = overbooked)

2. **Financial Overlay**:
   - Gantt bars show project value
   - Revenue forecast by week
   - "On track for monthly target" indicator

3. **Automated Alerts**:
   - Email/SMS when project goes from Green → Yellow
   - Daily digest of at-risk projects
   - Capacity warning when stage exceeds 100%

4. **Historical Analytics**:
   - Average days per stage (to improve estimates)
   - On-time delivery trends
   - Stage bottleneck frequency

---

## Conclusion: The Business Value

As Bryan Patton, owner of TCS Woodwork, these visual tools aren't just "nice to have" – they're essential for running a profitable, sustainable custom cabinet business.

**What I gain**:
- ✅ Data-driven decisions instead of gut feelings
- ✅ Proactive client management instead of reactive firefighting
- ✅ Optimized capacity instead of chaotic overload
- ✅ Predictable revenue instead of feast-or-famine cycles
- ✅ Team empowerment instead of bottlenecked decision-making

**The bottom line**:
I need tools that help me **see the business**, not just track tasks. Gantt + Kanban together give me the visibility to make smart decisions about scheduling, pricing, capacity, and client commitments.

If we build this right, I can finally answer the questions that keep me up at night:
- "Can we take on this job?"
- "Are we on track for our revenue target?"
- "What's about to go wrong, and how do I prevent it?"

That's the difference between surviving and thriving in custom woodworking.

---

**Next Steps for Development Team**:
1. Review this user story document
2. Propose FilamentPHP v3 implementation approach (widgets, custom pages, plugins?)
3. Design mockups for Gantt and Kanban views
4. Define database schema for project stages, milestones, dependencies
5. Build MVP with core features listed above

**Questions for Development Team**:
- Can we integrate with existing `projects_projects` table in AureusERP?
- How do we handle date adjustments (manual override vs. auto-calculation)?
- What's the best UI library for Gantt charts in FilamentPHP?
- How do we implement real-time sync between views?

---

**Document Version**: 1.0
**Author**: Bryan Patton (as user story)
**Date**: December 20, 2025
**Status**: Ready for Development Review
