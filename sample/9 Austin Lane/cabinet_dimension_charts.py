#!/usr/bin/env python3
"""
Cabinet Dimension Breakdown Charts
9 Austin Lane - Sink Base Cabinet

Generates visual breakdowns of:
- WIDTH (W = 41-5/16" = 41.3125")
- HEIGHT (H = 34" including countertop)
- DEPTH (D = 21")

Run with: python3 cabinet_dimension_charts.py
"""

import matplotlib.pyplot as plt
import matplotlib.patches as patches
import numpy as np
from pathlib import Path

# Output directory
OUTPUT_DIR = Path(__file__).parent


def generate_width_chart():
    """Generate WIDTH breakdown chart (W = 41-5/16")"""

    total_width = 41.3125

    # Key measurements:
    end_panel_thick = 0.75
    gap = 0.25
    side_panel_thick = 0.75
    stile_width = 1.75
    drawer_rail_width = 1.5  # Drawer rail (horizontal member in face frame)

    # Left side
    left_end_panel_start = 0
    left_end_panel_end = end_panel_thick  # 0 to 0.75

    left_gap_start = end_panel_thick  # 0.75
    left_gap_end = end_panel_thick + gap  # 1.0

    left_side_start = left_gap_end  # 1.0
    left_side_end = left_gap_end + side_panel_thick  # 1.75

    # Stile goes from 0 to inside of side panel
    left_stile_start = 0
    left_stile_end = left_side_end  # 0 to 1.75 (edge to inside of side)

    # Right side (mirror)
    right_side_start = total_width - side_panel_thick - gap - end_panel_thick  # 39.5625
    right_side_end = total_width - gap - end_panel_thick  # 40.3125

    right_gap_start = right_side_end  # 40.3125
    right_gap_end = total_width - end_panel_thick  # 40.5625

    right_end_panel_start = right_gap_end  # 40.5625
    right_end_panel_end = total_width  # 41.3125

    # Stile goes from inside of side panel to edge
    right_stile_start = right_side_start  # 39.5625
    right_stile_end = total_width  # 41.3125

    # Interior (Face Frame Opening Width)
    interior_start = left_side_end  # 1.75
    interior_end = right_side_start  # 39.5625
    interior_width = interior_end - interior_start  # 37.8125 (face frame opening)

    # Drawer Rail spans the INTERIOR (between the stiles)
    drawer_rail_start = left_stile_end  # 1.75 (inside left stile)
    drawer_rail_end = right_stile_start  # 39.5625 (inside right stile)
    drawer_rail_length = drawer_rail_end - drawer_rail_start  # 37.8125

    fig, ax = plt.subplots(figsize=(16, 12))

    # Component types (each on its own Y row)
    component_types = [
        "End Panel (3/4\" thick)",
        "Gap (1/4\")",
        "Side Panel (3/4\" thick)",
        "Face Frame Stile (1-3/4\" wide)",
        "Drawer Rail (1-1/2\" wide)",
        "Interior (FF Opening)",
    ]

    colors = {
        "End Panel (3/4\" thick)": '#8B4513',
        "Gap (1/4\")": '#FFFFFF',
        "Side Panel (3/4\" thick)": '#D2691E',
        "Face Frame Stile (1-3/4\" wide)": '#DAA520',
        "Drawer Rail (1-1/2\" wide)": '#F4A460',
        "Interior (FF Opening)": '#90EE90',
    }

    # Y positions
    y_positions = {comp: i for i, comp in enumerate(component_types)}

    # Segments for each component
    segments = {
        "End Panel (3/4\" thick)": [
            (left_end_panel_start, left_end_panel_end),
            (right_end_panel_start, right_end_panel_end),
        ],
        "Gap (1/4\")": [
            (left_gap_start, left_gap_end),
            (right_gap_start, right_gap_end),
        ],
        "Side Panel (3/4\" thick)": [
            (left_side_start, left_side_end),
            (right_side_start, right_side_end),
        ],
        "Face Frame Stile (1-3/4\" wide)": [
            (left_stile_start, left_stile_end),
            (right_stile_start, right_stile_end),
        ],
        "Drawer Rail (1-1/2\" wide)": [
            (drawer_rail_start, drawer_rail_end),
        ],
        "Interior (FF Opening)": [
            (interior_start, interior_end),
        ],
    }

    # Plot each segment
    bar_height = 0.6
    for comp_name, segs in segments.items():
        y = y_positions[comp_name]
        for start, end in segs:
            width = end - start
            edgecolor = 'black' if comp_name != "Gap (1/4\")" else 'gray'
            rect = patches.Rectangle((start, y - bar_height/2), width, bar_height,
                                       linewidth=1, edgecolor=edgecolor,
                                       facecolor=colors[comp_name], alpha=0.8)
            ax.add_patch(rect)

            label_text = f'{width:.4g}"'
            if width > 1.5:
                ax.text(start + width/2, y, label_text,
                       ha='center', va='center', fontsize=9, fontweight='bold')
            elif width > 0.5:
                ax.text(start + width/2, y + 0.35, label_text,
                       ha='center', va='bottom', fontsize=8)

    # Formatting
    ax.set_xlim(-1, 44)
    ax.set_ylim(-0.5, len(component_types) - 0.5)
    ax.set_xlabel('Width from Left Edge (inches)', fontsize=12)
    ax.set_ylabel('Component', fontsize=12)
    ax.set_title('Cabinet WIDTH Breakdown (W = 41-5/16")\n9 Austin Lane - Sink Base Cabinet\nObjects CAN overlap in X space (45° miter at corners)', fontsize=14, fontweight='bold')

    ax.set_yticks(range(len(component_types)))
    ax.set_yticklabels(component_types)

    # Vertical lines at key positions
    key_positions = [0, left_end_panel_end, left_gap_end, left_side_end,
                     right_side_start, right_gap_start, right_end_panel_start, total_width]
    for pos in key_positions:
        ax.axvline(x=pos, color='red', linestyle='--', alpha=0.3, linewidth=1)

    ax.grid(True, alpha=0.3, axis='x')

    # Summary box
    summary_text = f"""WIDTH BREAKDOWN ({total_width}" total):

LEFT SIDE (0" to {left_side_end}"):
  • End Panel: {left_end_panel_start}" to {left_end_panel_end}" ({end_panel_thick}")
  • Gap: {left_gap_start}" to {left_gap_end}" ({gap}")
  • Side Panel: {left_side_start}" to {left_side_end}" ({side_panel_thick}")
  • Stile: {left_stile_start}" to {left_stile_end}" ({stile_width}")
    (OVERLAPS end panel & side - 45° miter)

INTERIOR:
  • FF Opening: {interior_start}" to {interior_end}" ({interior_width}")
  • Drawer Rail: {drawer_rail_start}" to {drawer_rail_end}" ({drawer_rail_length}")
    (Spans between stiles, 1.5" wide in HEIGHT)

RIGHT SIDE ({right_side_start}" to {total_width}"):
  • Side Panel: {right_side_start}" to {right_side_end}" ({side_panel_thick}")
  • Gap: {right_gap_start}" to {right_gap_end}" ({gap}")
  • End Panel: {right_end_panel_start}" to {right_end_panel_end}" ({end_panel_thick}")
  • Stile: {right_stile_start}" to {right_stile_end}" ({stile_width}")
    (OVERLAPS end panel & side - 45° miter)"""

    props = dict(boxstyle='round', facecolor='wheat', alpha=0.8)
    ax.text(1.02, 0.5, summary_text, transform=ax.transAxes, fontsize=8,
            verticalalignment='center', bbox=props, family='monospace')

    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / 'width_breakdown.png', dpi=150, bbox_inches='tight')
    plt.close()
    print("Width breakdown chart saved!")


def generate_height_chart():
    """Generate HEIGHT breakdown chart (H = 34" including countertop)"""

    total_height = 34.0
    toe_kick = 4.0
    box_height = 28.75
    countertop = 1.25

    fig, ax = plt.subplots(figsize=(14, 10))

    component_types = [
        "Countertop",
        "Face Frame Stiles",
        "Face Frame Rails (top/bottom)",
        "Back Panel",
        "Side Panel (full height - sink base)",
        "Bottom Panel",
        "Toe Kick (recessed 3\")",
    ]

    colors = {
        "Toe Kick (recessed 3\")": '#8B4513',
        "Side Panel (full height - sink base)": '#D2691E',
        "Back Panel": '#CD853F',
        "Bottom Panel": '#DEB887',
        "Face Frame Rails (top/bottom)": '#F4A460',
        "Face Frame Stiles": '#DAA520',
        "Countertop": '#696969',
    }

    y_positions = {comp: i for i, comp in enumerate(component_types)}

    segments = {
        "Toe Kick (recessed 3\")": [(0, 4.0)],
        "Bottom Panel": [(4.0, 4.75)],
        "Side Panel (full height - sink base)": [(4.0, 32.75)],
        "Back Panel": [(4.0, 32.75)],
        "Face Frame Rails (top/bottom)": [(4.0, 5.5), (31.25, 32.75)],
        "Face Frame Stiles": [(4.0, 32.75)],
        "Countertop": [(32.75, 34.0)],
    }

    bar_height = 0.6
    for comp_name, segs in segments.items():
        y = y_positions[comp_name]
        for start, end in segs:
            width = end - start
            rect = patches.Rectangle((start, y - bar_height/2), width, bar_height,
                                       linewidth=1, edgecolor='black',
                                       facecolor=colors[comp_name], alpha=0.8)
            ax.add_patch(rect)

            if width > 2:
                ax.text(start + width/2, y, f'{width:.4g}"',
                       ha='center', va='center', fontsize=9, fontweight='bold')

    ax.set_xlim(-1, 36)
    ax.set_ylim(-0.5, len(component_types) - 0.5)
    ax.set_xlabel('Height from Floor (inches)', fontsize=12)
    ax.set_ylabel('Component', fontsize=12)
    ax.set_title('Cabinet HEIGHT Breakdown (H = 34")\n9 Austin Lane - Sink Base Cabinet\nFrom Floor (0") to Countertop Top (34")', fontsize=14, fontweight='bold')

    ax.set_yticks(range(len(component_types)))
    ax.set_yticklabels(component_types)

    key_positions = [0, 4.0, 32.75, 34.0]
    key_labels = ['Floor', 'Top of Toe Kick\n(Bottom of Box)', 'Top of Box', 'Countertop Top']
    for pos, label in zip(key_positions, key_labels):
        ax.axvline(x=pos, color='red', linestyle='--', alpha=0.5, linewidth=1)
        ax.text(pos, len(component_types) - 0.3, label, ha='center', va='bottom',
                fontsize=8, color='red', rotation=90)

    ax.grid(True, alpha=0.3, axis='x')

    summary_text = """HEIGHT BREAKDOWN (34" total):
• Toe Kick: 4"
• Cabinet Box: 28.75"
• Countertop: 1.25"

Key Points:
• Sink base: Sides go FULL box height (28.75")
• Normal base: Sides 3/4" shorter (stretchers on top)
• Face frame on front face of box
• Bottom panel sits on top of toe kick"""

    props = dict(boxstyle='round', facecolor='wheat', alpha=0.8)
    ax.text(1.02, 0.5, summary_text, transform=ax.transAxes, fontsize=9,
            verticalalignment='center', bbox=props, family='monospace')

    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / 'height_breakdown.png', dpi=150, bbox_inches='tight')
    plt.close()
    print("Height breakdown chart saved!")


def generate_depth_chart():
    """Generate DEPTH breakdown chart (D = 21")"""

    total_depth = 21.0
    face_frame_thick = 1.0  # 5/4 hardwood
    back_panel_thick = 0.75
    wall_gap = 0.5

    fig, ax = plt.subplots(figsize=(16, 10))

    component_types = [
        "Face Frame (1\" - 5/4 hardwood)",
        "Side Panel (3/4\" thick)",
        "Inside Cavity (18\" drawer space)",
        "Back Panel (3/4\" thick)",
        "Wall Gap (1/2\" shimming)",
        "End Panel (3/4\" thick)",
    ]

    colors = {
        "Face Frame (1\" - 5/4 hardwood)": '#DAA520',
        "Side Panel (3/4\" thick)": '#D2691E',
        "Inside Cavity (18\" drawer space)": '#90EE90',
        "Back Panel (3/4\" thick)": '#CD853F',
        "Wall Gap (1/2\" shimming)": '#FFFFFF',
        "End Panel (3/4\" thick)": '#8B4513',
    }

    y_positions = {comp: i for i, comp in enumerate(component_types)}

    segments = {
        "Face Frame (1\" - 5/4 hardwood)": [(0, 1.0)],
        "Side Panel (3/4\" thick)": [(0, 20.5)],
        "Inside Cavity (18\" drawer space)": [(1.0, 19.0)],
        "Back Panel (3/4\" thick)": [(19.75, 20.5)],
        "Wall Gap (1/2\" shimming)": [(20.5, 21.0)],
        "End Panel (3/4\" thick)": [(0, 20.5)],
    }

    bar_height = 0.6
    for comp_name, segs in segments.items():
        y = y_positions[comp_name]
        for start, end in segs:
            width = end - start
            edgecolor = 'black' if "Gap" not in comp_name else 'gray'
            rect = patches.Rectangle((start, y - bar_height/2), width, bar_height,
                                       linewidth=1, edgecolor=edgecolor,
                                       facecolor=colors[comp_name], alpha=0.8)
            ax.add_patch(rect)

            if width > 1.5:
                ax.text(start + width/2, y, f'{width:.4g}"',
                       ha='center', va='center', fontsize=9, fontweight='bold')
            elif width > 0.5:
                ax.text(start + width/2, y + 0.35, f'{width:.4g}"',
                       ha='center', va='bottom', fontsize=8)

    ax.set_xlim(-1, 24)
    ax.set_ylim(-0.5, len(component_types) - 0.5)
    ax.set_xlabel('Depth from Front (inches)', fontsize=12)
    ax.set_ylabel('Component', fontsize=12)
    ax.set_title('Cabinet DEPTH Breakdown (D = 21")\n9 Austin Lane - Sink Base Cabinet\nFrom Front (0") to Wall (21")', fontsize=14, fontweight='bold')

    ax.set_yticks(range(len(component_types)))
    ax.set_yticklabels(component_types)

    key_positions = [0, 1.0, 19.0, 19.75, 20.5, 21.0]
    key_labels = ['Front Face', 'Back of FF\n(Cavity Start)', 'Cavity End', 'Back Panel\nStart', 'Cabinet\nBack', 'Wall']
    for pos, label in zip(key_positions, key_labels):
        ax.axvline(x=pos, color='red', linestyle='--', alpha=0.5, linewidth=1)
        ax.text(pos, len(component_types) - 0.3, label, ha='center', va='bottom',
                fontsize=7, color='red', rotation=90)

    ax.grid(True, alpha=0.3, axis='x')

    summary_text = """DEPTH BREAKDOWN (21" total):

From FRONT (0") to WALL (21"):
  • Face Frame: 0" to 1" (1")
  • Side Panels: 0" to 20.5" (20.5")
  • Inside Cavity: 1" to 19" (18" for slides)
  • Back Panel: 19.75" to 20.5" (0.75")
  • Wall Gap: 20.5" to 21" (0.5")

Key Points:
• 18" drawer slide cavity
• Face frame sits ON front of box
• End panels same depth as sides
• 1/2" gap to wall for shimming
• Back panel inset from wall by 0.5\""""

    props = dict(boxstyle='round', facecolor='wheat', alpha=0.8)
    ax.text(1.02, 0.5, summary_text, transform=ax.transAxes, fontsize=8,
            verticalalignment='center', bbox=props, family='monospace')

    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / 'depth_breakdown.png', dpi=150, bbox_inches='tight')
    plt.close()
    print("Depth breakdown chart saved!")


def main():
    """Generate all dimension breakdown charts"""
    print("Generating Cabinet Dimension Breakdown Charts...")
    print(f"Output directory: {OUTPUT_DIR}")
    print()

    generate_width_chart()
    generate_height_chart()
    generate_depth_chart()

    print()
    print("All charts generated!")
    print()
    print("DIMENSION SUMMARY:")
    print("==================")
    print("WIDTH (W)  = 41-5/16\" (41.3125\")")
    print("HEIGHT (H) = 34\" (including 1.25\" countertop)")
    print("DEPTH (D)  = 21\"")
    print()
    print("Cabinet Box Only: 41.3125\" x 28.75\" x 18.75\"")


if __name__ == "__main__":
    main()
