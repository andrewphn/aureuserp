import rhinoscriptsyntax as rs

# Clear scene
all_objs = rs.AllObjects()
if all_objs:
    rs.DeleteObjects(all_objs)

# ==========================================
# TCS CABINET BUILD - 9 Austin Lane Sink Vanity
# Building piece by piece from JSON export
# ==========================================

# BOTTOM piece from JSON
# Width: 39.8125, Length: 20.25, Height: 0.75
# Starting at origin (0,0,0)

WIDTH = 39.8125
LENGTH = 20.25
HEIGHT = 0.75

bottom_pts = [
    (0, 0, 0),
    (WIDTH, 0, 0),
    (WIDTH, LENGTH, 0),
    (0, LENGTH, 0),
    (0, 0, HEIGHT),
    (WIDTH, 0, HEIGHT),
    (WIDTH, LENGTH, HEIGHT),
    (0, LENGTH, HEIGHT)
]

bottom = rs.AddBox(bottom_pts)
rs.ObjectName(bottom, 'BOTTOM')
rs.ObjectColor(bottom, (139, 90, 43))

print('BOTTOM created at origin')
print('  X: 0 to ' + str(WIDTH))
print('  Y: 0 to ' + str(LENGTH))
print('  Z: 0 to ' + str(HEIGHT))

# ==========================================
# LEFT SIDE from JSON
# Width: 0.75, Length: 21, Height: 28.75
# Sits ON TOP of bottom, at left edge
# ==========================================

LEFT_WIDTH = 0.75
LEFT_LENGTH = LENGTH  # Same depth as bottom (20.25)
LEFT_HEIGHT = 28.75

# Outer edge of left side lines up with outer edge (X=0) of bottom
# Sits ON TOP of bottom - starts at Z = HEIGHT (0.75)

left_side_pts = [
    (0, 0, HEIGHT),
    (LEFT_WIDTH, 0, HEIGHT),
    (LEFT_WIDTH, LEFT_LENGTH, HEIGHT),
    (0, LEFT_LENGTH, HEIGHT),
    (0, 0, HEIGHT + LEFT_HEIGHT),
    (LEFT_WIDTH, 0, HEIGHT + LEFT_HEIGHT),
    (LEFT_WIDTH, LEFT_LENGTH, HEIGHT + LEFT_HEIGHT),
    (0, LEFT_LENGTH, HEIGHT + LEFT_HEIGHT)
]

left_side = rs.AddBox(left_side_pts)
rs.ObjectName(left_side, 'LEFT_SIDE')
rs.ObjectColor(left_side, (139, 90, 43))

print('')
print('LEFT SIDE created')
print('  X: 0 to ' + str(LEFT_WIDTH))
print('  Y: 0 to ' + str(LEFT_LENGTH))
print('  Z: ' + str(HEIGHT) + ' to ' + str(HEIGHT + LEFT_HEIGHT))

# ==========================================
# RIGHT SIDE from JSON
# Width: 0.75, Length: 21, Height: 28.75
# Sits ON TOP of bottom, at right edge
# ==========================================

RIGHT_WIDTH = 0.75
RIGHT_LENGTH = LENGTH  # Same depth as bottom (20.25)
RIGHT_HEIGHT = 28.75

# Outer edge of right side lines up with outer edge (X=WIDTH) of bottom
# Sits ON TOP of bottom - starts at Z = HEIGHT (0.75)

right_side_pts = [
    (WIDTH - RIGHT_WIDTH, 0, HEIGHT),
    (WIDTH, 0, HEIGHT),
    (WIDTH, RIGHT_LENGTH, HEIGHT),
    (WIDTH - RIGHT_WIDTH, RIGHT_LENGTH, HEIGHT),
    (WIDTH - RIGHT_WIDTH, 0, HEIGHT + RIGHT_HEIGHT),
    (WIDTH, 0, HEIGHT + RIGHT_HEIGHT),
    (WIDTH, RIGHT_LENGTH, HEIGHT + RIGHT_HEIGHT),
    (WIDTH - RIGHT_WIDTH, RIGHT_LENGTH, HEIGHT + RIGHT_HEIGHT)
]

right_side = rs.AddBox(right_side_pts)
rs.ObjectName(right_side, 'RIGHT_SIDE')
rs.ObjectColor(right_side, (139, 90, 43))

print('')
print('RIGHT SIDE created')
print('  X: ' + str(WIDTH - RIGHT_WIDTH) + ' to ' + str(WIDTH))
print('  Y: 0 to ' + str(RIGHT_LENGTH))
print('  Z: ' + str(HEIGHT) + ' to ' + str(HEIGHT + RIGHT_HEIGHT))

# ==========================================
# BACK from JSON
# Width: 39.8125, Length: 0.75, Height: 28.75
# Goes full height from Z=0, attaches to rear of bottom
# ==========================================

BACK_WIDTH = 39.8125
BACK_LENGTH = 0.75
BACK_HEIGHT = 28.75

# Back is BEHIND everything - no overlap
# Bottom and sides go to Y = LENGTH (20.25), back starts there

BACK_Y_START = LENGTH  # 20.25 - behind everything
BACK_Y_END = BACK_Y_START + BACK_LENGTH  # 21

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
print('BACK created')
print('  X: 0 to ' + str(BACK_WIDTH))
print('  Y: ' + str(BACK_Y_START) + ' to ' + str(BACK_Y_END))
print('  Z: 0 to ' + str(BACK_HEIGHT))

rs.ZoomExtents()
