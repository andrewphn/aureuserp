"""
TCS Grasshopper Cabinet System
==============================

A complete Grasshopper-based UI for TCS cabinet design.

Components:
-----------

API (api/):
    - tcs_api_connect: API connection + auth token
    - tcs_api_fetch: GET requests with caching
    - tcs_api_write: POST/PUT/DELETE requests

Navigation (navigation/):
    - tcs_project_selector: Project dropdown + details
    - tcs_room_navigator: Room/Location cascading
    - tcs_cabinet_list: Cabinet selection
    - tcs_hierarchy_tree: Full tree visualization

Calculator (calculator/):
    - tcs_cabinet_calc: API calculation endpoint
    - tcs_cut_list: Cut list table display
    - tcs_override_manager: Override storage
    - tcs_pricing: Pricing calculations

Geometry (geometry/):
    - tcs_cabinet_box: Cabinet envelope
    - tcs_parts_generator: Individual parts
    - tcs_face_frame_geo: Face frame
    - tcs_drawer_geo: Drawer geometry

UI (ui/):
    - tcs_cabinet_panel: Human UI panel data
    - tcs_save_to_erp: Save to ERP

Usage:
------

1. Install Human UI from Food4Rhino
2. Copy components to Grasshopper User Objects
3. Create GHPython components and paste code
4. Wire according to README.md

API:
----

Base URL: http://aureuserp.test (or production URL)
Auth: Bearer token from admin panel
"""

__version__ = "1.0.0"
__author__ = "TCS Woodwork"

# Component list for reference
COMPONENTS = {
    'api': [
        'tcs_api_connect',
        'tcs_api_fetch',
        'tcs_api_write',
    ],
    'navigation': [
        'tcs_project_selector',
        'tcs_room_navigator',
        'tcs_cabinet_list',
        'tcs_hierarchy_tree',
    ],
    'calculator': [
        'tcs_cabinet_calc',
        'tcs_cut_list',
        'tcs_override_manager',
        'tcs_pricing',
    ],
    'geometry': [
        'tcs_cabinet_box',
        'tcs_parts_generator',
        'tcs_face_frame_geo',
        'tcs_drawer_geo',
    ],
    'ui': [
        'tcs_cabinet_panel',
        'tcs_save_to_erp',
    ],
}

# TCS Construction Constants
CONSTANTS = {
    'TOE_KICK_HEIGHT': 4.5,
    'TOE_KICK_SETBACK': 3.0,
    'STRETCHER_DEPTH': 3.0,
    'FACE_FRAME_STILE': 1.5,
    'FACE_FRAME_RAIL': 1.5,
    'MATERIAL_THICKNESS': 0.75,
    'BACK_PANEL_THICKNESS': 0.25,
    'COMPONENT_GAP': 0.125,
    'BLUM_SIDE_DEDUCTION': 0.625,
    'BLUM_HEIGHT_DEDUCTION': 0.8125,
}
