# Key Points Summary
## 392 N Montgomery St Building B - Workflow Planning Meeting

**Duration:** ~3 hours 19 minutes
**Participants:** Bryan (Operations Manager) & Andrew (Systems Consultant)
**Total Discussion Points:** 4,227 statements

---

## 🎯 PRIMARY OBJECTIVES

### System Being Built
- **Custom ERP system** for TCS Woodwork cabinet manufacturing
- **Hierarchical structure**: Project → Room → Room Location → Cabinet Run → Cabinet → Components
- Focus on **workflow automation** from discovery through installation

### People & Roles Defined
1. **Aiden** - Detailer + Warehouse Manager + QC (2-3 days/week)
2. **Sadie** - Inventory + Purchasing + Financial Tracking (2-4 hours/week)
3. **Alina** - Production Helper (3-week trial starting Monday)
4. **Dagger** - CNC Programming/Operation
5. **Chase** - Installation Lead
6. **Levi & Shaggy** - Production Team Leads

---

## 📋 CRITICAL WORKFLOWS ESTABLISHED

### 1. Inventory & Sourcing (Aiden/Sadie)
```
Receive BOM → Check Inventory → Allocate Available Items →
Generate Purchase Requests → Bryan Approves → Sadie Orders →
Receive & Label Materials → Update Finalized BOM with Locations
```

**Key Rules:**
- Hardware goes in job-specific boxes (allocated = untouchable)
- Sheet goods labeled with job ID
- Bulk stock: Pre-finished plywood only
- Weekly cycle: Friday inventory → Monday approval → Wednesday delivery

### 2. Production Assignment (Bryan → Levi/Shaggy)
```
Bryan assigns Cabinet Runs → Team leads sub-delegate tasks →
Workers use Job Cards → Update status → Complete & QC
```

**Payment:** Based on linear feet completed

### 3. Quality Control (Aiden - 2 checkpoints)
- **QC #1:** After production, before finishing
- **QC #2:** After finishing, before delivery

---

## 🏗️ CABINET SPECIFICATION SYSTEM

### Hierarchy Required
```
CABINET RUN (base/upper/full-height)
├── Linear Feet Auto-calculation
├── Default Materials/Hardware (cascade down)
├── Countertop specs (if base)
└── INDIVIDUAL CABINETS
    ├── Overall Dimensions
    ├── Face Frame Type
    ├── End Panels
    ├── Toe Kick
    └── COMPONENTS
        ├── DOORS (profile, dimensions, hinges, hardware)
        ├── DRAWERS (face type, box type, hardware, depth offset)
        └── SHELVES (adjustable/fixed/pullout)
```

### Critical Fields Andrew Needs
- **Every detail** must be specced out for automation
- Face frame dimensions (overall + openings)
- Door dimensions (width, height, rail/stile widths, check rails)
- Drawer specs (face dimensions, box type, hardware offset)
- Hardware selections with offset calculations

---

## 💰 LINEAR FEET PRICING METHOD

### Formula
- **Base Cabinets:** 1x the width (6' run = 6 linear feet)
- **Upper Cabinets:** 1x the width (6' run = 6 linear feet)
- **Full-Height:** 2x the width (6' run = 12 linear feet)
- **Appliances with panels:** Count as linear feet
- **Appliances without panels:** Don't count

### Purpose
- For **pricing bids**, not material costing
- "Averages out in the wash"
- Used for team payment allocation

---

## 📦 JOB CARD BUNDLE CONTENTS

What production receives:
1. **Cover sheet** - Job info, assignment, linear feet, dates
2. **Specifications** - Cabinet dimensions, materials, hardware, finish
3. **Visual PDFs** - Rhino snapshots, face frame drawings, door dimensions
4. **Task breakdown** - Step-by-step checklist with QC points
5. **CNC cut list** - Parts list with sheet layouts (if applicable)

---

## 🎓 TRAINING SYSTEM (Alina Example)

### Methodology: **Watch → Do → Document**
1. Bryan demonstrates task
2. Trainee performs with supervision
3. **Trainee documents steps** on task card (reinforcement)
4. Voice record session
5. End-of-day review (trainee explains back)

### Alina's 3-Week Trial Tasks
- Floating shelves
- Crown molding
- 2x2 blocks for pull-outs
- 3" cabinet rips
- Edge banding
- Cabinet assembly
- Glue-ups
- Domino/Lamello joinery
- Dado cutting
- OG trim routing

**Schedule:** 8am-4:30pm, Monday orientation with Andrew (10am-12pm), then Bryan

---

## 🔧 TECHNICAL REQUIREMENTS

### Rhino Integration Challenge
**Bryan's Preference:** One massive Rhino file per project
**Andrew's Need:** Extract individual cabinet data for system
**Compromise Discussed:** Script to auto-extract by labels

### PDF Generation Needed
- Face frame dimension drawings (manual upload)
- Door dimension drawings (manual upload)
- Cabinet detail PDFs (from Rhino)
- Cut lists with visual reference

### Hardware System (Blum)
- Drawer slides: 5/16" offset per side
- Auto-calculate drawer box = opening - hardware offset
- Track hardware specs for inventory

---

## ⚠️ CRITICAL GAPS IDENTIFIED

### Documentation
- ❌ Proposals not saved to project folders
- ❌ No job specification document at project start
- ❌ No QC checklist (what = good quality?)
- ❌ No formal job numbering system

### Process
- ❌ No systematic inventory tracking (staff pulled to side jobs)
- ❌ Change orders handled ad-hoc (need sub-project system)
- ❌ File organization inconsistent

### Financial
- ❌ No deposit tracking system
- ❌ No consumables budget/ledger
- ❌ Purchase approval workflow informal

---

## 📅 IMMEDIATE ACTION ITEMS

### This Weekend → Monday
**Andrew:**
- ✅ Complete Sankity project spec
- ✅ Prepare Alina orientation (2 hours, 10am-12pm Monday)
- ✅ Employee task card templates

**Bryan:**
- Print task cards for Alina
- Be available 8-10am Monday (Alina intro)
- 12pm+ Monday (skills training)

### Week 1
- Alina training (floating shelves, crown, etc.)
- Test ERP cabinet spec with Sankity project
- Sadie orientation (week 2, Thursday)

### Next 2 Weeks
**Andrew builds:**
1. Hierarchical project structure in ERP
2. Cabinet specification forms (all fields defined)
3. BOM generation system
4. Job card generation
5. Linear feet auto-calculation

### When Aiden Returns
- Orientation with Andrew
- Training with Bryan on reading design files
- Learn Rhino basics
- Warehouse organization system
- QC training

---

## 💡 DESIGN PHILOSOPHY TENSIONS

### Bryan's View
- "Don't need it as complicated as a professional business"
- Worried about over-engineering
- Prefers simple, practical solutions
- Wants to stay in Rhino workflow
- "Serious aversion" to form-filling

### Andrew's Counter
- Need complete data for automation
- "Every little thing counts" (Blue M&Ms story)
- Missing details now = rework later
- Building for scalability
- Others don't have Bryan's embedded knowledge

### Resolution Approach
- Iterate and refine
- Start comprehensive, trim what's unnecessary
- Test with real projects (Sankity)
- "Okay to disagree and agree on things"

---

## 🎯 SUCCESS METRICS

### System Working When:
✅ Bryan's workload reduced (effective delegation)
✅ Project handoffs smooth (no missing info)
✅ Materials available when needed (no delays)
✅ Quality consistent (QC system working)
✅ Profitability maintained or improved

---

## 🔑 KEY PRINCIPLE

> **"Watch → Do → Document"**
>
> For all new processes and training - ensures knowledge transfer and continuous improvement

---

## 📊 TOPICS DISCUSSED (Most Mentioned)

1. **Cabinet Specifications** - 84 mentions
2. **Production Workflows** - 54 mentions
3. **Design Process** - 53 mentions
4. **Inventory Management** - 44 mentions
5. **Sourcing/Purchasing** - 38 mentions
6. **Workflow Pipeline** - 17 mentions
7. **Training Methods** - 13 mentions
8. **Delivery/Installation** - 11 mentions
9. **Quality Control** - 10 mentions
10. **Pricing/Linear Feet** - 5 mentions

---

## 🚀 NEXT MILESTONES

### Immediate (Week 1)
- Alina starts training
- Sankity project spec testing

### Short-term (Weeks 2-4)
- Sadie onboards
- Aiden returns and trains
- Inventory system implemented
- Weekly PO approval meetings start

### Mid-term (Months 2-3)
- Full workflow implementation
- Task tracking live
- Job card system tested
- Training documentation library building

---

**Document Generated:** November 21, 2025
**Source:** 3hr 19min meeting transcript (4,227 statements)
**System Status:** ✅ Fully indexed and searchable
