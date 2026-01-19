import rhinoscriptsyntax as rs

# Clear scene
all_objs = rs.AllObjects()
if all_objs:
    rs.DeleteObjects(all_objs)

# ==========================================
# TCS CABINET BUILD - 9 Austin Lane Sink Vanity
#
# DEPTH FORMULA (TCS Standard):
# Total = Face Frame + Drawer + Clearance + Back + Wall Gap
# 21"   = 1.5"       + 18"    + 0.25"     + 0.75" + 0.5"
#
# Internal depth (sides) = Drawer + Clearance = 18.25"
# ==========================================

# Cabinet exterior dimensions
CABINET_WIDTH = 39.8125
CABINET_HEIGHT = 28.75  # Box height (excluding toe kick)

# Depth components
FACE_FRAME_DEPTH = 1.5
DRAWER_DEPTH = 18.0
CLEARANCE = 0.25
BACK_THICKNESS = 0.75
WALL_GAP = 0.5

# Calculated depths
INTERNAL_DEPTH = DRAWER_DEPTH + CLEARANCE  # 18.25" - side panel depth
TOTAL_DEPTH = FACE_FRAME_DEPTH + DRAWER_DEPTH + CLEARANCE + BACK_THICKNESS + WALL_GAP  # 21"

# Material thickness
MATERIAL = 0.75

print('=== TCS Cabinet Depth Breakdown ===')
print('Face Frame: ' + str(FACE_FRAME_DEPTH) + '"')
print('Drawer: ' + str(DRAWER_DEPTH) + '"')
print('Clearance: ' + str(CLEARANCE) + '"')
print('Back: ' + str(BACK_THICKNESS) + '"')
print('Wall Gap: ' + str(WALL_GAP) + '"')
print('Internal Depth (sides): ' + str(INTERNAL_DEPTH) + '"')
print('Total Depth: ' + str(TOTAL_DEPTH) + '"')
print('')

# ==========================================
# BOTTOM - sits at Z=0, defines internal width
# ==========================================

BOTTOM_WIDTH = CABINET_WIDTH
BOTTOM_DEPTH = INTERNAL_DEPTH  # 18.25"
BOTTOM_HEIGHT = MATERIAL

bottom_pts = [
    (0, 0, 0),
    (BOTTOM_WIDTH, 0, 0),
    (BOTTOM_WIDTH, BOTTOM_DEPTH, 0),
    (0, BOTTOM_DEPTH, 0),
    (0, 0, BOTTOM_HEIGHT),
    (BOTTOM_WIDTH, 0, BOTTOM_HEIGHT),
    (BOTTOM_WIDTH, BOTTOM_DEPTH, BOTTOM_HEIGHT),
    (0, BOTTOM_DEPTH, BOTTOM_HEIGHT)
]

bottom = rs.AddBox(bottom_pts)
rs.ObjectName(bottom, 'BOTTOM')
rs.ObjectColor(bottom, (139, 90, 43))

print('BOTTOM created')
print('  X: 0 to ' + str(BOTTOM_WIDTH))
print('  Y: 0 to ' + str(BOTTOM_DEPTH))
print('  Z: 0 to ' + str(BOTTOM_HEIGHT))

# ==========================================
# LEFT SIDE - sits ON TOP of bottom
# ==========================================

SIDE_WIDTH = MATERIAL  # 0.75"
SIDE_DEPTH = INTERNAL_DEPTH  # 18.25" - same as bottom
SIDE_HEIGHT = CABINET_HEIGHT - MATERIAL  # Reduced for sandwich construction

left_side_pts = [
    (0, 0, BOTTOM_HEIGHT),
    (SIDE_WIDTH, 0, BOTTOM_HEIGHT),
    (SIDE_WIDTH, SIDE_DEPTH, BOTTOM_HEIGHT),
    (0, SIDE_DEPTH, BOTTOM_HEIGHT),
    (0, 0, BOTTOM_HEIGHT + SIDE_HEIGHT),
    (SIDE_WIDTH, 0, BOTTOM_HEIGHT + SIDE_HEIGHT),
    (SIDE_WIDTH, SIDE_DEPTH, BOTTOM_HEIGHT + SIDE_HEIGHT),
    (0, SIDE_DEPTH, BOTTOM_HEIGHT + SIDE_HEIGHT)
]

left_side = rs.AddBox(left_side_pts)
rs.ObjectName(left_side, 'LEFT_SIDE')
rs.ObjectColor(left_side, (139, 90, 43))

print('')
print('LEFT SIDE created')
print('  X: 0 to ' + str(SIDE_WIDTH))
print('  Y: 0 to ' + str(SIDE_DEPTH))
print('  Z: ' + str(BOTTOM_HEIGHT) + ' to ' + str(BOTTOM_HEIGHT + SIDE_HEIGHT))

# ==========================================
# RIGHT SIDE - sits ON TOP of bottom
# ==========================================

right_side_pts = [
    (CABINET_WIDTH - SIDE_WIDTH, 0, BOTTOM_HEIGHT),
    (CABINET_WIDTH, 0, BOTTOM_HEIGHT),
    (CABINET_WIDTH, SIDE_DEPTH, BOTTOM_HEIGHT),
    (CABINET_WIDTH - SIDE_WIDTH, SIDE_DEPTH, BOTTOM_HEIGHT),
    (CABINET_WIDTH - SIDE_WIDTH, 0, BOTTOM_HEIGHT + SIDE_HEIGHT),
    (CABINET_WIDTH, 0, BOTTOM_HEIGHT + SIDE_HEIGHT),
    (CABINET_WIDTH, SIDE_DEPTH, BOTTOM_HEIGHT + SIDE_HEIGHT),
    (CABINET_WIDTH - SIDE_WIDTH, SIDE_DEPTH, BOTTOM_HEIGHT + SIDE_HEIGHT)
]

right_side = rs.AddBox(right_side_pts)
rs.ObjectName(right_side, 'RIGHT_SIDE')
rs.ObjectColor(right_side, (139, 90, 43))

print('')
print('RIGHT SIDE created')
print('  X: ' + str(CABINET_WIDTH - SIDE_WIDTH) + ' to ' + str(CABINET_WIDTH))
print('  Y: 0 to ' + str(SIDE_DEPTH))
print('  Z: ' + str(BOTTOM_HEIGHT) + ' to ' + str(BOTTOM_HEIGHT + SIDE_HEIGHT))

# ==========================================
# BACK - attaches to OUTSIDE/REAR of sides
# Full height from Z=0
# ==========================================

BACK_WIDTH = CABINET_WIDTH
BACK_DEPTH = BACK_THICKNESS  # 0.75"
BACK_HEIGHT = CABINET_HEIGHT

# Back starts where sides end (Y = INTERNAL_DEPTH)
BACK_Y_START = INTERNAL_DEPTH  # 18.25"
BACK_Y_END = BACK_Y_START + BACK_DEPTH  # 19"

back_pts = [
    (0, BACK_Y_START, 0),
    (BACK_WIDTH, BACK_Y_START, 0),
    (BACK_WIDTH, BACK_Y_END, 0),
    (0, BACK_Y_END, 0),
    (0, BACK_Y_START, BACK_HEIGHT),
    (BACK_WIDTH, BACK_Y_START, BACK_HEIGHT),
    (BACK_WIDTH, BACK_Y_END, BACK_HEIGHT),
    (0, BACK_Y_END, BACK_HEIGHT)
]

back = rs.AddBox(back_pts)
rs.ObjectName(back, 'BACK')
rs.ObjectColor(back, (139, 90, 43))

print('')
print('BACK created (attaches to OUTSIDE of sides)')
print('  X: 0 to ' + str(BACK_WIDTH))
print('  Y: ' + str(BACK_Y_START) + ' to ' + str(BACK_Y_END))
print('  Z: 0 to ' + str(BACK_HEIGHT))

print('')
print('=== Summary ===')
print('Internal depth (usable for drawer): ' + str(INTERNAL_DEPTH) + '"')
print('Back Y position: ' + str(BACK_Y_START) + '" to ' + str(BACK_Y_END) + '"')
print('Total depth to back of cabinet: ' + str(BACK_Y_END) + '"')
print('(Add ' + str(WALL_GAP) + '" wall gap for total ' + str(TOTAL_DEPTH) + '" to wall)')

rs.ZoomExtents()
