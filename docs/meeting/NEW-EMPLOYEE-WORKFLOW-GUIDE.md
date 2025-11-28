# New Employee Workflow Guide
## Understanding TCS Woodwork's Complete Process

**Welcome to TCS Woodwork!** This guide explains how custom cabinet projects flow through our company from start to finish, and where YOU fit into the process.

---

## 🏢 The Big Picture - How Projects Flow

```mermaid
flowchart TD
    Start([Customer Wants<br/>Custom Cabinets]) --> Phase1[📋 DISCOVERY & DESIGN<br/>Bryan meets customer,<br/>creates design]

    Phase1 --> Phase2[📐 DETAILING<br/>Aiden creates specs<br/>& job cards]

    Phase2 --> Phase3[📦 SOURCING<br/>Aiden/Sadie get<br/>materials]

    Phase3 --> Phase4[🔨 PRODUCTION<br/>YOU build cabinets!<br/>Levi/Shaggy lead]

    Phase4 --> Phase5[🎨 FINISHING<br/>Stain/Paint applied]

    Phase5 --> Phase6[🚚 DELIVERY<br/>Ship to customer]

    Phase6 --> Phase7[🏠 INSTALLATION<br/>Chase installs on-site]

    Phase7 --> End([Happy Customer!<br/>Project Complete])

    style Phase1 fill:#e1f5ff
    style Phase2 fill:#fff4e1
    style Phase3 fill:#e1ffe1
    style Phase4 fill:#ffe1e1
    style Phase5 fill:#f5e1ff
    style Phase6 fill:#e1ffff
    style Phase7 fill:#f5ffe1
    style End fill:#FFD700
```

---

## 👥 Who Does What? - Meet Your Team

```mermaid
graph TD
    subgraph Office["🏢 OFFICE TEAM"]
        Bryan[👔 BRYAN<br/>Operations Manager<br/>• Designs in Rhino<br/>• Meets customers<br/>• Assigns your work]

        Aiden[📋 AIDEN<br/>Detailer/Warehouse/QC<br/>• Creates specs<br/>• Manages materials<br/>• Inspects quality]

        Sadie[💰 SADIE<br/>Inventory/Purchasing<br/>• Orders materials<br/>• Tracks budget<br/>• Manages vendors]
    end

    subgraph Shop["🔨 SHOP FLOOR"]
        Levi[👷 LEVI<br/>Production Lead<br/>• Assigns tasks<br/>• Builds cabinets<br/>• Trains crew]

        Shaggy[👷 SHAGGY<br/>Production Lead<br/>• Assigns tasks<br/>• Builds cabinets<br/>• Trains crew]

        You[⭐ YOU<br/>Production Helper<br/>• Build cabinets<br/>• Learn skills<br/>• Follow job cards]

        Dagger[🖥️ DAGGER<br/>CNC Operator<br/>• Programs CNC<br/>• Cuts parts<br/>• Preps materials]
    end

    subgraph Field["🏗️ FIELD TEAM"]
        Chase[🔧 CHASE<br/>Installation Lead<br/>• Delivers cabinets<br/>• Installs on-site<br/>• Final customer contact]
    end

    Bryan -.->|Assigns work| Levi
    Bryan -.->|Assigns work| Shaggy
    Levi -->|Gives tasks to| You
    Shaggy -->|Gives tasks to| You
    Aiden -.->|Prepares materials for| You
    Aiden -.->|Inspects work from| You
    Dagger -.->|Cuts parts for| You

    style You fill:#FFD700
```

---

## 📅 Your Daily Work - What to Expect

```mermaid
flowchart TD
    Morning[🌅 START YOUR DAY<br/>8:00 AM] --> Check[Check In with<br/>Levi or Shaggy]

    Check --> Receive[Receive Your<br/>Task Assignment]

    Receive --> WhatTask{What Type<br/>of Task?}

    WhatTask -->|New to you| Learn[📚 LEARNING MODE<br/>1. Watch lead demonstrate<br/>2. Ask questions<br/>3. Take notes]

    WhatTask -->|You know this| Work[🔨 WORKING MODE<br/>1. Get job card<br/>2. Gather materials<br/>3. Start building]

    Learn --> DoWithHelp[Practice with<br/>Supervision]
    DoWithHelp --> Document[Write procedure<br/>on task card]
    Document --> Review[End of day review<br/>Explain back]

    Work --> GetMaterials[Get Materials<br/>Check job card for location]
    GetMaterials --> Build[Build According<br/>to Specifications]
    Build --> SelfCheck[❗ CHECK YOUR WORK<br/>Does it match specs?<br/>Is quality good?]

    SelfCheck --> Good{Looks<br/>Good?}
    Good -->|No| Fix[Fix Issues<br/>Ask for help if needed]
    Fix --> SelfCheck

    Good -->|Yes| UpdateStatus[Update Task Status<br/>Mark complete in system]

    Review --> NextTask[Ready for<br/>Next Task Tomorrow]
    UpdateStatus --> NextTask

    NextTask --> EndDay[🌙 END OF DAY<br/>4:30 PM<br/>Clean workspace]

    style Learn fill:#e1f5ff
    style Work fill:#ffe1e1
    style SelfCheck fill:#FFB6C1
    style NextTask fill:#90EE90
```

---

## 📋 Understanding Your Job Card

**What is it?** A packet of information telling you exactly what to build and how.

```mermaid
flowchart TD
    JobCard[📋 YOUR JOB CARD] --> Section1[1️⃣ COVER SHEET<br/>What job is this?<br/>Who assigned it?<br/>How much time?]

    JobCard --> Section2[2️⃣ SPECIFICATIONS<br/>What size?<br/>What materials?<br/>What hardware?]

    JobCard --> Section3[3️⃣ DRAWINGS<br/>Pictures showing<br/>how it should look]

    JobCard --> Section4[4️⃣ CHECKLIST<br/>Step-by-step tasks<br/>Check off as you go]

    Section1 --> Read[📖 READ EVERYTHING<br/>Before You Start!]
    Section2 --> Read
    Section3 --> Read
    Section4 --> Read

    Read --> Questions{Do you<br/>understand<br/>everything?}

    Questions -->|No| Ask[❓ ASK LEVI/SHAGGY<br/>NEVER guess!]
    Ask --> Read

    Questions -->|Yes| Start[✅ Start Building<br/>Follow checklist]

    Start --> CheckOff[✓ Check off each step<br/>as you complete it]

    style JobCard fill:#FFD700
    style Questions fill:#FFB6C1
    style Ask fill:#90EE90
```

---

## 🔨 Production Process - Your Role in Detail

```mermaid
flowchart TD
    Assignment[You Receive<br/>Cabinet Run Assignment] --> Materials[Step 1:<br/>GATHER MATERIALS]

    Materials --> FindMat[Check job card for<br/>material locations]
    FindMat --> GetSheets[Get sheet goods<br/>from labeled bay]
    FindMat --> GetHardware[Get hardware from<br/>job-specific box]
    FindMat --> GetLumber[Get lumber if needed]

    GetSheets --> Verify[✓ Verify you have<br/>everything on list]
    GetHardware --> Verify
    GetLumber --> Verify

    Verify --> AllThere{Everything<br/>there?}
    AllThere -->|No| TellAiden[Tell Aiden<br/>something is missing]
    TellAiden --> WaitMaterial[Wait for materials<br/>Work on different task]

    AllThere -->|Yes| CNCCheck{Does job<br/>need CNC<br/>parts?}

    CNCCheck -->|Yes| WaitDagger[Wait for Dagger<br/>to cut CNC parts]
    WaitDagger --> DaggerCuts[Dagger cuts parts<br/>on CNC machine]
    DaggerCuts --> AidenSorts[Aiden sorts &<br/>labels parts for you]
    AidenSorts --> GetCNCParts[Collect your<br/>CNC parts]

    CNCCheck -->|No| StartBuild[Step 2:<br/>START BUILDING]
    GetCNCParts --> StartBuild

    StartBuild --> BuildBox[Build Cabinet Box<br/>Follow dimensions]
    BuildBox --> FaceFrame[Build & Attach<br/>Face Frame]
    FaceFrame --> Doors[Install Doors<br/>with hinges]
    Doors --> Drawers[Install Drawer Boxes<br/>& Slides]
    Drawers --> Shelves[Install Shelves<br/>Adjustable or fixed]
    Shelves --> Hardware[Install All Hardware<br/>Knobs, pulls, etc.]

    Hardware --> SelfQC[Step 3:<br/>SELF-CHECK QUALITY]

    SelfQC --> CheckList{Check ALL:<br/>□ Dimensions correct?<br/>□ Square & level?<br/>□ Smooth finish?<br/>□ Hardware works?<br/>□ Doors/drawers open smoothly?}

    CheckList -->|Something wrong| FixIt[Fix the issue<br/>Ask for help if needed]
    FixIt --> SelfQC

    CheckList -->|All good!| CallAiden[Step 4:<br/>Call Aiden for<br/>QC INSPECTION #1]

    CallAiden --> AidenInspects[Aiden Inspects<br/>Your Work]

    AidenInspects --> AidenDecision{Aiden<br/>Approves?}

    AidenDecision -->|No| AidenExplains[Aiden explains<br/>what needs fixing]
    AidenExplains --> FixIt

    AidenDecision -->|Yes| Approved[✅ APPROVED!<br/>Good job!]

    Approved --> UpdateSystem[Update task status<br/>Mark as complete]
    UpdateSystem --> ToFinishing[Cabinet goes to<br/>Finishing department]

    ToFinishing --> YourDone[✨ YOU'RE DONE<br/>WITH THIS TASK!]

    YourDone --> NextAssignment[Ready for next<br/>assignment from<br/>Levi/Shaggy]

    style SelfQC fill:#FFB6C1
    style CallAiden fill:#FFD700
    style Approved fill:#90EE90
    style YourDone fill:#90EE90
```

---

## 🎓 Learning Mode - How Training Works

```mermaid
flowchart TD
    NewTask[New Task You've<br/>Never Done Before] --> Safety[⚠️ SAFETY FIRST<br/>What PPE needed?<br/>What dangers exist?]

    Safety --> Step1[STEP 1: WATCH<br/>Levi/Shaggy demonstrates<br/>📹 May record for reference]

    Step1 --> Questions1[Ask Questions:<br/>• Why do we do this?<br/>• What could go wrong?<br/>• What's the trick?]

    Questions1 --> Step2[STEP 2: DO TOGETHER<br/>You perform task<br/>Lead supervises closely]

    Step2 --> Feedback[Get feedback:<br/>• What went well?<br/>• What to improve?<br/>• Tips & tricks]

    Feedback --> Step3[STEP 3: DOCUMENT<br/>✏️ YOU write the procedure<br/>on a task card]

    Step3 --> Write[Write down:<br/>1. Tools needed<br/>2. Materials needed<br/>3. Step-by-step process<br/>4. Quality checks<br/>5. Common mistakes]

    Write --> Step4[STEP 4: EXPLAIN BACK<br/>End of day:<br/>Tell lead what you learned]

    Step4 --> LeadCheck{Lead confirms<br/>you understand?}

    LeadCheck -->|Not quite| Clarify[Lead clarifies<br/>confusing parts]
    Clarify --> Step4

    LeadCheck -->|Yes!| Practice[STEP 5: PRACTICE<br/>Do task again<br/>on your own<br/>with less supervision]

    Practice --> Multiple[Repeat several times<br/>until comfortable]

    Multiple --> Mastered[✅ SKILL MASTERED!<br/>Add to your skillset]

    Mastered --> SaveDoc[Your written procedure<br/>saved as reference<br/>for future workers]

    style Safety fill:#FFB6C1
    style Step3 fill:#FFD700
    style Mastered fill:#90EE90
```

---

## 🎯 Quality Standards - What "Good" Looks Like

```mermaid
flowchart TD
    Quality[🎯 QUALITY CHECK] --> Dimension[📏 DIMENSIONS<br/>Must match specs<br/>within 1/16 inch]

    Quality --> Square[📐 SQUARE & LEVEL<br/>Check with square<br/>No wobble or twist]

    Quality --> Smooth[✨ SMOOTHNESS<br/>No splinters<br/>No rough edges<br/>Sand properly]

    Quality --> Joints[🔗 JOINTS<br/>Tight fit<br/>No gaps<br/>Glue properly applied]

    Quality --> Hardware[🔧 HARDWARE<br/>Works smoothly<br/>Properly aligned<br/>All screws tight]

    Quality --> Finish[🎨 FINISH READY<br/>Clean surface<br/>No glue marks<br/>No scratches]

    Dimension --> Pass1{Meets<br/>standard?}
    Square --> Pass2{Meets<br/>standard?}
    Smooth --> Pass3{Meets<br/>standard?}
    Joints --> Pass4{Meets<br/>standard?}
    Hardware --> Pass5{Meets<br/>standard?}
    Finish --> Pass6{Meets<br/>standard?}

    Pass1 -->|No| Fix1[Measure again<br/>Trim if needed<br/>Ask for help]
    Pass2 -->|No| Fix2[Adjust clamps<br/>Re-square<br/>Ask for help]
    Pass3 -->|No| Fix3[Sand more<br/>Check edges<br/>Ask for help]
    Pass4 -->|No| Fix4[Reglue<br/>Reclamp<br/>Ask for help]
    Pass5 -->|No| Fix5[Adjust mounting<br/>Tighten screws<br/>Ask for help]
    Pass6 -->|No| Fix6[Clean up<br/>Remove marks<br/>Ask for help]

    Pass1 -->|Yes| AllGood[✅ ALL CHECKS PASS]
    Pass2 -->|Yes| AllGood
    Pass3 -->|Yes| AllGood
    Pass4 -->|Yes| AllGood
    Pass5 -->|Yes| AllGood
    Pass6 -->|Yes| AllGood

    Fix1 --> Recheck[Re-check<br/>everything]
    Fix2 --> Recheck
    Fix3 --> Recheck
    Fix4 --> Recheck
    Fix5 --> Recheck
    Fix6 --> Recheck

    Recheck --> Quality

    AllGood --> ReadyQC[Ready for<br/>Aiden's QC<br/>Inspection]

    style AllGood fill:#90EE90
    style ReadyQC fill:#FFD700
```

---

## 📦 Material Flow - Where to Find Things

```mermaid
flowchart TD
    NeedMaterials[Need Materials<br/>for Your Job] --> CheckJobCard[Check Job Card<br/>for Material List]

    CheckJobCard --> BOM[Look at BOM<br/>Bill of Materials<br/>Shows location of each item]

    BOM --> ItemType{What Type<br/>of Material?}

    ItemType -->|Hardware| HardwareLoc[🔩 HARDWARE<br/>Location: Job-specific box<br/>Look for job number label]

    ItemType -->|Sheet Goods| SheetLoc[📋 SHEET GOODS<br/>Location: Vertical racks<br/>Look for job number on edge]

    ItemType -->|Lumber| LumberLoc[🪵 LUMBER<br/>Location: Horizontal storage<br/>Look for job tag]

    ItemType -->|Supplies| SupplyLoc[🧰 SUPPLIES<br/>Location: Shop storage<br/>Screws, glue, sandpaper, etc.]

    HardwareLoc --> GetItem[Get What You Need<br/>from Location]
    SheetLoc --> GetItem
    LumberLoc --> GetItem
    SupplyLoc --> GetItem

    GetItem --> VerifyItem[✓ Verify:<br/>Correct item?<br/>Right quantity?<br/>Good condition?]

    VerifyItem --> Problem{Any<br/>Issues?}

    Problem -->|Wrong item| TellAiden1[Tell Aiden<br/>Wrong material]
    Problem -->|Missing| TellAiden2[Tell Aiden<br/>Material missing]
    Problem -->|Damaged| TellAiden3[Tell Aiden<br/>Material damaged]

    TellAiden1 --> AidenFixes[Aiden resolves<br/>issue]
    TellAiden2 --> AidenFixes
    TellAiden3 --> AidenFixes

    AidenFixes --> GetItem

    Problem -->|All good| PutInWorkspace[Move materials to<br/>your workspace]

    PutInWorkspace --> Organize[Organize materials<br/>Keep workspace neat]

    Organize --> ReadyToBuild[✅ Ready to Build!]

    style ReadyToBuild fill:#90EE90
```

---

## ⚠️ Important Rules - Never Skip These!

```mermaid
graph TD
    Rules[⚠️ CRITICAL RULES] --> Rule1[1️⃣ SAFETY FIRST<br/>Always wear PPE<br/>Never skip safety steps<br/>If unsure, ASK!]

    Rules --> Rule2[2️⃣ CHECK YOUR WORK<br/>Self-inspect before QC<br/>Catch your own mistakes<br/>Take pride in quality]

    Rules --> Rule3[3️⃣ FOLLOW THE JOB CARD<br/>Don't guess or improvise<br/>If specs unclear, ASK<br/>Don't 'wing it']

    Rules --> Rule4[4️⃣ UPDATE STATUS<br/>Mark tasks complete<br/>Track your progress<br/>System needs to know]

    Rules --> Rule5[5️⃣ COMMUNICATE ISSUES<br/>Materials missing? Tell Aiden<br/>Don't understand? Ask lead<br/>Mistake made? Speak up early]

    Rules --> Rule6[6️⃣ KEEP WORKSPACE CLEAN<br/>Clean as you go<br/>Return tools<br/>End of day cleanup]

    Rules --> Rule7[7️⃣ ASK QUESTIONS<br/>No question is stupid<br/>Better to ask than guess<br/>We want you to succeed!]

    Rule1 --> Remember[🎯 REMEMBER:<br/>Quality cabinets<br/>come from<br/>quality work!]
    Rule2 --> Remember
    Rule3 --> Remember
    Rule4 --> Remember
    Rule5 --> Remember
    Rule6 --> Remember
    Rule7 --> Remember

    style Rules fill:#FFB6C1
    style Remember fill:#FFD700
```

---

## 🚀 Your Growth Path - Week by Week

```mermaid
flowchart TD
    Week1[WEEK 1<br/>🌱 Foundation] --> W1Tasks[Learn Basic Tasks:<br/>• Floating shelves<br/>• Simple cuts<br/>• Edge banding<br/>• Tool safety]

    Week1 --> W1Goal[Goal: Understand<br/>shop layout & safety<br/>Complete 3-4 simple tasks]

    W1Tasks --> Week2[WEEK 2<br/>🔨 Building Skills]
    W1Goal --> Week2

    Week2 --> W2Tasks[Learn Building:<br/>• Cabinet assembly<br/>• Face frames<br/>• Glue-ups<br/>• Basic joinery]

    Week2 --> W2Goal[Goal: Build first<br/>complete cabinet<br/>with supervision]

    W2Tasks --> Week3[WEEK 3<br/>⚙️ Refinement]
    W2Goal --> Week3

    Week3 --> W3Tasks[Learn Details:<br/>• Drawer installation<br/>• Door hanging<br/>• Hardware mounting<br/>• Final adjustments]

    Week3 --> W3Goal[Goal: Complete job<br/>independently<br/>Pass QC consistently]

    W3Tasks --> Evaluation[END OF TRIAL<br/>📊 Evaluation]
    W3Goal --> Evaluation

    Evaluation --> Assess{Performance<br/>Assessment}

    Assess -->|Excellent| Permanent[✅ PERMANENT POSITION<br/>Welcome to the team!<br/>Keep growing skills]

    Assess -->|Needs Work| Decision{Can improve<br/>with more time?}

    Decision -->|Yes| Extension[Trial Extension<br/>1-2 more weeks<br/>Focus on weak areas]
    Decision -->|No| NotFit[Not the right fit<br/>Part ways respectfully]

    Extension --> Evaluation

    Permanent --> Month2[MONTH 2+<br/>🌟 Mastery]

    Month2 --> Advanced[Learn Advanced:<br/>• Complex joinery<br/>• Crown molding<br/>• Special hardware<br/>• Lead small jobs]

    Advanced --> Future[FUTURE GROWTH<br/>🎯 Possible paths:<br/>• Team lead<br/>• Specialty skills<br/>• Training others<br/>• More responsibility]

    style Week1 fill:#e1f5ff
    style Week2 fill:#fff4e1
    style Week3 fill:#ffe1e1
    style Permanent fill:#90EE90
    style Future fill:#FFD700
```

---

## 💬 Key Phrases You'll Hear

| Phrase | What It Means |
|--------|---------------|
| **"BOM"** | Bill of Materials - list of everything needed for a job |
| **"Job Card"** | Your instruction packet for a task |
| **"Linear Feet"** | How we measure and price cabinet runs (width of cabinets) |
| **"Face Frame"** | The front frame that doors attach to |
| **"Cabinet Run"** | A group of cabinets along one wall or area |
| **"QC"** | Quality Control - Aiden inspects your work |
| **"CNC"** | Computer-controlled cutting machine (Dagger runs it) |
| **"Finalized BOM"** | Materials list with warehouse locations added |
| **"Allocated"** | Materials assigned to a specific job (don't touch!) |
| **"Job Box"** | Box containing hardware for a specific job |
| **"Rhino"** | Design software Bryan uses |
| **"Chase job"** | Work going out to installation soon |

---

## 📞 Who to Ask When...

```mermaid
graph TD
    Question[I Have a Question] --> Type{What Type?}

    Type -->|How to do a task| AskLead[Ask Levi or Shaggy<br/>Your team leads]

    Type -->|Material missing| AskAiden[Ask Aiden<br/>Warehouse manager]

    Type -->|Tool broken/missing| AskLead2[Ask Levi or Shaggy<br/>They'll handle it]

    Type -->|Design unclear| AskBryan[Ask Bryan<br/>Through Levi/Shaggy]

    Type -->|Timesheets/pay| AskAndrew[Ask Andrew<br/>Systems/admin]

    Type -->|Safety concern| AskAnyone[Tell anyone immediately!<br/>Safety is priority #1]

    Type -->|Job card confusing| AskLead3[Ask Levi or Shaggy<br/>They'll clarify]

    style AskAnyone fill:#FFB6C1
```

---

## 🎯 Success Tips - How to Excel

### DO ✅
- ✅ Ask questions when unsure
- ✅ Check your work before QC
- ✅ Keep your workspace organized
- ✅ Follow job cards exactly
- ✅ Communicate problems early
- ✅ Take notes on new skills
- ✅ Show up on time
- ✅ Be willing to learn
- ✅ Take pride in quality
- ✅ Help keep shop clean

### DON'T ❌
- ❌ Guess when you don't know
- ❌ Skip safety equipment
- ❌ Rush through work
- ❌ Hide mistakes
- ❌ Use materials from other jobs
- ❌ Leave workspace messy
- ❌ Ignore quality issues
- ❌ Be afraid to ask for help
- ❌ Take shortcuts
- ❌ Forget to update task status

---

## 📚 Your First Day Checklist

- [ ] Tour of shop (where everything is)
- [ ] Safety equipment location
- [ ] Timesheet system explained
- [ ] Introduction to job cards
- [ ] Meet all team members
- [ ] Learn shop cleanup routine
- [ ] Practice first simple task
- [ ] Write your first procedure
- [ ] End-of-day review with lead
- [ ] Questions answered

---

**Remember:** Everyone started where you are now. We want you to succeed! The key is asking questions, following procedures, and taking pride in your work.

**Good luck and welcome to the team! 🎉**

---

**Document Created:** November 21, 2025
**For:** New Production Team Members
**Your Success = Our Success!**
