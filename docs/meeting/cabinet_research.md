# Cabinet Making Fundamentals & Layout Research for Rhino Scripting

## I. Hierarchical Structure for 3D Modeling

This hierarchy breaks down the required information from the largest context to the smallest detail needed for accurate 3D modeling in Rhino.

1.  **Environment/Context:**
    *   Room dimensions (optional)
    *   Wall definition (Length, Height, Start/End Points, Corner Conditions)
    *   Obstacles (Windows, Doors, Outlets - Location, Dimensions)

2.  **Cabinet Assembly/Layout:**
    *   Arrangement/Configuration (Sequence of units: Straight, L-Shape, U-Shape, Galley)
    *   Placement relative to walls and each other.
    *   Special units (Islands, Peninsulas - defined relative to main runs)
    *   Overall footprint/envelope of the assembly.
    *   Spacing & Fillers (Gaps, filler strip widths and locations).

3.  **Individual Cabinet Unit:**
    *   Type (Base, Wall, Tall, Corner - defines form & defaults)
    *   Overall Dimensions (Width, Height, Depth - bounding box)
    *   Placement/Context (Freestanding, against wall, etc.)

4.  **Carcass (The Box):**
    *   Construction Style (Face Frame vs. Frameless/Euro-style)
    *   Panel Material Thickness (Actual thickness is critical)
    *   Panel Components (Sides, Bottom, Top/Stretchers, Back)
    *   Assembly Method (Geometric impact: Panel overlaps, back type/position)

5.  **Face Frame (If Applicable):**
    *   Component Dimensions (Stile & Rail widths)
    *   Material Thickness
    *   Joinery (How stiles/rails meet - geometric impact)
    *   Relationship to Carcass (Overhangs, Flush)

6.  **Doors & Drawer Fronts:**
    *   `Style`: (String, Enum - Required) Defines visual style (e.g., `Slab`, `Shaker`, `RaisedPanel_Square`, `RaisedPanel_Arch`, `RecessedPanel_Square`, `GlassPanel_SingleLite`, `BeadboardPanel`, `VGroovePanel`, `Louvered`).
    *   `ConstructionMethod`: (String, Enum, Optional) How it's built (e.g., `SolidSlab`, `CopeAndStick`, `Mitered`).
    *   Overall Dimensions (Width, Height)
    *   Material Thickness (Overall)
    *   `MaterialName`: (String - Required) Primary material (frame/slab).
    *   `PanelMaterialName`: (String - Optional) Material for center panel if different (e.g., glass, plywood, MDF beadboard).
    *   Reveal/Overlay (Critical for sizing and placement).
    *   *(Non-slab styles):* Stile/Rail dimensions, panel details (`StyleParams` dictionary).
    *   `EdgeProfile`: (String, Optional) Definition for the outside edge treatment (e.g., 'Roundover_1/8', 'Ogee').
    *   `MachiningOperations`: (List of Dictionaries, Optional) Specific operations applied to *this* part. Examples:
        *   Hinge Cup Boring: `{"op_type": "Drill", "pattern_ref": "HingeCup_Blum71B", "location_ref": "TopHinge", "face": "Back"}`
        *   Pull Handle Drilling: `{"op_type": "Drill", "pattern_ref": "PullHandle_3inchCenter", "location_ref": "Center", "face": "Front"}`
        *   Decorative Groove: `{"op_type": "DecorativeGroove", "width": 0.25, "depth": 0.125, "path_definition": "SVG_or_Points", "face": "Front", "tool_ref": "1/4_BallNose"}`

7.  **Drawer Boxes:**
    *   Overall Dimensions (Width, Height, Depth - accounting for clearances)
    *   Material Thickness (Sides, Front/Back, Bottom)
    *   Components (Sides, Front, Back, Bottom)
    *   Joinery (Geometric impact: Corners, bottom panel capture - Method specified, details in `MachiningOperations`)
    *   Slide Clearance (Space needed for hardware)
    *   `MachiningOperations`: (List of Dictionaries, Optional) Specific operations applied to drawer box parts. Examples:
        *   Slide Mounting Holes: `{"op_type": "Drill", "pattern_ref": "SlideMount_RevAShelf4WCSC_BoxMember", "location_ref": "LeftSlide", "target_part": "Side_Left", "face": "Outside"}`
        *   Undermount Notch: `{"op_type": "Notch", "height": 1.0, "depth": 0.5, "edge": "BackBottom", "target_part": "Side_Both"}`
        *   Joinery Prep (e.g., Dado for bottom): `{"op_type": "Dado", "width": 0.25, "depth": 0.25, "distance_from_edge": 0.5, "edge": "Bottom", "target_part": "Side_Both, Front, Back", "face": "Inside"}`

8.  **Shelves:**
    *   Type (Fixed, Adjustable)
    *   Dimensions (Width, Depth)
    *   Material Thickness
    *   Placement (Fixed) or Hole Pattern Definition (Adjustable - defined in `ShelfPinHoleConfig`, applied via `MachiningOperations` on carcass sides/dividers).

9.  **Joinery & Machining Details (Micro-Level):**
    *   **Conceptual Definition:** This level defines the *types* of joinery and detailed machining operations possible.
    *   **Specific Application:** The *actual application* of these operations (with precise dimensions, locations, parameters, tool references) is stored within the `MachiningOperations` list attribute associated with the specific affected part (Component, Door/Front, Drawer Box).
    *   **Examples of Operation Types (`op_type` in `MachiningOperations`):**
        *   `Dado`: Groove cut across the grain (Width, Depth, Location, Tool Ref).
        *   `Groove`: Groove cut with the grain (Width, Depth, Location, Tool Ref).
        *   `Rabbet`: Notch cut along an edge (Width, Depth, Edge, Face).
        *   `PocketHole`: Angled hole for screw joinery (often simplified geometrically, placement is key).
        *   `DowelHole`: Hole for dowel joinery (Diameter, Depth, Location - often simplified).
        *   `Mortise`, `Tenon`: More complex joinery, potentially modeled simplified or defined by parameters.
        *   `Drill`: General purpose drilling (Diameter, Depth, Location, Face, optionally `pattern_ref`).
        *   `Counterbore`, `Countersink`: Specific drill modifications.
        *   `VGroove`: Decorative V-shaped groove (Width, Depth, Angle, Location, Path, Tool Ref).
        *   `Flute`: Decorative rounded groove (Profile, Depth, Location, Path, Tool Ref).
        *   `EdgeProfile`: Applying a profile shape to an edge (Profile Ref, Edge(s)).
        *   `Notch`: Cutout, typically for clearance (Height, Depth, Location/Edge).
        *   `Chamfer`: Beveled edge (Size, Angle, Edge(s)).
        *   `Roundover`: Rounded edge (Radius, Edge(s)).
        *   `DecorativeGroove`: Generic decorative cut defined by a path (Path Definition, Depth, Tool Ref).

10. **Hardware Mounting Details (Micro-Level):**
    *   **Conceptual Definition:** This level focuses on the requirements for mounting specific hardware.
    *   **Implementation via Patterns:** The preferred method is to define **reusable, named mounting patterns** within an external `HardwareSpec` entity (derived from manufacturer data). These patterns precisely define hole locations, diameters, depths, and any associated clearance cuts (like notches).
        *   *Example Pattern Names:* `HingeCup_Blum71B`, `HingePlate_Blum71B_Frame`, `SlideMount_RevAShelf4WCSC_BoxMember`, `PullHandle_3inchCenter`.
    *   **Application via MachiningOperations:** The `MachiningOperations` list on the relevant part (Component, Door/Front, Drawer Box) references these named patterns using the `pattern_ref` key within a `Drill` or other appropriate `op_type` entry.
        *   *Example Usage:* `{"op_type": "Drill", "pattern_ref": "HingeCup_Blum71B", "location_ref": "TopHinge", "face": "Back"}` applied to a Door object.
    *   **Direct Definition (Fallback):** If a standard pattern isn't available or applicable, individual `Drill` operations with explicit coordinates can be defined within `MachiningOperations`.
    *   **SSOT Principle:** Storing the detailed pattern definition in `HardwareSpec` and referencing it ensures consistency and makes updates easier if hardware changes.

## II. Common Cabinet Layouts by Room Type

Understanding typical layouts helps anticipate script requirements.

**1. Kitchen:**
    *   **Layout Shapes:** Single Wall, Galley, L-Shape, U-Shape.
    *   **Special Features:** Island, Peninsula. Needs relative placement definition. Requires corner solutions (Lazy Susan, Blind Corner).
    *   **Units:** Base (Std, Sink, Drawers, Corner), Wall (Std, Fridge, Corner, Micro), Tall (Pantry, Oven).

**2. Bathroom:**
    *   **Layout Shapes:** Vanity (Single run, Wall-to-wall, Alcove, Freestanding). Often shorter standard height.
    *   **Special Features:** Linen Tower (Tall), Over-Toilet Cabinet.
    *   **Units:** Vanity Base (Sink, Drawers, Std), Tall Linen, Wall Cabinet.

**3. Laundry Room:**
    *   **Layout Shapes:** Often Single Wall.
    *   **Special Features:** Cabinets around appliances, Tall utility storage.
    *   **Units:** Base (Sink, Std), Wall Cabinets, Tall Utility.

**4. Office / Study:**
    *   **Layout Shapes:** Variable, often integrated with desks (Desk Base, Wall Units/Bookshelves).
    *   **Special Features:** Integrated countertops, equipment openings.
    *   **Units:** Base (File Drawer, Std), Wall Cabinets, Open Shelves.

**5. Living Room / Entertainment Center:**
    *   **Layout Shapes:** Console (low unit), Built-in (flanking central point).
    *   **Special Features:** Media openings, glass doors, lighting, bridges.
    *   **Units:** Base Console, Tall Piers, Wall Cabinets, Bridge Units, Open Shelving.

**6. Closet / Wardrobe:**
    *   **Layout Shapes:** Modular systems within closets or standalone.
    *   **Special Features:** Frameless often, internal organizers.
    *   **Units:** Drawer Stacks, Shelf Units, Hanging Sections.

**7. Garage / Workshop:**
    *   **Layout Shapes:** Functional, often Single Wall or L-Shape.
    *   **Special Features:** Heavy-duty, deeper cabinets, simple aesthetics.
    *   **Units:** Base, Wall, Tall Cabinets.

## III. Parameter Definitions by Level

This section will detail the specific parameters needed at each level for the Rhino script input.

**Modeling Methodology Note:** The script should follow a progressive detailing approach. First, generate the primary geometric shapes of components (cabinet boxes, face frames, doors, drawers, shelves) based on their overall dimensions and core construction parameters (Levels 3-8). Once the main forms are established, apply finer details as subsequent operations, primarily through boolean subtractions or specific geometric modifications. This includes features like shelf pin holes, hardware mounting holes (referencing patterns), joinery representations (defined via `MachiningOperations`), decorative cuts (also via `MachiningOperations`), and toe kick recesses. This hierarchical approach (gross geometry first, then refinement using `MachiningOperations`) helps manage complexity and ensures the fundamental structure is correct before adding micro-level details (Levels 9-10).

### Level 1: Environment/Context Parameters

Defines the surrounding space where the cabinets are placed.

*   `environment_id`: (String, Optional) Identifier for the overall environment/room context.
*   `units`: (String, Optional, Default='inches') Specifies the measurement units used (e.g., 'inches', 'mm', 'cm').
*   `walls`: (List of Dictionaries, Required) Defines the walls involved in the layout.
    *   `wall_id`: (String, Required) Unique identifier for the wall (e.g., "MainWall", "ReturnWallLeft").
    *   `start_point`: (Vector/Coordinate, Required) Rhino coordinates for the wall's starting point.
    *   `end_point`: (Vector/Coordinate, Required) Rhino coordinates for the wall's ending point.
    *   `height`: (Float, Optional) Height of the wall (useful for context/visualization).
    *   `thickness`: (Float, Optional) Thickness of the wall (useful for context/visualization).
*   `obstacles`: (List of Dictionaries, Optional) Defines items on walls that might affect cabinet placement.
    *   `obstacle_id`: (String, Optional) Identifier for the obstacle.
    *   `target_wall_id`: (String, Required) ID of the wall the obstacle is on.
    *   `type`: (String, Optional) e.g., 'Window', 'Door', 'Outlet', 'Pipe'.
    *   `position_from_wall_start`: (Float, Required) Distance along the wall from its `start_point` to the obstacle's start.
    *   `width`: (Float, Required)
    *   `height`: (Float, Required)
    *   `elevation_from_floor`: (Float, Optional) Distance from the floor (or wall base) to the bottom of the obstacle.
*   `global_defaults`: (Dictionary, Optional) Define project-wide defaults that can be overridden at lower levels.
    *   `default_construction_style`: (String, e.g., 'Frameless')
    *   `default_carcass_material_thickness`: (Float)
    *   `default_faceframe_material_thickness`: (Float)
    *   `default_drawer_slide_type`: (String) Reference to a `HardwareSpec` ID.
    *   `default_hinge_type`: (String) Reference to a `HardwareSpec` ID.
    *   `default_shelf_pin_config`: (String) Reference to a `ShelfPinHoleConfig` ID.
    *   *(Add other common defaults)*

### Level 2: Cabinet Assembly/Layout Parameters

Defines how individual cabinet units (Level 3) are arranged relative to each other and the environment (Level 1).

*   `layout_id`: (String, Optional) Identifier for this specific layout configuration (e.g., "KitchenMainRun", "Island").
*   `target_wall_id`: (String, Optional) ID of the primary wall from Level 1 this layout runs along (if applicable).
*   `start_offset_from_wall_start`: (Float, Optional, Default=0) Distance along the `target_wall_id` from its `start_point` to where this layout begins.
*   `layout_configuration`: (String, Optional) Describes the overall shape (e.g., 'Straight', 'L_Shape_LeftCorner', 'L_Shape_RightCorner', 'U_Shape', 'Island', 'Peninsula'). Influences corner logic and placement.
*   `island_peninsula_offset`: (Dictionary, Optional, Required for 'Island'/'Peninsula')
    *   `reference_point`: (Vector/Coordinate or WallID+Offset) Point from which the island/peninsula is positioned.
    *   `offset_vector`: (Vector) Distance and direction from the reference point.
*   `cabinet_sequence`: (List of Dictionaries, Required) Ordered list defining the cabinets in the layout.
    *   `unit_definition`: (Dictionary, Required) Contains the full parameter set for an *Individual Cabinet Unit* (Level 3). This allows defining each cabinet directly within the sequence.
    *   `filler_strip_left`: (Float, Optional, Default=0) Width of a filler strip placed *before* this cabinet in the sequence.
    *   `filler_strip_right`: (Float, Optional, Default=0) Width of a filler strip placed *after* this cabinet in the sequence.
*   `corner_treatment`: (Dictionary, Optional, Relevant for L/U shapes)
    *   `corner_type`: (String, e.g., 'BlindCorner', 'LazySusan', 'DiagonalFront')
    *   `primary_cabinet_id`: (String) ID of the cabinet on the main run extending into the corner.
    *   `secondary_cabinet_id`: (String) ID of the cabinet on the return run meeting the primary.
    *   *(Specific parameters based on corner type)*
*   `countertop`: (Dictionary, Optional) Defines a countertop spanning this layout.
    *   `material`: (String) Reference to a `Material` name.
    *   `thickness`: (Float)
    *   `overhang_front`: (Float)
    *   `overhang_left`: (Float)
    *   `overhang_right`: (Float)
    *   `overhang_back`: (Float, Optional, Default=0)
    *   `edge_profile`: (String/Definition) Reference to an edge profile type.

### Level 3: Individual Cabinet Unit Parameters

Defines the core characteristics of a single cabinet before breaking it down into its components (carcass, face frame, etc.). Critical for setting the overall size, type, and style constraints.

### General Design Considerations for Individual Units (Standards, Critical Factors, Recommendations)

Before defining the specific parameters for an individual cabinet unit, consider these overarching factors that influence the design choices:

*   **1. Purpose & Function:**
    *   **Critical:** What will this specific cabinet store or house? (e.g., Pots/pans favor deep drawers, spices favor shallow drawers/racks, display items favor glass doors/open shelves, sink base needs plumbing space).
    *   **Recommendation:** Choose the `unit_type` that best matches the primary function. Consider internal accessories early (pull-outs, dividers).

*   **2. Ergonomics & Standard Dimensions:**
    *   **Standard (Base):** 34.5" height (box) for ~36" counter. 24" depth common.
    *   **Standard (Wall):** 12" depth common. Height varies (30", 36", 42"). 18" clearance above counter is standard.
    *   **Critical:** Ensure dimensions are suitable for comfortable human reach and use. Deeper wall cabinets (e.g., 15" or 24" over fridge) require careful consideration of accessibility. Toe kick (Base) provides foot space.

*   **3. Context & Relationships:**
    *   **Critical:** How does this unit relate to adjacent units, walls, corners, and appliances? (See Level 2 Layout).
    *   **Standard:** Maintain consistent heights/depths within a run where appropriate. Use fillers for spacing against walls or between units. Consider appliance door swings and clearance requirements.
    *   **Recommendation:** Specify `scribe_left`/`right` if the unit meets a wall for a tighter fit. Use `finished_end_left`/`right` if a side is exposed.

*   **4. Material Selection:**
    *   **Standard:** Carcass often plywood/particle board/MDF. Face frames/doors often solid wood or MDF (for paint).
    *   **Critical:** Balance cost, durability, moisture resistance (esp. sink bases), weight, and aesthetics. Ensure `material_thickness` parameters reflect *actual* material thickness, not nominal.
    *   **Recommendation:** Use moisture-resistant materials for sink bases. Consider higher quality plywood (e.g., Baltic Birch) for drawer boxes.

*   **5. Construction Style Choice (`construction_style`):**
    *   **Critical:** Face Frame vs. Frameless impacts aesthetics, accessibility (frameless offers slightly more interior width), hardware choices (hinges especially), and assembly complexity.
    *   **Recommendation:** Choice often driven by overall kitchen style (Frameless = modern/Euro, Face Frame = traditional/transitional). Ensure consistency within a project unless intentionally mixing styles.

*   **6. Hardware Selection (Impacts Levels 6, 7, 10):**
    *   **Critical:** Decide on hinge type (overlay/inset) and drawer slide type (side/undermount) early, as they dictate door/drawer front sizing (`overlay_reveal_values`) and drawer box construction/clearances (`clearances`, `MachiningOperations` referencing slide specs).
    *   **Recommendation:** Consult hardware manufacturer specifications (`HardwareSpec`) for precise clearance and mounting pattern requirements (Level 10).

*   **7. Accessibility & Storage Efficiency:**
    *   **Recommendation:** Drawers are generally more ergonomic for accessing base cabinet contents than deep shelves behind doors. Consider pull-out shelves or trays. Adjustable shelves (Level 8) offer flexibility in wall/tall cabinets.

*   **8. Aesthetics & Proportion:**
    *   **Standard:** Maintain consistent reveals/gaps between doors and drawer fronts (Level 6 `overlay_reveal_values`). Align units vertically where appropriate.
    *   **Recommendation:** Consider the visual weight and proportion of the unit within the overall layout. Ensure door/drawer styles (Level 6 `style`) are consistent or complementary.

*   `unit_id`: (String, Optional) A unique identifier for this cabinet unit (e.g., "BaseLeftOfSink", "Wall1"). Useful for referencing.
*   `unit_type`: (String, Required) Specifies the kind of cabinet, influencing default construction and features. Examples:
    *   **Base Cabinets:**
        *   `Base_Standard`: General purpose base cabinet, typically with door(s) and potentially a top drawer. **A very common configuration is a single top drawer with double doors below, requiring appropriate face frame intermediate rails and door/drawer front definitions.**
            *   **_Std Dimensions:_** Width varies (common: 12", 15", 18", 21", 24", 30", 36"). Height 34.5", Depth 24".
        *   `Base_Sink`: Standard base modified for a sink. Often has false front(s) instead of top drawer(s) and may lack a full back or top stretchers depending on sink type and plumbing access needs.
            *   **_Std Dimensions:_** Width varies (common: 30", 33", 36"). Height 34.5", Depth 24".
            *   **_Critical Measurement:_** Sink Cutout Dimensions (Width, Depth, Location - specific to sink model).
            *   **Sink Support Configuration (Crucial for Heavy Sinks):**
                *   `sink_support_method`: (String, Optional, Default='None') Defines how the sink is supported. Examples: 'SideCleats' (wood strips attached to internal sides), 'TopEdgeSupport' (sink rests on top edge of carcass sides, requires specific construction), 'InternalSupports' (dedicated internal panels transferring load to floor), 'MetalBrackets', 'None'.
                    *   **_Standard Practice Note:_** Heavy sinks (cast iron, fireclay, large composites) NEED dedicated support beyond just the countertop or face frame. 'SideCleats' (using 2x4s or plywood strips) is very common. Method choice impacts cabinet construction.
                *   `cleat_material_dimensions`: (String, Optional, Required if `sink_support_method`='SideCleats') e.g., "2x4 Lumber", "3/4 Plywood Strips 3in Wide". Defines the material used for cleats.
            *   `is_full_height_opening`: (Boolean, Optional, Default=False) Set to True if the cabinet interior is a single tall space (no top drawer/false front dividing structure), maximizing vertical clearance for plumbing.
            *   **Farmhouse/Apron Sink Considerations (Specific to this sink type):**
                *   **Cabinet Width:** The cabinet `dimensions.width` (Level 3) may need to be slightly *wider* than the nominal sink width to allow for fitting the sink and scribing the face frame or filler strips accurately to the sink's potentially irregular sides.
                *   **Front Cutout:** The method for creating the front opening needs definition. Options include cutting directly into the face frame (requires appropriate stile/rail configuration) or attaching a thicker "slab false front" panel to the cabinet below the sink and cutting the opening into that panel.
                    *   `front_cutout_panel_thickness`: (Float, Optional) If using the slab false front method, specifies the thickness of that panel.
        *   `Base_DrawerStack`: Composed entirely or primarily of drawers (e.g., 3 or 4 drawers). No doors.
            *   **_Std Dimensions:_** Width varies (common: 15", 18", 24", 30"). Height 34.5", Depth 24".
            *   **_Critical Measurement:_** Individual drawer opening heights.
        *   `Base_Cooktop`: Designed to house a drop-in cooktop, often with modified top rails/stretchers and space below for the appliance.
            *   **_Std Dimensions:_** Width varies (common: 30", 36"). Height 34.5", Depth 24".
            *   **_Critical Measurement:_** Cooktop Cutout Dimensions (Width, Depth, Location - specific to cooktop model).
        *   `Base_Corner_LazySusan`: L-shaped or diagonal corner cabinet designed for rotating shelves. Requires specific internal dimensions and door configuration.
            *   **_Std Dimensions:_** Typically 33"x33" or 36"x36" footprint for L-shape. Diagonal front varies.
            *   **_Critical Measurement:_** Door size/angle, internal clearance for mechanism.
        *   `Base_Corner_Blind`: Standard cabinet box partially obscured in a corner, requiring access through an adjacent opening. Often needs a filler and careful planning for door swing.
            *   **_Std Dimensions:_** Width varies (e.g., 36"-48", but only part is accessible). Height 34.5", Depth 24".
            *   **_Critical Measurement:_** Accessible Opening Width, Filler Width needed for clearance.
        *   `Base_Corner_Diagonal`: Corner cabinet with a single diagonal front face, typically with one door.
            *   **_Std Dimensions:_** Footprint usually requires 24" clear on each wall, diagonal face width varies.
        *   `Base_EndShelf`: Open shelf unit designed for the end of a cabinet run.
            *   **_Std Dimensions:_** Width often matches wall cabinet depth (e.g., 12"). Height 34.5", Depth 24" (or less if styled).
    *   **Wall Cabinets:**
        *   `Wall_Standard`: General purpose wall cabinet, typically with door(s) and adjustable shelves.
            *   **_Std Dimensions:_** Width varies (common: 12"-36"). Height varies (30", 36", 42"). Depth 12" (standard).
        *   `Wall_Corner_Diagonal`: Corner wall cabinet with a single diagonal front face.
            *   **_Std Dimensions:_** Footprint usually requires 12" clear on each wall, diagonal face width varies (often 24"). Depth 12" along wall.
        *   `Wall_Corner_L`: L-shaped corner wall cabinet, often with a bi-fold door.
            *   **_Std Dimensions:_** Typically 24"x24" footprint. Depth 12".
        *   `Wall_Corner_Blind`: Similar concept to Base Blind Corner but for wall cabinets.
            *   **_Std Dimensions:_** Width varies (e.g., 24"-36"). Height/Depth standard.
        *   `Wall_OverFridge`: Typically deeper (e.g., 24") and shorter height, often with vertical dividers for trays.
            *   **_Std Dimensions:_** Width matches fridge (e.g., 36"). Height varies (12"-24"). Depth 24".
        *   `Wall_MicrowaveShelf`: Open bottom section designed to hold a countertop microwave.
            *   **_Std Dimensions:_** Width varies (e.g., 24", 30"). Depth often 15"-18". Height standard wall height.
            *   **_Critical Measurement:_** Open Shelf Dimensions (W, H, D).
        *   `Wall_MicrowaveHood`: Designed to fit above a range with an integrated microwave/hood combo.
            *   **_Std Dimensions:_** Width typically 30". Depth 12". Height varies (e.g., 12"-18").
        *   `Wall_PlateRack`: Features vertical slots for plate storage.
        *   `Wall_WineRack`: Features lattice or individual cubbies for wine bottles.
        *   `Wall_EndShelf`: Open shelf unit for the end of a wall cabinet run.
            *   **_Std Dimensions:_** Width often matches wall cabinet depth (e.g., 12"). Height standard wall height. Depth 12".
    *   **Tall Cabinets:**
        *   `Tall_Pantry`: Floor-to-ceiling storage, typically shelves, potentially drawers or roll-outs at bottom.
            *   **_Std Dimensions:_** Width varies (18", 24", 30", 36"). Height standard (84", 90", 96"). Depth 24".
        *   `Tall_Oven`: Designed to house single or double wall ovens.
            *   **_Std Dimensions:_** Width typically 30" or 33". Height standard. Depth 24".
            *   **_Critical Measurement:_** Oven Cutout Dimensions (W, H, D, Placement - specific to oven model).
        *   `Tall_Utility`: Often used for brooms, mops, vacuum storage. May have reduced depth shelves or open space.
            *   **_Std Dimensions:_** Width narrow (e.g., 18"). Height standard. Depth 24".
        *   `Tall_RefrigeratorEnclosure`: Panels designed to box in a refrigerator, often with a `Wall_OverFridge` cabinet above.
            *   **_Std Dimensions:_** Width/Height/Depth sized to fit specific refrigerator model. Typically consists of two side panels and a cabinet above.
*   `dimensions`: (Dictionary, Required) Overall bounding box dimensions.
    *   `width`: (Float, Required)
    *   `height`: (Float, Required)
    *   `depth`: (Float, Required)
*   `construction_style`: (String, Required, Enum: 'Frameless', 'FaceFrame') Overrides global default if provided.
*   `carcass_material`: (String, Optional) Material name for main carcass panels. Overrides defaults.
*   `carcass_material_thickness`: (Float, Optional) Actual thickness. Overrides defaults.
*   `faceframe_material`: (String, Optional, Required if `construction_style`='FaceFrame') Material for face frame.
*   `faceframe_material_thickness`: (Float, Optional, Required if `construction_style`='FaceFrame') Actual thickness.
*   `finished_end_left`: (Boolean, Optional, Default=False) Indicates if the left side panel requires a finished material/appearance.
*   `finished_end_right`: (Boolean, Optional, Default=False) Indicates if the right side panel requires a finished material/appearance.
*   `scribe_left`: (Float, Optional, Default=0) Additional material width added to the left side for scribing to a wall.
*   `scribe_right`: (Float, Optional, Default=0) Additional material width added to the right side for scribing to a wall.
*   `toe_kick`: (Dictionary, Optional, Relevant for Base units)
    *   `height`: (Float, Required)
    *   `depth`: (Float, Required)
*   `door_drawer_config`: (List of Dictionaries, Required) Defines the layout of openings and what fills them (doors, drawers, false fronts, or open).
    *   `opening_id`: (String, Required) Unique identifier for this opening within the cabinet (e.g., "TopDrawerOpening", "LowerDoorOpeningLeft", "MainOpening").
    *   `opening_height`: (Float, Required) Height of this opening (critical for face frames and door/drawer sizing).
    *   `opening_width`: (Float, Required) Width of this opening (defaults to full cabinet internal width if single opening).
    *   `fill_type`: (String, Required, Enum: 'Door', 'Drawer', 'FalseFront', 'Open')
    *   `fill_definition`: (Dictionary, Required if `fill_type` != 'Open') Contains the full parameter set for the *Door/Drawer Front* (Level 6) that fills this opening. Allows defining doors/drawers directly within the cabinet configuration.
*   `shelf_config`: (Dictionary, Optional) Defines shelving for this unit.
    *   `fixed_shelf_locations`: (List of Floats, Optional) Vertical positions (e.g., distance from bottom) for fixed shelves.
    *   `adjustable_shelf_count`: (Integer, Optional, Default=0)
    *   `shelf_pin_hole_config_id`: (String, Optional) Reference to a `ShelfPinHoleConfig` ID (Level 5/10). If not provided, uses global default or a standard based on `construction_style`.
*   `internal_dividers`: (List of Dictionaries, Optional) Defines vertical or horizontal dividers within the carcass.
    *   `divider_id`: (String, Optional)
    *   `orientation`: (String, Required, Enum: 'Vertical', 'Horizontal')
    *   `position`: (Float, Required) Distance from left edge (for Vertical) or bottom edge (for Horizontal).
    *   `material`: (String, Optional) Defaults to carcass material.
    *   `thickness`: (Float, Optional) Defaults to carcass thickness.

### Level 4: Carcass Parameters

Defines the construction details of the main cabinet box, derived from Level 3 choices.

*   `carcass_id`: (String, Optional) Identifier for the carcass assembly.
*   `parent_unit_id`: (String, Required) Links back to the `unit_id` from Level 3.
*   `construction_style`: (String, Required, from Level 3) 'Frameless' or 'FaceFrame'.
*   `panel_material`: (String, Required, from Level 3)
*   `panel_thickness`: (Float, Required, from Level 3)
*   `assembly_method`: (String, Optional, Default based on style) How the carcass panels join (e.g., 'Dado', 'Rabbet', 'ButtJoint_Screw', 'PocketHole'). **Note:** Specific parameters for joinery cuts are defined in `MachiningOperations` on the individual component panels (Level 2).
*   `back_panel`: (Dictionary, Required)
    *   `type`: (String, Required, Enum: 'FullBack_Inset', 'FullBack_Applied', 'RailMount', 'None')
        *   `FullBack_Inset`: Back panel sits in a groove/rabbet within the sides, top, and bottom.
        *   `FullBack_Applied`: Back panel overlaps and is attached to the back edges of the sides, top, and bottom.
        *   `RailMount`: Uses horizontal rails (nailers) at top and bottom, often with a thinner back panel or none.
    *   `material`: (String, Optional) Often thinner (e.g., 1/4" or 1/2") than main carcass.
    *   `thickness`: (Float, Required if material different)
    *   `inset_depth`: (Float, Optional, Required for 'FullBack_Inset') Depth of the dado/rabbet.
    *   `rail_height`: (Float, Optional, Required for 'RailMount') Height of the mounting rails.
*   `top_construction`: (String, Required for Base/Tall, Enum: 'FullTop', 'Stretchers')
    *   `FullTop`: Solid panel across the top (common in frameless or specific designs).
    *   `Stretchers`: Rails across front and back (common in face frame base cabinets).
    *   `stretcher_width`: (Float, Optional, Required for 'Stretchers')
*   `component_definitions`: (List of Dictionaries, Auto-Generated) Details for each carcass panel based on above parameters, including dimensions and references for `MachiningOperations` (Level 9/10).
    *   `component_type`: (String, e.g., 'CT_CARCASS_SIDE')
    *   `component_instance`: (String, e.g., 'CI_SIDE_LEFT')
    *   `calculated_width`: (Float)
    *   `calculated_length`: (Float)
    *   `thickness`: (Float)
    *   `material`: (String)
    *   `machining_operations`: (List, initially empty or populated with basic joinery prep based on `assembly_method` and `back_panel.type`)

### Level 5: Face Frame Parameters (If Applicable)

Defines the structure attached to the front of a 'FaceFrame' style carcass.

*   `faceframe_id`: (String, Optional) Identifier for the face frame assembly.
*   `parent_unit_id`: (String, Required) Links back to the `unit_id` from Level 3.
*   `material`: (String, Required, from Level 3)
*   `thickness`: (Float, Required, from Level 3)
*   `stile_width_left`: (Float, Required)
*   `stile_width_right`: (Float, Required)
*   `rail_width_top`: (Float, Required)
*   `rail_width_bottom`: (Float, Required)
*   `intermediate_rails`: (List of Dictionaries, Optional) Defines horizontal rails between openings.
    *   `rail_width`: (Float, Required)
    *   `position_between_openings`: (List of Strings, Required) IDs of the openings this rail separates (from Level 3 `door_drawer_config`).
*   `intermediate_stiles`: (List of Dictionaries, Optional) Defines vertical stiles between openings.
    *   `stile_width`: (Float, Required)
    *   `position_between_openings`: (List of Strings, Required) IDs of the openings this stile separates.
*   `joinery_method`: (String, Optional, Default='PocketHole') How stiles and rails connect (e.g., 'PocketHole', 'Dowel', 'MortiseTenon'). **Note:** Specific parameters for joinery cuts/holes are defined in `MachiningOperations` on the individual stile/rail components.
*   `carcass_attachment_method`: (String, Optional, Default='GlueScrew') How face frame attaches to carcass.
*   `carcass_overhang`: (Float, Optional, Default=0.25) Amount the face frame overhangs the carcass sides (inside edge).
*   `component_definitions`: (List of Dictionaries, Auto-Generated) Details for each stile and rail based on above parameters, including dimensions and references for `MachiningOperations` (Level 9/10).
    *   `component_type`: (String, 'CT_FACEFRAME_STILE' or 'CT_FACEFRAME_RAIL')
    *   `component_instance`: (String, e.g., 'CI_FACEFRAME_STILE_LEFT', 'CI_FACEFRAME_RAIL_TOP')
    *   `calculated_width`: (Float)
    *   `calculated_length`: (Float)
    *   `thickness`: (Float)
    *   `material`: (String)
    *   `machining_operations`: (List, initially empty or populated with basic joinery prep based on `joinery_method`)

### Level 6: Doors & Drawer Fronts Parameters

Defines the specific appearance and construction details of a single door or drawer front, derived from the `fill_definition` in Level 3 `door_drawer_config`.

*   `door_drawer_front_id`: (String, Optional) Unique identifier.
*   `parent_opening_id`: (String, Required) Links back to the `opening_id` from Level 3.
*   `type`: (String, Required, Enum: 'Door', 'DrawerFront', 'FalseFront')
*   `style`: (String, Enum, Required) Visual style (e.g., `Slab`, `Shaker`, `RaisedPanel_Square`, `GlassPanel_SingleLite`). Inherited/defined in Level 3 `fill_definition`.
*   `construction_method`: (String, Enum, Optional) How it's built (e.g., `SolidSlab`, `CopeAndStick`, `Mitered`).
*   `material`: (String, Required) Primary material (frame/slab).
*   `thickness`: (Float, Required) Overall thickness.
*   `panel_material`: (String, Optional) Material for center panel if different (e.g., glass, plywood).
*   `overlay_type`: (String, Required, Enum: 'FullOverlay', 'PartialOverlay', 'Inset') Determines hinge type and sizing logic.
*   `overlay_reveal_values`: (Dictionary, Required) Defines gaps/overlaps relative to the opening.
    *   `top`: (Float) Reveal/Overlay amount at the top.
    *   `bottom`: (Float)
    *   `left`: (Float)
    *   `right`: (Float)
    *   `center_gap`: (Float, Optional, Default=0.125) Gap between pairs of doors/fronts.
*   `style_parameters`: (Dictionary, Optional) Style-specific dimensions.
    *   *(Example for Shaker/5-piece):* `stile_width`, `rail_width`, `panel_recess_depth`.
    *   *(Example for Raised Panel):* `stile_width`, `rail_width`, `panel_raise_profile_ref`, `panel_thickness`.
    *   *(Example for Glass Panel):* `stile_width`, `rail_width`, `mullion_width` (if applicable), `lite_count_x`, `lite_count_y`.
    *   *(Example for Beadboard):* `bead_spacing`, `bead_profile_ref`.
*   `edge_profile`: (String, Optional) Reference to a profile definition (e.g., 'Roundover_1/8', 'Chamfer_1/4'). Applied to the outside perimeter.
*   `hinge_specs`: (Dictionary, Required for 'Door')
    *   `hinge_id`: (String, Required) Reference to `HardwareSpec` ID for the hinge.
    *   `hinge_count`: (Integer, Required) Number of hinges (typically 2 or 3 based on height).
    *   `hinge_locations`: (List of Floats or String, Required) Positions from top/bottom edge, or standard pattern name (e.g., 'Standard_3').
*   `pull_knob_specs`: (Dictionary, Optional)
    *   `pull_id`: (String, Required) Reference to `HardwareSpec` ID for the pull/knob.
    *   `location`: (String or Coordinates, Optional, Default='Standard') Placement rule (e.g., 'CenterStileTopRailCorner', 'CenterOfDrawer', explicit coordinates).
*   `component_definitions`: (List of Dictionaries, Auto-Generated for non-slab styles) Details for stiles, rails, panels.
    *   `component_type`: (String, e.g., 'CT_DOOR_STILE', 'CT_DOOR_PANEL')
    *   `component_instance`: (String, e.g., 'CI_STILE_LEFT', 'CI_PANEL')
    *   `calculated_width`: (Float)
    *   `calculated_length`: (Float)
    *   `thickness`: (Float)
    *   `material`: (String)
    *   `machining_operations`: (List, initially empty or populated with basic joinery prep, hinge boring refs, pull drilling refs based on specs above).

### Level 7: Drawer Box Parameters

Defines the construction of the box behind a drawer front, heavily influenced by slide hardware.

*   `drawer_box_id`: (String, Optional) Unique identifier.
*   `parent_drawer_front_id`: (String, Required) Links back to the `door_drawer_front_id` from Level 6.
*   `slide_specs`: (Dictionary, Required)
    *   `slide_id`: (String, Required) Reference to `HardwareSpec` ID for the drawer slides.
    *   `slide_length`: (Float, Required) Nominal length of the slide (e.g., 18", 21").
    *   `clearances`: (Dictionary, Required, from `HardwareSpec`) Defines space needed.
        *   `side_clearance_total`: (Float) Total width reduction needed.
        *   `bottom_clearance`: (Float) Space needed below the box.
        *   `top_clearance`: (Float) Space needed above the box.
        *   `back_clearance`: (Float) Space needed behind the box.
    *   `undermount_slide_modifications`: (Dictionary, Optional, from `HardwareSpec`) Defines required cuts for undermount slides.
        *   `notch_required`: (Boolean)
        *   `notch_height`: (Float)
        *   `notch_depth`: (Float)
        *   `rear_mounting_holes_required`: (Boolean)
*   `box_material`: (String, Required) Material for sides, front, back.
*   `box_side_thickness`: (Float, Required)
*   `box_front_back_thickness`: (Float, Optional) Defaults to `box_side_thickness`.
*   `bottom_panel_material`: (String, Required)
*   `bottom_panel_thickness`: (Float, Required)
*   `corner_joinery`: (String, Required, Enum: 'DadoRabbit', 'Dovetail', 'BoxJoint', 'ButtScrew', 'PocketHole') Assembly method for corners. **Note:** Specific parameters for joinery cuts are defined in `MachiningOperations` on the individual components.
*   `bottom_panel_attachment`: (String, Required, Enum: 'Groove', 'AppliedBottom') How bottom attaches.
    *   `bottom_groove_depth`: (Float, Optional, Required for 'Groove')
    *   `bottom_groove_distance_from_edge`: (Float, Optional, Required for 'Groove')
*   `calculated_dimensions`: (Dictionary, Auto-Generated) Final box dimensions based on opening size and slide clearances.
    *   `width`: (Float)
    *   `height`: (Float)
    *   `depth`: (Float)
*   `component_definitions`: (List of Dictionaries, Auto-Generated) Details for sides, front, back, bottom.
    *   `component_type`: (String, e.g., 'CT_DRAWER_SIDE', 'CT_DRAWER_BOTTOM')
    *   `component_instance`: (String, e.g., 'CI_SIDE_LEFT', 'CI_BOTTOM')
    *   `calculated_width`: (Float)
    *   `calculated_length`: (Float)
    *   `thickness`: (Float)
    *   `material`: (String)
    *   `machining_operations`: (List, initially empty or populated with joinery prep, bottom groove refs, slide mounting refs based on specs above).

### Level 8: Shelf Parameters

Defines fixed or adjustable shelves within a cabinet.

*   `shelf_id`: (String, Optional) Unique identifier.
*   `parent_unit_id`: (String, Required) Links back to the `unit_id` from Level 3.
*   `type`: (String, Required, Enum: 'Fixed', 'Adjustable')
*   `material`: (String, Required)
*   `thickness`: (Float, Required)
*   `calculated_dimensions`: (Dictionary, Auto-Generated) Based on internal cabinet dimensions.
    *   `width`: (Float)
    *   `depth`: (Float)
*   `position`: (Float, Optional, Required for 'Fixed') Vertical location from bottom.
*   `edge_banding_front`: (String, Optional) Material/Spec reference.
*   `shelf_pin_hole_config_id`: (String, Optional, Required for 'Adjustable') Reference to the pattern used (Level 5/10).
*   `component_definitions`: (List of Dictionaries, Auto-Generated) Details for the shelf panel.
    *   `component_type`: (String, 'CT_SHELF')
    *   `component_instance`: (String, e.g., 'CI_SHELF_ADJ1', 'CI_SHELF_FIXED_MID')
    *   `calculated_width`: (Float)
    *   `calculated_length`: (Float)
    *   `thickness`: (Float)
    *   `material`: (String)
    *   `machining_operations`: (List, initially empty)

### Level 9: Joinery & Machining Details (Micro-Level - Conceptual Definitions)

Describes the *types* of precise geometric modifications applied to components. The *specific application* (dimensions, location) is stored in the `MachiningOperations` list on the Level 2-8 components.

*   **Purpose:** Define the geometry of connections and detailed features beyond the basic shape of a panel.
*   **Methodology:** These details are typically applied as secondary operations (e.g., boolean subtractions, extrusions) to the primary component geometry generated in Levels 4-8.
*   **Joinery Types:**
    *   `Dado`: Groove cut across the grain, typically to receive another panel (e.g., shelf, bottom panel into side panel). Requires `width`, `depth`, `location` (start/end points or offset), `face`, optionally `tool_ref`.
    *   `Groove`: Groove cut with the grain (similar parameters to Dado).
    *   `Rabbet`: L-shaped cut along an edge, often to receive a back panel. Requires `width`, `depth`, `edge`, `face`.
    *   `Mortise & Tenon`: Rectangular hole (mortise) receiving a corresponding projection (tenon). Complex, often simplified or defined by parameters in `MachiningOperations`.
    *   `Dovetail`: Interlocking trapezoidal pins and tails for strong drawer box corners. Complex, geometry often generated by CAM or specialized tools, parameters stored in `MachiningOperations`.
    *   `Box Joint`: Interlocking rectangular fingers for drawer box corners. Similar complexity to Dovetail.
    *   `Pocket Hole`: Angled hole for screw joinery, primarily used for face frames. Defined by `angle`, `diameter`, `depth`, `location` in `MachiningOperations`.
    *   `Dowel Hole`: Hole for cylindrical dowel joinery. Defined by `diameter`, `depth`, `location` in `MachiningOperations`.
*   **Other Machining Operation Types (for `MachiningOperations` list):**
    *   `Drill`: Standard hole (e.g., pilot holes, shelf pins, hardware mounting). Requires `diameter`, `depth`, `location` (coordinates or reference), `face`, optionally `pattern_ref`.
    *   `Counterbore/Countersink`: Modifications to a drilled hole. Requires parameters defining the bore/sink shape.
    *   `Notch`: Rectangular cutout, often for hardware clearance (e.g., undermount slides). Requires `height`, `depth`, `location/edge`.
    *   `Chamfer`: Beveled edge. Requires `size`/`angle`, `edge(s)`.
    *   `Roundover` / `Fillet`: Rounded edge. Requires `radius`, `edge(s)`.
    *   `EdgeProfile`: Application of a complex profile (e.g., Ogee, Cove) to an edge. Requires `profile_ref`, `edge(s)`, `tool_ref`.
    *   `VGroove`, `Flute`, `Bead`: Decorative linear cuts. Requires `profile_definition` (or standard type), `depth`, `path` (start/end points), `face`, `tool_ref`.
    *   `DecorativeGroove`: Freeform decorative cut. Requires `path_definition` (e.g., point list, SVG path), `depth`, `profile/tool_ref`, `face`.

### Level 10: Hardware Mounting Details (Micro-Level - Pattern Definition & Application)

Focuses on the precise requirements for attaching specific hardware, primarily through defined patterns.

*   **Purpose:** Ensure components are prepared correctly for hardware installation, enabling accurate assembly and function.
*   **Methodology - Pattern Definition (`HardwareSpec`):**
    *   Define reusable, named patterns within an external `HardwareSpec` entity (see Data Schema). Each pattern corresponds to a specific hardware component and application (e.g., hinge cup on door, hinge plate on frame, slide on cabinet side, slide on drawer side, pull handle).
    *   Each pattern definition includes:
        *   `pattern_name`: Unique identifier (e.g., `HingeCup_Blum71B`, `SlideMount_RevAShelf4WCSC_Cab`).
        *   `reference_point`: How the pattern is located (e.g., 'center_of_cup', 'center_of_pull', 'top_front_corner').
        *   `hole_definitions`: A list of holes, each with:
            *   Relative coordinates (`dx`, `dy`, `dz`) from the reference point.
            *   `diameter`.
            *   `depth`.
            *   Type (e.g., 'ScrewPilot', 'Cup', 'Dowel').
        *   `associated_cuts`: (Optional) Definitions for related notches or other modifications (e.g., undermount slide notches).
*   **Methodology - Pattern Application (`MachiningOperations`):**
    *   On the specific Component, Door/Front, or Drawer Box part, add an entry to the `MachiningOperations` list.
    *   Set `op_type` to 'Drill' (or 'Notch' etc. for associated cuts).
    *   Reference the pre-defined pattern using `pattern_ref`: (e.g., `"pattern_ref": "HingeCup_Blum71B"`).
    *   Specify the placement using `location_ref`: (e.g., `"location_ref": "TopHinge"`, `"location_ref": "Center"`, or explicit coordinates if needed).
    *   Specify the `face` of the part where the operation occurs (e.g., 'Inside', 'Back', 'Front', 'Edge_Top').
*   **Benefits:**
    *   **SSOT:** Hardware details defined once in `HardwareSpec`.
    *   **Consistency:** Ensures correct mounting for specific hardware every time.
    *   **Maintainability:** Updating hardware involves changing the `HardwareSpec` and potentially the `pattern_ref` in the script logic, not searching for explicit coordinates scattered throughout.
    *   **Automation:** Allows the script to automatically apply the correct complex hole patterns based on selected hardware.

## IV. Construction Styles & Impact

Detailed comparison of Face Frame vs. Frameless construction.

**1. Frameless (Euro-style):**
    *   **Description:** Cabinet box constructed from panels only (sides, top, bottom, back). Doors and drawer fronts mount directly to the cabinet box edges, covering most or all of the front edge (full overlay is typical).
    *   **Pros:** Maximizes interior accessibility and space, cleaner modern aesthetic, potentially simpler box construction, well-suited for modular systems and manufactured panels (melamine, laminate).
    *   **Cons:** Requires precise box construction (squareness is critical), relies heavily on edge banding for appearance and durability, requires specific Euro-style hardware (concealed hinges, often system holes/line boring).
    *   **Rhino Script Impact:**
        *   Requires accurate panel thickness.
        *   Focus on precise door/drawer sizing based on full overlay reveals.
        *   Edge banding specifications become important.
        *   Requires logic for system holes (line boring) for adjustable shelves and hardware mounting if implementing that level of detail.
        *   Back panel typically inset into grooves/rabbets.

**2. Face Frame:**
    *   **Description:** A solid wood frame (stiles - vertical, rails - horizontal) is attached to the front of the cabinet box. Doors and drawer fronts mount to this frame.
    *   **Pros:** Provides rigidity to the box, traditional aesthetic, more forgiving of slight box imperfections, allows for various door overlay types (inset, partial, full overlay on frame), joinery options for frame itself add strength.
    *   **Cons:** Reduces interior access width slightly due to frame thickness, can be more complex/time-consuming to build (frame joinery), requires different hinge types depending on overlay.
    *   **Rhino Script Impact:**
        *   Requires separate modeling logic for the face frame components (stiles, rails) based on Level 5 parameters.
        *   Requires logic for face frame joinery (pocket holes, dowels, etc.) - potentially simplified representation or stored in `MachiningOperations`.
        *   Requires logic for attaching frame to carcass (often with glue/screws, may involve rabbet on carcass edge).
        *   Door/drawer sizing depends on the overlay type *relative to the face frame opening*, not the carcass edge.
        *   Requires different hinge mounting logic (plate mounts to frame stile).
        *   Back panel can be inset or applied.
        *   Top construction often uses stretchers instead of a full top panel.

## V. Common Joinery Methods & Geometric Impact

How parts connect and how it affects modeling.

*   **Butt Joint:** Simplest. Edges meet. Often reinforced (screws, dowels, pocket holes). **Model Impact:** Minimal, parts touch. Reinforcement modeled via `MachiningOperations` (Drill) if needed.
*   **Dado/Groove:** Slot cut in one piece receives edge of another. **Model Impact:** Requires boolean subtraction on the receiving piece. Parameters (`width`, `depth`, `location`) needed for `MachiningOperations`.
*   **Rabbet:** L-shaped cut on edge. Often used for back panels or where parts overlap. **Model Impact:** Requires boolean subtraction on the receiving piece. Parameters (`width`, `depth`, `edge`) needed for `MachiningOperations`.
*   **Mortise & Tenon:** Rectangular hole (mortise) + projection (tenon). Strong frame joinery. **Model Impact:** Complex. Often simplified to butt joint visually, with strength implied, or detailed geometry generated if needed for CNC. Parameters stored in `MachiningOperations`.
*   **Dovetail / Box Joint:** Interlocking joints for drawer boxes. **Model Impact:** Very complex. Usually represented as simple box corners visually unless detailed CNC output is required. Geometry generated by specialized logic/CAM. Parameters stored in `MachiningOperations`.
*   **Pocket Hole:** Angled holes for screws. Common for face frames. **Model Impact:** Requires angled cylindrical subtractions. Defined via `MachiningOperations` (`Drill` type with angle).
*   **Dowel:** Cylindrical pins in matching holes. **Model Impact:** Requires cylindrical subtractions (`Drill` type). Defined via `MachiningOperations`.

## VI. Standard Material Thicknesses & Considerations

Nominal vs. Actual is critical for accurate modeling.

*   **Plywood (North America):**
    *   Nominal 3/4" -> Actual ~0.70" - 0.73" (Commonly 18mm or slightly less)
    *   Nominal 1/2" -> Actual ~0.45" - 0.48" (Commonly 12mm)
    *   Nominal 1/4" -> Actual ~0.18" - 0.22" (Commonly 5mm)
*   **MDF/Particle Board:** Often closer to nominal, but verify. Metric sizes (12mm, 15mm, 18mm) common.
*   **Hardwood (S4S - Surfaced 4 Sides):**
    *   Nominal 1" (4/4) -> Actual ~0.75"
    *   Nominal 5/4 -> Actual ~1.0"
    *   Nominal 6/4 -> Actual ~1.25"
    *   Nominal 8/4 -> Actual ~1.75"
*   **Impact:** Using nominal thickness (e.g., 0.75") when actual is 0.71" will cause cumulative errors in joinery depth, overall dimensions, and component fit.
*   **Recommendation:** Script **MUST** use `actual_thickness` parameter provided for materials. Maintain a material library with accurate actual thicknesses.

## VII. Hardware Considerations & Clearances

Key hardware types and their impact on cabinet dimensions and component design.

**1. Hinges:**
    *   **Euro/Concealed Hinges (Frameless & Face Frame w/ specific plates):**
        *   **Critical:** Require cup hole drilled in door (`diameter`, `depth`, `edge_offset` - defined by `HardwareSpec` pattern).
        *   **Critical:** Mounting plate attaches to cabinet side (frameless) or face frame (face frame). Hole pattern specific to hinge/plate (`HardwareSpec` pattern).
        *   **Critical:** Overlay type (full, half, inset) determined by hinge/plate combination. Dictates door sizing relative to opening.
    *   **Face Mount / Wrap Hinges (Face Frame Only):**
        *   Visible hinge leaf mounts to face frame edge and surface.
        *   Requires specific overlay calculation based on hinge geometry.
        *   Requires screw pilot holes (`Drill` operations).
    *   **Inset Hinges (Face Frame Only):**
        *   Barrel is visible in the gap. Leaf mounts to frame edge and door edge/back.
        *   Requires precise door sizing for inset reveal.
        *   May require mortise cut (`MachiningOperations`).

**2. Drawer Slides:**
    *   **Side Mount (Epoxy/Ball Bearing):**
        *   Mount to cabinet side and drawer box side.
        *   **Critical:** Require specific side clearance (typically 1/2" total per pair). Drawer box width = Opening Width - Total Clearance.
        *   Requires mounting holes (`Drill` operations) on cabinet side and drawer side.
    *   **Undermount (Concealed):**
        *   Mount to cabinet side/bottom and underside of drawer box.
        *   **Critical:** Require specific side, bottom, and often top/back clearances (defined in `HardwareSpec`).
        *   **Critical:** Often require notch in drawer box back (`Notch` operation defined in `HardwareSpec` associated modifications and applied via `MachiningOperations`).
        *   **Critical:** May require specific drawer box side thickness.
        *   Requires mounting holes (`Drill` operations, often referencing `HardwareSpec` patterns).

**3. Pulls & Knobs:**
    *   Requires mounting hole(s) drilled through door/drawer front (`Drill` operation, often referencing `HardwareSpec` pattern for spacing).
    *   Location standardized or specified.

**4. Shelf Pins/Supports:**
    *   Requires pattern of holes drilled in cabinet sides/dividers (`Drill` operation referencing `ShelfPinHoleConfig` pattern).
    *   Common system: 32mm spacing, 5mm diameter.

**5. Adjustable Feet/Levelers:**
    *   May require mounting holes/sockets in cabinet bottom panel (`Drill` operation).

**Recommendation:** Abstract hardware details into a `HardwareSpec` definition (external JSON/DB). The script logic selects the appropriate hardware ID, retrieves its required clearances and mounting patterns, and applies them during component generation and `MachiningOperations` definition.

## VIII. Aesthetics & Style Choices

How visual choices impact parameters.

*   **Door/Drawer Style:** (Slab, Shaker, Raised Panel, etc.) - Directly impacts Level 6 parameters (`style`, `style_parameters`).
*   **Wood Species/Grain:** Affects material choice (Levels 3, 4, 5, 6, 7, 8) and potentially grain direction considerations for component orientation.
*   **Finish:** (Paint, Stain, Clear Coat) - Primarily affects material choice (e.g., Paint Grade MDF/Maple vs. Stain Grade Oak/Cherry) and potentially edge banding choice. Does not usually impact geometry directly, but crucial for final product definition.
*   **Edge Profiles:** (Roundover, Chamfer, Ogee) - Defined as `EdgeProfile` parameter (Level 6) or `EdgeProfile` `MachiningOperation` (Level 9).
*   **Reveals/Overlays:** Critical parameter set (Level 6 `overlay_reveal_values`) dictating door/drawer size and spacing.
*   **Decorative Elements:** (Fluting, V-Grooves, Applied Moldings) - Require specific `MachiningOperations` (Level 9) or potentially separate component modeling (for complex moldings).

## IX. Output Considerations for Downstream Use

What information needs to be embedded or easily extractable for other processes?

*   **Cut List Generation:** Needs accurate final dimensions (W, L, T) for every component (Levels 4, 5, 6, 7, 8), Material Type, Part Name/ID, Edge Banding info.
*   **CNC Machining:** Needs geometry suitable for CAM, plus embedded `MachiningOperations` data (hole locations/diameters/depths, dado/groove paths/depths/widths, profile paths) linked to specific faces/edges. Tool references (`tool_ref`) are valuable.
*   **Assembly Instructions:** Needs clear part identification (Part IDs), joinery indicators (even if simplified), hardware locations marked.
*   **Ordering/Purchasing:** Needs Material summaries, Hardware counts (hinges, slides, pulls referenced by `HardwareSpec` ID).
*   **Visualization:** Needs accurate geometry, material assignments (potentially linked to Rhino materials/textures).

**Recommendation:** Store as much relevant data as possible using Rhino's User Text (Attribute User Text on individual objects/geometry). Use clear, consistent key names (matching the Data Schema). For complex data (like `MachiningOperations`), store as serialized JSON strings. Assign unique, persistent IDs (e.g., UUIDs, generated Part IDs) to components for reliable tracking.

## X. Edge Banding

Process and where it applies.

*   **Purpose:** Cover exposed core of panel materials (Plywood, MDF, Particle Board) for aesthetics and durability.
*   **Application:** Typically applied to front edges of frameless cabinets, shelves, and sometimes door/drawer edges (if slab construction).
*   **Material:** Thin tape (PVC, Veneer, Melamine) or thicker solid wood strips.
*   **Thickness:** Important consideration. Thin tape (~0.5mm) has minimal geometric impact. Thicker wood bands (~1/8" - 1/4") effectively change the finished dimension of the part and must be accounted for in modeling (either by adjusting panel size or modeling the band as separate geometry).
*   **Rhino Script Impact:** Need parameters on relevant components (Carcass panels, Shelves, Doors/Drawers) to specify which edges get banding and the banding material/thickness (`EdgeBandingFront`, `Back`, `Left`, `Right` attributes).

## XI. Specific Cabinet Types & Considerations

**1. `unit_type: Base_DishwasherSpace`**
    *   **Description:** Represents the standard opening required for a built-in dishwasher. This unit type typically does not generate its own cabinet geometry but acts as a critical placeholder in the `cabinet_sequence` (Level 2) to define the space and influence adjacent units.
    *   **_Standard Dishwasher Considerations:_**
        *   **Function:** Defines the gap where a dishwasher will be installed.
        *   **Standard Widths:** Primarily 18" or 24". The `dimensions.width` parameter for this unit MUST match the required dishwasher width.
        *   **Standard Height:** Typically aligns with standard base cabinet height (34.5") to fit under the countertop.
        *   **Standard Depth:** Assumed to be standard 24" cabinet depth.
        *   **Impact on Adjacent Cabinets:** The cabinets immediately to the left and right of this space often require `finished_end_panels` facing the dishwasher opening, unless the dishwasher model is designed to integrate directly with unfinished sides or decorative panels are added separately.
            *   **_Edge Treatment Note:_** If an adjacent cabinet is **Frameless**, its side panel edge facing the dishwasher opening **must be edge-banded**. If the adjacent cabinet is **Face Frame**, the face frame stile itself provides the finished edge.
        *   **Countertop Support:** Ensure adequate countertop support across the dishwasher span (often handled by the countertop material itself or support strips attached to adjacent cabinets).
        *   **Utility Planning:** Requires dedicated space/routing for water supply, drain hose, and electrical connection nearby (usually planned in adjacent sink base). Script does not model utilities, but this is a critical installation consideration.
    *   **Required Parameters (Level 3):**
        *   `unit_type` = 'Base_DishwasherSpace'.
        *   `dimensions`: Primarily `width` (Required, e.g., 18 or 24), `height` (Required, e.g., 34.5), `depth` (Required, e.g., 24).
    *   **Optional Parameters / Notes:**
        *   `adjacent_left_requires_finished_end`: (Boolean, Optional) Hint for the cabinet to the left.
        *   `adjacent_right_requires_finished_end`: (Boolean, Optional) Hint for the cabinet to the right.
        *   `dishwasher_model`: (String, Optional) For reference.
    *   **Modeling Implications:** The script should primarily use this definition to reserve space in the layout and potentially trigger modifications (like adding finished ends) to the neighboring `unit_id`s based on the optional boolean parameters.

**2. `unit_type: Base_ApplianceOpening`**
    *   **Description:** Base cabinet modified to provide a specific opening for an under-counter appliance like a microwave drawer, wine fridge, or small refrigerator. Requires precise cutout dimensions based on the appliance specifications.
    *   **_Standard Appliance Opening Considerations:_**
        *   **Function:** Creates a dedicated housing with a finished opening for a specific appliance.
        *   **Construction:** Typically a standard base cabinet carcass (sides, bottom, toe kick) but with a modified front (face frame or frameless) to create the required opening. Top construction might be minimal stretchers or a full top depending on appliance height and countertop support needs.
            *   **_Edge Treatment Note:_** If **Frameless**, the cut edges of the carcass panels forming the opening **must be edge-banded**. If **Face Frame**, the face frame itself provides the finished edge around the opening.
        *   **Cutout Dimensions (W x H):** **MANUFACTURER SPECIFICATIONS ARE CRITICAL.** Opening width and height must precisely match the appliance requirements for proper fit and potentially required ventilation gaps.
        *   **Depth:** Cabinet depth must accommodate appliance depth plus any required rear clearance for ventilation or utility connections (electrical outlet often behind or adjacent).
        *   **Support:** Some heavier appliances (like packed wine fridges) might require a reinforced bottom shelf/platform within the cabinet.
        *   **Ventilation:** Check appliance specs for required airflow clearances (front, back, top, sides) and if any specific vent cutouts are needed in the cabinet toe kick, back, or sides.
        *   **Utility Access:** Ensure required electrical outlet (or other utilities) is located appropriately, accessible but not obstructing the appliance.
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Base_ApplianceOpening', `dimensions` (Overall W/H/D), `construction_style`.
        *   Level 4: Carcass parameters (`panel_material`, `thickness`, `assembly_method`, back details, toe kick, `bottom_panel`). `top_construction` type may vary.
        *   Level 5 (if FaceFrame): Face frame parameters defining the *specific opening size* required by the appliance. This will likely involve specific stile/rail widths and potentially no intermediate rails/stiles within the opening.
        *   **External Input:** Appliance Required Opening Dimensions (Width, Height) - *Must be provided from manufacturer specs.*
        *   **External Input:** Appliance Depth & Rear Clearance Required - *Must be provided from manufacturer specs.*
        *   **External Input:** Appliance Electrical/Utility Requirements & Location.
    *   **Optional Parameters / Modifiers (from Levels 3-10):**
        *   Level 3: `scribe_left`, `scribe_right`, `finished_end_left`, `finished_end_right`.
        *   Level 4: Specific modifications for ventilation cutouts (toe kick, back), reinforced bottom platform details.
        *   Level 5: Specific face frame joinery.
        *   Level 6: Potentially a small `DrawerFront` above or below the opening if design allows.
        *   Level 7: Corresponding `Drawer Box` if drawer exists.
        *   Level 9: Specific `Joinery Details`.
        *   Level 10: Specific `Hardware Mounting Details` (e.g., mounting brackets if required by appliance).
        *   `appliance_model`: (String, Optional) For reference.
        *   `ventilation_notes`: (String, Optional) Specific instructions for vent cutouts.
        *   `support_notes`: (String, Optional) Specific instructions for reinforcement.
    *   **_ACTION REQUIRED / WARNING:_** Before finalizing any cabinet design, fabrication, or generating cutting lists for a `Base_ApplianceOpening` unit, the **actual manufacturer's specifications (required opening W/H, appliance depth, clearances, ventilation, utility locations) MUST be obtained and meticulously followed.** Failure to do so risks the appliance not fitting, overheating, malfunctioning, or creating safety hazards.

**3. `unit_type: Base_EndShelf`**
    *   **Description:** Open shelf unit designed to terminate a run of base cabinets, providing display space and a softer visual end than a flat panel. Can have square, angled, or curved front edges.
    *   **_Standard End Shelf Considerations:_**
        *   **Function:** Primarily decorative display, cookbook storage.
        *   **Common Width:** Typically narrow, often 9" to 12", sometimes matching adjacent wall cabinet depth for visual continuity.
        *   **Common Height/Depth:** Matches standard base cabinet height (34.5") and depth (24").
        *   **Construction Style:** Usually matches the main cabinets (FaceFrame or Frameless appearance).
        *   **Box Components:** Typically includes an exposed side panel, a bottom panel, a top panel (or top stretchers), fixed shelves, and a back panel or attachment method to the adjacent cabinet.
            *   **_Attachment Method (Critical):_** Define how it joins the adjacent cabinet. Options: 
                1.  Built with its own back panel, screwed through the back into the adjacent cabinet's side.
                2.  Attached directly to the adjacent cabinet's side panel (using pocket holes or screws from inside the adjacent cabinet *before* its back is installed).
            *   **_Build Note:_** Method 2 creates a cleaner interior transition but requires coordinated assembly. Method 1 is simpler to build as a separate unit.
        *   **Shelves:** Usually fixed for rigidity, especially if wider than 12". Shelf spacing can be equal or custom.
        *   **Toe Kick:** Must align with the adjacent cabinet's toe kick height and depth.
        *   **Edge Finishing (Critical):** The front edges of the exposed side panel and all shelves **must** have appropriate finish (e.g., edge banding for plywood/MDF, solid wood nosing). Raw core material should not be visible.
    *   **_Style Variations:_**
        *   **Angled Front:** Front edge of side panel and shelves cut at an angle (e.g., 45 degrees). Requires precise angle cuts.
        *   **Curved Front:** Front edge of side panel and shelves have a radius (concave or convex). Requires more complex fabrication (shaping solid wood or bent lamination).
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Base_EndShelf', `dimensions` (W/H/D), `construction_style` (determines appearance).
        *   Level 4: Carcass parameters (`panel_material`, `thickness`, `bottom_panel`, `top_construction`, `back_panel` definition or `attachment_method` clarification), `toe_kick` details.
        *   Level 8: Shelf parameters (`type`='Fixed' typically, `material`, `thickness`, `position_vertical` for each shelf), `front_edge_banding` definition.
    *   **Optional Parameters / Modifiers (from Levels 3-10):**
        *   `shelf_front_style`: (String, Optional, Default='Square') Values: 'Square', 'Angled', 'Curved'.
        *   `shelf_angle` / `shelf_radius`: (Float, Optional, Required if style is Angled/Curved).
        *   Level 9: Specific `Joinery Details` (e.g., dadoes for fixed shelves).

**4. `unit_type: Base_Corner_LazySusan`**
    *   **Description:** Corner base cabinet designed to maximize accessibility using rotating shelves (Lazy Susan hardware). Can be constructed as an L-shape fitting a square corner or with a diagonal front face.
    *   **_Standard Lazy Susan Considerations (Reference/Defaults):_**
        *   **Common Footprints:**
            *   **L-Shape (90-degree corner):** Typically requires 33"x33" or 36"x36" wall space. Results in an L-shaped cabinet box.
            *   **Diagonal Front:** Typically requires 24" of wall space from the corner along each wall. Results in a pentagonal cabinet box with a single diagonal face.
        *   **Hardware Types & Corresponding Doors:**
            *   **Pie-Cut:** Shelves are ~3/4 circles. **Doors are attached to the shelves** and rotate inward. Requires a **bi-fold door** hinged at the center of the cabinet opening.
            *   **Kidney-Shaped:** Shelves rotate independently *behind* the door. Typically used with **L-shaped cabinets** and a standard hinged door (or sometimes bi-fold).
            *   **Full-Round:** Shelves are full circles. Requires a **Diagonal Front** cabinet with a single hinged door.
        *   **Shelf Diameter:** Common diameters range from 18" to 32", chosen based on cabinet size to maximize storage while allowing rotation clearance.
        *   **Mounting:** Hardware typically uses a central post mounted bottom/top (or under countertop) and potentially bearings.
        *   **Clearances:** Internal cabinet dimensions must provide adequate clearance for the specified shelf diameter to rotate without hitting cabinet walls, face frames, or adjacent cabinet components.
        *   **_Note:_** The *exact hardware model* dictates precise shelf dimensions, required clearances, mounting hole locations, and door attachment method (if pie-cut).
        *   **_Build Accuracy Note (Critical):_** For smooth operation, especially with Pie-Cut doors, the cabinet box **must be built perfectly square** (90-degree corners for L-shape, correct angles for diagonal). Any deviation can cause doors/shelves to bind against the frame or carcass.
        *   **_Installation Timing Note:_** The Lazy Susan hardware mechanism (post, shelves) is typically installed *after* the main cabinet carcass has been assembled, and often after the cabinet unit has been installed in its final location.
    *   **Required Parameters (from Levels 4-10):**
        *   Level 3: `construction_style`, `dimensions` (defining the overall footprint, e.g., 36x36 for L-shape, or width/depth/diagonal_face_width for diagonal).
        *   Level 4: `side_panels` (thickness), `bottom_panel` (thickness, position), `top_construction` (Type, thickness/width - often stretchers or modified), `back_panel` (Often partial or specific configuration for L-shape/diagonal).
        *   Level 4/Internal: `lazy_susan_hardware_type` (String, Required: 'PieCut', 'Kidney', 'FullRound') - Dictates door logic and internal clearances.
        *   Level 4/Internal: `lazy_susan_shelf_diameter` (Float, Required).
        *   Level 5 (if `construction_style`='FaceFrame'): Face frame configuration depends heavily on shape (L-shape vs Diagonal) and hardware.
            *   *L-Shape:* Requires specific frame members meeting at 90 degrees.
            *   *Diagonal:* Requires a single frame opening at 45 degrees to the sides.
        *   Level 6: Door Definition(s):
            *   If `lazy_susan_hardware_type`='PieCut': Requires definition for a **bi-fold door pair** (`opening_reference`, `type`='Door', `style`, `hinge_type`='BiFold', etc.) size calculated based on opening & hardware.
            *   If 'Kidney' or 'FullRound': Requires definition for a standard hinged door (`opening_reference`, `type`='Door', `style`, `overlay_type`, etc.).
        *   Level 10 (Implicit): Hardware mounting locations for the Lazy Susan post/bearings and door hinges (details derived from hardware type/specs).
    *   **Optional Parameters / Modifiers (from Levels 4-10):**
        *   Level 3: `scribe_left`, `scribe_right`, `finished_end_left`, `finished_end_right` (less common for corner units unless end of run).
        *   Level 4: `panel_material`, `carcass_joinery_method`.
        *   Level 5: `face_frame_material`, `face_frame_joinery_method`.
        *   Level 6: `material`, `style` for the door(s).
        *   Level 8: `Shelves` (The Lazy Susan shelves replace standard shelves, but hardware definition is key).
        *   Level 9: Specific `Joinery Details` for carcass assembly.
        *   Level 10: Explicit hardware mounting parameters if deviating from standard placement based on hardware type.
        *   `lazy_susan_shelf_material`: (String, Optional: 'Polymer', 'Wood', 'Wire')
        *   `lazy_susan_number_of_shelves`: (Integer, Optional, Default=2)
    *   **External Input:** Specific Lazy Susan Hardware Model/Manufacturer Specs (Recommended for ensuring correct clearances, mounting locations, and door interaction, especially for Pie-Cut).
        *   **_ACTION REQUIRED / WARNING:_** While the script can model common Lazy Susan configurations based on `hardware_type` and `shelf_diameter`, using the **specific hardware manufacturer's specifications is strongly recommended.** Clearances, post locations, and door mounting details (for Pie-Cut) vary. Incorrect assumptions can lead to binding, improper fit, or inability to install the hardware.

**5. `unit_type: Base_Corner_Blind`**
    *   **Description:** Standard base cabinet box designed to occupy a corner where access to the interior is partially obstructed by the adjacent perpendicular cabinet run. Requires careful planning for access, fillers, and often utilizes specialized pull-out hardware to make the hidden space usable.
    *   **_Standard Blind Corner Considerations (Reference/Defaults):_**
        *   **Functionality:** Maximizes corner space utilization, but access is inherently limited compared to other corner solutions. Functionality heavily depends on the chosen internal hardware.
        *   **Construction:** Typically a standard rectangular cabinet box, often wider than a normal base (e.g., 39", 42", 45", 48" total wall space used) to provide a usable opening and internal depth for hardware. One side extends fully into the "blind" corner.
        *   **Filler Requirement (CRITICAL):** A filler strip **must** be planned between the blind cabinet's accessible opening and the adjacent cabinet run. This prevents the adjacent cabinet's doors/drawers/hardware from colliding with the blind cabinet's door/hardware.
            *   *Typical Filler Width:* 3" to 6", determined by adjacent cabinet door style, overlay, thickness, and hardware projection.
            *   *Calculation:* This is a critical calculation involving both cabinets. **Ensure the calculation accounts for the projection of handles/knobs on the adjacent cabinet, not just the door thickness/overlay.**
        *   **Door/Opening:** Usually a single door covering the accessible opening (Cabinet Width - Blind Side Depth - Filler Width = Opening). Drawer stacks are uncommon due to access limitations.
        *   **Internal Hardware:** Essential for accessing the blind space. Common types:
            *   *Swing-Out Shelves:* Kidney-shaped or similar shelves mounted on pivots/slides that swing out of the cabinet.
            *   *Pull-Out Units:* Basket/shelf systems that pull forward and then slide sideways into the opening (e.g., Hafele Magic Corner I/II, Rev-A-Shelf Blind Corner Optimizer).
            *   Each hardware type has specific minimum opening width/height requirements and internal cabinet depth/width needs.
        *   **_Build Accuracy Note (Critical):_** The cabinet box must be square, and the filler calculation and installation must be precise for proper door function and adjacent cabinet clearance.
        *   **_Installation Note:_** Installing complex pull-out hardware is often significantly easier *before* the countertop is installed due to limited access afterwards.
        *   **_Client Communication Note:_** Clearly communicate the access limitations and the functionality of the specific chosen hardware to manage expectations.
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Base_Corner_Blind', `dimensions` (Overall Width, Height, Depth), `style` (FaceFrame/Frameless), `finished_end_left`/`right` (esp. the side facing out).
        *   Level 3/Other: `blind_side`: ('Left'/'Right') Specifies which side extends into the blind corner.
        *   Level 3/Other: `filler_width_adjacent`: (Float, Required) The calculated width of the filler needed on the *access* side.
        *   Level 3/Other: `pull_out_hardware_model`: (String, Required for functionality) Specific make/model of the internal hardware (e.g., "Rev-A-Shelf 5PSP-15-CR"). This dictates internal requirements.
        *   Level 4: Carcass parameters (`construction_style`, `panel_material`, `thickness`, `assembly_method`, back details, toe kick).
        *   Level 5 (if FaceFrame): Face frame parameters (`stiles_rails_material`, `width`, `thickness`, `joinery`). Frame design must accommodate the opening and filler.
        *   Level 6: Door parameters (`opening_reference`, `type`='Door', `style`, `overlay_type`, `overlay_reveal_values`, `thickness`).
        *   Level 8: Shelves (typically determined *by* the pull-out hardware, not standard shelves).
        *   Level 9: Joinery details.
        *   Level 10: Hardware mounting (hinges for the door, potentially mounting points for the pull-out if not covered by its specs).
        *   `hardware_installation_timing`: (String, Optional, Default="After Carcass Assembly") Note on *when* hardware should be installed (e.g., "Before Countertop", "During Final Install").

**6. `unit_type: Base_Corner_Diagonal`**
    *   **Description:** Corner base cabinet featuring a single front face set diagonally (typically 45 degrees) between the two adjacent cabinet runs. Provides a large, open interior space but can present accessibility challenges.
    *   **_Standard Diagonal Corner Considerations (Reference/Defaults):_**
        *   **Footprint:** Commonly requires 24" of clear wall space from the corner along each adjacent wall for a standard unit. This typically results in a diagonal face width of ~17". Larger units, especially for sinks, require more wall space (e.g., 36"-42" per wall).
        *   **Construction:** The cabinet box is pentagonal. Side panels meet the back panels at a 135-degree internal angle (requiring a 22.5-degree bevel cut on the back edge of the side panels if joining directly). The front face (face frame or frameless edge) sits at 45 degrees to the side panels.
            *   **_Build Accuracy Note (Critical):_** Precise angle cuts on carcass panels and the face frame (if used) are essential for proper assembly and a square final unit, potentially using jigs or double-checking saw/CNC setups. Ensure back panels fit correctly to maintain rigidity.
        *   **Door:** Typically a single, large door hinged on one side of the diagonal face. Requires careful consideration of door swing clearance against adjacent cabinets/appliances.
        *   **Interior/Accessibility:** Usually contains one or two full-depth adjustable shelves. The deep central space can be difficult to access fully. Specialized pull-out accessories are less common than for other corner types.
        *   **Countertop Impact:** The angled front requires a larger countertop piece with a diagonal cut, potentially increasing material waste and fabrication cost compared to 90-degree corners.
        *   **Popularity:** Less common than Lazy Susan or Blind Corner units for general storage due to accessory limitations, but frequently used for corner sinks.
    *   **_Corner Sink Variation:_**
        *   If used as a sink base, it requires a significantly larger footprint (e.g., 36" or 42" wall space) to achieve a diagonal front face wide enough for a standard sink cutout (e.g., ~25"+ face width often needed).
        *   Calculating required wall space based on desired face width involves trigonometry (similar to calculations mentioned in research for sink bases).
        *   Requires robust sink support structure within the angled cabinet.
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Base_Corner_Diagonal', `dimensions` (defining wall space used, e.g., 24x24 or 36x36, plus Height, Depth), `construction_style` (FaceFrame/Frameless).
        *   Level 4: Carcass parameters (`panel_material`, `thickness`, `assembly_method`, back details, toe kick). *Note: Back panel configuration needs careful definition for the angled shape.* `top_construction` often stretchers.
        *   Level 5 (if FaceFrame): Face frame parameters (`material`, `thickness`, `joinery`). Frame is a single opening with stiles/rails meeting at 45 degrees to sides.
        *   Level 6: Door parameters (`opening_reference`, `type`='Door', `style`, `overlay_type`, `overlay_reveal_values`, `thickness`).
        *   Level 8: Shelf parameters (`type`='Adjustable' or 'Fixed', `material`, `thickness`, `shelf_pin_holes_config` if adjustable).
        *   **External Input (If Sink Base):** Sink Cutout Dimensions & Location.
    *   **Optional Parameters / Modifiers (from Levels 3-10):**
        *   Level 3: `scribe_left`, `scribe_right` (less common as it meets other cabinets), `finished_end_left`/`right` (if end of run).
        *   Level 4: Specific carcass joinery details (esp. angled joints).
        *   Level 5: Specific face frame joinery details.
        *   Level 6: Door `material`, `style`, `edge_profile`, `pull_hardware_location`.
        *   Level 9: Specific `Joinery Details` (e.g., modeling bevels).
        *   Level 10: Specific `Hardware Mounting Details` (hinges, shelf pins).

**7. `unit_type: Wall_Standard`**
    *   **Description:** General purpose wall-mounted cabinet, typically featuring one or two doors and adjustable shelving. Used for storing dishes, glasses, food items, etc.
    *   **_Standard Wall Cabinet Considerations:_**
        *   **Common Dimensions:** Width varies (12"-36"+). Height varies (12", 15", 18", 24", 30", 36", 42"). Depth is typically 12" standard, but can be 15", 18", 24" (e.g., over refrigerators).
        *   **Mounting (Critical):** Requires a secure method for hanging on the wall. Common methods:
            *   **Internal Hanging Rail:** A sturdy strip (e.g., 3/4" plywood, 3-4" wide) integrated into the top back of the carcass, screwed through into wall studs.
            *   **French Cleat:** A two-part system with interlocking bevels (one on wall, one on cabinet back).
            *   **Direct Screw:** Screwing through a reinforced back panel (1/2" minimum recommended) into studs.
            *   **_Build Note:_** The chosen mounting method impacts carcass construction (need for rail, reinforced back).
            *   **_Installation Safety Note (CRITICAL):_** Regardless of the cabinet's mounting features (rail, cleat, back panel), the cabinet **MUST be securely fastened into multiple wall studs** using appropriate cabinet mounting screws. Failure to properly secure the cabinet to studs can result in the cabinet falling, causing serious injury or property damage.
        *   **Construction:** Can be FaceFrame or Frameless. Face frames are less common on standard wall cabinets compared to bases but still used.
        *   **Shelving:** Typically uses adjustable shelves (`type`='Adjustable') via shelf pin holes (Level 8).
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Wall_Standard', `dimensions` (W/H/D), `construction_style`.
        *   Level 4: Carcass parameters (`panel_material`, `thickness`, `side_panels`, `bottom_panel`, `top_construction` - often 'FullTop', `back_panel` - type/thickness critical for mounting).
        *   Level 4/Internal: `mounting_method`: (String, Required: 'HangingRail', 'FrenchCleat', 'DirectScrewBack')
        *   Level 4/Internal (If HangingRail): `hanging_rail_material`, `hanging_rail_dimensions`.
        *   Level 6: Door definition(s) (`opening_reference`, `type`='Door', `style`, `overlay_type`, `overlay_reveal_values`, `thickness`).
        *   Level 8: Shelf parameters (`shelf_id`, `carcass_reference`, `type`='Adjustable', `material`, `thickness`, `shelf_pin_holes_config`).
    *   **Optional Parameters / Modifiers (from Levels 3-10):**
        *   Level 3: `scribe_left`, `scribe_right`, `finished_end_left`, `finished_end_right`.
        *   Level 4: Specific joinery method, specific back panel configuration.
        *   Level 5 (if FaceFrame): Face frame parameters.
        *   Level 6: Door `material`, `style` details (Shaker, Glass), `edge_profile`, `pull_hardware_location`.
        *   Level 8: `front_edge_banding` for shelves.
        *   Level 9: Specific `Joinery Details`.
        *   Level 10: Specific `Hardware Mounting Details` (hinges, shelf pins, pulls).

**8. `unit_type: Wall_Corner_Diagonal`**
    *   **Description:** Wall-mounted corner cabinet with a single diagonal front face (typically 45 degrees to walls), designed to utilize corner space.
    *   **_Standard Diagonal Wall Cabinet Considerations:_**
        *   **Footprint:** Typically requires equal wall space from the corner along each adjacent wall. Common sizes are 24"x24" overall (using 12" of wall space per side for a standard 12" deep cabinet) or 27"x27" (using 15" per side for deeper wall cabinets).
        *   **Construction:** Forms a pentagonal box. Side panels meet back panels at a 135-degree internal angle (requires 22.5-degree bevels). The front face (face frame or frameless edge) is at 45 degrees to sides.
            *   **_Build Accuracy Note (Critical):_** Precise angle cuts are essential for assembly.
        *   **Door:** Usually a single door hinged on one side of the diagonal face. Check swing clearance.
        *   **Shelving:** Typically uses adjustable shelves (Level 8).
        *   **Accessibility:** The deep central corner can be difficult to reach compared to other wall corner solutions.
        *   **Mounting (Critical):** Requires robust mounting securely fastened into studs on **both** adjacent walls. A strong internal hanging rail along the top back edges is highly recommended, potentially supplemented by direct screwing through reinforced back/side panels into corner framing or blocking. *Script Consideration: Ensure mounting features (hanging rail screw locations, back panel hole patterns) are placed assuming standard stud locations (e.g., 16" or 24" OC) or provide parameters for explicit stud location input if available.*
            *   **_Installation Safety Note (CRITICAL):_** Must be securely fastened into multiple wall studs using appropriate screws. Failure to do so poses a significant falling hazard.
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Wall_Corner_Diagonal', `dimensions` (defining wall space used, e.g., 24x24, plus Height), `construction_style`.
        *   Level 4: Carcass parameters (`panel_material`, `thickness`, `side_panels`, `bottom_panel`, `top_construction`, `back_panel` config, `mounting_method` info including rail details).
        *   Level 5 (if FaceFrame): Face frame parameters for the single diagonal opening.
        *   Level 6: Door definition (`opening_reference`, `type`='Door', `style`, `overlay_type`, `overlay_reveal_values`, `thickness`).
        *   Level 8: Shelf parameters (`type`='Adjustable', `material`, `thickness`, `shelf_pin_holes_config`).
    *   **Optional Parameters / Modifiers (from Levels 3-10):**
        *   Level 3: `scribe_left`, `scribe_right`.
        *   Level 4: Specific joinery.
        *   Level 5: Specific face frame details.
        *   Level 6: Door `material`, `style` details, `edge_profile`, `pull_hardware_location`.
        *   Level 8: `front_edge_banding` for shelves.
        *   Level 9: Specific `Joinery Details`.
        *   Level 10: Specific `Hardware Mounting Details` (hinges, pins, pulls).

**9. `unit_type: Wall_Corner_L`**
    *   **Description:** L-shaped wall cabinet designed to fit into a 90-degree corner, often featuring a bi-fold door for improved accessibility.
    *   **_Standard L-Shape Wall Cabinet Considerations:_**
        *   **Footprint:** Typically requires equal wall space from the corner along each adjacent wall, common sizes are 24"x24" or 27"x27" overall (using 12" or 15" of wall space per side for standard depth cabinets).
        *   **Construction:** Forms an L-shaped box. Requires accurate 90-degree assembly. Back panels meet at the internal corner.
            *   **_Build Accuracy Note (Critical):_** The cabinet box must be built perfectly square for the bi-fold door to operate correctly.
        *   **Door (Bi-fold):** The most common configuration uses a two-leaf bi-fold door. One leaf is hinged to the cabinet (standard hinges), and the two leaves are hinged together (bi-fold hinges). This allows the door to open wide, providing good access to the corner interior.
            *   **_Hinge Requirements:_** Needs both standard cabinet hinges (appropriate for overlay type) and specialized bi-fold hinges.
            *   **_Door Sizing:_** Calculation must account for both door leaves and the clearance needed for the bi-fold hinge action.
            *   *Script Consideration:* Modeling bi-fold door mechanics requires defining two separate door panels linked by specific hinges and calculating their combined size relative to the L-shaped opening.
        *   **Shelving:** Typically uses adjustable shelves cut to the L-shape (Level 8).
        *   **Accessibility:** Generally offers better access than `Wall_Corner_Diagonal` due to the wider opening provided by the bi-fold door.
        *   **Mounting (Critical):** Requires secure mounting into studs on **both** adjacent walls. A strong internal hanging rail along the top back edges is highly recommended.
            *   **_Installation Safety Note (CRITICAL):_** Must be securely fastened into multiple wall studs using appropriate screws.
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Wall_Corner_L', `dimensions` (defining wall space used, e.g., 24x24, plus Height), `construction_style`.
        *   Level 4: Carcass parameters (`panel_material`, `thickness`, `side_panels`, `bottom_panel`, `top_construction`, `back_panel` config for L-shape, `mounting_method` info).
        *   Level 5 (if FaceFrame): Face frame parameters for the L-shaped opening (stiles/rails meeting at 90 degrees).
        *   Level 6: Door definition for a **bi-fold pair** (`opening_reference`, `type`='Door', `style`, `overlay_type`, `overlay_reveal_values`, `thickness`, specific `hinge_type`='BiFold').
        *   Level 8: Shelf parameters (`type`='Adjustable', `material`, `thickness`, `shelf_pin_holes_config`). Shelves will be L-shaped.
    *   **Optional Parameters / Modifiers (from Levels 3-10):**
        *   Level 3: `scribe_left`, `scribe_right`.
        *   Level 4: Specific joinery details.
        *   Level 5: Specific face frame details.
        *   Level 6: Door `material`, `style` details, `edge_profile`, `pull_hardware_location` (often one pull on the leaf hinged to cabinet).
        *   Level 8: `front_edge_banding` for shelves.
        *   Level 9: Specific `Joinery Details`.
        *   Level 10: Specific `Hardware Mounting Details` (standard hinges, bi-fold hinges, shelf pins, pulls).

**10. `unit_type: Wall_Corner_Blind`**
    *   **Description:** Wall-mounted cabinet box designed to occupy a corner, where access to the interior is partially obstructed by the adjacent perpendicular cabinet run. Requires a filler strip for clearance.
    *   **_Standard Blind Wall Cabinet Considerations:_**
        *   **Footprint:** Typically uses a standard wall cabinet width (e.g., 24", 27", 30") along the main wall, extending into the corner. Requires careful calculation of wall space needed based on cabinet width and filler.
        *   **Construction:** Standard wall cabinet box construction (sides, top, bottom, back).
        *   **Filler Requirement (CRITICAL):** Similar to the base version, a filler strip **must** be planned between the blind cabinet's accessible opening and the adjacent cabinet run to prevent collisions.
            *   *Typical Filler Width:* 1.5" to 3"+ depending on adjacent doors/hardware projection.
            *   *Calculation:* Requires knowing adjacent cabinet details. *Script Consideration: The script needs input for this filler width, as it cannot be determined solely from the blind cabinet's parameters.*
        *   **Door:** Usually a single door covering the accessible opening (Cabinet Width - Filler Width = Door Width Calculation Base).
        *   **Accessibility & Shelving:** Access to the "blind" portion is limited. Standard adjustable or fixed shelves are typically placed within the accessible section only. Specialized pull-out hardware is uncommon for wall blind corners.
        *   **Mounting (Critical):** Must be securely mounted to wall studs on the accessible side, typically via an internal hanging rail or reinforced back. May also secure to corner framing on the blind side.
            *   **_Installation Safety Note (CRITICAL):_** Must be securely fastened into multiple wall studs.
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Wall_Corner_Blind', `dimensions` (W/H/D of the box), `construction_style`.
        *   Level 3/Other: `blind_side`: ('Left'/'Right') Specifies which side extends into the corner.
        *   Level 3/Other: `filler_width_adjacent`: (Float, Required) Calculated width of the filler needed.
        *   Level 4: Carcass parameters (`panel_material`, `thickness`, `side_panels`, `bottom_panel`, `top_construction`, `back_panel`, `mounting_method` info).
        *   Level 5 (if FaceFrame): Face frame parameters for the single accessible opening.
        *   Level 6: Door definition (`opening_reference`, `type`='Door', `style`, `overlay_type`, `overlay_reveal_values`, `thickness`).
        *   Level 8: Shelf parameters (`type`='Adjustable' typical, `material`, `thickness`, `shelf_pin_holes_config` - applied only to accessible side walls).
    *   **Optional Parameters / Modifiers (from Levels 3-10):**
        *   Level 3: `scribe_left`, `scribe_right`, `finished_end_left`/`right` (esp. the side facing out).
        *   Level 4: Specific joinery details.
        *   Level 5: Specific face frame details.
        *   Level 6: Door `material`, `style` details, `edge_profile`, `pull_hardware_location`.
        *   Level 8: `front_edge_banding` for shelves.
        *   Level 9: Specific `Joinery Details`.
        *   Level 10: Specific `Hardware Mounting Details` (hinges, pins, pulls).

**11. `unit_type: Wall_Refrigerator`**
    *   **Description:** Wall cabinet designed to be mounted above a refrigerator. Typically deeper than standard wall cabinets (e.g., 24") to align with the refrigerator carcass or surrounding enclosure panels.
    *   **_Standard Refrigerator Wall Cabinet Considerations:_**
        *   **Depth (Key Feature):** Most commonly 24" deep, but can vary based on refrigerator model (standard vs. counter-depth) and enclosure design. Verify required depth.
        *   **Width:** Typically matches the refrigerator width or the opening provided by enclosure panels (e.g., 30", 33", 36", sometimes wider for built-in models).
        *   **Height:** Varies depending on refrigerator height, ceiling height, and alignment with adjacent wall cabinets (e.g., 12", 15", 18", 21", 24").
        *   **Construction:** Standard wall cabinet box construction (sides, top, bottom, back). Due to the increased depth, consider reinforcing the bottom panel or using thicker shelf material (Level 8) if heavy items will be stored.
        *   **Doors:** Often two doors due to typical widths. Full overlay hinges are common.
        *   **Mounting (Critical):** Requires secure mounting into wall studs using appropriate methods (hanging rail, reinforced back). Ensure fasteners are long enough to achieve proper stud penetration, especially considering the cabinet depth.
            *   **_Installation Safety Note (CRITICAL):_** Must be securely fastened into multiple wall studs.
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Wall_Refrigerator', `dimensions` (W/H/D - **Depth typically 24"**), `construction_style`.
        *   Level 4: Carcass parameters (`panel_material`, `thickness`, `side_panels`, `bottom_panel`, `top_construction`, `back_panel`, `mounting_method` info).
        *   Level 6: Door definition(s) (`opening_reference`, `type`='Door', `style`, `overlay_type`, `overlay_reveal_values`, `thickness`).
        *   Level 8: Shelf parameters (`type`='Adjustable' typical, `material`, `thickness` - potentially thicker due to depth, `shelf_pin_holes_config`).
    *   **Optional Parameters / Modifiers (from Levels 3-10):**
        *   Level 3: `scribe_left`, `scribe_right` (less common if flanked by enclosure panels), `finished_end_left`/`right` (if side exposed).
        *   Level 4: Specific joinery details, potential bottom panel reinforcement notes.
        *   Level 5 (if FaceFrame): Face frame parameters.
        *   Level 6: Door `material`, `style` details, `edge_profile`, `pull_hardware_location`.
        *   Level 8: `front_edge_banding` for shelves.
        *   Level 9: Specific `Joinery Details`.
        *   Level 10: Specific `Hardware Mounting Details` (hinges, pins, pulls).

**12. `unit_type: Wall_MicrowaveShelf`**
    *   **Description:** Wall cabinet designed with an open shelf section specifically sized to house a countertop microwave oven. May have a cabinet above or below the open shelf.
    *   **_Standard Microwave Shelf Considerations:_**
        *   **Function:** Provides a dedicated, off-counter space for a standard countertop microwave.
        *   **Appliance Type:** Designed for **countertop** models, NOT Over-The-Range (OTR) units.
        *   **Clearances (CRITICAL):** Countertop microwaves require air circulation. Minimum clearances specified by the manufacturer MUST be maintained.
            *   *Common Minimums (Verify Specific Model):* Often ~3 inches on each side, ~3 inches above, and ~1 inch at the rear.
            *   *Script Consideration:* The script needs input for the actual microwave dimensions (W/H/D) and its required clearances to calculate the necessary `opening_width`, `opening_height`, and `opening_depth` for the shelf space.
        *   **Shelf Dimensions:**
            *   *Width:* Opening needs to be Microwave Width + Left Clearance + Right Clearance. Common target opening widths are 24"-30".
            *   *Height:* Opening needs to be Microwave Height + Top Clearance. Common microwave heights are 11"-14".
            *   *Depth:* Opening needs to be Microwave Depth + Rear Clearance + Plug Clearance. Standard 12" wall cabinet depth is often **insufficient**. Cabinet depths of 15"-18" are typically required. The shelf itself might be slightly shallower than the cabinet depth.
        *   **Construction:** Typically a standard wall cabinet box, but the section for the microwave is an open shelf (no door). The shelf supporting the microwave should be fixed and robust (e.g., 3/4" plywood), potentially dadoed into the sides for extra support.
        *   **Electrical:** Requires a dedicated electrical outlet within or immediately behind the open shelf space. Location must allow plug connection without obstructing the microwave or required rear clearance.
            *   *Script Consideration:* While the script won't model the outlet, noting its requirement is important for planning. The cabinet back panel may need a cutout for wall outlet access.
        *   **Ventilation:** Ensure clearances are maintained. Do not block vents on the microwave unit.
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Wall_MicrowaveShelf', `dimensions` (Overall W/H/D - **Depth often >12"**), `construction_style`.
        *   **External Input:** Actual Microwave Dimensions (Width, Height, Depth).
        *   **External Input:** Required Microwave Clearances (Left, Right, Top, Rear).
        *   Level 4: Carcass parameters (`panel_material`, `thickness`, `side_panels`, `bottom_panel`, `top_construction`, `back_panel` - potentially with cutout, `mounting_method` info).
        *   Level 8: Shelf parameters for the **fixed microwave shelf** (`type`='Fixed', `material`, `thickness`, `position_vertical` defining bottom of microwave opening).
    *   **Optional Parameters / Modifiers (from Levels 3-10):**
        *   Level 3: `scribe_left`, `scribe_right`, `finished_end_left`/`right`.
        *   Level 4: Specific joinery for fixed shelf (e.g., dado depth).
        *   Level 5 (if FaceFrame): Face frame parameters (defining the open shelf area and any cabinet above/below).
        *   Level 6: Door/DrawerFront definitions if there's a cabinet portion above or below the microwave shelf.
        *   Level 7: Drawer Box parameters if drawers exist.
        *   Level 8: Additional adjustable shelves if cabinet space exists above/below.
        *   Level 9: Specific `Joinery Details`.
        *   Level 10: Specific `Hardware Mounting Details` (for doors/drawers if present).
        *   `outlet_location_note`: (String, Optional) e.g., "Recessed outlet centered in back panel opening".

**13. `unit_type: Wall_MicrowaveHood`**
    *   **Description:** Wall cabinet designed to have an Over-The-Range (OTR) microwave/hood unit mounted directly beneath it. This cabinet is typically shorter than adjacent wall cabinets to accommodate the OTR unit's height and required clearance above the cooktop.
    *   **_Standard OTR Microwave Cabinet Considerations:_**
        *   **Function:** Provides the structural mounting point for an OTR microwave and fills the space above it.
        *   **Appliance Type:** Specifically for OTR Microwave/Hood combination units.
        *   **Mounting Height (CRITICAL):** The height of this cabinet is determined by the required installation height of the OTR unit below it. Manufacturer specifications for the OTR unit dictate the minimum clearance needed above the cooktop surface (e.g., 18"-24"+), which in turn determines the vertical space available for this cabinet.
            *   *Script Consideration:* The script needs the target bottom height for the OTR unit (or the required cooktop clearance) as input to calculate the necessary height for this cabinet, ensuring top alignment with adjacent cabinets if desired.
        *   **Cabinet Dimensions:**
            *   *Width:* Matches standard range/OTR widths (typically 30" or 36").
            *   *Height:* Shorter than standard wall cabinets (e.g., 12", 15", 18", 21") calculated based on mounting height needs.
            *   *Depth:* Standard wall cabinet depth (typically 12").
        *   **Construction:** Standard wall cabinet box, but the **bottom panel must be solid and structural** (e.g., 3/4" plywood) to accept the mounting bolts from the OTR unit below.
        *   **OTR Mounting Provisions:** Requires precisely located holes drilled through the cabinet bottom panel for the OTR unit's top mounting bolts. A template usually comes with the OTR unit.
            *   *Script Consideration:* The script needs the OTR mounting bolt pattern (coordinates relative to cabinet front/sides) as input to accurately model these holes (Level 10 detail).
        *   **Electrical:** Requires a dedicated electrical outlet, typically located inside this upper cabinet for the OTR unit's plug.
        *   **Ventilation Ducting:** If the OTR unit is vented externally (upward), this cabinet will require a cutout in the top panel (and potentially intermediate shelves/back) to allow the duct to pass through.
            *   *Script Consideration:* Requires input defining duct size (e.g., 6" round, 3.25"x10" rect) and location for modeling the cutout.
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Wall_MicrowaveHood', `dimensions` (W/H/D - **Height is calculated/critical**), `construction_style`.
        *   **External Input:** OTR Required Mounting Height / Cooktop Clearance.
        *   **External Input:** OTR Mounting Bolt Pattern/Locations.
        *   Level 4: Carcass parameters (`panel_material`, `thickness`, solid `bottom_panel`, `top_construction`, `back_panel`, `mounting_method` info).
        *   Level 6: Door definition(s) (`opening_reference`, `type`='Door', `style`, `overlay_type`, `overlay_reveal_values`, `thickness`).
    *   **Optional Parameters / Modifiers (from Levels 3-10):**
        *   Level 3: `scribe_left`, `scribe_right`, `finished_end_left`/`right`.
        *   Level 4: Specific joinery details, potential reinforcement notes for bottom.
        *   Level 5 (if FaceFrame): Face frame parameters.
        *   Level 6: Door `material`, `style` details, `edge_profile`, `pull_hardware_location`.
        *   Level 8: Shelves parameters (less common in these short cabinets, maybe one fixed/adjustable).
        *   Level 9: Specific `Joinery Details`.
        *   Level 10: Specific `Hardware Mounting Details` (hinges, pulls, **OTR Bolt Holes**), `vent_duct_cutout` (dictionary defining size/location/shape).
        *   `outlet_location_note`: (String, Optional) e.g., "Outlet centered top rear".

**14. `unit_type: Wall_PlateRack`**
    *   **Description:** Open-front wall cabinet featuring integrated vertical storage for plates, typically using dowels or slotted dividers.
    *   **_Standard Plate Rack Considerations:_**
        *   **Function:** Decorative display and storage of plates.
        *   **Construction:** Typically a standard wall cabinet box (sides, top, bottom, back) but without doors. Interior finish quality is important as it's visible.
        *   **Dimensions:** Width varies (e.g., 18", 24", 30"). Height and Depth usually match adjacent standard wall cabinets.
        *   **Internal Rack System (Key Feature):**
            *   `rack_type`: (String, Required: 'Dowels', 'Dividers')
            *   **If 'Dowels':**
                *   `dowel_diameter`: (Float, Required, e.g., 0.5, 0.75 inches)
                *   `dowel_material`: (String, Optional, Default='Hardwood')
                *   `dowel_spacing_horizontal`: (Float, Required) Center-to-center spacing between dowels front-to-back.
                *   `dowel_spacing_vertical`: (Float, Required) Center-to-center spacing between dowels top-to-bottom (if multiple rows).
                *   `number_of_rows`: (Integer, Optional, Default=2) How many rows of dowels front-to-back.
                *   `row_offset_from_front`: (Float, Required) Distance from cabinet front edge to center of first dowel row.
                *   *Script Consideration:* Requires modeling arrays of cylindrical dowels positioned based on spacing parameters, and potentially corresponding mounting holes in the top/bottom panels (Level 10 detail).
            *   **If 'Dividers':**
                *   `divider_material`: (String, Required)
                *   `divider_thickness`: (Float, Required)
                *   `divider_spacing`: (Float, Required) Clear space between dividers.
                *   `divider_height`: (Float, Required) Height of the vertical dividers.
                *   `divider_attachment`: (String, Optional, Default='Grooves') How dividers are held (e.g., 'Grooves' in top/bottom panels, 'SlottedSubRails').
                *   *Script Consideration:* Requires modeling multiple thin vertical panels (dividers) spaced correctly, and potentially grooves/slots in the top/bottom panels (Level 9 detail).
        *   **Mounting (Critical):** Standard secure wall mounting (hanging rail, etc.).
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Wall_PlateRack', `dimensions` (W/H/D), `construction_style`.
        *   Level 4: Carcass parameters (`panel_material`, `thickness`, `side_panels`, `bottom_panel`, `top_construction`, `back_panel`, `mounting_method` info).
        *   Level 8/Internal: Rack parameters (`rack_type` and associated details like `dowel_diameter`/`spacing` or `divider_thickness`/`spacing`).
    *   **Optional Parameters / Modifiers (from Levels 3-10):**
        *   Level 3: `scribe_left`, `scribe_right`, `finished_end_left`/`right`.
        *   Level 4: Specific joinery details.
        *   Level 5 (if FaceFrame): Face frame parameters (defines the open front).
        *   Level 6: Doors (uncommon, but could have Glass Doors).
        *   Level 9: Specific `Joinery Details` (e.g., grooves for dividers).
        *   Level 10: Specific `Hardware Mounting Details` (e.g., dowel mounting holes).

**15. `unit_type: Wall_WineRack`**
    *   **Description:** Wall cabinet featuring a lattice or cubby system designed for storing wine bottles horizontally.
    *   **_Standard Wine Rack Considerations:_**
        *   **Function:** Storage and display of standard wine bottles.
        *   **Construction:** Typically a standard wall cabinet box (sides, top, bottom, back), often open-front or with glass doors.
        *   **Dimensions:** Width varies (e.g., 12", 18", 24"). Height and Depth usually match adjacent standard wall cabinets (12" depth common).
        *   **Internal Rack System (Key Feature):**
            *   `rack_style`: (String, Required: 'Lattice_Diamond', 'Lattice_Square', 'Cubby')
            *   `opening_width`: (Float, Required) Clear width of each bottle opening (typically 3.5" - 4").
            *   `opening_height`: (Float, Required) Clear height of each bottle opening (typically 3.5" - 4").
            *   **If 'Lattice_Diamond' or 'Lattice_Square':**
                *   `lattice_material`: (String, Required)
                *   `lattice_thickness`: (Float, Required) Thickness of the lattice strips.
                *   `lattice_strip_width`: (Float, Required) Width (depth) of the lattice strips.
                *   `lattice_joinery`: (String, Optional, Default='HalfLap') How intersecting strips join ('HalfLap', 'ButtJoint').
                *   *Script Consideration:* Modeling lattice requires creating arrays of intersecting strips at specified angles (45deg for diamond, 90deg for square) based on `opening_width`/`height`. Half-lap joints add modeling complexity (Level 9 detail).
            *   **If 'Cubby':**
                *   `divider_material`: (String, Required)
                *   `divider_thickness`: (Float, Required)
                *   `cubby_arrangement`: (String, Optional) e.g., "3_Wide_x_4_High". Calculated from overall dimensions and opening size if not provided.
                *   `divider_joinery`: (String, Optional, Default='Dado') How vertical/horizontal dividers join ('Dado', 'Groove', 'ButtJoint').
                *   *Script Consideration:* Modeling cubbies involves creating intersecting vertical and horizontal divider panels. Joinery adds detail (Level 9).
        *   **Mounting (Critical):** Standard secure wall mounting (hanging rail, etc.).
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Wall_WineRack', `dimensions` (W/H/D), `construction_style`.
        *   Level 4: Carcass parameters (`panel_material`, `thickness`, `side_panels`, `bottom_panel`, `top_construction`, `back_panel`, `mounting_method` info).
        *   Level 8/Internal: Rack parameters (`rack_style`, `opening_width`, `opening_height`, and associated details like `lattice_thickness` or `divider_thickness`).
    *   **Optional Parameters / Modifiers (from Levels 3-10):**
        *   Level 3: `scribe_left`, `scribe_right`, `finished_end_left`/`right`.
        *   Level 4: Specific joinery details for carcass.
        *   Level 5 (if FaceFrame): Face frame parameters (defines the open front).
        *   Level 6: Doors (Glass Doors sometimes used).
        *   Level 9: Specific `Joinery Details` for lattice/dividers.
        *   Level 10: Specific `Hardware Mounting Details` (e.g., hinges if doors used).

**16. `unit_type: Wall_EndShelf`**
    *   **Description:** Open shelf unit designed to terminate a run of wall cabinets, providing display space and a softer visual end. Matches adjacent cabinet height and depth, typically narrower width. Can have square, angled, or curved front edges.
    *   **_Standard Wall End Shelf Considerations:_**
        *   **Function:** Primarily decorative display (e.g., cookbooks, small items).
        *   **Dimensions:** Width typically narrow (e.g., 9"-12"). Height and Depth (typically 12") match adjacent wall cabinets.
        *   **Construction:** Similar to Base End Shelf but scaled for wall cabinets. Includes exposed side panel, top, bottom, fixed shelves, and back/attachment method.
        *   **Attachment/Mounting (Critical):** Must be securely attached to the adjacent cabinet AND securely mounted to the wall studs (hanging rail, etc.).
        *   **Shelves:** Usually fixed for appearance and rigidity. Spacing can be equal or custom.
        *   **Edge Finishing (Critical):** Front edges of the exposed side panel and shelves require proper finishing (edge banding, solid nosing).
    *   **_Style Variations:_**
        *   **Angled Front:** Front edge of side panel and shelves cut at an angle.
        *   **Curved Front:** Front edge of side panel and shelves have a radius.
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Wall_EndShelf', `dimensions` (W/H/D), `construction_style` (visual match).
        *   Level 4: Carcass parameters (`panel_material`, `thickness`, `bottom_panel`, `top_construction`, `back_panel` or `attachment_method` clarification), `mounting_method` info.
        *   Level 8: Shelf parameters (`type`='Fixed' typically, `material`, `thickness`, `position_vertical` for each shelf), `front_edge_banding` definition.
    *   **Optional Parameters / Modifiers (from Levels 3-10):**
        *   `shelf_front_style`: (String, Optional, Default='Square') Values: 'Square', 'Angled', 'Curved'.
        *   `shelf_angle` / `shelf_radius`: (Float, Optional, Required if style is Angled/Curved).
        *   Level 9: Specific `Joinery Details` (e.g., dadoes for fixed shelves).

**17. `unit_type: Tall_Pantry`**
    *   **Description:** Full-height cabinet designed for general storage, typically food, pantry items, or utility storage. Internal configuration can vary greatly (shelves, drawers, roll-outs).
    *   **_Standard Pantry Cabinet Considerations:_**
        *   **Function:** Bulk storage.
        *   **Dimensions:** Width varies (18", 24", 30", 36" common). Height typically matches ceiling clearance or standard heights (84", 90", 96"). Depth usually 24", but can be shallower (18", 12").
        *   **Construction:** Tall cabinet box. Due to height and potential load, sturdy construction is key (3/4" sides/top/bottom recommended). A solid back (1/2" min) adds significant rigidity.
        *   **Internal Configuration:** Highly variable. Can include:
            *   Fixed Shelves
            *   Adjustable Shelves (Level 8)
            *   Internal Drawers (Requires Level 7 `Drawer Boxes` parameters, often shallower depth than external drawers)
            *   Roll-Out Trays/Shelves (Similar to drawers but often with lower sides, require slide clearances).
            *   *Script Consideration:* Internal configuration requires specific parameters defining shelf types/positions, drawer box details, or roll-out specifications, including necessary clearances and mounting provisions.
        *   **Door Configuration:** Can be single full-height door (requires multiple hinges), double full-height doors, or split configuration (e.g., upper doors, middle drawers, lower doors).
            *   *Script Consideration:* Requires flexible door/drawer front definition (Level 6) linked to internal openings defined by fixed shelves or internal dividers.
        *   **Mounting/Anchoring (CRITICAL):** Tall cabinets **MUST be securely anchored to wall studs** to prevent tipping, which poses a serious safety hazard. Requires appropriate fasteners through the back panel or internal blocking into studs.
            *   **_Installation Safety Note (CRITICAL):_** Failure to properly anchor tall cabinets can lead to severe injury or death.
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Tall_Pantry', `dimensions` (W/H/D), `construction_style`.
        *   Level 4: Carcass parameters (`panel_material`, `thickness`, `side_panels`, `bottom_panel`, `top_construction`, `back_panel`, `mounting_method` info - anchoring detail critical).
        *   Level 6: Door/DrawerFront definitions corresponding to the chosen external configuration.
        *   (Internal Configuration - Requires parameters from Level 7/8 depending on choice):
            *   Level 8: Shelf parameters (`type`, `material`, `thickness`, position/pin holes).
            *   Level 7: Internal Drawer Box parameters (if applicable, including slide type/clearances).
            *   *(Roll-out tray parameters might need specific definition if different from standard drawers)*.
    *   **Optional Parameters / Modifiers (from Levels 3-10):**
        *   Level 3: `scribe_left`, `scribe_right`, `finished_end_left`/`right`.
        *   Level 4: Specific joinery, toe kick details (often integrated).
        *   Level 5 (if FaceFrame): Face frame parameters defining external openings.
        *   Level 6: Door/Drawer `material`, `style` details, `edge_profile`, `pull_hardware_location`.
        *   Level 9: Specific `Joinery Details`.
        *   Level 10: Specific `Hardware Mounting Details` (hinges - consider quantity for tall doors, shelf pins, pulls, internal slide mounting).

**18. `unit_type: Tall_Oven`**
    *   **Description:** Full-height cabinet designed to house one or two built-in wall ovens, potentially with a microwave or warming drawer combo. Requires precise cutouts and robust support structures.
    *   **_Standard Oven Cabinet Considerations:_**
        *   **Function:** Houses built-in cooking appliances.
        *   **Dimensions:** Width typically matches oven size + necessary clearance/structure (e.g., 27", 30", 33" common widths for ovens, cabinet may be slightly wider). Height matches standard tall heights (84", 90", 96"). Depth typically 24".
        *   **Cutout(s) (CRITICAL):** **MANUFACTURER SPECIFICATIONS ARE PARAMOUNT.** The size (W/H/D) and location (vertical position) of the cutout(s) for the oven(s)/microwave must be precisely followed. There are no reliable standards.
        *   **Oven Support Structure (CRITICAL):** Ovens are very heavy. A reinforced fixed shelf/platform is required below *each* appliance cutout. Typically 3/4"+ plywood, supported by cleats securely attached to cabinet sides or internal vertical supports.
        *   **Ventilation & Clearances:** Oven specs dictate required air gaps (sides, top, bottom, rear). Cabinet construction must allow for this airflow. Back panel often needs significant cutouts or removal in the oven area.
        *   **Electrical:** Requires dedicated circuit(s) and junction box(es). Location(s) must be accessible according to specs, often in an adjacent cabinet or specific location within the oven cabinet.
        *   **Construction:** Tall cabinet box, typically 3/4" material. Back panel configuration is heavily modified by cutout/ventilation needs.
        *   **Configuration:** Often includes drawers below the lowest oven and cabinet space (doors) above the highest oven.
        *   **Mounting/Anchoring (CRITICAL):** **MUST be securely anchored to wall studs** due to the significant weight of the appliances.
            *   **_Installation Safety Note (CRITICAL):_** Improper anchoring poses a severe tipping hazard.
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Tall_Oven', `dimensions` (W/H/D), `construction_style`.
        *   **External Input:** Appliance 1 Cutout Dimensions & Location (W, H, D, Vertical Position).
        *   **External Input:** Appliance 1 Support Shelf Requirements/Position.
        *   **External Input (if applicable):** Appliance 2 Cutout Dimensions & Location.
        *   **External Input (if applicable):** Appliance 2 Support Shelf Requirements/Position.
        *   **External Input:** Electrical Location Requirements.
        *   Level 4: Carcass parameters (`panel_material`, `thickness`, `side_panels`, `bottom_panel`, `top_construction`, heavily modified `back_panel`, anchoring details). Definition of internal support shelf/shelves.
        *   Level 6: Door/DrawerFront definitions for sections above/below the appliance cutouts.
    *   **Optional Parameters / Modifiers (from Levels 3-10):**
        *   Level 3: `scribe_left`, `scribe_right`, `finished_end_left`/`right`.
        *   Level 4: Specific joinery, toe kick details.
        *   Level 5 (if FaceFrame): Face frame parameters defining openings around appliances, drawers, upper cabinet.
        *   Level 6: Door/Drawer `material`, `style`, `edge_profile`, `pull_hardware_location`.
        *   Level 7 (if drawers below): Internal Drawer Box parameters.
        *   Level 8 (if cabinet above): Shelf parameters for upper section.
        *   Level 9: Specific `Joinery Details` (e.g., for support structure).
        *   Level 10: Specific `Hardware Mounting Details` (hinges, pulls, drawer slides).
        *   `ventilation_notes`: (String, Optional) Specific instructions for vent cutouts if not standard.
        *   `heat_shield_requirements`: (String, Optional) If required by appliance specs.
    *   **_ACTION REQUIRED / WARNING:_** Before finalizing any design, fabrication, or generating cutting lists for a `Tall_Oven` unit, the **actual manufacturer's specifications for ALL installed appliances (cutout dimensions W/H/D, location, clearances, support needs, utility locations) MUST be obtained and meticulously followed.** Failure to do so poses significant risks of improper fit, appliance malfunction, overheating, fire hazard, voiding warranties, and safety hazards.

**19. `unit_type: Tall_Utility`**
    *   **Description:** Full-height cabinet designed for storing tall items like brooms, mops, vacuums, or cleaning supplies. Internal configuration typically features a tall open section, possibly combined with shelves or dividers.
    *   **_Standard Utility Cabinet Considerations:_**
        *   **Function:** Storage for tall/utility items.
        *   **Dimensions:** Width often narrower (18", 24" common). Height matches standard tall heights (84", 90", 96"). Depth usually 24", but can be shallower.
        *   **Construction:** Standard tall cabinet box (3/4" sides recommended, 1/2" back min).
        *   **Internal Configuration (Key Feature):** Needs specific definition. Common layouts:
            *   `Layout_FullHeightOpen`: Single tall open space.
            *   `Layout_VerticalDivide`: Full-height vertical divider creating two sections (e.g., one tall open, one with shelves).
            *   `Layout_ShelvesPartialDepth`: Full-width shelves that are shallower than the cabinet depth to allow tall items in front.
            *   *Script Consideration:* Requires parameters defining the internal layout: `internal_layout_type` (String), plus dimensions/positions for dividers or shelves based on the chosen layout.
        *   **Door Configuration:** Typically single or double full-height doors.
        *   **Mounting/Anchoring (CRITICAL):** **MUST be securely anchored to wall studs** to prevent tipping.
            *   **_Installation Safety Note (CRITICAL):_** Failure to properly anchor tall cabinets can lead to severe injury.
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Tall_Utility', `dimensions` (W/H/D), `construction_style`.
        *   Level 4: Carcass parameters (`panel_material`, `thickness`, `side_panels`, `bottom_panel`, `top_construction`, `back_panel`, anchoring details).
        *   Level 6: Door definition(s) corresponding to the external configuration.
        *   (Internal Configuration - Requires specific layout parameters):
            *   `internal_layout_type`: (String, Required: e.g., 'FullHeightOpen', 'VerticalDivide', 'ShelvesPartialDepth').
            *   (If 'VerticalDivide'): `divider_position`, `divider_material`, `divider_thickness`. Shelf parameters (Level 8) for the shelved section.
            *   (If 'ShelvesPartialDepth'): Shelf parameters (Level 8) including `shelf_depth`.
    *   **Optional Parameters / Modifiers (from Levels 3-10):**
        *   Level 3: `scribe_left`, `scribe_right`, `finished_end_left`/`right`.
        *   Level 4: Specific joinery, toe kick details.
        *   Level 5 (if FaceFrame): Face frame parameters.
        *   Level 6: Door `material`, `style`, `edge_profile`, `pull_hardware_location`.
        *   Level 8: Additional shelf details (e.g., fixed vs adjustable within a section).
        *   Level 9: Specific `Joinery Details` (e.g., dado for divider).
        *   Level 10: Specific `Hardware Mounting Details` (hinges, pulls, internal hooks/brackets).

**20. `unit_type: Tall_RefrigeratorEnclosure`**
    *   **Description:** Structural and decorative unit, typically composed of two tall side panels and often a connecting cabinet above (`Wall_Refrigerator` type), designed to frame a freestanding refrigerator for a built-in look.
    *   **_Standard Refrigerator Enclosure Considerations:_**
        *   **Function:** Frames the refrigerator, provides side support/visual integration.
        *   **Appliance Fit (CRITICAL):** Dimensions are dictated entirely by the specific refrigerator model's requirements.
        *   **Components:** Usually two vertical side panels, potentially connected by a top bridge cabinet (`Wall_Refrigerator` unit definition) and/or rear support cleats.
        *   **Opening Dimensions (CRITICAL):** **MANUFACTURER SPECIFICATIONS ARE PARAMOUNT.** The clear internal width, height, and depth must precisely match the refrigerator's required installation opening, including all necessary air clearances (top, sides, back).
        *   **Panel Construction:** Side panels are typically full depth (e.g., 24"-30"+) and height (e.g., 84"-96"). Often made from 3/4" finished material (plywood, MDF) for stability and appearance.
        *   **Top Connection:** If a `Wall_Refrigerator` cabinet is used above, the side panels attach to it. If no top cabinet, sturdy rear cleats or a structural top frame may be needed to connect and stabilize the side panels.
        *   **Anchoring (CRITICAL):** The entire enclosure structure (side panels, top cabinet/cleats) **MUST be securely anchored to wall studs** behind the refrigerator space.
            *   **_Installation Safety Note (CRITICAL):_** Failure to anchor can lead to instability or tipping.
        *   **Finish:** The inside faces of the side panels are visible and require appropriate finishing.
    *   **Required Parameters (Levels 3-10):**
        *   Level 3: `unit_type`='Tall_RefrigeratorEnclosure', `dimensions` (defining the **required clear opening** W/H/D for the refrigerator).
        *   **External Input:** Refrigerator Model & Required Opening Dimensions (W/H/D).
        *   **External Input:** Refrigerator Required Air Clearances (Top, Sides, Back).
        *   Level 4 (Defining Panels/Structure): `panel_material`, `panel_thickness` (for side panels), `top_connection_type` (String: 'BridgeCabinet', 'RearCleats', 'None').
        *   (If `top_connection_type`='BridgeCabinet'): Reference to the `unit_id` of the `Wall_Refrigerator` cabinet definition used above.
        *   (If `top_connection_type`='RearCleats'): `cleat_material`, `cleat_dimensions`.
        *   Anchoring details (how panels/cleats attach to wall).
    *   **Optional Parameters / Modifiers (from Levels 3-10):**
        *   Level 3: `scribe_left`, `scribe_right` (applied to side panels).
        *   Level 9: Specific `Joinery Details` (e.g., how side panels attach to top cabinet/cleats).
    *   **_ACTION REQUIRED / WARNING:_** Before finalizing design or fabrication, the **actual manufacturer's specifications for the specific refrigerator model MUST be obtained and meticulously followed** regarding required opening dimensions and air clearances. Incorrect sizing can prevent installation, impede ventilation (causing appliance failure), or create safety issues.

## VIII. Practical Design & Manufacturing Considerations

This section covers overarching practical factors that influence design choices and manufacturing feasibility, providing context for the parameters defined earlier and informing how the script's output might be used.

### 1. Standard Material Sizes & Formats

Understanding common material sizes is crucial for planning and minimizing waste.

*   **Sheet Goods (Plywood, MDF, Particle Board):**
    *   **Imperial Standard:** 4 feet x 8 feet (48 inches x 96 inches, or ~1219mm x 2438mm).
    *   **Metric Standard:** 1220mm x 2440mm (Slightly larger than 4x8 ft).
    *   **Other Sizes:** Less common, but 5'x5' (Baltic Birch often), 4'x10', or oversized sheets exist.
    *   **Thickness:** Nominal vs. Actual is critical. "3/4 inch" plywood is often slightly thinner (e.g., 23/32" or ~18mm). Metric thicknesses (e.g., 6mm, 12mm, 18mm) are usually more precise. **Script parameters MUST use actual thickness.**
*   **Solid Wood (Lumber):**
    *   Sold by the board foot (volume), but typically available in standard nominal thicknesses (e.g., 4/4 = ~3/4" finished, 5/4 = ~1" finished, 6/4, 8/4) and random widths/lengths.
    *   For face frames, edge banding, etc., specific dimensional lumber (e.g., 1x2, 1x3, 1x4) is often used. Nominal dimensions (1x2 is actually ~0.75" x 1.5") vs. actual dimensions matter.

### 2. Material Usage Estimation Concepts & Caveats

Accurately predicting material needs before detailed nesting is difficult, but conceptual estimation helps planning.

*   **Sheet Goods (Area Estimation - ROUGH):**
    *   **Concept:** Calculate the total surface area (sq ft or sq m) of all required sheet good parts for a project.
    *   Divide the total part area by the area of a standard sheet (e.g., 32 sq ft for 4x8).
    *   **Add a Waste Factor:** Crucially, add a percentage for waste due to cutting (kerf), nesting inefficiency, defects, and grain direction constraints. **A waste factor of 15-30% is common, potentially higher for complex parts or grain matching.**
    *   **Caveat:** This is a very rough estimate. Actual yield depends heavily on nesting layout.
*   **Solid Stock (Linear Estimation):**
    *   **Concept:** Sum the total length (linear feet or meters) required for each specific dimension of solid stock (e.g., total length of all 1x2 face frame parts).
    *   **Add a Waste Factor:** Add a percentage for waste due to cutting to length, defects, and avoiding knots. **A waste factor of 10-20% is typical.**

### 3. Factors Affecting Material Yield

Several factors significantly impact how many sheets or board feet are actually consumed:

*   **Nesting Efficiency:** How tightly parts can be arranged on a sheet. Software optimization is best.
*   **Grain Direction:** Plywood often has a grain direction on the face veneer. Parts usually need to be oriented consistently (typically vertically for cabinet sides/doors), restricting nesting.
*   **Part Size & Mix:** Many small parts generally lead to lower yield than fewer large parts.
*   **Kerf:** The width of the saw blade (e.g., 1/8" or 3mm) removes material with every cut.
*   **Material Defects:** Working around knots, voids, or surface blemishes in plywood or solid wood.
*   **Edge Trimming:** Sheets often require initial edge trimming for a clean reference.

### 4. Cut List Recommendation

*   **Script Output:** The Rhino automation script should ideally be capable of generating a **Cut List** – a detailed list of every individual component (e.g., left side, bottom panel, door stile) with its final required dimensions (Length, Width, Thickness) and material type.
*   **Usage:** This cut list is the essential input for:
    *   Manual layout planning on sheets/lumber.
    *   Input into specialized nesting software (e.g., CutList Plus, MaxCut, Fusion 360 Arrange) for optimized layouts and accurate material purchasing.
    *   Generating CNC machining files.

### 5. Ceiling Clearance & Floor-to-Ceiling Integration

Cabinet heights relative to the ceiling require consideration during design.

*   **Standard Ceiling Height:** Often 8 feet (96 inches), but varies.
*   **Wall Cabinet Top Clearance:**
    *   A gap is often left between the top of wall cabinets and the ceiling (e.g., 6"-12") to allow for installation ease and/or decorative crown molding.
    *   Alternatively, cabinets might run directly to a soffit or bulkhead.
    *   **Parameter Need:** Designs should specify either the `room_ceiling_height` (Level 1) or a desired `top_clearance_gap` to accurately size wall/tall cabinets.
*   **Floor-to-Ceiling Units:**
    *   Tall cabinets intended to reach the ceiling (e.g., pantries, oven cabinets in 9ft+ ceiling rooms) require precise overall height measurement.
    *   Often built in sections (e.g., base + upper) or require a separate top **scribe filler piece** to close the gap against potentially uneven ceilings.
    *   Crown molding integration needs careful planning at the top transition.

### 6. Cost Factor Considerations

Material and design choices significantly impact the final cost.

*   **Materials:** Plywood grades (shop vs. hardwood veneer), MDF, particle board, solid wood species all have different costs. Actual thickness affects price.
*   **Hardware:** Hinges (standard vs. soft-close), slides (side-mount vs. undermount soft-close), pull-out accessories (Lazy Susans, blind corner units), pulls/knobs vary widely in price.
*   **Construction Complexity:** Face frame construction is typically more labor/material intensive than frameless. Complex joinery (dovetails) adds time/cost. Angled or curved elements increase complexity.
*   **Finishing:** Paint vs. stain vs. clear coat impacts labor and material costs. High-gloss or multi-step finishes are more expensive.
*   **Sheet Yield:** As discussed above, poor yield directly increases material cost.

// ... (End of Section VIII) ...

---

## IX. TCS Woodwork Construction Standards

**Source:** Bryan Patton (Owner, TCS Woodwork) - Documented January 2025

This section documents the specific construction standards used at TCS Woodwork. These standards represent the shop's established practices for custom cabinet construction and have been implemented in the ERP system as configurable templates.

### 1. Cabinet Heights

| Cabinet Type | TCS Standard | Notes |
|--------------|--------------|-------|
| **Base Cabinet** | 34.75" (34 3/4") | Box height only. With 1.25" countertop = 36" finished counter height |
| **Wall Cabinet 30"** | 30" | Standard wall cabinet |
| **Wall Cabinet 36"** | 36" | Extended wall cabinet |
| **Wall Cabinet 42"** | 42" | Tall wall cabinet |
| **Tall Cabinet 84"** | 84" | Standard tall/pantry |
| **Tall Cabinet 96"** | 96" | Full-height tall |

**Bryan's Quote:** *"Cabinets for kitchens are normally 34, 3 quarter tall, because countertops are typically an inch of quarter"*

### 2. Toe Kick

| Dimension | TCS Standard | Notes |
|-----------|--------------|-------|
| **Height** | 4.5" (4 1/2") | Standard toe kick height |
| **Recess** | 3" | Distance from face frame to toe kick face |

**Bryan's Quote:** *"Toe kick standard is 4.5 inches tall"* and *"3 inches from the face"*

### 3. Stretchers

| Dimension | TCS Standard | Notes |
|-----------|--------------|-------|
| **Depth** | 3" | Front-to-back dimension |
| **Thickness** | 0.75" (3/4") | Material thickness |
| **Min Depth** | 2.5" | Minimum allowable |
| **Max Depth** | 4" | Maximum allowable |

**Bryan's Quote:** *"3 inch stretchers"*

**Stretcher Rules:**
- Base cabinets use stretchers (not full tops) for countertop attachment
- Wall cabinets have full tops
- Number of stretchers = 2 (front + back) + drawer count (one per drawer for slide mounting)

### 4. Face Frame

| Dimension | TCS Standard | Notes |
|-----------|--------------|-------|
| **Stile Width** | 1.5" (1 1/2") | Vertical members |
| **Rail Width** | 1.5" (1 1/2") | Horizontal members |
| **Door Gap** | 0.125" (1/8") | Gap between frame and door overlay |
| **Thickness** | 0.75" (3/4") | Material thickness |

**Bryan's Quote:** *"Face frame... typically is an inch and a half or inch of 3 quarter, then you have an 8th inch gap to your door"*

### 5. Materials

| Component | TCS Standard | Notes |
|-----------|--------------|-------|
| **Box Material** | 3/4" prefinished maple plywood | All cabinet boxes including backs |
| **Box Thickness** | 0.75" (3/4") | Side panels |
| **Back Thickness** | 0.75" (3/4") | Full thickness backs (NOT 1/4" backs) |
| **Face Frame Material** | Hardwood lumber | Species matches cabinet style |

**Bryan's Quote:** *"Our cabinets are built out of 3 quarter prefinished maple plywood, including the backs"*

**Important:** TCS uses full 3/4" backs, not the thinner 1/4" backs common in production cabinets. This provides:
- Greater structural rigidity
- Better screw holding for wall mounting
- Enhanced moisture resistance
- Premium quality appearance

### 6. Sink Cabinet

| Feature | TCS Standard | Notes |
|---------|--------------|-------|
| **Side Extension** | 0.75" (3/4") | Extra height at sink locations |

**Bryan's Quote:** *"At sink locations... sides will come up an additional 3/4 of an inch"*

This extension provides additional support for heavy sinks and allows the countertop to be properly supported around the sink cutout.

### 7. Section Layout Ratios

| Ratio Type | TCS Default | Notes |
|------------|-------------|-------|
| **Drawer Bank** | 40% | Width ratio for drawer banks |
| **Door Section** | 60% | Width ratio for door sections |
| **Equal Split** | 50% | For equal-width sections |

### 8. Countertop

| Dimension | TCS Standard | Notes |
|-----------|--------------|-------|
| **Thickness** | 1.25" (1 1/4") | Standard countertop thickness |
| **Finished Height** | 36" | Total height from floor (34.75" cabinet + 1.25" countertop) |

### 9. Construction Type Summary

**TCS Face Frame Construction:**
- Full 3/4" box construction with prefinished maple plywood
- Full 3/4" backs (not 1/4" backs)
- 1.5" face frame stiles and rails
- 1/8" door gap/reveal
- 3" stretchers on base cabinets
- 4.5" toe kicks with 3" recess
- Pocket hole or dowel joinery for face frames

**Top Construction by Cabinet Type:**
| Cabinet Type | Top Construction |
|--------------|------------------|
| Base | Stretchers |
| Wall | Full Top |
| Tall | Stretchers (typically) |

### 10. Implementation in ERP System

These TCS standards are implemented in the AureusERP system as configurable **Construction Templates**. This allows:

1. **Default Template:** "TCS Standard" template with all Bryan Patton values as defaults
2. **Template Inheritance:** Cabinet → Room → Project → Global Default
3. **Override Capability:** Individual cabinets can override template values when needed
4. **Alternative Templates:** Support for "European Frameless" or "Traditional Inset" styles

**Database Location:** `projects_construction_templates` table
**Admin UI:** Settings → Configurations → Construction Standards → Construction Templates

### 11. Key Differences from Industry Standards

| Aspect | Industry Common | TCS Standard | Benefit |
|--------|-----------------|--------------|---------|
| Base Height | 34.5" | 34.75" | Precise countertop alignment |
| Back Thickness | 0.25" | 0.75" | Structural rigidity, premium quality |
| Stretcher Depth | 3.5"-4" | 3" | Optimized material usage |
| Back Material | Hardboard/thin ply | Prefinished maple ply | Consistent box construction |

### 12. Calculation Examples

**Interior Height for Base Cabinet:**
```
Cabinet Height:     34.75"
- Toe Kick:         - 4.50"
- Stretcher:        - 3.00"
= Interior Height:  27.25"
```

**Interior Width for 36" Base Cabinet:**
```
Cabinet Width:      36.00"
- Left Side:        - 0.75"
- Right Side:       - 0.75"
= Interior Width:   34.50"
```

**Interior Depth for 24" Base Cabinet:**
```
Cabinet Depth:      24.00"
- Back Panel:       - 0.75"
= Interior Depth:   23.25"
```

### 13. Service Integration

The `ConstructionStandardsService` provides programmatic access to these values:

```php
use App\Services\ConstructionStandardsService;

$service = app(ConstructionStandardsService::class);
$cabinet = Cabinet::find(1);

// Get values with template inheritance
$stretcherDepth = $service->getStretcherDepth($cabinet);     // 3.0
$toeKickHeight = $service->getToeKickHeight($cabinet);       // 4.5
$stileWidth = $service->getFaceFrameStileWidth($cabinet);    // 1.5
$boxThickness = $service->getBoxMaterialThickness($cabinet); // 0.75

// Get all standards as array
$allStandards = $service->getAllStandards($cabinet);
```

---

*Last Updated: January 2025*
*Source: Bryan Patton, TCS Woodwork Owner*