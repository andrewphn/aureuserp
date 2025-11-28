# Cabinet Hierarchy Database Schema - Meeting Notes Outline
**Meeting:** 392 N Montgomery St Building B Discussion
**Participants:** Andrew, Bryan

## Overview
Discussion about implementing a hierarchical database structure for cabinet projects that supports task assignment, specification inheritance, and automated reporting.

---

## Database Hierarchy Structure

### 1. Project Level
**Purpose:** Top-level container for entire job

**Fields/Requirements:**
- Customer details
- Access permissions
- Overall project specifications
- Can be divided into multiple Rooms

**Key Quote (00:26):**
> "At the project level, we have customer details. Access. Right?"

---

### 2. Room Level
**Purpose:** Major divisions within a project (e.g., Kitchen, Bathroom, Living Room)

**Fields/Requirements:**
- Room name/identifier
- Access permissions
- Basic room information

**Key Quote (00:34-00:42):**
> "At the room level, we have access. We have. What do we agree to access? That's really all we need for a room"

---

### 3. Room Location Level
**Purpose:** Specific locations/walls within a room (e.g., "Kitchen Sink Wall", "K1")

**Fields/Requirements:**
- Location identifier (e.g., "K1", "Sink Wall")
- Electrical requirements
- Plumbing requirements
- Window fixtures
- Wall type specifications

**Key Quotes:**
- (00:47-00:49): "Location, we have electrical and plumbing and wind fixtures"
- (50:01): "Here is a room location"
- (53:02-53:07): "Wall type would be room location. Yeah, that'll be at room location"
- (56:10): "So looking at our plan here, This is a room location"

**Example Structure (56:43):**
> "Inside the room location is a cabinet front, SW base, sink, wall base, cabinets inside SW base is Bo1, Bo2, Bo3, and then 4"

---

### 4. Cabinet Run Level
**Purpose:** Groups of cabinets in a specific position/configuration (e.g., base cabinets along one wall, upper cabinets)

**Cabinet Run Types:**
- Base run
- Upper run
- Full/Pantry (floor-to-ceiling)
- Trim
- Paneling
- Doors (passage doors)

**Key Quote (01:01:21-01:01:49):**
> Bryan: "Base cabinets, upper cabinets, pantry, cabinets, appliances."
> Andrew: "What's pantry?"
> Bryan: "A floor ceiling."
> Andrew: "So we just call it full because that could be a closet."

**Fields/Requirements:**
- Run type (base, upper, full, trim, paneling, doors)
- Floor to ceiling dimensions (for full-height runs)
- Overall run measurements (start to end)
- Default pricing specifications
- Default hardware selections (can be overridden at component level)
- Face frame specifications
- Linear footage calculations

**Key Quotes:**
- (55:59-56:03): "So after room location, we have cabinet run. Now, what we consider a cabinet run is multiple cabinets in a position on that wall"
- (59:01): "So at the cabinet run level, tell me what specifications I need to include so you can fill it in"
- (59:30-59:34): "Now you won't do linear feeds at the cabinet run level. You'll do it at the cabinet level, and then it'll escalate up to the cabinet run"
- (01:02:42-01:02:46): "So in cabinet run, what else am I defining? I'm not just determining cabinet run type. What else do I need to know about cabinet runs?"
- (01:30:14-01:30:19): "So face frames are on the cabinet run level"

**Specification Hierarchy (58:35-58:58):**
> Andrew: "Let's say that they've decided they want to change all the hardware for this base cabinet run. Then you should just be able to go to the cabinet run and change it. So there's a specification hierarchy kind of thing"

**Default Hardware (01:21:01-01:21:04):**
> Andrew: "So at the cabinet run level, you can have a default to like, I'm selecting this piece of harbor anticipated"

---

### 5. Cabinet Level
**Purpose:** Individual cabinet units within a run

**Cabinet Types:**
- Generic/Middle cabinet
- End cabinet
- Sink cabinet
- Appliance cabinet
- Inside corner cabinet
- Blind corner cabinet

**Key Quote (01:14:56-01:15:14):**
> Andrew: "So like, is it on an end? Is it a sink cabinet? Is it an appliance cabinet?"
> Bryan: "Is it a middle cabinet?"
> Andrew: "No, because middle is going to be the generic."
> Bryan: "Inside corner, blind corner."

**Global Cabinet Fields:**
- Cabinet name/identifier
- Finish material
- Construction type (face frame or European)
- Overall dimensions (height, width, depth)
- Toe kick (yes/no)
- Toe kick dimensions (if applicable - height, depth variations)
- Face frame dimensions
- Does it need a top? (yes/no)
- End panel (yes/no)
  - Decorative or flat end panel
- General notes
- Cabinet type (end, sink, appliance, corner, blind corner)
- Pricing level

**Key Quotes:**
- (01:12:40-01:13:40): Discussion of global cabinet fields including finish material, face frame type, toe kicks
- (01:13:33-01:13:56): "Does it have a kick? Does it not? For a kick. So this is base. We're talking base. What do we do about countertop? Does it need a top? Sometimes you build a cabinet without a top, right?"
- (01:14:19-01:14:31): "End panels. Does this have an end panel? Not all of them have end panels. And then is it a decorative end panel or is it a flat end panel?"
- (01:27:45-01:28:35): Discussion of dimensional requirements including face frame widths, overall height/width/depth, toe kick variations

**Face Frame Requirements (01:29:10-01:30:27):**
- Face frame width (separate from cabinet width)
- Opening heights
- Opening widths
- Scribe considerations
- Corner considerations (blind, inside)
- Connected at cabinet level (NOT cabinet run level)

---

### 6. Cabinet Component Level (Sub-Components)
**Purpose:** Individual elements within a cabinet

**Component Types:**
- Doors
- Drawers
- Shelves
- Pull-outs
- (End panels tracked at cabinet level)

**Key Quote (01:14:06-01:14:32):**
> Andrew: "So then the next part is what sub components exist?"
> Bryan: "Doors, drawers, shelving, pull outs. End panels."

#### 6.1 Door Components

**Fields:**
- Profile type
- Finish (inherits from cabinet if not specified)
- Fabrication method (CNC vs. manual/five-piece)
- Hinge type:
  - Blind inset
  - Half overlay
  - Full overlay
  - (Future: Tektus)
- Dimensions:
  - Overall width
  - Overall height
  - Rail width
  - Style width
  - Check rail (yes/no)
  - Check rail width (if applicable)
- Hardware (defined at component level):
  - Hinge selection
  - Decorative hardware (if supplied)

**Key Quotes:**
- (01:15:48-01:16:04): "Profile, type, finish, fabrication. Is the finish ever different from the cabinet for doors? Yeah. We did one that was like all painted cabinets and the walnut cabinets"
- (01:16:27-01:17:15): Discussion of hinge types: "blind inset, half overlay, full overlay"
- (01:20:30-01:20:46): "It has to be at the component level. Because in one cabinet you could have a blind door and a regular door. So it has to be at the component level"
- (01:26:43-01:27:37): Door measurements including width, height, rail and style sizes, check rail

#### 6.2 Drawer Components

**Fields:**
- Dimensions:
  - Overall width
  - Overall height
  - Top rail width
  - Bottom rail width
  - Style width (matches drawer styles)
- Hardware (component-specific):
  - Slide type
  - Slide length
- Profile type
- Finish (inherits from cabinet if not specified)
- Fabrication method

**Key Quotes:**
- (01:27:30-01:27:41): "What you need on drawers is top rail, bottom rail. And then your styles are probably gonna. Your styles will match your drawers"
- (01:21:41-01:21:46): "Let's say we have a vanity where the top drawer has to be shallow. So each of those as a component"

#### 6.3 Shelf Components

**Fields:**
- Type: Adjustable, Fixed, or Pull-out
- Dimensions
- Material specifications

**Key Quote (01:57:01-01:58:29):**
> Andrew: "You're going to have a parallel screen that when you add a cabinet to a cabinet run, it's going to ask you how many sections, how many openings are in it. Whereas is it adjustable? Adjustable or fixed? I would just go adjustable, fixed or pull out"

#### 6.4 Pull-out Components

**Fields:**
- Type
- Dimensions
- Hardware specifications

**Key Quote (01:15:29-01:15:31):**
> Bryan: "Yeah, like you open a door and you have a pull out"

---

### 7. Section Level
**Purpose:** Subdivisions within a cabinet (e.g., drawer opening, door opening, open shelving)

**Key Quote (01:30:40-01:30:45):**
> Andrew: "Let's say there's a door and a drawer. And then this. There's the another one next to it. That is open shelves. Are you considering that's one cabinet? So then how do I call those sections? Oh, they call sections"

---

## Task Assignment Hierarchy

The system supports task assignment at multiple levels:

**Key Quote (02:18:56):**
> Andrew: "I can assign room level, cabinet level, cabinet run level, section level, tasks which you can then go, I'm making a task for so and so, and I'm assigning it to this section"

**Task Assignment Example (01:04:36-01:05:03):**
> Andrew: "What I'd like you to be able to do is say, hey, Levi, this base is yours. And Shaggy, you've got the uppers. And then walk away. And every detail should be inspect out for them. Because then we know how much this costs, how much to do it. That's why I didn't just go, you are assigned this cabinet"

**Job Card Types (02:32:15-02:32:44):**
- Task level job cards
- Cabinet level job cards
- Cabinet level job cards contain tasks within them

---

## Specification Inheritance & Defaults

The system uses a hierarchy where specifications can be set at higher levels and inherited/overridden at lower levels.

**Hardware Example (01:20:15-01:21:09):**
- Can set default hardware at cabinet run level
- Can override at cabinet level
- Must specify at component level (because components can vary)
- System defaults to "predominantly inset blum", "predominantly 21 inch slides"
- Can be changed as needed at component level

**Key Quote (01:21:01-01:21:04):**
> Andrew: "So at the cabinet run level, you can have a default to like, I'm selecting this piece of harbor anticipated"

---

## Database Integration & Reporting

### Rhino Integration
**Goal:** Bidirectional integration with Rhino for automatic dimension extraction

**Key Quotes:**
- (01:10:15-01:10:23): "Now, my dream thing and what I know I can do is I can spin from that because it's in a database. I can spit an actual Rhino file, not the actual drawing of The Rhino files"
- (01:21:49-01:21:53): "It'll just pull and I'll just label that correctly to match the database"
- (01:48:29-01:49:32): Discussion about making printable PDFs from database and uploading them back

### Report Generation
**Capability:** Generate reports at any hierarchy level

**Key Quote (01:06:42-01:06:49):**
> Andrew: "You can pull a cabinet run report, and it gives you the entire cabinet run report. You can pull a page report for that cabinet and it gives you the plan, it gives you the elevation for it, and it gives you all the specs that you did"

### Bill of Materials (BOM)
**Integration:** Specifications flow into BOM automatically

**Key Quote (01:10:10-01:10:15):**
> Andrew: "Cool. So that'd be cool because then it would spit right into the BOM 100%"

---

## Pre-Rhino vs Post-Rhino Data

### Pre-Rhino Data (Can be entered before detailed drawings):
- Total quantity for hardware (hinges, slides, blind corners)
- Cabinet names
- Cabinet style/door profiles
- Species (if hardwood)
- Quantity calculations
- Linear footage for:
  - Cabinetry
  - Millwork
  - Countertops
  - Float gels
- Material type

**Key Quote (01:09:06-01:09:59):**
> Bryan: "So pre Rhino, we can get this whole job summary. We can look at the drawings and we can get a total quantity for hardware... We can get names, which is a big thing, just to name every single cabinet... We can get style with the cabinet door. Profiles are. We can get species if there's hardwood"

### Post-Rhino Data (Requires detailed drawings):
- Exact dimensions for all components
- Face frame specifications
- Detailed section breakdowns
- Opening dimensions
- Precise measurements for doors/drawers

**Key Quote (01:11:24-01:11:42):**
> Andrew: "Now what are those fields that you need to get the job done? That's what I need to get from you in right up. What other things that the guys need to know like face frames, how far is the kit? How's it assembled? Those things are the things I don't know"

---

## Validation & Quality Control

**Automatic Validation (01:03:24-01:03:46):**
> Andrew: "Like, for example, if you start speccing out these cabinets and your math doesn't map it, I need to red flag you. Do you get what I'm saying? Like, if we start building all these cabinets, it's this link, this link. And then it comes back and it's like, hey, that's not what you said for 50. And you're building 70"

System should validate:
- Cabinet dimensions sum to room location dimensions
- Component dimensions fit within cabinet dimensions
- Hardware compatibility with component types

---

## Implementation Notes

### Data Entry Workflow
1. Project created with customer details
2. Rooms defined within project
3. Room locations defined with electrical/plumbing/fixtures
4. Cabinet runs created within room locations
5. Cabinets defined within runs
6. Components added to cabinets
7. Sections defined within cabinets (if applicable)

### Specification Entry
- Specifications can be entered at any level
- Higher-level specs serve as defaults
- Lower levels can override inherited specs
- Component level is most granular (required for hardware)

### Key Design Principle (01:17:58-01:18:21):**
> Andrew: "I don't want you to do this. I want somebody else to do it. It's not about you. It's about the person who is going to fulfill that role. You hate doing it because you're doing 5,000 other things. But if your job is to spec out a job, you're filling in the details that are going to come up. Because guess what? Those guys are going to ask. What is it? Is it an inset hinge? Is it a boulevard? It should already be there for them. And as well as we've ordered the correct hinge for it"

---

## Additional Context

### Hierarchy Context (19:20-19:32):**
> Andrew: "Just put hierarchy for me, just task. Under main task, put hierarchy. We've got hierarchy, we've got..."

### Linear Feet Calculation (59:30-59:34):**
> Andrew: "Now you won't do linear feeds at the cabinet run level. You'll do it at the cabinet level, and then it'll escalate up to the cabinet run"

### Parallel Design Work (02:57:15):**
> Andrew: "What I'm saying is I'll take that file and I already on my own should be able to get to the point of like defining all the cabinets and runs and stuff"

---

## Summary

The requested database schema implements a **7-level hierarchy**:
1. **Project** → Customer/Access
2. **Room** → Basic room info
3. **Room Location** → Wall/electrical/plumbing details
4. **Cabinet Run** → Grouped cabinets with type and defaults
5. **Cabinet** → Individual cabinet with global specs
6. **Component** → Doors/drawers/shelves with specific specs
7. **Section** → Subdivisions within cabinets

**Key Features:**
- Specification inheritance with override capability
- Task assignment at any level
- Automatic validation and red-flagging
- Integration with Rhino for bi-directional data flow
- Report generation at any hierarchy level
- BOM auto-generation from specifications
- Linear feet calculations rolling up from cabinet to run level
- Hardware selection at component level with run-level defaults
