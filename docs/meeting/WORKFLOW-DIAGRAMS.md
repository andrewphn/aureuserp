# TCS Woodwork ERP - Complete Workflow Diagrams

## 📋 Table of Contents
1. [Complete End-to-End Workflow](#complete-end-to-end-workflow)
2. [Discovery & Design Phase](#discovery--design-phase)
3. [Detailing Phase](#detailing-phase)
4. [Sourcing & Procurement](#sourcing--procurement)
5. [Production Workflow](#production-workflow)
6. [Quality Control & Delivery](#quality-control--delivery)
7. [Data Hierarchy Structure](#data-hierarchy-structure)
8. [Role Responsibilities](#role-responsibilities)
9. [Linear Feet Calculation](#linear-feet-calculation)
10. [Inventory Management](#inventory-management)

---

## Complete End-to-End Workflow

```mermaid
flowchart TD
    Start([Customer Inquiry]) --> Discovery[Discovery Phase]
    Discovery --> Design[Design Phase]
    Design --> Detailing[Detailing Phase]
    Detailing --> Sourcing[Sourcing/Procurement]
    Sourcing --> Production[Production]
    Production --> Finishing[Finishing]
    Finishing --> Delivery[Delivery]
    Delivery --> Install[Installation]
    Install --> End([Project Complete])

    Discovery -.->|Creates| JobFolder[Job Folder]
    Design -.->|Generates| RhinoFiles[Rhino Files]
    Detailing -.->|Produces| JobCard[Job Card Bundle]
    Sourcing -.->|Creates| FinalBOM[Finalized BOM]
    Production -.->|Updates| TaskStatus[Task Status]

    style Discovery fill:#e1f5ff
    style Design fill:#fff4e1
    style Detailing fill:#ffe1f5
    style Sourcing fill:#e1ffe1
    style Production fill:#ffe1e1
    style Finishing fill:#f5e1ff
    style Delivery fill:#e1ffff
    style Install fill:#f5ffe1
```

---

## Discovery & Design Phase

```mermaid
flowchart TD
    Inquiry[Customer Inquiry] --> Meeting[Initial Meeting<br/>Bryan + Client]
    Meeting --> SiteVisit[Site Visit & Measurements]
    SiteVisit --> Scope[Define Scope]

    Scope --> CreateFolder[Create Project Folder]
    CreateFolder --> AssignJobNum[Assign Job Number]

    Scope --> Proposal[Create Proposal<br/>Bryan + Andrew]
    Proposal --> SaveProposal[Save to Project Folder]
    SaveProposal --> SendClient[Send to Client]

    SendClient --> ClientDecision{Client<br/>Approves?}
    ClientDecision -->|Yes| Deposit[Collect 30% Deposit]
    ClientDecision -->|No| Revise[Revise Proposal]
    Revise --> SendClient

    Deposit --> StartDesign[Begin Design Phase]

    StartDesign --> RhinoModel[Create Rhino 3D Model<br/>Bryan]
    RhinoModel --> RoomLayout[Design Room Layout]
    RoomLayout --> CabinetDesign[Design Cabinets]
    CabinetDesign --> Materials[Select Materials<br/>Hardware, Finishes]

    Materials --> GeneratePDFs[Generate PDF Drawings]
    GeneratePDFs --> ExportDWG[Export DWG Files]
    ExportDWG --> InitialBOM[Create Initial BOM]

    InitialBOM --> DesignComplete[Design Complete]
    DesignComplete --> ToDetailing[Hand off to Detailing]

    style Deposit fill:#90EE90
    style DesignComplete fill:#FFD700
```

---

## Detailing Phase

```mermaid
flowchart TD
    Inputs[Detailing Inputs] --> ReviewFiles[Aiden Reviews<br/>Rhino & DWG Files]

    ReviewFiles --> CreateSpecs[Create Cabinet Specifications]
    CreateSpecs --> FaceFrame[Face Frame Dimensions]
    CreateSpecs --> DoorSpecs[Door Dimension Drawings]
    CreateSpecs --> DrawerSpecs[Drawer Specifications]

    FaceFrame --> UploadPDF1[Upload Face Frame PDFs]
    DoorSpecs --> UploadPDF2[Upload Door PDFs]

    ReviewFiles --> CNCFiles[Generate CNC-Ready Files]
    CNCFiles --> CutLists[Create Cut Lists<br/>with Sheet Layouts]

    UploadPDF1 --> JobCardBundle[Assemble Job Card Bundle]
    UploadPDF2 --> JobCardBundle
    CutLists --> JobCardBundle

    JobCardBundle --> JobCardContents{Job Card<br/>Contains}
    JobCardContents --> Cover[Cover Sheet<br/>Job Info, Assignment]
    JobCardContents --> Specs[Specifications<br/>Materials, Hardware]
    JobCardContents --> Visual[Visual PDFs<br/>Drawings, Dimensions]
    JobCardContents --> Tasks[Task Breakdown<br/>Checklist]
    JobCardContents --> CNC[CNC Cut List]

    JobCardBundle --> DetailComplete[Detailing Complete]
    DetailComplete --> ToSourcing[Send BOM to Sourcing]

    style JobCardBundle fill:#FFD700
```

---

## Sourcing & Procurement

```mermaid
flowchart TD
    BOM[Receive BOM] --> AidenChecks[Aiden Checks<br/>Current Inventory]

    AidenChecks --> Available{Items<br/>Available?}

    Available -->|Yes| Allocate[Allocate to Job]
    Allocate --> UpdateMaster1[Update Master Inventory]
    UpdateMaster1 --> LabelStock[Label with Job ID]

    Available -->|Partial/No| CreatePO[Create Purchase<br/>Order Request]
    CreatePO --> BryanReview[Bryan Reviews & Approves]

    BryanReview --> Approved{Approved?}
    Approved -->|No| Revise[Revise Request]
    Revise --> BryanReview

    Approved -->|Yes| SadieOrders[Sadie Processes<br/>Purchase Order]
    SadieOrders --> VendorOrder[Submit to Vendors]

    VendorOrder --> Schedule[Schedule Delivery<br/>Wednesday Target]
    Schedule --> Receive[Receive Materials]

    Receive --> AidenReceives[Aiden Receives & Verifies]
    AidenReceives --> LabelMaterials[Label with Job ID]

    LabelMaterials --> HardwareQ{Hardware?}
    HardwareQ -->|Yes| JobBox[Put in Job Box]
    HardwareQ -->|No| SheetGoods[Label Sheets<br/>Store in Bay]

    JobBox --> UpdateInventory[Update Master Inventory]
    SheetGoods --> UpdateInventory

    LabelStock --> UpdateBOM[Update BOM with Locations]
    UpdateInventory --> UpdateBOM

    UpdateBOM --> FinalizedBOM[Finalized BOM<br/>All Items Located]
    FinalizedBOM --> ToProduction[Ready for Production]

    style FinalizedBOM fill:#90EE90
    style ToProduction fill:#FFD700
```

---

## Production Workflow

```mermaid
flowchart TD
    Start[Production Starts] --> BryanAssigns[Bryan Assigns<br/>Cabinet Run to Team Lead]

    BryanAssigns --> TeamLead{Team Lead}
    TeamLead --> Levi[Levi]
    TeamLead --> Shaggy[Shaggy]

    Levi --> ReceiveJobCard1[Receive Job Card Bundle]
    Shaggy --> ReceiveJobCard2[Receive Job Card Bundle]

    ReceiveJobCard1 --> VerifyMaterials1[Verify Materials Available<br/>Check Finalized BOM]
    ReceiveJobCard2 --> VerifyMaterials2[Verify Materials Available<br/>Check Finalized BOM]

    VerifyMaterials1 --> CNCReady1[CNC Operator Gets<br/>Sheets & Files Ready]
    VerifyMaterials2 --> CNCReady2[CNC Operator Gets<br/>Sheets & Files Ready]

    CNCReady1 --> DaggerCNC[Dagger Programs<br/>& Runs CNC]
    CNCReady2 --> DaggerCNC

    DaggerCNC --> CutParts[Cut Parts on CNC]
    CutParts --> AidenSorts[Aiden Sorts & Organizes Parts]

    AidenSorts --> AssembleCabs[Assemble Cabinet Boxes]
    AssembleCabs --> FaceFrames[Build & Install<br/>Face Frames]
    FaceFrames --> DoorsDrawers[Install Doors,<br/>Drawers, Hardware]

    DoorsDrawers --> UpdateTask[Update Task Status]
    UpdateTask --> QC1[QC Checkpoint #1<br/>Aiden Inspects]

    QC1 --> QCPass1{Pass?}
    QCPass1 -->|No| Rework[Fix Issues]
    Rework --> QC1
    QCPass1 -->|Yes| ToFinishing[Send to Finishing]

    style QC1 fill:#FFB6C1
    style ToFinishing fill:#90EE90
```

---

## Quality Control & Delivery

```mermaid
flowchart TD
    FromProd[From Production] --> Finishing[Finishing Department]

    Finishing --> ReceiveParts[Receive Parts<br/>with Quantity List]
    ReceiveParts --> CheckFinish[Check Finish<br/>Specifications]
    CheckFinish --> ApplyFinish[Apply Finish<br/>Stain/Paint]

    ApplyFinish --> QC2[QC Checkpoint #2<br/>Aiden Inspects]
    QC2 --> QCPass2{Pass?}
    QCPass2 -->|No| Refinish[Refinish]
    Refinish --> QC2

    QCPass2 -->|Yes| StageDelivery[Stage for Delivery]

    StageDelivery --> TrackParts[Track All Parts<br/>Going to Trailer]
    TrackParts --> GenerateBOL[Generate Bill of Lading<br/>Auto-populated from Tasks]

    GenerateBOL --> BookTransport[Book Transportation<br/>Cape Cod Express]
    BookTransport --> AssignDriver[Assign Driver]

    AssignDriver --> AidenLoads[Aiden Assists<br/>Loading Trailer]
    AidenLoads --> LoadTrailer[Load Trailer<br/>Track Items]

    LoadTrailer --> DeliveryConfirm[Delivery to Site]
    DeliveryConfirm --> ChaseReceives[Chase Receives<br/>on Site]

    ChaseReceives --> Installation[Installation Phase]
    Installation --> UsePDFs[Use PDF Drawings<br/>for Placement]
    UsePDFs --> InstallCabs[Install Cabinets]
    InstallCabs --> SiteIssues[Handle Site Issues]

    SiteIssues --> ProjectComplete[Project Complete]

    style QC2 fill:#FFB6C1
    style ProjectComplete fill:#FFD700
```

---

## Data Hierarchy Structure

```mermaid
graph TD
    Project[PROJECT<br/>Job #, Customer, Budget] --> Room1[ROOM: Kitchen]
    Project --> Room2[ROOM: Bathroom]

    Room1 --> RoomLoc1[ROOM LOCATION: Wall]
    Room1 --> RoomLoc2[ROOM LOCATION: Island]

    RoomLoc1 --> Run1[CABINET RUN: Base]
    RoomLoc1 --> Run2[CABINET RUN: Upper]
    RoomLoc2 --> Run3[CABINET RUN: Full-Height]

    Run1 --> RunInfo1[Linear Feet: Auto-calc<br/>Materials: Defaults<br/>Hardware: Defaults<br/>Countertop: Specs]

    Run1 --> Cab1[CABINET: K1-B01<br/>Overall: H×W×D<br/>Face Frame Type<br/>Toe Kick<br/>End Panels]
    Run1 --> Cab2[CABINET: K1-B02]

    Cab1 --> Door1[DOOR<br/>Profile, Dimensions<br/>Hardware, Hinges]
    Cab1 --> Drawer1[DRAWER<br/>Type, Box, Hardware<br/>Depth Offset]
    Cab1 --> Shelf1[SHELF<br/>Adjustable/Fixed<br/>Material]

    style Project fill:#e1f5ff
    style Room1 fill:#fff4e1
    style RoomLoc1 fill:#ffe1f5
    style Run1 fill:#e1ffe1
    style Cab1 fill:#ffe1e1
    style Door1 fill:#f5e1ff
```

---

## Role Responsibilities

```mermaid
graph TD
    subgraph Bryan[BRYAN - Operations Manager]
        B1[Design in Rhino]
        B2[Client Communication]
        B3[Approve Purchase Orders]
        B4[Assign Cabinet Runs]
        B5[Create Proposals]
    end

    subgraph Aiden[AIDEN - Detailer/Warehouse/QC]
        A1[Create Cabinet Specs]
        A2[Generate CNC Files]
        A3[Check Inventory]
        A4[Receive Materials]
        A5[QC Inspections x2]
        A6[Warehouse Organization]
        A7[Stage Materials]
    end

    subgraph Sadie[SADIE - Inventory/Purchasing/Finance]
        S1[Process Purchase Orders]
        S2[Vendor Management]
        S3[Track Financials]
        S4[Monitor Inventory]
        S5[Weekly PO Reviews]
    end

    subgraph Dagger[DAGGER - CNC Operator]
        D1[Program CNC]
        D2[Operate CNC]
        D3[Draw while Running]
    end

    subgraph Production[LEVI & SHAGGY - Production Leads]
        P1[Receive Cabinet Runs]
        P2[Delegate to Crew]
        P3[Build Cabinets]
        P4[Update Task Status]
    end

    subgraph Alina[ALINA - Production Helper]
        AL1[Learn Fundamentals]
        AL2[Assist Production]
        AL3[Document Procedures]
        AL4[Maintain BOL]
    end

    subgraph Chase[CHASE - Installation Lead]
        C1[On-site Installation]
        C2[Use PDF Drawings]
        C3[Handle Site Issues]
    end

    Bryan -->|Rhino Files| Aiden
    Aiden -->|Purchase Requests| Sadie
    Sadie -->|Approved Orders| Vendors[Vendors]
    Vendors -->|Materials| Aiden
    Aiden -->|CNC Files| Dagger
    Bryan -->|Cabinet Runs| Production
    Aiden -->|Job Cards| Production
    Production -->|Completed Work| Aiden
    Aiden -->|Finished Cabinets| Chase
```

---

## Linear Feet Calculation

```mermaid
flowchart TD
    CabRun[Cabinet Run] --> Type{Run Type?}

    Type -->|Base| BaseCalc[Width × 1]
    Type -->|Upper| UpperCalc[Width × 1]
    Type -->|Full-Height| FullCalc[Width × 2]
    Type -->|Appliance| AppCheck{Has Panels?}

    AppCheck -->|Yes| AppCount[Count as LF]
    AppCheck -->|No| AppNoCount[Don't Count]

    BaseCalc --> Example1[Example: 6' base run<br/>= 6 linear feet]
    UpperCalc --> Example2[Example: 6' upper run<br/>= 6 linear feet]
    FullCalc --> Example3[Example: 6' full-height<br/>= 12 linear feet]

    Example1 --> Usage[Usage:<br/>Bidding & Team Payment]
    Example2 --> Usage
    Example3 --> Usage
    AppCount --> Usage

    Usage --> NotFor[NOT for:<br/>Material Costing]

    style Usage fill:#90EE90
    style NotFor fill:#FFB6C1
```

---

## Inventory Management

```mermaid
flowchart TD
    Start[Weekly Cycle Starts] --> Friday[FRIDAY<br/>Aiden Checks Inventory]

    Friday --> CheckLevels[Check Stock Levels<br/>vs Upcoming Jobs]
    CheckLevels --> NeedOrder{Need to<br/>Order?}

    NeedOrder -->|Yes| CreateRequest[Create Purchase Request]
    CreateRequest --> SendSadie[Send to Sadie]

    NeedOrder -->|No| WaitNext[Wait for Next Week]

    SendSadie --> Monday[MONDAY<br/>Sadie Compiles Requests]
    Monday --> AddPricing[Add Pricing &<br/>Vendor Info]
    AddPricing --> PresentBryan[Present to Bryan]

    PresentBryan --> BryanApprove{Bryan<br/>Approves?}
    BryanApprove -->|No| Revise[Revise]
    Revise --> PresentBryan

    BryanApprove -->|Yes| SadieOrders[Sadie Processes<br/>Orders]
    SadieOrders --> SubmitVendor[Submit to Vendors]

    SubmitVendor --> Wednesday[WEDNESDAY<br/>Deliveries Arrive]
    Wednesday --> AidenReceive[Aiden Receives<br/>& Verifies]

    AidenReceive --> LabelItems[Label with Job ID]
    LabelItems --> StoreType{Item Type?}

    StoreType -->|Hardware| JobBox[Job-Specific Box<br/>ALLOCATED]
    StoreType -->|Sheets| BayStorage[Bay Storage<br/>Labeled with Job]
    StoreType -->|Bulk| BulkArea[Bulk Stock Area<br/>Pre-finished Plywood]

    JobBox --> UpdateInv1[Update Inventory]
    BayStorage --> UpdateInv1
    BulkArea --> UpdateInv1

    UpdateInv1 --> UpdateBOM[Update BOM<br/>with Locations]
    UpdateBOM --> NextWeek[Next Friday]
    NextWeek --> Friday

    WaitNext --> NextWeek

    style Friday fill:#e1f5ff
    style Monday fill:#fff4e1
    style Wednesday fill:#e1ffe1
    style JobBox fill:#FFB6C1
```

---

## Job Card Bundle Assembly

```mermaid
flowchart TD
    Start[Job Card Creation] --> Cover[COVER SHEET]

    Cover --> CoverInfo[Job Name/Number<br/>Customer Info<br/>Room, Run, Cabinet ID<br/>Assigned To<br/>Linear Feet<br/>Dates & Priority]

    Start --> Specs[SPECIFICATIONS]
    Specs --> SpecInfo[Overall Dimensions<br/>Face Frame Details<br/>Components List<br/>Materials & Hardware<br/>Finish Specs]

    Start --> Visual[VISUAL REFERENCES]
    Visual --> VisualInfo[Rhino PDF Snapshots<br/>Face Frame Drawings<br/>Door Dimensions<br/>Detail Callouts]

    Start --> Tasks[TASK BREAKDOWN]
    Tasks --> TaskInfo[Step-by-step Checklist<br/>QC Checkpoints<br/>Time Estimates<br/>Notes Space]

    Start --> CNC[CNC CUT LIST]
    CNC --> CNCInfo[Part Names & Quantities<br/>Sheet Layout Diagrams<br/>Material Specifications]

    CoverInfo --> Bundle[Complete Job Card Bundle]
    SpecInfo --> Bundle
    VisualInfo --> Bundle
    TaskInfo --> Bundle
    CNCInfo --> Bundle

    Bundle --> Distribute[Distribute to Production Team]

    style Bundle fill:#FFD700
```

---

## Training Workflow (Watch → Do → Document)

```mermaid
flowchart TD
    NewEmployee[New Employee<br/>Example: Alina] --> Orientation[Orientation with Andrew<br/>2 hours: Workflow, Systems]

    Orientation --> SkillTraining[Skills Training with Bryan]

    SkillTraining --> Task1[Select Task<br/>Example: Floating Shelves]

    Task1 --> Watch[WATCH<br/>Bryan Demonstrates]
    Watch --> Video[Video/Voice Record]

    Video --> Do[DO<br/>Trainee Performs<br/>Bryan Supervises]

    Do --> Document[DOCUMENT<br/>Trainee Writes Procedure<br/>on Task Card]

    Document --> Review[END OF DAY<br/>Trainee Explains Back]

    Review --> Pass{Understanding<br/>Complete?}
    Pass -->|No| Clarify[Clarify & Re-demonstrate]
    Clarify --> Do

    Pass -->|Yes| SaveProcedure[Save as<br/>Reference Document]

    SaveProcedure --> NextTask{More Tasks?}
    NextTask -->|Yes| Task1
    NextTask -->|No| TrialComplete[3-Week Trial Complete]

    TrialComplete --> Evaluate{Performance<br/>Satisfactory?}
    Evaluate -->|Yes| Hire[Permanent Position]
    Evaluate -->|No| EndTrial[End Trial]

    style Watch fill:#e1f5ff
    style Do fill:#fff4e1
    style Document fill:#e1ffe1
    style Review fill:#ffe1f5
```

---

## Purchase Order Approval Flow

```mermaid
flowchart TD
    Start[Inventory Check] --> Needed[Items Needed]

    Needed --> AidenCreate[Aiden Creates<br/>Purchase Request]

    AidenCreate --> Include[Includes:<br/>- Item descriptions<br/>- Quantities needed<br/>- Job allocation<br/>- Urgency]

    Include --> SendSadie[Send to Sadie]

    SendSadie --> SadieWork[Sadie Adds:<br/>- Pricing<br/>- Vendor info<br/>- Delivery timeline<br/>- Budget impact]

    SadieWork --> BryanReview[Bryan Reviews]

    BryanReview --> Check1{Check Project<br/>Budget}
    Check1 -->|Over Budget| Reject1[Reject/Modify]
    Reject1 --> AidenCreate

    Check1 -->|OK| Check2{Check BOM<br/>vs Request}
    Check2 -->|Mismatch| Reject2[Question Quantities]
    Reject2 --> AidenCreate

    Check2 -->|OK| Check3{Check Cash<br/>Flow}
    Check3 -->|Issue| Defer[Defer Order]
    Defer --> Schedule[Reschedule]
    Schedule --> SendSadie

    Check3 -->|OK| Approve[APPROVE]

    Approve --> SadieProcess[Sadie Processes<br/>Purchase Order]

    SadieProcess --> SubmitVendor[Submit to Vendor]
    SubmitVendor --> TrackDelivery[Track Delivery]

    TrackDelivery --> WednesdayDelivery[Wednesday Delivery]

    style Approve fill:#90EE90
    style Reject1 fill:#FFB6C1
    style Reject2 fill:#FFB6C1
```

---

## System Data Flow

```mermaid
flowchart LR
    subgraph Input[Input Sources]
        Client[Client Requirements]
        Field[Field Measurements]
        Inspo[Inspiration Photos]
    end

    subgraph Design[Design Phase]
        Rhino[Rhino 3D Model]
        PDF1[PDF Drawings]
        DWG[DWG Files]
    end

    subgraph ERP[ERP Database]
        Project[Project Record]
        Rooms[Room Hierarchy]
        Cabinets[Cabinet Specs]
        BOM1[Bill of Materials]
    end

    subgraph Processing[Processing]
        Detailing[Cabinet Detailing]
        CNCGen[CNC File Generation]
        JobCard[Job Card Assembly]
    end

    subgraph Execution[Execution]
        Inventory[Inventory Allocation]
        Production[Production Tasks]
        QC[Quality Control]
    end

    subgraph Output[Outputs]
        FinishedCabs[Finished Cabinets]
        BOL[Bill of Lading]
        Install[Installed Project]
    end

    Input --> Design
    Design --> ERP
    ERP --> Processing
    Processing --> Execution
    Execution --> Output

    ERP -.->|Auto-generate| BOM1
    ERP -.->|Auto-generate| JobCard
    Inventory -.->|Updates| ERP
    Production -.->|Status Updates| ERP
```

---

## Change Order Process

```mermaid
flowchart TD
    Request[Client Change Request] --> Review[Bryan Reviews]

    Review --> Impact{Impact<br/>Assessment}

    Impact --> Timeline[Timeline Impact]
    Impact --> Cost[Cost Impact]
    Impact --> Materials[Material Impact]

    Timeline --> CreateSub[Create Sub-Project]
    Cost --> CreateSub
    Materials --> CreateSub

    CreateSub --> SubWorkflow[Same Workflow<br/>as Main Project]

    SubWorkflow --> SubDesign[Design Changes]
    SubDesign --> SubDetail[Detail Changes]
    SubDetail --> SubSource[Source Materials]
    SubSource --> SubProd[Produce Changes]

    SubProd --> Integrate[Integrate with<br/>Main Project]

    Integrate --> SeparateFinancial[Separate Financial<br/>Tracking]

    SeparateFinancial --> ClientApprove{Client<br/>Approves Cost?}

    ClientApprove -->|Yes| Execute[Execute Change]
    ClientApprove -->|No| Negotiate[Negotiate/Modify]
    Negotiate --> ClientApprove

    Execute --> UpdateTimeline[Update Main<br/>Project Timeline]

    style CreateSub fill:#FFD700
    style SeparateFinancial fill:#90EE90
```

---

**Documentation Generated:** November 21, 2025
**Total Diagrams:** 13 comprehensive workflow visualizations
**Source:** 392 N Montgomery St Building B Meeting Analysis
