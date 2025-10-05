# TCS Woodwork ERP - Comprehensive Project Tagging System Recommendations

**Date:** 2025-10-03
**Purpose:** Establish a practical, scalable project tagging system that addresses real pain points from TCS personas
**Total Recommended Tags:** 38 (across 6 categories)

---

## Executive Summary

Based on analysis of TCS Woodwork personas (Owner Bryan, PM David, Lead Craftsman Miguel), workflow documentation, and ERP/construction industry best practices, this document proposes a comprehensive yet manageable tagging system organized into 6 strategic categories.

**Key Principles Applied:**
- **Solve Real Pain Points:** Each tag addresses specific issues identified in persona documentation
- **Avoid Redundancy:** Tags complement existing fields (project_type, lifecycle phases) rather than duplicate them
- **Color Psychology:** Strategic use of colors for quick visual scanning
- **Practical Limit:** 38 total tags keeps system usable, not overwhelming

---

## Tag Categories Overview

| Category | Count | Purpose | Primary User |
|----------|-------|---------|--------------|
| **1. Lifecycle Phase** | 12 | Already created - project workflow stages | All |
| **2. Priority/Urgency** | 5 | Resource allocation and scheduling | Bryan, David |
| **3. Project Health** | 6 | Early warning system for issues | Bryan, David |
| **4. Work Scope/Type** | 7 | Work classification beyond project_type | David, Miguel |
| **5. Risk/Issue** | 5 | Proactive problem tracking | All |
| **6. Quality/Complexity** | 3 | Production planning and resource assignment | Miguel, David |

**Total: 38 tags**

---

## 1. Lifecycle Phase Tags (Already Created ✓)

These 12 tags are already in the system and track the project through its workflow stages:

| Tag Name | Color | Status |
|----------|-------|--------|
| Awareness & Contact | #3B82F6 (Blue) | ✓ Created |
| Intake & Qualify | #8B5CF6 (Purple) | ✓ Created |
| Bid/Proposal | #EC4899 (Pink) | ✓ Created |
| Agreement & Contract | #10B981 (Green) | ✓ Created |
| Kickoff & Deposit | #14B8A6 (Teal) | ✓ Created |
| Design & Development | #F59E0B (Amber) | ✓ Created |
| Production | #EF4444 (Red) | ✓ Created |
| Change Orders | #F97316 (Orange) | ✓ Created |
| QC & Finishing | #6366F1 (Indigo) | ✓ Created |
| Delivery & Install | #06B6D4 (Cyan) | ✓ Created |
| Acceptance & Payment | #84CC16 (Lime) | ✓ Created |
| Post-Project | #64748B (Slate) | ✓ Created |

**Recommendation:** Keep these as-is. They align perfectly with the documented workflow.

---

## 2. Priority/Urgency Tags (5 tags)

**Purpose:** Address Bryan's need to filter noise and focus on critical items; help David manage competing demands.

**Pain Points Addressed:**
- Bryan: "Constant firefighting, lack of prioritization support"
- David: "Resource conflicts, schedule delays"

| Tag Name | Color | Hex | When to Use | Business Impact |
|----------|-------|-----|-------------|-----------------|
| **🔥 Critical Priority** | Bright Red | #DC2626 | Revenue at risk, safety issues, client escalations | Immediate action required |
| **⚡ High Priority** | Orange-Red | #EA580C | Contractual deadlines, major milestones approaching | Complete within 48 hours |
| **📋 Standard Priority** | Yellow | #EAB308 | Normal workflow, no special urgency | Standard queue processing |
| **📅 Low Priority** | Light Gray | #94A3B8 | Nice-to-have, flexible timeline | Complete when capacity allows |
| **❄️ On Hold** | Blue-Gray | #475569 | Waiting on client/vendor, paused projects | No active work until unblocked |

**Usage Guidelines:**
- Only 20% of projects should be Critical/High Priority (prevents priority inflation)
- Review priorities weekly to prevent everything becoming "urgent"
- Auto-escalate priority as deadlines approach

---

## 3. Project Health/Status Tags (6 tags)

**Purpose:** Early warning system for Bryan; proactive problem-solving for David; addresses "surprises and lack of communication"

**Pain Points Addressed:**
- Bryan: "Dropping balls, operational chaos, missed follow-ups"
- David: "Client dissatisfaction, budget overruns, schedule delays"

| Tag Name | Color | Hex | Trigger Conditions | Action Required |
|----------|-------|-----|-------------------|------------------|
| **✅ On Track** | Green | #16A34A | Meeting milestones, budget healthy, no blockers | Continue monitoring |
| **⚠️ At Risk** | Yellow | #CA8A04 | Minor delays (<1 week), budget variance <10%, solvable issues | PM attention needed |
| **🚨 Red Flag** | Red | #DC2626 | Major delays (>1 week), budget overrun >10%, critical issues | Immediate escalation |
| **⏸️ Blocked** | Purple | #9333EA | Waiting on approvals, materials, client decisions | Daily follow-up required |
| **💰 Budget Watch** | Orange | #EA580C | Budget variance 5-10%, trending toward overrun | Cost review meeting |
| **⏰ Schedule Watch** | Amber | #F59E0B | Deadline in <2 weeks, dependencies at risk | Resource reallocation |

**Dashboard Impact:** Bryan can filter to show only "At Risk" + "Red Flag" + "Blocked" projects for daily review.

---

## 4. Work Scope/Type Tags (7 tags)

**Purpose:** Complement project_type field with specific work classifications; help Miguel assess buildability and complexity.

**Pain Points Addressed:**
- Miguel: "Poor drawings/specs, rushed schedules compromising quality"
- David: "Scope creep, resource allocation challenges"

**Note:** These work alongside the existing project_type field (Residential, Commercial, etc.)

| Tag Name | Color | Hex | Description | Why Useful |
|----------|-------|-----|-------------|------------|
| **🪚 Custom Cabinetry** | Brown | #92400E | Built-in cabinets, kitchens, vanities | Shop capacity planning |
| **🏛️ Architectural Millwork** | Dark Blue | #1E3A8A | Wainscoting, crown molding, trim packages | Finish scheduling |
| **🪑 Custom Furniture** | Warm Brown | #78350F | Tables, chairs, built-in seating | Different workflow/timeline |
| **🚪 Doors & Casework** | Teal | #115E59 | Custom doors, frames, specialty casework | Hardware coordination |
| **🏗️ Commercial Fixtures** | Steel Blue | #0F766E | Retail displays, office millwork | Commercial specs/compliance |
| **🔧 Service/Repair** | Gray | #475569 | Warranty work, modifications, fixes | Fast-track priority |
| **🎨 Finishing Only** | Purple | #7C2D94 | Refinishing, touch-up, finishing services | Schedule around production |

**Usage:** Projects can have multiple work scope tags (e.g., Custom Cabinetry + Custom Furniture).

---

## 5. Risk/Issue Tags (5 tags)

**Purpose:** Proactive tracking of common problems; prevent issues from becoming crises.

**Pain Points Addressed:**
- Bryan: "Quality control issues, rework, financial instability"
- David: "Poor communication, lack of information, scope creep"
- Miguel: "Material issues, unclear standards, repeated team errors"

| Tag Name | Color | Hex | Common Scenarios | Prevention Action |
|----------|-------|-----|------------------|-------------------|
| **📐 Design Risk** | Pink | #DB2777 | Complex/novel designs, unclear specs, multiple revisions | Extra design review, buildability check |
| **📦 Material Risk** | Brown | #A16207 | Long lead times, availability issues, quality concerns | Early ordering, backup suppliers |
| **👥 Coordination Risk** | Blue | #2563EB | Multiple stakeholders, GC dependencies, designer conflicts | Communication plan, clear roles |
| **💵 Payment Risk** | Red-Orange | #DC2626 | Deposit delays, credit concerns, disputed change orders | Strict payment milestones |
| **⚙️ Capacity Risk** | Indigo | #4F46E5 | Tight timeline, overlapping projects, specialty skills needed | Resource leveling, outsourcing plan |

**Dashboard View:** Filter by risk tags to see vulnerable projects requiring mitigation plans.

---

## 6. Quality/Complexity Tags (3 tags)

**Purpose:** Help Miguel plan builds and mentor team; assist David with realistic scheduling.

**Pain Points Addressed:**
- Miguel: "Rushed schedules, quality vs. speed tensions, team skill gaps"
- David: "Unrealistic timelines, production bottlenecks"

| Tag Name | Color | Hex | Criteria | Impact on Workflow |
|----------|-------|-----|----------|-------------------|
| **⭐ Standard Complexity** | Green | #16A34A | Proven designs, standard materials, experienced team capable | Normal timeline, standard QC |
| **⭐⭐ Advanced Complexity** | Yellow | #CA8A04 | Custom designs, specialty materials, requires lead craftsman | +25% timeline, enhanced QC |
| **⭐⭐⭐ Master Complexity** | Red | #DC2626 | Novel techniques, exotic materials, Miguel-led builds only | +50% timeline, prototype phase |

**Production Planning:** Miguel can filter by complexity to assign appropriate team members and plan mentoring opportunities.

---

## Implementation Strategy

### Phase 1: Core Tags (Week 1)
Start with the most impactful tags:
- Priority/Urgency tags (5)
- Project Health tags (6)
- Risk/Issue tags (5)

**Rationale:** These solve Bryan's biggest pain points immediately.

### Phase 2: Operational Tags (Week 2-3)
Add tags that improve workflow:
- Work Scope/Type tags (7)
- Quality/Complexity tags (3)

**Rationale:** These help David and Miguel with day-to-day operations.

### Phase 3: Refinement (Month 2)
- Review tag usage analytics
- Merge/remove underused tags
- Add custom tags if specific needs emerge

---

## SQL Implementation Commands

```sql
-- Phase 1: Priority/Urgency Tags
INSERT INTO projects_tags (name, color, creator_id, created_at, updated_at) VALUES
('Critical Priority', '#DC2626', 1, NOW(), NOW()),
('High Priority', '#EA580C', 1, NOW(), NOW()),
('Standard Priority', '#EAB308', 1, NOW(), NOW()),
('Low Priority', '#94A3B8', 1, NOW(), NOW()),
('On Hold', '#475569', 1, NOW(), NOW());

-- Phase 1: Project Health Tags
INSERT INTO projects_tags (name, color, creator_id, created_at, updated_at) VALUES
('On Track', '#16A34A', 1, NOW(), NOW()),
('At Risk', '#CA8A04', 1, NOW(), NOW()),
('Red Flag', '#DC2626', 1, NOW(), NOW()),
('Blocked', '#9333EA', 1, NOW(), NOW()),
('Budget Watch', '#EA580C', 1, NOW(), NOW()),
('Schedule Watch', '#F59E0B', 1, NOW(), NOW());

-- Phase 1: Risk/Issue Tags
INSERT INTO projects_tags (name, color, creator_id, created_at, updated_at) VALUES
('Design Risk', '#DB2777', 1, NOW(), NOW()),
('Material Risk', '#A16207', 1, NOW(), NOW()),
('Coordination Risk', '#2563EB', 1, NOW(), NOW()),
('Payment Risk', '#DC2626', 1, NOW(), NOW()),
('Capacity Risk', '#4F46E5', 1, NOW(), NOW());

-- Phase 2: Work Scope/Type Tags
INSERT INTO projects_tags (name, color, creator_id, created_at, updated_at) VALUES
('Custom Cabinetry', '#92400E', 1, NOW(), NOW()),
('Architectural Millwork', '#1E3A8A', 1, NOW(), NOW()),
('Custom Furniture', '#78350F', 1, NOW(), NOW()),
('Doors & Casework', '#115E59', 1, NOW(), NOW()),
('Commercial Fixtures', '#0F766E', 1, NOW(), NOW()),
('Service/Repair', '#475569', 1, NOW(), NOW()),
('Finishing Only', '#7C2D94', 1, NOW(), NOW());

-- Phase 2: Quality/Complexity Tags
INSERT INTO projects_tags (name, color, creator_id, created_at, updated_at) VALUES
('Standard Complexity', '#16A34A', 1, NOW(), NOW()),
('Advanced Complexity', '#CA8A04', 1, NOW(), NOW()),
('Master Complexity', '#DC2626', 1, NOW(), NOW());
```

---

## Usage Guidelines & Best Practices

### For Bryan (Owner)
**Daily Dashboard Filters:**
```
Priority: Critical Priority OR High Priority
Health: Red Flag OR At Risk OR Blocked
```
This shows only projects needing your attention (typically 10-15% of total projects).

**Weekly Strategic Review:**
```
Risk Tags: Any project with 2+ risk tags
Complexity: Master Complexity projects
Health: Budget Watch OR Schedule Watch
```

### For David (Project Manager)
**Daily Task Queue:**
```
Health: Blocked (resolve blockers first)
Priority: Critical Priority → High Priority → Standard Priority
```

**Change Order Triggers:**
Automatically apply "Design Risk" tag when project has 3+ design revisions.

**Weekly Coordination:**
Filter by "Coordination Risk" + upcoming milestones to plan stakeholder meetings.

### For Miguel (Lead Craftsman)
**Production Planning:**
```
Scope: Custom Cabinetry OR Architectural Millwork
Complexity: Advanced Complexity OR Master Complexity
Health: NOT Blocked
```

**Mentoring Opportunities:**
Assign "Standard Complexity" projects to journeymen; "Advanced" projects require your oversight.

**Quality Checkpoints:**
All "Master Complexity" projects require your sign-off before QC & Finishing phase.

---

## Color Psychology & Visual Design

**Why These Colors Matter:**

| Color Family | Use Case | Psychological Impact |
|--------------|----------|---------------------|
| **Reds (#DC2626)** | Critical, urgent, problems | Immediate attention, danger, stop |
| **Oranges (#EA580C)** | High priority, warnings | Caution, important but not critical |
| **Yellows (#EAB308)** | Standard priority, watch items | Awareness, moderate attention |
| **Greens (#16A34A)** | On track, standard work | Safe, proceed, positive status |
| **Blues (#2563EB)** | Information, coordination | Trust, communication, stability |
| **Purples (#9333EA)** | Blocked, special cases | Waiting, different workflow |
| **Grays (#475569)** | Low priority, on hold | Neutral, background, deferred |
| **Browns (#92400E)** | Physical work types | Craftsmanship, materials, production |

---

## Metrics & KPIs to Track

Once implemented, monitor these tag-related metrics:

### Health Metrics
- % of projects in each health status
- Average time to resolve "Blocked" status
- Trend of "At Risk" → "Red Flag" conversions

### Priority Distribution
- % of projects by priority level (goal: <20% High/Critical)
- Average time-to-completion by priority
- Priority escalation rate over time

### Risk Management
- Most common risk tags (identify systemic issues)
- Projects with 2+ risk tags (high vulnerability)
- Risk tag → actual problem conversion rate

### Complexity Planning
- Average timeline by complexity level
- Team member assignments by complexity
- Complexity vs. profitability analysis

---

## Integration with Existing Systems

### Project Type Field (Existing)
**Keep Using For:** Client/market segmentation
- Residential
- Commercial
- Designer Firm
- General Contractor

**Tags Complement By:** Adding work-specific detail
- Project Type: "Commercial"
- Work Scope Tags: "Custom Cabinetry" + "Commercial Fixtures"

### Lifecycle Phase Tags (Existing)
**Keep Using For:** Workflow progression tracking

**Tags Complement By:** Adding orthogonal information
- Lifecycle Phase: "Production"
- Health: "At Risk"
- Priority: "High Priority"
- Complexity: "Advanced Complexity"

### FilamentPHP Filtering
Tags enable powerful multi-dimensional filtering:
```php
->query(function ($query) {
    return $query->whereHas('tags', function ($q) {
        $q->whereIn('name', ['Critical Priority', 'Red Flag']);
    });
})
```

---

## Common Tagging Scenarios

### Scenario 1: Complex Commercial Project
**Tags Applied:**
- Lifecycle: "Design & Development"
- Priority: "High Priority"
- Health: "On Track"
- Work Scope: "Commercial Fixtures" + "Custom Cabinetry"
- Risk: "Coordination Risk" (multiple stakeholders)
- Complexity: "Advanced Complexity"

**Why:** Gives complete project snapshot at a glance.

### Scenario 2: Urgent Repair Job
**Tags Applied:**
- Lifecycle: "Intake & Qualify"
- Priority: "Critical Priority"
- Health: "Blocked" (waiting on parts)
- Work Scope: "Service/Repair"
- Risk: "Material Risk"

**Why:** Immediately visible on Bryan's critical dashboard; David knows to expedite parts.

### Scenario 3: High-End Residential Kitchen
**Tags Applied:**
- Lifecycle: "Production"
- Priority: "Standard Priority"
- Health: "On Track"
- Work Scope: "Custom Cabinetry" + "Custom Furniture"
- Complexity: "Master Complexity"

**Why:** Miguel knows he needs to lead this build; David plans extended timeline.

---

## Migration Plan for Existing Projects

### Bulk Tagging Strategy (First 2 Weeks)

**Week 1: Priority & Health Tags**
1. Auto-tag all projects with deadlines <30 days: "High Priority"
2. Auto-tag projects with budget variance >10%: "Budget Watch"
3. Manual review of active projects for health status

**Week 2: Work Scope & Complexity**
1. Map existing project descriptions to work scope tags
2. Review production history to assign complexity tags
3. PM/Miguel collaboration to validate complexity ratings

### Automated Tagging Rules (To Implement Later)

```php
// Auto-apply "Blocked" when no activity for 7 days
// Auto-escalate priority as deadline approaches
// Auto-apply "Budget Watch" when costs exceed 90% of estimate
// Auto-apply "Schedule Watch" when milestone missed
```

---

## Success Metrics (3-Month Review)

**Quantitative Goals:**
- 95% of active projects properly tagged
- Bryan's daily review time reduced by 40%
- 30% reduction in "surprise" issues/delays
- 25% improvement in on-time project completion

**Qualitative Goals:**
- Team reports clearer priorities
- Less time spent in status meetings
- Improved client communication about project status
- Better resource allocation across shop floor

---

## Appendix: Alternative/Future Tags to Consider

These tags were considered but not included in the core system. Add if specific needs emerge:

### Client Relationship Tags
- VIP Client
- Repeat Customer
- Referral Source
- First-Time Client

**Why Not Included:** Better tracked in CRM/customer relationship system.

### Financial Tags
- Deposit Received
- Net 30
- Payment Plan
- COD Required

**Why Not Included:** Better tracked in payments/invoicing system.

### Marketing Source Tags
- Website Lead
- Referral
- Trade Show
- Social Media

**Why Not Included:** Better tracked in lead_source field.

### Team Assignment Tags
- Miguel Lead
- Carlos Lead
- Outsourced
- All Hands

**Why Not Included:** Better tracked through actual task/resource assignment.

---

## Questions & Answers

**Q: Why only 38 tags? Some ERPs have hundreds.**
**A:** Research shows diminishing returns after 30-40 tags. More tags = decision fatigue and inconsistent application. Focus on high-impact categories.

**Q: Can projects have multiple tags from the same category?**
**A:** Yes for Work Scope (a project can be Cabinetry + Furniture). No for Priority (one priority per project). Use judgment for others.

**Q: Who is responsible for tagging projects?**
**A:**
- Lifecycle: Auto-updates with workflow (future enhancement)
- Priority/Health: Project Manager (David) with owner review
- Work Scope: Assigned at intake/bid phase
- Risk: PM identifies during kickoff and weekly reviews
- Complexity: Lead Craftsman (Miguel) during design review

**Q: What if we need a tag that's not in this system?**
**A:** Document the use case, track how often the need arises, and add to system after 5+ instances. Prevents tag proliferation.

**Q: How do tags differ from custom fields?**
**A:** Tags are multi-select, visual, and flexible. Custom fields are single-value, structured data. Use custom fields for data you'll report on (dates, numbers, dropdowns). Use tags for multi-dimensional categorization.

---

## References & Research Sources

- **Eisenhower Matrix:** Urgency vs. Important prioritization framework
- **ERP Best Practices:** Construction/manufacturing project tagging systems
- **TCS Persona Documentation:** Pain points from Bryan, David, Miguel
- **Project Lifecycle Workflow:** 12-phase TCS workflow analysis
- **Color Psychology:** Visual design for dashboard scanning
- **FilamentPHP v3:** Tag implementation patterns and filtering capabilities

---

## Revision History

| Date | Version | Changes | Author |
|------|---------|---------|--------|
| 2025-10-03 | 1.0 | Initial recommendation document | Claude Code |

---

**Next Steps:**
1. Review this document with Bryan (owner) for strategic alignment
2. Validate work scope tags with David (PM) and Miguel (craftsman)
3. Implement Phase 1 tags (Priority, Health, Risk)
4. Train team on tagging best practices
5. Set up Bryan's daily dashboard filters
6. Monitor usage for 30 days and refine

