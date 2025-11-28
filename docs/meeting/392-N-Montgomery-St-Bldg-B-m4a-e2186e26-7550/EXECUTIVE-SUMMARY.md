# Executive Summary - 1 Page
## 392 N Montgomery St Building B Workflow Meeting

**Date:** November 21, 2025 | **Duration:** 3h 19m | **Participants:** Bryan & Andrew

---

## 🎯 MISSION
Build ERP system to automate TCS Woodwork cabinet manufacturing from customer inquiry through installation, enabling Bryan to delegate effectively.

---

## 👥 NEW TEAM STRUCTURE

| Person | Role | Hours | Start |
|--------|------|-------|-------|
| **Aiden** | Detailer + Warehouse + QC | 2-3 days/week | When recovered |
| **Sadie** | Inventory + Purchasing + Finance | 2-4 hrs/week | Week 2 |
| **Alina** | Production Helper | 8am-4:30pm | Monday (trial) |
| **Dagger** | CNC Programming/Operation | Full-time | Current |
| **Chase** | Installation Lead | Full-time | Current |

---

## 📋 CORE WORKFLOWS DEFINED

### Inventory Flow
```
BOM → Check Stock → Allocate → Purchase Request →
Bryan Approves → Sadie Orders → Receive → Label →
Finalized BOM (with locations)
```

### Production Flow
```
Job Card → CNC Parts → Assembly → QC #1 →
Finishing → QC #2 → Delivery → Installation
```

### Weekly Cycle
- **Friday:** Aiden checks inventory
- **Monday:** Bryan approves purchase orders
- **Wednesday:** Materials delivered

---

## 🏗️ SYSTEM ARCHITECTURE

### Data Hierarchy
```
PROJECT
└── ROOM (Kitchen, Bathroom)
    └── ROOM LOCATION (Wall, Island)
        └── CABINET RUN (Base, Upper, Full-height)
            └── CABINET (Individual unit)
                └── COMPONENTS (Doors, Drawers, Shelves)
```

### Critical Data Needed
✅ Cabinet dimensions (H × W × D)
✅ Face frame type & dimensions
✅ Door specs (profile, dimensions, hardware)
✅ Drawer specs (type, hardware, depth offset)
✅ Shelf configuration
✅ Materials & finish selections

---

## 💰 LINEAR FEET PRICING

| Type | Formula | Example |
|------|---------|---------|
| Base/Upper | 1x width | 6' run = 6 LF |
| Full-Height | 2x width | 6' run = 12 LF |
| Appliance w/ panels | Counts | Dishwasher = counts |
| Appliance bare | Doesn't count | Fridge = doesn't count |

**Purpose:** Bidding & team payment, not material costing

---

## 📦 JOB CARD BUNDLE

Production receives:
1. Cabinet specs (dimensions, materials, hardware)
2. PDF drawings (Rhino snapshots, face frames, doors)
3. CNC cut lists
4. Task checklist with QC points
5. Assignment & linear feet allocation

---

## ⚡ IMMEDIATE PRIORITIES

### Monday (This Week)
- **10am-12pm:** Andrew orients Alina (workflow, systems, timesheets)
- **12pm+:** Bryan trains Alina (floating shelves, crown molding, etc.)
- **Afternoon:** Bryan specs Sankity project for system testing

### Week 1-2 (Andrew Builds)
1. Hierarchical project structure
2. Cabinet specification forms
3. BOM auto-generation
4. Job card system
5. Linear feet calculation

### Week 2 (Sadie Starts)
- Thursday orientation
- First task: Collect all vendor information
- Setup weekly PO review meetings (Mondays)

---

## ⚠️ CRITICAL CHALLENGES

### Bryan's Concerns
- ❌ "Don't over-complicate it"
- ❌ Worried about form-filling burden
- ❌ Wants to stay in Rhino workflow
- ❌ Questions long-term ROI of detailed system

### Andrew's Needs
- ✅ Complete data for automation
- ✅ Missing details = future rework
- ✅ Others need documented knowledge
- ✅ Building for scalability

### Compromise
Test with Sankity project → Iterate → Trim unnecessary complexity

---

## 🎓 TRAINING PHILOSOPHY

**Method:** Watch → Do → Document
1. Bryan demonstrates
2. Trainee performs
3. **Trainee writes procedure** (reinforcement)
4. Voice record session
5. End-of-day review (explain back)

---

## ✅ SUCCESS METRICS

| Metric | Current | Target |
|--------|---------|--------|
| Bryan's workload | 100% | 60% (delegation working) |
| Project delays | Frequent | Rare (materials ready) |
| Quality issues | Variable | Consistent (QC system) |
| Missing handoff info | Common | None (complete job cards) |
| Profitability | Baseline | Maintained or improved |

---

## 🚀 30/60/90 DAY PLAN

### 30 Days
✅ Alina trained on fundamentals
✅ Aiden onboarded (detailing + warehouse)
✅ Sadie processing purchase orders
✅ Sankity project tests ERP cabinet specs
✅ Inventory tracking operational

### 60 Days
✅ Job card system in production
✅ Weekly PO meetings routine
✅ Bay organization system working
✅ QC checkpoints established
✅ Training documentation library started

### 90 Days
✅ Full workflow live (discovery → install)
✅ Task tracking & project status updates
✅ Linear feet payment system
✅ Team self-managing with minimal Bryan intervention

---

## 💡 KEY INSIGHT

> **The Bottleneck is Bryan**
>
> System success = Bryan delegating effectively through:
> - Complete job cards (no missing info)
> - Clear task assignments (linear feet based)
> - Automated BOM generation (reduces manual work)
> - Standardized procedures (others can execute)

---

## 📊 BY THE NUMBERS

- **4,227** discussion points in 3h 19m
- **10** distinct topics identified
- **329** topic segments
- **310** action items extracted
- **13** entities tracked (people/tools/projects)
- **2** participants (Bryan 28%, Andrew 72%)

---

**Bottom Line:** Build detailed cabinet spec system → Auto-generate job cards & BOMs → Enable delegation → Reduce Bryan's workload → Scale business

**Next Review:** After Sankity project testing (Week 2)
