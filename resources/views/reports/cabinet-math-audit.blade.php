<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabinet Shop Drawing | {{ $audit['project_name'] ?? 'TCS Woodwork' }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap');

        @media print {
            @page { size: 17in 11in landscape; margin: 0.35in; }
            body { margin: 0; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .sheet { width: 100%; margin: 0; padding: 0; page-break-after: always; box-shadow: none; }
            .sheet:last-child { page-break-after: auto; }
            .no-print { display: none !important; }
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            line-height: 1.25;
            color: #1c1917;
            background: #e5e5e5;
            font-size: 8pt;
        }

        /* 11x17 Landscape Sheet */
        .sheet {
            width: 17in;
            height: 11in;
            margin: 8px auto;
            padding: 0.3in;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
            display: grid;
            grid-template-rows: auto 1fr auto;
            overflow: hidden;
        }

        /* Title Block - Architectural Style */
        .title-block {
            display: grid;
            grid-template-columns: 1fr auto;
            border: 2px solid #1c1917;
            margin-bottom: 10px;
        }

        .title-left {
            display: grid;
            grid-template-columns: 50px 1fr 1fr 1fr;
            border-right: 2px solid #1c1917;
        }

        .logo-cell {
            background: linear-gradient(135deg, #d4a574 0%, #8b5a2b 100%);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 18px;
            border-right: 1px solid #1c1917;
        }

        .title-cell {
            padding: 6px 10px;
            border-right: 1px solid #d4d4d4;
        }

        .title-cell:last-child { border-right: none; }

        .title-label {
            font-size: 6px; color: #666;
            text-transform: uppercase; letter-spacing: 0.5px;
        }

        .title-value {
            font-size: 10px; font-weight: 600; margin-top: 2px;
        }

        .title-right {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            width: 200px;
        }

        .status-cell {
            display: flex; align-items: center; justify-content: center;
            border-left: 1px solid #d4d4d4;
        }

        .status-badge {
            font-size: 12px; font-weight: 700; padding: 4px 12px;
            border-radius: 3px; text-transform: uppercase;
        }
        .status-pass { background: #dcfce7; color: #166534; }
        .status-warning { background: #fef3c7; color: #92400e; }
        .status-fail { background: #fee2e2; color: #991b1b; }

        .sheet-info {
            padding: 6px 10px; text-align: center;
            border-left: 1px solid #d4d4d4;
        }

        .sheet-label { font-size: 6px; color: #666; text-transform: uppercase; }
        .sheet-num { font-size: 14px; font-weight: 700; }

        /* Main Content Grid - 4 columns for elevations */
        .content-grid {
            display: grid;
            grid-template-columns: 240px 220px 1fr 280px;
            gap: 10px;
            height: 100%;
            overflow: hidden;
        }

        /* Elevation Preview Cards */
        .elevation-card {
            background: #fafafa;
            border: 1px solid #e5e5e5;
            border-radius: 3px;
            padding: 4px;
            text-align: center;
        }

        .elevation-title {
            font-size: 6px;
            font-weight: 600;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 2px;
        }

        .elevation-svg {
            background: #fff;
            border: 1px solid #ddd;
        }

        /* Column Sections */
        .section {
            border: 1px solid #d4d4d4;
            border-radius: 3px;
            overflow: hidden;
        }

        .section-header {
            background: #1c1917; color: white;
            padding: 4px 8px;
            font-size: 8px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.5px;
        }

        .section-body {
            padding: 6px;
        }

        /* Golden Numbers Box */
        .golden-box {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 8px;
        }

        .golden-title {
            font-size: 7px; font-weight: 700; color: #92400e;
            text-transform: uppercase; letter-spacing: 0.5px;
            margin-bottom: 6px; text-align: center;
        }

        .golden-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px;
        }

        .golden-item {
            text-align: center;
            background: white;
            padding: 4px;
            border-radius: 2px;
        }

        .golden-value {
            font-size: 12px; font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }

        .golden-label {
            font-size: 6px; color: #666;
            text-transform: uppercase;
        }

        /* Cabinet Dims */
        .cabinet-dims-box {
            background: #eff6ff;
            border: 1px solid #3b82f6;
            border-radius: 3px;
            padding: 8px;
            text-align: center;
            margin-bottom: 8px;
        }

        .cabinet-dims-value {
            font-size: 14px; font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            color: #1d4ed8;
        }

        .cabinet-dims-label {
            font-size: 6px; color: #64748b; margin-top: 2px;
        }

        /* Compact Tables */
        .mini-table {
            width: 100%; border-collapse: collapse;
            font-size: 7pt;
        }

        .mini-table th {
            background: #f5f5f5;
            padding: 3px 5px;
            text-align: left;
            font-size: 6px;
            font-weight: 600;
            text-transform: uppercase;
            border-bottom: 1px solid #d4d4d4;
        }

        .mini-table td {
            padding: 3px 5px;
            border-bottom: 1px solid #f0f0f0;
        }

        .mini-table tr:nth-child(even) { background: #fafafa; }

        .mono {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 500;
        }

        /* Cut List Table */
        .cut-table {
            width: 100%; border-collapse: collapse;
            font-size: 8pt;
        }

        .cut-table th {
            background: #1c1917; color: white;
            padding: 4px 6px;
            text-align: left;
            font-size: 7px; font-weight: 600;
            text-transform: uppercase;
        }

        .cut-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #e5e5e5;
        }

        .cut-table tr:nth-child(even) { background: #fafafa; }

        .cut-table .material-row {
            background: #d4a574; color: white;
            font-weight: 600; font-size: 7px;
        }

        .cut-table .material-row td {
            padding: 3px 6px;
            border-bottom: none;
        }

        .qty-badge {
            display: inline-block;
            background: #1c1917; color: white;
            padding: 1px 6px; border-radius: 8px;
            font-size: 8px; font-weight: 700;
        }

        .check-col {
            width: 18px; text-align: center;
        }

        .check-box {
            width: 12px; height: 12px;
            border: 1.5px solid #1c1917;
            border-radius: 2px;
            display: inline-block;
        }

        /* Drawer Cards */
        .drawer-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 4px;
        }

        .drawer-card {
            background: #fafafa;
            border: 1px solid #e5e5e5;
            border-radius: 3px;
            padding: 5px;
            text-align: center;
        }

        .drawer-num {
            font-size: 6px; font-weight: 600; color: #666;
            text-transform: uppercase;
        }

        .drawer-dims {
            font-size: 10px; font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }

        .drawer-sub {
            font-size: 6px; color: #999;
        }

        /* Blum Specs */
        .blum-box {
            background: #eff6ff;
            border: 1px solid #3b82f6;
            border-radius: 3px;
            padding: 6px;
            margin-bottom: 8px;
        }

        .blum-title {
            font-size: 7px; font-weight: 600; color: #1d4ed8;
            margin-bottom: 4px;
        }

        .blum-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 3px;
            font-size: 7px;
        }

        .blum-item {
            display: flex;
            justify-content: space-between;
        }

        .blum-label { color: #64748b; }
        .blum-value { font-weight: 600; font-family: 'JetBrains Mono', monospace; }

        /* Machining Checklist */
        .mach-list {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .mach-item {
            display: flex;
            align-items: flex-start;
            gap: 5px;
            padding: 4px 6px;
            background: #fafafa;
            border: 1px solid #e5e5e5;
            border-radius: 2px;
        }

        .mach-text {
            font-size: 7px;
        }

        .mach-detail {
            font-size: 6px; color: #666;
        }

        /* Assembly Steps */
        .assembly-steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px;
        }

        .assembly-step {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 2px;
        }

        .step-num {
            width: 16px; height: 16px;
            background: #3b82f6; color: white;
            border-radius: 50%;
            font-size: 8px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .step-text {
            font-size: 7px; font-weight: 500;
        }

        /* Gap Assessment Mini */
        .gap-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 3px;
        }

        .gap-item {
            display: flex;
            justify-content: space-between;
            padding: 2px 4px;
            font-size: 7px;
            background: #fafafa;
            border-radius: 2px;
        }

        .gap-ok { color: #166534; }
        .gap-warn { color: #92400e; }

        /* Nesting Preview */
        .nesting-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
        }

        .nesting-card {
            background: #fafafa;
            border: 1px solid #e5e5e5;
            border-radius: 3px;
            padding: 6px;
        }

        .nesting-header {
            display: flex;
            justify-content: space-between;
            font-size: 7px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .nesting-eff {
            color: #166534;
        }

        /* Material Summary */
        .material-summary {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 4px;
        }

        .material-item {
            display: flex;
            justify-content: space-between;
            padding: 3px 6px;
            background: #fafafa;
            border-radius: 2px;
            font-size: 7px;
        }

        /* Footer */
        .footer-bar {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 20px;
            padding-top: 6px;
            border-top: 1px solid #d4d4d4;
            font-size: 6px;
            color: #666;
        }

        .footer-notes {
            display: flex;
            gap: 20px;
        }

        /* Warnings */
        .warnings-box {
            background: #fef3c7;
            border-left: 3px solid #f59e0b;
            padding: 4px 8px;
            margin-bottom: 8px;
            border-radius: 2px;
        }

        .warnings-title {
            font-size: 7px; font-weight: 600; color: #92400e;
        }

        .warning-item {
            font-size: 7px; color: #78350f;
            margin-left: 8px;
        }

        /* Print button */
        .print-controls {
            position: fixed; top: 12px; right: 12px; z-index: 1000;
        }

        .print-btn {
            background: #1c1917; color: white; border: none;
            padding: 8px 16px; border-radius: 4px;
            font-size: 11px; font-weight: 500; cursor: pointer;
            display: flex; align-items: center; gap: 6px;
        }

        .print-btn:hover { background: #292524; }

        /* Signature */
        .sig-box {
            display: flex;
            gap: 20px;
            padding: 6px 10px;
            background: #fafafa;
            border: 1px solid #e5e5e5;
            border-radius: 3px;
        }

        .sig-field {
            flex: 1;
        }

        .sig-line {
            border-bottom: 1px solid #1c1917;
            height: 14px;
        }

        .sig-label {
            font-size: 6px; color: #666;
        }

        /* Verification row */
        .verify-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 4px;
        }

        .verify-item {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 3px 6px;
            background: #fafafa;
            border: 1px solid #e5e5e5;
            border-radius: 2px;
            font-size: 7px;
        }
    </style>
</head>
<body>
    <div class="print-controls no-print">
        <button class="print-btn" onclick="window.print()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print 11×17
        </button>
    </div>

    <!-- ==================== SHEET 1: COMPLETE JOB SHEET ==================== -->
    <div class="sheet">
        <!-- Title Block -->
        <div class="title-block">
            <div class="title-left">
                <div class="logo-cell">TCS</div>
                <div class="title-cell">
                    <div class="title-label">Project</div>
                    <div class="title-value">{{ $audit['project_name'] ?? 'Cabinet Specification' }}</div>
                </div>
                <div class="title-cell">
                    <div class="title-label">Cabinet Code</div>
                    <div class="title-value mono">{{ $audit['cabinet_code'] ?? 'BTH1-B1-C1' }}</div>
                </div>
                <div class="title-cell">
                    <div class="title-label">Generated</div>
                    <div class="title-value">{{ now()->format('M j, Y g:i A') }}</div>
                </div>
            </div>
            <div class="title-right">
                <div class="status-cell">
                    <span class="status-badge {{ $audit['summary']['status'] === 'PASS' ? 'status-pass' : ($audit['summary']['status'] === 'WARNING' ? 'status-warning' : 'status-fail') }}">
                        {{ $audit['summary']['status'] }}
                    </span>
                </div>
                <div class="sheet-info">
                    <div class="sheet-label">Sheet</div>
                    <div class="sheet-num">1/1</div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- LEFT COLUMN: Specs & Reference -->
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <!-- Cabinet Dimensions -->
                <div class="cabinet-dims-box">
                    <div class="cabinet-dims-value">{{ $formatInches($audit['input_specs']['width']) }} W × {{ $formatInches($audit['input_specs']['height']) }} H × {{ $formatInches($audit['input_specs']['depth']) }} D</div>
                    <div class="cabinet-dims-label">Cabinet Overall Dimensions</div>
                </div>

                <!-- Golden Numbers -->
                <div class="golden-box">
                    <div class="golden-title">Golden Numbers</div>
                    <div class="golden-grid">
                        <div class="golden-item">
                            <div class="golden-value">{{ $formatInches($audit['gates']['gate_1_cabinet_box']['outputs']['box_height']) }}</div>
                            <div class="golden-label">Box Ht</div>
                        </div>
                        <div class="golden-item">
                            <div class="golden-value">{{ $formatInches($audit['gates']['gate_1_cabinet_box']['outputs']['inside_width']) }}</div>
                            <div class="golden-label">In Width</div>
                        </div>
                        <div class="golden-item">
                            <div class="golden-value">{{ $formatInches($audit['gates']['gate_1_cabinet_box']['outputs']['inside_depth']) }}</div>
                            <div class="golden-label">In Depth</div>
                        </div>
                        <div class="golden-item">
                            <div class="golden-value">{{ $formatInches($audit['gates']['gate_2_face_frame_opening']['outputs']['opening_width']) }}</div>
                            <div class="golden-label">FF Open W</div>
                        </div>
                        <div class="golden-item">
                            <div class="golden-value">{{ $formatInches($audit['gates']['gate_2_face_frame_opening']['outputs']['opening_height']) }}</div>
                            <div class="golden-label">FF Open H</div>
                        </div>
                        <div class="golden-item">
                            <div class="golden-value">{{ count($audit['input_specs']['drawer_heights']) }}</div>
                            <div class="golden-label">Drawers</div>
                        </div>
                    </div>
                </div>

                <!-- Warnings -->
                @if(!empty($audit['summary']['warnings']))
                <div class="warnings-box">
                    <div class="warnings-title">Warnings</div>
                    @foreach($audit['summary']['warnings'] as $warning)
                    <div class="warning-item">{{ $warning }}</div>
                    @endforeach
                </div>
                @endif

                <!-- Drawer Summary -->
                <div class="section">
                    <div class="section-header">Drawer Boxes (Shop Values)</div>
                    <div class="section-body">
                        <div class="drawer-grid">
                            @foreach($audit['gates']['gate_4_drawer_clearances']['drawer_boxes'] as $drawer)
                            <div class="drawer-card">
                                <div class="drawer-num">Drw {{ $drawer['drawer_number'] }}</div>
                                <div class="drawer-dims">{{ $formatInches($drawer['outputs']['box_width']) }} × {{ $formatInches($drawer['outputs']['box_height_shop']) }}</div>
                                <div class="drawer-sub">× {{ $formatInches($drawer['outputs']['box_depth_shop']) }} D</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Input Specs (compact) -->
                <div class="section">
                    <div class="section-header">Input Specifications</div>
                    <div class="section-body">
                        <table class="mini-table">
                            <tr>
                                <td>Toe Kick</td>
                                <td class="mono">{{ $formatInches($audit['input_specs']['toe_kick_height']) }}</td>
                            </tr>
                            <tr>
                                <td>Side Thick</td>
                                <td class="mono">{{ $formatInches($audit['input_specs']['side_thickness'] ?? 0.75) }}</td>
                            </tr>
                            <tr>
                                <td>FF Stile</td>
                                <td class="mono">{{ $formatInches($audit['input_specs']['face_frame_stile']) }}</td>
                            </tr>
                            <tr>
                                <td>FF Rail</td>
                                <td class="mono">{{ $formatInches($audit['input_specs']['face_frame_rail']) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Gap Assessment (compact) -->
                <div class="section" style="font-size: 6px;">
                    <div class="section-header">Gap Assessment</div>
                    <div class="section-body" style="padding: 4px;">
                        <div class="gap-grid" style="gap: 2px;">
                            @foreach($audit['gap_assessment'] as $category => $gaps)
                                @foreach($gaps as $gap)
                                <div class="gap-item" style="padding: 1px 3px;">
                                    <span>{{ Str::limit($gap['location'], 10) }}</span>
                                    <span class="{{ strtolower($gap['status']) === 'ok' ? 'gap-ok' : 'gap-warn' }} mono">{{ $gap['status'] }}</span>
                                </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- ELEVATIONS COLUMN: Front & Side Views -->
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <!-- Front Elevation - Reference from BOTTOM, matching CAD-front-elevation.png -->
                <div class="section" style="flex: 1;">
                    <div class="section-header">Front Elevation</div>
                    <div class="section-body" style="display: flex; align-items: center; justify-content: center; padding: 4px;">
                        @php
                            // Cabinet dimensions
                            $cabW = $audit['input_specs']['width'];      // 41.3125"
                            $cabH = $audit['input_specs']['height'];     // 32.75"
                            $cabD = $audit['input_specs']['depth'];      // 21"
                            $toeKick = $audit['input_specs']['toe_kick_height']; // 4"
                            $boxH = $audit['gates']['gate_1_cabinet_box']['outputs']['box_height']; // 28.75"

                            // Face frame
                            $ffStile = $audit['input_specs']['face_frame_stile']; // 1.75"
                            $ffRail = $audit['input_specs']['face_frame_rail'];   // 1.5"
                            $ffOpenW = $audit['gates']['gate_2_face_frame_opening']['outputs']['opening_width']; // 37.8125"
                            $ffOpenH = $audit['gates']['gate_2_face_frame_opening']['outputs']['opening_height']; // 25.75"

                            // False front (if exists)
                            $hasFalseFront = !empty($audit['input_specs']['false_fronts']);
                            $ffFaceH = $hasFalseFront ? ($audit['input_specs']['false_fronts'][0]['face_height'] ?? 7) : 0;

                            // Drawer heights from specs
                            $drawerHeights = $audit['input_specs']['drawer_heights'] ?? [];
                            $gap = 0.125; // 1/8" gap between components

                            // Scale to fit (leave room for dimensions)
                            $frontScale = min(170 / $cabW, 200 / $cabH);
                            $fSvgW = $cabW * $frontScale;
                            $fSvgH = $cabH * $frontScale;
                            $boxHScaled = $boxH * $frontScale;
                            $toeKickScaled = $toeKick * $frontScale;
                            $margin = 35;
                            $topY = 25; // SVG top
                            $floorY = $topY + $fSvgH; // SVG bottom (floor level)

                            // Coordinate transformation functions
                            $toSvgY = fn($y) => $topY + ($y * $frontScale); // Transform Y from inches to SVG
                        @endphp
                        <svg viewBox="0 0 {{ $fSvgW + 70 }} {{ $fSvgH + 55 }}" style="width: 100%; max-width: 200px; height: auto;">
                            <!--
                                3D COORDINATE SYSTEM:
                                Origin (0,0,0) = FRONT-TOP-LEFT corner of cabinet

                                X-axis: Left → Right (positive)
                                Y-axis: Top → Bottom (positive, matches SVG)
                                Z-axis: Front → Back (positive)

                                SVG uses X,Y where Y goes DOWN
                            -->

                            @php
                                // ========================================
                                // 3D POSITION CALCULATIONS
                                // Origin: Front-Top-Left corner (0,0,0)
                                // X: 0 = left edge, +cabW = right edge
                                // Y: 0 = top of cabinet, +cabH = floor
                                // Z: 0 = front face, +cabD = back
                                // ========================================

                                // SVG coordinate helpers (Y=0 at top, matches our 3D Y)
                                $svgMarginX = $margin;
                                $svgTopY = $topY;

                                // Transform 3D X,Y to SVG coordinates
                                $to2dX = fn($x3d) => $svgMarginX + ($x3d * $frontScale);
                                $to2dY = fn($y3d) => $svgTopY + ($y3d * $frontScale);

                                // Opening position in 3D space
                                $openingX3d = $ffStile;  // X: starts after left stile
                                $openingW3d = $ffOpenW;  // Width of opening

                                // SVG versions
                                $openingX = $to2dX($openingX3d);
                                $openingW = $openingW3d * $frontScale;

                                // ========================================
                                // COMPONENT 3D POSITIONS
                                // All Y values measured from TOP (Y=0)
                                // Layout order (top to bottom):
                                //   Top Rail → False Front → Mid Rail → U-Drawer → Mid Rail → Lower Drawer → Bottom Rail → Toe Kick
                                // ========================================

                                $positions3d = [];

                                // 1. TOP RAIL: Y=0 to Y=rail_width
                                $positions3d['top_rail'] = [
                                    'x' => $openingX3d,
                                    'y' => 0,          // Starts at top
                                    'z' => 0,          // At front face
                                    'w' => $openingW3d,
                                    'h' => $ffRail,
                                    'd' => 0.75,       // Rail thickness
                                    'type' => 'rail',
                                ];

                                // Track current Y position (from top going down)
                                $currentY = $ffRail + $gap; // Start below top rail

                                // 2. FALSE FRONT (if exists) - at top below top rail
                                if ($hasFalseFront) {
                                    $positions3d['false_front'] = [
                                        'x' => $openingX3d,
                                        'y' => $currentY,
                                        'z' => 0,
                                        'w' => $openingW3d,
                                        'h' => $ffFaceH,
                                        'd' => 0.75,
                                        'type' => 'false_front',
                                        'label' => 'FALSE FRONT',
                                    ];
                                    $currentY += $ffFaceH + $gap;

                                    // 3. MID RAIL (between false front and upper drawer)
                                    $positions3d['mid_rail_2'] = [
                                        'x' => $openingX3d,
                                        'y' => $currentY,
                                        'z' => 0,
                                        'w' => $openingW3d,
                                        'h' => $ffRail,
                                        'd' => 0.75,
                                        'type' => 'rail',
                                    ];
                                    $currentY += $ffRail + $gap;
                                }

                                // 4. UPPER DRAWER (U-shaped if false front exists)
                                if (count($drawerHeights) >= 1) {
                                    $upperH = $drawerHeights[0];
                                    $positions3d['upper_drawer'] = [
                                        'x' => $openingX3d,
                                        'y' => $currentY,
                                        'z' => 0,
                                        'w' => $openingW3d,
                                        'h' => $upperH,
                                        'd' => $audit['input_specs']['drawer_slide_length'] ?? 18,
                                        'type' => $hasFalseFront ? 'u_drawer' : 'drawer',
                                        'label' => $hasFalseFront ? 'U-SHAPED DRAWER' : 'DRAWER',
                                    ];
                                    $currentY += $upperH + $gap;
                                }

                                // 5. MID RAIL (between upper and lower drawers)
                                if (count($drawerHeights) >= 2) {
                                    $positions3d['mid_rail_1'] = [
                                        'x' => $openingX3d,
                                        'y' => $currentY,
                                        'z' => 0,
                                        'w' => $openingW3d,
                                        'h' => $ffRail,
                                        'd' => 0.75,
                                        'type' => 'rail',
                                    ];
                                    $currentY += $ffRail + $gap;

                                    // 6. LOWER DRAWER
                                    $lowerH = $drawerHeights[1];
                                    $positions3d['lower_drawer'] = [
                                        'x' => $openingX3d,
                                        'y' => $currentY,
                                        'z' => 0,
                                        'w' => $openingW3d,
                                        'h' => $lowerH,
                                        'd' => $audit['input_specs']['drawer_slide_length'] ?? 18,
                                        'type' => 'drawer',
                                        'label' => 'DRAWER',
                                    ];
                                    $currentY += $lowerH + $gap;
                                }

                                // 7. BOTTOM RAIL
                                $positions3d['bottom_rail'] = [
                                    'x' => $openingX3d,
                                    'y' => $boxH - $ffRail,  // Fixed position near bottom
                                    'z' => 0,
                                    'w' => $openingW3d,
                                    'h' => $ffRail,
                                    'd' => 0.75,
                                    'type' => 'rail',
                                ];

                                // 8. TOE KICK: from bottom of box to floor
                                $positions3d['toe_kick'] = [
                                    'x' => 0,
                                    'y' => $boxH,      // Starts at bottom of box
                                    'z' => 3,         // Recessed 3" from front
                                    'w' => $cabW,
                                    'h' => $toeKick,
                                    'd' => $cabD - 3, // Full depth minus setback
                                    'type' => 'toe_kick',
                                ];

                                // STILES (full height vertical members)
                                $positions3d['left_stile'] = [
                                    'x' => 0,
                                    'y' => 0,
                                    'z' => 0,
                                    'w' => $ffStile,
                                    'h' => $boxH,
                                    'd' => 0.75,
                                    'type' => 'stile',
                                ];

                                $positions3d['right_stile'] = [
                                    'x' => $cabW - $ffStile,
                                    'y' => 0,
                                    'z' => 0,
                                    'w' => $ffStile,
                                    'h' => $boxH,
                                    'd' => 0.75,
                                    'type' => 'stile',
                                ];
                            @endphp

                            <!-- TOE KICK - recessed from front -->
                            @php $tk = $positions3d['toe_kick']; @endphp
                            <rect x="{{ $to2dX($tk['x']) }}"
                                  y="{{ $to2dY($tk['y']) }}"
                                  width="{{ $tk['w'] * $frontScale }}"
                                  height="{{ $tk['h'] * $frontScale }}"
                                  fill="#3d3d3d" stroke="#333" stroke-width="0.5"/>

                            <!-- CABINET BOX (face frame area) -->
                            <rect x="{{ $svgMarginX }}"
                                  y="{{ $svgTopY }}"
                                  width="{{ $fSvgW }}"
                                  height="{{ $boxHScaled }}"
                                  fill="#f8f4ef" stroke="#8b5a2b" stroke-width="1.2"/>

                            <!-- LEFT STILE -->
                            @php $ls = $positions3d['left_stile']; @endphp
                            <rect x="{{ $to2dX($ls['x']) }}"
                                  y="{{ $to2dY($ls['y']) }}"
                                  width="{{ $ls['w'] * $frontScale }}"
                                  height="{{ $ls['h'] * $frontScale }}"
                                  fill="#d4c4b0" stroke="#8b5a2b" stroke-width="0.5"/>

                            <!-- RIGHT STILE -->
                            @php $rs = $positions3d['right_stile']; @endphp
                            <rect x="{{ $to2dX($rs['x']) }}"
                                  y="{{ $to2dY($rs['y']) }}"
                                  width="{{ $rs['w'] * $frontScale }}"
                                  height="{{ $rs['h'] * $frontScale }}"
                                  fill="#d4c4b0" stroke="#8b5a2b" stroke-width="0.5"/>

                            <!-- TOP RAIL -->
                            @php $tr = $positions3d['top_rail']; @endphp
                            <rect x="{{ $to2dX($tr['x']) }}"
                                  y="{{ $to2dY($tr['y']) }}"
                                  width="{{ $tr['w'] * $frontScale }}"
                                  height="{{ $tr['h'] * $frontScale }}"
                                  fill="#c9a882" stroke="#8b5a2b" stroke-width="0.5"/>

                            <!-- BOTTOM RAIL -->
                            @php $br = $positions3d['bottom_rail']; @endphp
                            <rect x="{{ $to2dX($br['x']) }}"
                                  y="{{ $to2dY($br['y']) }}"
                                  width="{{ $br['w'] * $frontScale }}"
                                  height="{{ $br['h'] * $frontScale }}"
                                  fill="#c9a882" stroke="#8b5a2b" stroke-width="0.5"/>

                            <!-- DRAW COMPONENTS FROM 3D POSITIONS -->
                            @foreach(['false_front', 'mid_rail_2', 'upper_drawer', 'mid_rail_1', 'lower_drawer'] as $key)
                                @if(isset($positions3d[$key]))
                                    @php
                                        $pos = $positions3d[$key];
                                        $compX = $to2dX($pos['x']);
                                        $compY = $to2dY($pos['y']);
                                        $compW = $pos['w'] * $frontScale;
                                        $compH = $pos['h'] * $frontScale;
                                        $type = $pos['type'] ?? 'rail';
                                        $label = $pos['label'] ?? '';
                                    @endphp

                                    @if($type === 'drawer')
                                        <rect x="{{ $compX }}" y="{{ $compY }}" width="{{ $compW }}" height="{{ $compH }}"
                                              fill="#e5d9c8" stroke="#8b5a2b" stroke-width="0.8"/>
                                        <text x="{{ $compX + $compW/2 }}" y="{{ $compY + $compH/2 + 2 }}"
                                              text-anchor="middle" style="font-size: 5px; fill: #5a4a3a; font-weight: 600;">{{ $label }}</text>
                                    @elseif($type === 'u_drawer')
                                        <rect x="{{ $compX }}" y="{{ $compY }}" width="{{ $compW }}" height="{{ $compH }}"
                                              fill="#d4c4b0" stroke="#8b5a2b" stroke-width="0.8"/>
                                        <!-- U-shape cutout representation -->
                                        <rect x="{{ $compX + $compW * 0.25 }}" y="{{ $compY }}"
                                              width="{{ $compW * 0.5 }}" height="{{ $compH * 0.6 }}"
                                              fill="#f5f5f5" stroke="#999" stroke-width="0.5" stroke-dasharray="2,1"/>
                                        <text x="{{ $compX + $compW/2 }}" y="{{ $compY + $compH * 0.75 }}"
                                              text-anchor="middle" style="font-size: 4px; fill: #5a4a3a; font-weight: 600;">U-SHAPED DRAWER</text>
                                    @elseif($type === 'false_front')
                                        <rect x="{{ $compX }}" y="{{ $compY }}" width="{{ $compW }}" height="{{ $compH }}"
                                              fill="#b8a082" stroke="#8b5a2b" stroke-width="0.8"/>
                                        <text x="{{ $compX + $compW/2 }}" y="{{ $compY + $compH/2 + 2 }}"
                                              text-anchor="middle" style="font-size: 4px; fill: #5a4a3a; font-weight: 600;">FALSE FRONT</text>
                                    @elseif($type === 'rail')
                                        <rect x="{{ $compX }}" y="{{ $compY }}" width="{{ $compW }}" height="{{ $compH }}"
                                              fill="#c9a882" stroke="#8b5a2b" stroke-width="0.5"/>
                                    @endif

                                    <!-- Height dimension on right side for major components -->
                                    @if($type !== 'rail' && $pos['h'] > 2)
                                        <line x1="{{ $compX + $compW + 3 }}" y1="{{ $compY }}"
                                              x2="{{ $compX + $compW + 3 }}" y2="{{ $compY + $compH }}"
                                              stroke="#666" stroke-width="0.3"/>
                                        <text x="{{ $compX + $compW + 6 }}" y="{{ $compY + $compH/2 + 2 }}"
                                              style="font-size: 4px; fill: #666; font-family: 'JetBrains Mono';">{{ $formatInches($pos['h']) }}</text>
                                    @endif
                                @endif
                            @endforeach

                            <!-- DIMENSION LINES -->
                            <!-- Overall WIDTH (top) -->
                            <line x1="{{ $margin }}" y1="15" x2="{{ $margin + $fSvgW }}" y2="15" stroke="#3b82f6" stroke-width="0.5"/>
                            <line x1="{{ $margin }}" y1="11" x2="{{ $margin }}" y2="19" stroke="#3b82f6" stroke-width="0.5"/>
                            <line x1="{{ $margin + $fSvgW }}" y1="11" x2="{{ $margin + $fSvgW }}" y2="19" stroke="#3b82f6" stroke-width="0.5"/>
                            <text x="{{ $margin + $fSvgW/2 }}" y="10" text-anchor="middle" style="font-size: 6px; font-family: 'JetBrains Mono'; fill: #3b82f6; font-weight: 600;">{{ $formatInches($cabW) }}</text>

                            <!-- Overall HEIGHT (left) -->
                            <line x1="26" y1="{{ $toSvgY($cabH) }}" x2="26" y2="{{ $floorY }}" stroke="#3b82f6" stroke-width="0.5"/>
                            <line x1="22" y1="{{ $toSvgY($cabH) }}" x2="30" y2="{{ $toSvgY($cabH) }}" stroke="#3b82f6" stroke-width="0.5"/>
                            <line x1="22" y1="{{ $floorY }}" x2="30" y2="{{ $floorY }}" stroke="#3b82f6" stroke-width="0.5"/>
                            <text x="12" y="{{ $toSvgY($cabH/2) }}" text-anchor="middle" transform="rotate(-90, 12, {{ $toSvgY($cabH/2) }})" style="font-size: 6px; font-family: 'JetBrains Mono'; fill: #3b82f6; font-weight: 600;">{{ $formatInches($cabH) }}</text>

                            <!-- Opening width (bottom) -->
                            <line x1="{{ $openingX }}" y1="{{ $floorY + 8 }}" x2="{{ $openingX + $openingW }}" y2="{{ $floorY + 8 }}" stroke="#666" stroke-width="0.3"/>
                            <text x="{{ $openingX + $openingW/2 }}" y="{{ $floorY + 14 }}" text-anchor="middle" style="font-size: 5px; font-family: 'JetBrains Mono'; fill: #666;">{{ $formatInches($ffOpenW) }}</text>

                            <!-- Toe kick dimension -->
                            <text x="{{ $margin + $fSvgW/2 }}" y="{{ $toSvgY($toeKick/2) + 2 }}"
                                  text-anchor="middle" style="font-size: 4px; fill: #999;">{{ $formatInches($toeKick) }}</text>
                        </svg>
                    </div>
                </div>

                <!-- Side Section - Matches CAD-side-section.png -->
                <div class="section" style="flex: 1;">
                    <div class="section-header">Side Section</div>
                    <div class="section-body" style="display: flex; align-items: center; justify-content: center; padding: 4px;">
                        @php
                            $cabD = $audit['input_specs']['depth'];   // 18.75"
                            $insideD = $audit['gates']['gate_1_cabinet_box']['outputs']['inside_depth']; // 18"
                            $sideThick = $audit['input_specs']['side_thickness'] ?? 0.75;
                            $backThick = 0.75;
                            $stretcherD = 3;
                            $slideLen = $audit['input_specs']['drawer_slide_length'] ?? 18;

                            // Scale
                            $sideScale = min(180 / $cabD, 220 / $cabH);
                            $sSvgW = $cabD * $sideScale;
                            $sSvgH = $cabH * $sideScale;
                            $sMargin = 30;

                            // Component positions from CAD
                            $ffFaceH = $hasFalseFront ? ($audit['input_specs']['false_fronts'][0]['face_height'] ?? 7) : 0;
                            $ffBackingH = $hasFalseFront ? ($audit['input_specs']['false_fronts'][0]['backing_height'] ?? 3) : 0;
                        @endphp
                        <svg viewBox="0 0 {{ $sSvgW + 60 }} {{ $sSvgH + 50 }}" style="width: 100%; max-width: 200px; height: auto;">
                            <!-- Cabinet side panel (full outline) -->
                            <rect x="{{ $sMargin }}" y="20" width="{{ $sSvgW }}" height="{{ $boxH * $sideScale }}"
                                  fill="#f8f4ef" stroke="#8b5a2b" stroke-width="1"/>

                            <!-- Toe kick (dark recessed area) -->
                            <rect x="{{ $sMargin }}" y="{{ 20 + $boxH * $sideScale }}" width="{{ $sSvgW }}" height="{{ $toeKick * $sideScale }}"
                                  fill="#3d3d3d" stroke="#333" stroke-width="0.5"/>

                            <!-- Back panel (3/4" thick at rear) -->
                            <rect x="{{ $sMargin + $sSvgW - ($backThick * $sideScale) }}" y="20"
                                  width="{{ $backThick * $sideScale }}" height="{{ $boxH * $sideScale }}"
                                  fill="#c9a882" stroke="#8b5a2b" stroke-width="0.5"/>

                            @php $currentSideY = 20; @endphp

                            <!-- FALSE FRONT SECTION (top 7" area) -->
                            @if($hasFalseFront)
                                <!-- False front face (shown as front panel) -->
                                <rect x="{{ $sMargin }}" y="{{ $currentSideY }}"
                                      width="{{ 2 * $sideScale }}" height="{{ $ffFaceH * $sideScale }}"
                                      fill="#b8a082" stroke="#8b5a2b" stroke-width="0.5"/>

                                <!-- False front BACKING - laid FLAT (horizontal stretcher) - ORANGE for visibility -->
                                <rect x="{{ $sMargin }}" y="{{ $currentSideY + ($ffFaceH * $sideScale) - ($backThick * $sideScale) }}"
                                      width="{{ $ffBackingH * $sideScale }}" height="{{ $backThick * $sideScale }}"
                                      fill="#e67e22" stroke="#d35400" stroke-width="0.8"/>
                                <text x="{{ $sMargin + ($ffBackingH * $sideScale) + 2 }}" y="{{ $currentSideY + ($ffFaceH * $sideScale) - 2 }}"
                                      style="font-size: 4px; fill: #d35400; font-weight: 600;">BACKING</text>

                                <!-- 7" dimension line for false front -->
                                <line x1="{{ $sMargin + $sSvgW + 5 }}" y1="{{ $currentSideY }}" x2="{{ $sMargin + $sSvgW + 5 }}" y2="{{ $currentSideY + ($ffFaceH * $sideScale) }}" stroke="#666" stroke-width="0.3"/>
                                <text x="{{ $sMargin + $sSvgW + 8 }}" y="{{ $currentSideY + ($ffFaceH * $sideScale / 2) + 2 }}"
                                      style="font-size: 5px; fill: #666; font-family: 'JetBrains Mono';">{{ $formatInches($ffFaceH) }}</text>

                                @php $currentSideY += $ffFaceH * $sideScale; @endphp
                            @endif

                            <!-- UPPER DRAWER OPENING (17-1/8") -->
                            @if(count($drawerHeights) > 0)
                                @php $upperDrawerH = $drawerHeights[0] ?? 17.125; @endphp
                                <!-- Opening area (lighter) -->
                                <rect x="{{ $sMargin + ($sideThick * $sideScale) }}" y="{{ $currentSideY }}"
                                      width="{{ ($insideD - $backThick) * $sideScale }}" height="{{ $upperDrawerH * $sideScale }}"
                                      fill="#fafafa" stroke="#ccc" stroke-width="0.3"/>

                                <!-- Drawer box outline (inside opening) -->
                                <rect x="{{ $sMargin + ($sideThick * $sideScale) + 2 }}" y="{{ $currentSideY + 2 }}"
                                      width="{{ ($slideLen - 1) * $sideScale }}" height="{{ ($upperDrawerH - 1) * $sideScale }}"
                                      fill="none" stroke="#8b5a2b" stroke-width="0.5" stroke-dasharray="2,1"/>

                                <!-- 17-1/8" dimension -->
                                <line x1="{{ $sMargin + $sSvgW + 5 }}" y1="{{ $currentSideY }}" x2="{{ $sMargin + $sSvgW + 5 }}" y2="{{ $currentSideY + ($upperDrawerH * $sideScale) }}" stroke="#666" stroke-width="0.3"/>
                                <text x="{{ $sMargin + $sSvgW + 8 }}" y="{{ $currentSideY + ($upperDrawerH * $sideScale / 2) + 2 }}"
                                      style="font-size: 5px; fill: #666; font-family: 'JetBrains Mono';">{{ $formatInches($upperDrawerH) }}</text>

                                @php $currentSideY += $upperDrawerH * $sideScale; @endphp
                            @endif

                            <!-- MID STRETCHER (between drawers) - highlighted red like CAD -->
                            @if(count($drawerHeights) > 1)
                                <rect x="{{ $sMargin }}" y="{{ $currentSideY }}"
                                      width="{{ $stretcherD * $sideScale }}" height="{{ 0.75 * $sideScale }}"
                                      fill="#e74c3c" stroke="#c0392b" stroke-width="0.5"/>
                                <line x1="{{ $sMargin }}" y1="{{ $currentSideY + (0.75 * $sideScale / 2) }}" x2="{{ $sMargin + $sSvgW - ($backThick * $sideScale) }}" y2="{{ $currentSideY + (0.75 * $sideScale / 2) }}"
                                      stroke="#e74c3c" stroke-width="0.5"/>
                                <text x="{{ $sMargin + ($sSvgW / 2) }}" y="{{ $currentSideY + 3 }}"
                                      text-anchor="middle" style="font-size: 4px; fill: #c0392b; font-weight: 600;">{{ $formatInches($slideLen) }}</text>
                            @endif

                            <!-- LOWER DRAWER AREA -->
                            @if(count($drawerHeights) > 1)
                                @php
                                    $lowerDrawerH = $drawerHeights[1] ?? 11;
                                    $lowerY = $currentSideY + (0.75 * $sideScale);
                                @endphp
                                <!-- Opening area -->
                                <rect x="{{ $sMargin + ($sideThick * $sideScale) }}" y="{{ $lowerY }}"
                                      width="{{ ($insideD - $backThick) * $sideScale }}" height="{{ $lowerDrawerH * $sideScale }}"
                                      fill="#fafafa" stroke="#ccc" stroke-width="0.3"/>

                                <!-- Drawer box outline -->
                                <rect x="{{ $sMargin + ($sideThick * $sideScale) + 2 }}" y="{{ $lowerY + 2 }}"
                                      width="{{ ($slideLen - 1) * $sideScale }}" height="{{ ($lowerDrawerH - 1) * $sideScale }}"
                                      fill="none" stroke="#8b5a2b" stroke-width="0.5" stroke-dasharray="2,1"/>
                            @endif

                            <!-- DEPTH dimension (top) -->
                            <line x1="{{ $sMargin }}" y1="12" x2="{{ $sMargin + $sSvgW }}" y2="12" stroke="#3b82f6" stroke-width="0.5"/>
                            <line x1="{{ $sMargin }}" y1="8" x2="{{ $sMargin }}" y2="16" stroke="#3b82f6" stroke-width="0.5"/>
                            <line x1="{{ $sMargin + $sSvgW }}" y1="8" x2="{{ $sMargin + $sSvgW }}" y2="16" stroke="#3b82f6" stroke-width="0.5"/>
                            <text x="{{ $sMargin + $sSvgW/2 }}" y="8" text-anchor="middle" style="font-size: 6px; font-family: 'JetBrains Mono'; fill: #3b82f6; font-weight: 600;">{{ $formatInches($cabD) }}</text>

                            <!-- HEIGHT dimension (left) -->
                            <line x1="22" y1="20" x2="22" y2="{{ 20 + $sSvgH }}" stroke="#3b82f6" stroke-width="0.5"/>
                            <line x1="18" y1="20" x2="26" y2="20" stroke="#3b82f6" stroke-width="0.5"/>
                            <line x1="18" y1="{{ 20 + $sSvgH }}" x2="26" y2="{{ 20 + $sSvgH }}" stroke="#3b82f6" stroke-width="0.5"/>
                            <text x="10" y="{{ 20 + $sSvgH/2 }}" text-anchor="middle" transform="rotate(-90, 10, {{ 20 + $sSvgH/2 }})" style="font-size: 6px; font-family: 'JetBrains Mono'; fill: #3b82f6; font-weight: 600;">{{ $formatInches($boxH) }}</text>

                            <!-- Box height label (right side) -->
                            <text x="{{ $sMargin + $sSvgW + 18 }}" y="{{ 20 + ($boxH * $sideScale / 2) }}"
                                  text-anchor="middle" transform="rotate(90, {{ $sMargin + $sSvgW + 18 }}, {{ 20 + ($boxH * $sideScale / 2) }})"
                                  style="font-size: 5px; fill: #666;">Box {{ $formatInches($boxH) }}</text>
                        </svg>
                    </div>
                </div>

                <!-- Blum Specs (compact) -->
                <div class="blum-box" style="padding: 4px;">
                    <div class="blum-title" style="font-size: 6px;">Blum TANDEM 563H</div>
                    <div class="blum-grid" style="font-size: 6px; gap: 2px;">
                        <div class="blum-item">
                            <span class="blum-label">Slide:</span>
                            <span class="blum-value">{{ $formatInches($audit['input_specs']['drawer_slide_length']) }}</span>
                        </div>
                        <div class="blum-item">
                            <span class="blum-label">Runner:</span>
                            <span class="blum-value">37mm</span>
                        </div>
                        <div class="blum-item">
                            <span class="blum-label">Lock:</span>
                            <span class="blum-value">6×10mm</span>
                        </div>
                        <div class="blum-item">
                            <span class="blum-label">Dado:</span>
                            <span class="blum-value">1/4"</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CUT LIST COLUMN -->
            <div style="display: flex; flex-direction: column; gap: 8px; overflow: hidden;">
                <div class="section" style="flex: 1; display: flex; flex-direction: column;">
                    <div class="section-header">
                        Cut List
                        @php
                            $totalPieces = 0;
                            foreach ($audit['cut_list'] as $section => $data) {
                                if ($section === 'machining_operations') continue;
                                foreach ($data['pieces'] ?? [] as $piece) {
                                    $totalPieces += $piece['qty'];
                                }
                            }
                        @endphp
                        <span style="float: right; background: #fff; color: #1c1917; padding: 1px 8px; border-radius: 10px; font-size: 9px;">{{ $totalPieces }} pcs</span>
                    </div>
                    <div class="section-body" style="flex: 1; overflow-y: auto;">
                        <table class="cut-table">
                            <thead>
                                <tr>
                                    <th class="check-col">&#9744;</th>
                                    <th style="width: 30px;">Qty</th>
                                    <th>Part</th>
                                    <th style="width: 70px;">Width</th>
                                    <th style="width: 70px;">Length</th>
                                    <th style="width: 40px;">T</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($audit['cut_list'] as $section => $data)
                                    @if($section === 'machining_operations') @continue @endif
                                    <tr class="material-row">
                                        <td colspan="6">{{ strtoupper(str_replace('_', ' ', $section)) }}</td>
                                    </tr>
                                    @foreach($data['pieces'] ?? [] as $piece)
                                    <tr>
                                        <td class="check-col"><div class="check-box"></div></td>
                                        <td><span class="qty-badge">{{ $piece['qty'] }}</span></td>
                                        <td>{{ $piece['part'] }}</td>
                                        <td class="mono">{{ $piece['width_formatted'] ?? $formatInches($piece['width']) }}</td>
                                        <td class="mono">{{ $piece['length_formatted'] ?? $formatInches($piece['length']) }}</td>
                                        <td class="mono">{{ $piece['thickness'] }}"</td>
                                    </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Verification -->
                <div class="section">
                    <div class="section-header">Verification</div>
                    <div class="section-body">
                        <div class="verify-grid">
                            <div class="verify-item"><div class="check-box"></div> All pieces cut</div>
                            <div class="verify-item"><div class="check-box"></div> Dados cut</div>
                            <div class="verify-item"><div class="check-box"></div> Runners @ 37mm</div>
                            <div class="verify-item"><div class="check-box"></div> Edge banded</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Machining + Assembly + Nesting -->
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <!-- Machining Operations -->
                <div class="section">
                    <div class="section-header">Machining Operations</div>
                    <div class="section-body">
                        <div class="mach-list">
                            <div class="mach-item">
                                <div class="check-box"></div>
                                <div>
                                    <div class="mach-text">Drawer Dados</div>
                                    <div class="mach-detail">1/4" × 1/4", 1/2" from bottom</div>
                                </div>
                            </div>
                            <div class="mach-item">
                                <div class="check-box"></div>
                                <div>
                                    <div class="mach-text">Runner Mounting</div>
                                    <div class="mach-detail">37mm (1-15/32") from bottom, 3mm setback</div>
                                </div>
                            </div>
                            <div class="mach-item">
                                <div class="check-box"></div>
                                <div>
                                    <div class="mach-text">Locking Device Bore</div>
                                    <div class="mach-detail">6mm × 10mm deep, 75° angle</div>
                                </div>
                            </div>
                            <div class="mach-item">
                                <div class="check-box"></div>
                                <div>
                                    <div class="mach-text">Rear Hook Bore</div>
                                    <div class="mach-detail">7mm up, 11mm in from corner</div>
                                </div>
                            </div>
                            <div class="mach-item">
                                <div class="check-box"></div>
                                <div>
                                    <div class="mach-text">Pocket Holes</div>
                                    <div class="mach-detail">FF rail ends, 3/4" setting</div>
                                </div>
                            </div>
                            <div class="mach-item">
                                <div class="check-box"></div>
                                <div>
                                    <div class="mach-text">Edge Banding</div>
                                    <div class="mach-detail">Front edges of sides & shelves</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assembly Sequence -->
                <div class="section">
                    <div class="section-header">Assembly Sequence</div>
                    <div class="section-body">
                        <div class="assembly-steps">
                            <div class="assembly-step"><div class="step-num">1</div><div class="step-text">Cut pieces</div></div>
                            <div class="assembly-step"><div class="step-num">2</div><div class="step-text">Cabinet box</div></div>
                            <div class="assembly-step"><div class="step-num">3</div><div class="step-text">Stretchers</div></div>
                            <div class="assembly-step"><div class="step-num">4</div><div class="step-text">Drawer boxes</div></div>
                            <div class="assembly-step"><div class="step-num">5</div><div class="step-text">Install slides</div></div>
                            <div class="assembly-step"><div class="step-num">6</div><div class="step-text">Face frame</div></div>
                        </div>
                    </div>
                </div>

                <!-- Nesting Preview -->
                @if(isset($nesting) && !empty($nesting))
                <div class="section">
                    <div class="section-header">Sheet Nesting</div>
                    <div class="section-body">
                        <div class="nesting-grid">
                            @foreach($nesting as $thickness => $data)
                                @foreach($data['sheets'] as $sheetIndex => $sheet)
                                <div class="nesting-card">
                                    <div class="nesting-header">
                                        <span>{{ $thickness }} #{{ $sheetIndex + 1 }}</span>
                                        <span class="nesting-eff">{{ number_format($data['stats']['efficiency_percent'], 0) }}%</span>
                                    </div>
                                    @php
                                        $scale = 1.2;
                                        $svgW = $sheet['width'] * $scale;
                                        $svgH = $sheet['height'] * $scale;
                                        $colors = ['#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c'];
                                    @endphp
                                    <svg viewBox="0 0 {{ $svgW }} {{ $svgH }}" style="width: 100%; max-width: 130px; height: auto; border: 1px solid #ccc; background: #f5f5f5;">
                                        @foreach($sheet['placements'] as $pIndex => $placement)
                                        @php
                                            $px = $placement['x'] * $scale;
                                            $py = $placement['y'] * $scale;
                                            $pw = $placement['width'] * $scale;
                                            $ph = $placement['height'] * $scale;
                                        @endphp
                                        <rect x="{{ $px }}" y="{{ $py }}" width="{{ $pw }}" height="{{ $ph }}"
                                              fill="{{ $colors[$pIndex % count($colors)] }}" fill-opacity="0.8" stroke="#333" stroke-width="0.2"/>
                                        @endforeach
                                    </svg>
                                </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Material Summary -->
                <div class="section">
                    <div class="section-header">Material Summary</div>
                    <div class="section-body">
                        @php
                            $ply34SqFt = 0; $ply12SqFt = 0; $ply14SqFt = 0;
                            foreach ($audit['cut_list'] as $section => $data) {
                                if ($section === 'machining_operations') continue;
                                foreach ($data['pieces'] ?? [] as $piece) {
                                    $sqFt = ($piece['width'] * $piece['length'] * $piece['qty']) / 144;
                                    $t = $piece['thickness'] ?? 0.75;
                                    if ($t == 0.75) $ply34SqFt += $sqFt;
                                    elseif ($t == 0.5) $ply12SqFt += $sqFt;
                                    elseif ($t == 0.25) $ply14SqFt += $sqFt;
                                }
                            }
                        @endphp
                        <div class="material-summary">
                            <div class="material-item">
                                <span>3/4" Ply</span>
                                <span class="mono">{{ number_format($ply34SqFt, 1) }} sf</span>
                            </div>
                            <div class="material-item">
                                <span>1/2" Ply</span>
                                <span class="mono">{{ number_format($ply12SqFt, 1) }} sf</span>
                            </div>
                            <div class="material-item">
                                <span>1/4" Ply</span>
                                <span class="mono">{{ number_format($ply14SqFt, 1) }} sf</span>
                            </div>
                            <div class="material-item">
                                <span>Slides</span>
                                <span class="mono">{{ count($audit['input_specs']['drawer_heights']) }} pr</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Signature -->
                <div class="sig-box">
                    <div class="sig-field">
                        <div class="sig-line"></div>
                        <div class="sig-label">Approved By</div>
                    </div>
                    <div class="sig-field">
                        <div class="sig-line"></div>
                        <div class="sig-label">Date</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Bar -->
        <div class="footer-bar">
            <div class="footer-notes">
                <span>TCS Woodwork • The Carpenter's Son</span>
                <span>Gates of Construction Audit</span>
                <span>TCS Standards (Bryan Patton, Jan 2025)</span>
            </div>
            <div>Blum TANDEM 563H INST-TDM563H-563</div>
            <div>{{ $audit['summary']['status'] }} • {{ now()->format('m/d/Y') }}</div>
        </div>
    </div>

    <!-- ==================== SHEET 2: SHOP DRAWINGS WITH THUMBNAILS ==================== -->
    <div class="sheet">
        <!-- Title Block -->
        <div class="title-block">
            <div class="title-left">
                <div class="logo-cell">TCS</div>
                <div class="title-cell">
                    <div class="title-label">Project</div>
                    <div class="title-value">{{ $audit['project_name'] ?? 'Cabinet Specification' }}</div>
                </div>
                <div class="title-cell">
                    <div class="title-label">Cabinet Code</div>
                    <div class="title-value mono">{{ $audit['cabinet_code'] ?? 'BTH1-B1-C1' }}</div>
                </div>
                <div class="title-cell">
                    <div class="title-label">Sheet Title</div>
                    <div class="title-value">SHOP DRAWINGS</div>
                </div>
            </div>
            <div class="title-right">
                <div class="status-cell">
                    <span class="status-badge status-pass">CNC</span>
                </div>
                <div class="sheet-info">
                    <div class="sheet-label">Sheet</div>
                    <div class="sheet-num">2/3</div>
                </div>
            </div>
        </div>

        <!-- Shop Drawings Content -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; height: 100%;">
            <!-- Left: Cabinet Box & Face Frame -->
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <!-- Cabinet Box Drawing -->
                <div class="section" style="flex: 1;">
                    <div class="section-header">Cabinet Box - Front Elevation</div>
                    <div class="section-body" style="display: flex; align-items: center; justify-content: center; height: calc(100% - 24px);">
                        @php
                            $boxW = $audit['gates']['gate_1_cabinet_box']['outputs']['inside_width'];
                            $boxH = $audit['gates']['gate_1_cabinet_box']['outputs']['box_height'];
                            $scale = min(280 / $boxW, 200 / $boxH);
                            $svgW = $boxW * $scale;
                            $svgH = $boxH * $scale;
                        @endphp
                        <div style="text-align: center;">
                            <svg viewBox="0 0 {{ $svgW + 60 }} {{ $svgH + 60 }}" style="width: 100%; max-width: 350px; height: auto;">
                                <!-- Cabinet outline -->
                                <rect x="30" y="30" width="{{ $svgW }}" height="{{ $svgH }}" fill="#fafafa" stroke="#1c1917" stroke-width="2"/>

                                <!-- Dimension lines -->
                                <!-- Width -->
                                <line x1="30" y1="20" x2="{{ $svgW + 30 }}" y2="20" stroke="#666" stroke-width="0.5"/>
                                <line x1="30" y1="15" x2="30" y2="25" stroke="#666" stroke-width="0.5"/>
                                <line x1="{{ $svgW + 30 }}" y1="15" x2="{{ $svgW + 30 }}" y2="25" stroke="#666" stroke-width="0.5"/>
                                <text x="{{ ($svgW / 2) + 30 }}" y="12" text-anchor="middle" style="font-size: 8px; font-family: 'JetBrains Mono', monospace; font-weight: 600;">{{ $formatInches($boxW) }}</text>

                                <!-- Height -->
                                <line x1="20" y1="30" x2="20" y2="{{ $svgH + 30 }}" stroke="#666" stroke-width="0.5"/>
                                <line x1="15" y1="30" x2="25" y2="30" stroke="#666" stroke-width="0.5"/>
                                <line x1="15" y1="{{ $svgH + 30 }}" x2="25" y2="{{ $svgH + 30 }}" stroke="#666" stroke-width="0.5"/>
                                <text x="10" y="{{ ($svgH / 2) + 30 }}" text-anchor="middle" transform="rotate(-90, 10, {{ ($svgH / 2) + 30 }})" style="font-size: 8px; font-family: 'JetBrains Mono', monospace; font-weight: 600;">{{ $formatInches($boxH) }}</text>

                                <!-- Stretchers -->
                                <rect x="30" y="30" width="{{ $svgW }}" height="{{ 3 * $scale }}" fill="#d4a574" stroke="#8b5a2b" stroke-width="0.5"/>
                                <rect x="30" y="{{ $svgH + 30 - (3 * $scale) }}" width="{{ $svgW }}" height="{{ 3 * $scale }}" fill="#d4a574" stroke="#8b5a2b" stroke-width="0.5"/>

                                <!-- Label -->
                                <text x="{{ ($svgW / 2) + 30 }}" y="{{ $svgH + 50 }}" text-anchor="middle" style="font-size: 7px; fill: #666;">Inside Width × Box Height</text>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Face Frame Drawing -->
                <div class="section" style="flex: 1;">
                    <div class="section-header">Face Frame - Front View</div>
                    <div class="section-body" style="display: flex; align-items: center; justify-content: center; height: calc(100% - 24px);">
                        @php
                            $ffOpenW = $audit['gates']['gate_2_face_frame_opening']['outputs']['opening_width'];
                            $ffOpenH = $audit['gates']['gate_2_face_frame_opening']['outputs']['opening_height'];
                            $stileW = $audit['input_specs']['face_frame_stile'];
                            $railH = $audit['input_specs']['face_frame_rail'];
                            $ffTotalW = $ffOpenW + (2 * $stileW);
                            $ffTotalH = $ffOpenH + (2 * $railH);
                            $ffScale = min(280 / $ffTotalW, 180 / $ffTotalH);
                            $ffSvgW = $ffTotalW * $ffScale;
                            $ffSvgH = $ffTotalH * $ffScale;
                        @endphp
                        <div style="text-align: center;">
                            <svg viewBox="0 0 {{ $ffSvgW + 80 }} {{ $ffSvgH + 60 }}" style="width: 100%; max-width: 350px; height: auto;">
                                <!-- Outer frame -->
                                <rect x="40" y="30" width="{{ $ffSvgW }}" height="{{ $ffSvgH }}" fill="none" stroke="#1c1917" stroke-width="2"/>

                                <!-- Left stile -->
                                <rect x="40" y="30" width="{{ $stileW * $ffScale }}" height="{{ $ffSvgH }}" fill="#e5d4c0" stroke="#8b5a2b" stroke-width="1"/>

                                <!-- Right stile -->
                                <rect x="{{ 40 + $ffSvgW - ($stileW * $ffScale) }}" y="30" width="{{ $stileW * $ffScale }}" height="{{ $ffSvgH }}" fill="#e5d4c0" stroke="#8b5a2b" stroke-width="1"/>

                                <!-- Top rail -->
                                <rect x="{{ 40 + ($stileW * $ffScale) }}" y="30" width="{{ $ffOpenW * $ffScale }}" height="{{ $railH * $ffScale }}" fill="#d4c4b0" stroke="#8b5a2b" stroke-width="1"/>

                                <!-- Bottom rail -->
                                <rect x="{{ 40 + ($stileW * $ffScale) }}" y="{{ 30 + $ffSvgH - ($railH * $ffScale) }}" width="{{ $ffOpenW * $ffScale }}" height="{{ $railH * $ffScale }}" fill="#d4c4b0" stroke="#8b5a2b" stroke-width="1"/>

                                <!-- Opening -->
                                <rect x="{{ 40 + ($stileW * $ffScale) }}" y="{{ 30 + ($railH * $ffScale) }}" width="{{ $ffOpenW * $ffScale }}" height="{{ $ffOpenH * $ffScale }}" fill="#f5f5f5" stroke="#ccc" stroke-width="0.5" stroke-dasharray="3,2"/>

                                <!-- Dimension: Opening Width -->
                                <line x1="{{ 40 + ($stileW * $ffScale) }}" y1="{{ $ffSvgH + 45 }}" x2="{{ 40 + ($stileW * $ffScale) + ($ffOpenW * $ffScale) }}" y2="{{ $ffSvgH + 45 }}" stroke="#3b82f6" stroke-width="0.5"/>
                                <text x="{{ 40 + ($stileW * $ffScale) + ($ffOpenW * $ffScale / 2) }}" y="{{ $ffSvgH + 55 }}" text-anchor="middle" style="font-size: 7px; font-family: 'JetBrains Mono', monospace; fill: #3b82f6;">{{ $formatInches($ffOpenW) }} open</text>

                                <!-- Dimension: Stile -->
                                <text x="{{ 40 + ($stileW * $ffScale / 2) }}" y="{{ 30 + $ffSvgH / 2 }}" text-anchor="middle" transform="rotate(-90, {{ 40 + ($stileW * $ffScale / 2) }}, {{ 30 + $ffSvgH / 2 }})" style="font-size: 6px; font-family: 'JetBrains Mono', monospace;">{{ $formatInches($stileW) }}</text>

                                <!-- Dimension: Rail -->
                                <text x="{{ 40 + ($stileW * $ffScale) + ($ffOpenW * $ffScale / 2) }}" y="{{ 30 + ($railH * $ffScale / 2) + 2 }}" text-anchor="middle" style="font-size: 6px; font-family: 'JetBrains Mono', monospace;">{{ $formatInches($railH) }}</text>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Drawer Boxes -->
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @foreach($audit['gates']['gate_4_drawer_clearances']['drawer_boxes'] as $drawer)
                <div class="section" style="flex: 1;">
                    <div class="section-header">Drawer {{ $drawer['drawer_number'] }} Box - Exploded View</div>
                    <div class="section-body" style="display: flex; gap: 16px; align-items: center; padding: 10px;">
                        @php
                            $dw = $drawer['outputs']['box_width'];
                            $dh = $drawer['outputs']['box_height_shop'];
                            $dd = $drawer['outputs']['box_depth_shop'];
                            $dScale = min(140 / $dw, 80 / $dh);
                        @endphp
                        <!-- SVG Drawing -->
                        <svg viewBox="0 0 200 120" style="width: 180px; height: auto; flex-shrink: 0;">
                            <!-- Back panel -->
                            <rect x="30" y="10" width="{{ $dw * $dScale * 0.8 }}" height="{{ $dh * $dScale }}" fill="#e8dcc8" stroke="#8b5a2b" stroke-width="1" transform="skewY(-5)"/>

                            <!-- Left side -->
                            <polygon points="10,{{ 20 + $dh * $dScale }} 10,20 30,10 30,{{ 10 + $dh * $dScale }}" fill="#d4c4b0" stroke="#8b5a2b" stroke-width="1"/>

                            <!-- Right side -->
                            <polygon points="{{ 30 + $dw * $dScale * 0.8 }},{{ 10 + $dh * $dScale }} {{ 30 + $dw * $dScale * 0.8 }},10 {{ 50 + $dw * $dScale * 0.8 }},20 {{ 50 + $dw * $dScale * 0.8 }},{{ 20 + $dh * $dScale }}" fill="#d4c4b0" stroke="#8b5a2b" stroke-width="1"/>

                            <!-- Bottom -->
                            <polygon points="10,{{ 20 + $dh * $dScale }} 30,{{ 10 + $dh * $dScale }} {{ 30 + $dw * $dScale * 0.8 }},{{ 10 + $dh * $dScale }} {{ 50 + $dw * $dScale * 0.8 }},{{ 20 + $dh * $dScale }}" fill="#f5efe6" stroke="#8b5a2b" stroke-width="0.5"/>

                            <!-- Front panel (offset/exploded) -->
                            <rect x="25" y="{{ 30 + $dh * $dScale }}" width="{{ $dw * $dScale * 0.8 }}" height="{{ $dh * $dScale * 0.3 }}" fill="#c9b896" stroke="#8b5a2b" stroke-width="1"/>

                            <!-- Dado line indicator -->
                            <line x1="12" y1="{{ 18 + $dh * $dScale - 4 }}" x2="28" y2="{{ 8 + $dh * $dScale - 4 }}" stroke="#e74c3c" stroke-width="0.5" stroke-dasharray="2,1"/>
                            <text x="5" y="{{ 15 + $dh * $dScale }}" style="font-size: 5px; fill: #e74c3c;">dado</text>
                        </svg>

                        <!-- Dimensions Table -->
                        <div style="flex: 1;">
                            <table class="mini-table" style="font-size: 8px;">
                                <tr>
                                    <td style="font-weight: 600;">Box Width</td>
                                    <td class="mono" style="text-align: right;">{{ $formatInches($dw) }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600;">Box Height</td>
                                    <td class="mono" style="text-align: right;">{{ $formatInches($dh) }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600;">Box Depth</td>
                                    <td class="mono" style="text-align: right;">{{ $formatInches($dd) }}</td>
                                </tr>
                                <tr style="background: #eff6ff;">
                                    <td style="font-weight: 600; color: #1d4ed8;">Side Thickness</td>
                                    <td class="mono" style="text-align: right; color: #1d4ed8;">1/2"</td>
                                </tr>
                            </table>
                            <div style="margin-top: 6px; padding: 4px 6px; background: #fef3c7; border-radius: 3px; font-size: 7px;">
                                <strong>Dado:</strong> 1/4" × 1/4", 1/2" from bottom
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Footer Bar -->
        <div class="footer-bar">
            <div class="footer-notes">
                <span>TCS Woodwork</span>
                <span>Shop Drawings - CNC Reference</span>
            </div>
            <div>1/2" drawer sides • 1/4" drawer bottom</div>
            <div>Sheet 2 of 3</div>
        </div>
    </div>

    <!-- ==================== SHEET 3: SHEET NESTING ==================== -->
    @if(isset($nesting) && !empty($nesting))
    <div class="sheet">
        <!-- Title Block -->
        <div class="title-block">
            <div class="title-left">
                <div class="logo-cell">TCS</div>
                <div class="title-cell">
                    <div class="title-label">Project</div>
                    <div class="title-value">{{ $audit['project_name'] ?? 'Cabinet Specification' }}</div>
                </div>
                <div class="title-cell">
                    <div class="title-label">Cabinet Code</div>
                    <div class="title-value mono">{{ $audit['cabinet_code'] ?? 'BTH1-B1-C1' }}</div>
                </div>
                <div class="title-cell">
                    <div class="title-label">Sheet Title</div>
                    <div class="title-value">SHEET NESTING</div>
                </div>
            </div>
            <div class="title-right">
                <div class="status-cell">
                    @php
                        $totalSheets = 0;
                        $avgEff = 0;
                        foreach ($nesting as $data) {
                            $totalSheets += count($data['sheets']);
                            $avgEff += $data['stats']['efficiency_percent'] ?? 0;
                        }
                        $avgEff = count($nesting) > 0 ? $avgEff / count($nesting) : 0;
                    @endphp
                    <span class="status-badge {{ $avgEff >= 70 ? 'status-pass' : 'status-warning' }}">{{ number_format($avgEff, 0) }}% EFF</span>
                </div>
                <div class="sheet-info">
                    <div class="sheet-label">Sheet</div>
                    <div class="sheet-num">3/3</div>
                </div>
            </div>
        </div>

        <!-- Nesting Content -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 16px; height: 100%; overflow-y: auto;">
            @foreach($nesting as $thickness => $data)
                @foreach($data['sheets'] as $sheetIndex => $sheet)
                <div class="section">
                    <div class="section-header" style="display: flex; justify-content: space-between;">
                        <span>{{ $thickness }} Plywood - Sheet #{{ $sheetIndex + 1 }}</span>
                        <span style="background: {{ $data['stats']['efficiency_percent'] >= 70 ? '#dcfce7' : '#fef3c7' }}; color: {{ $data['stats']['efficiency_percent'] >= 70 ? '#166534' : '#92400e' }}; padding: 1px 8px; border-radius: 8px; font-size: 8px;">
                            {{ number_format($data['stats']['efficiency_percent'], 1) }}% efficiency
                        </span>
                    </div>
                    <div class="section-body" style="display: flex; gap: 16px;">
                        <!-- Large SVG -->
                        @php
                            $nestScale = min(350 / $sheet['width'], 220 / $sheet['height']);
                            $nestSvgW = $sheet['width'] * $nestScale;
                            $nestSvgH = $sheet['height'] * $nestScale;
                            $colors = ['#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c', '#e67e22', '#16a085'];
                        @endphp
                        <div style="flex-shrink: 0;">
                            <svg viewBox="0 0 {{ $nestSvgW + 40 }} {{ $nestSvgH + 40 }}" style="width: {{ min($nestSvgW + 40, 400) }}px; height: auto; border: 1px solid #ccc; background: #f9f9f9;">
                                <!-- Sheet outline -->
                                <rect x="20" y="20" width="{{ $nestSvgW }}" height="{{ $nestSvgH }}" fill="#f5f5f5" stroke="#1c1917" stroke-width="1.5"/>

                                <!-- Sheet dimensions -->
                                <text x="{{ 20 + $nestSvgW / 2 }}" y="12" text-anchor="middle" style="font-size: 8px; font-family: 'JetBrains Mono', monospace;">{{ $sheet['width'] }}" × {{ $sheet['height'] }}"</text>

                                <!-- Pieces -->
                                @foreach($sheet['placements'] as $pIndex => $placement)
                                @php
                                    $px = 20 + ($placement['x'] * $nestScale);
                                    $py = 20 + ($placement['y'] * $nestScale);
                                    $pw = $placement['width'] * $nestScale;
                                    $ph = $placement['height'] * $nestScale;
                                    $color = $colors[$pIndex % count($colors)];
                                    $fontSize = min(9, max(6, min($pw, $ph) / 8));
                                @endphp
                                <rect x="{{ $px }}" y="{{ $py }}" width="{{ $pw }}" height="{{ $ph }}"
                                      fill="{{ $color }}" fill-opacity="0.75" stroke="#333" stroke-width="0.5"/>
                                @if($pw > 30 && $ph > 15)
                                <text x="{{ $px + $pw / 2 }}" y="{{ $py + $ph / 2 - 3 }}" text-anchor="middle" style="font-size: {{ $fontSize }}px; font-weight: 600; fill: white; text-shadow: 0 0 2px rgba(0,0,0,0.5);">{{ Str::limit($placement['part'], 10) }}</text>
                                <text x="{{ $px + $pw / 2 }}" y="{{ $py + $ph / 2 + 6 }}" text-anchor="middle" style="font-size: {{ $fontSize - 1 }}px; fill: white; font-family: 'JetBrains Mono', monospace; text-shadow: 0 0 2px rgba(0,0,0,0.5);">{{ $formatInches($placement['original_width']) }}×{{ $formatInches($placement['original_height']) }}</text>
                                @endif
                                @endforeach
                            </svg>
                        </div>

                        <!-- Legend / Parts List -->
                        <div style="flex: 1; min-width: 150px;">
                            <div style="font-size: 7px; font-weight: 600; text-transform: uppercase; color: #666; margin-bottom: 6px;">Parts on this sheet</div>
                            <table class="mini-table" style="font-size: 7px;">
                                <thead>
                                    <tr>
                                        <th style="width: 12px;"></th>
                                        <th>Part</th>
                                        <th style="text-align: right;">Size</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sheet['placements'] as $pIndex => $placement)
                                    <tr>
                                        <td><div style="width: 10px; height: 10px; background: {{ $colors[$pIndex % count($colors)] }}; border-radius: 2px;"></div></td>
                                        <td>{{ $placement['part'] }}</td>
                                        <td class="mono" style="text-align: right; font-size: 7px;">{{ $formatInches($placement['original_width']) }}×{{ $formatInches($placement['original_height']) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- Stats -->
                            <div style="margin-top: 10px; padding: 6px; background: #f5f5f5; border-radius: 3px;">
                                <div style="font-size: 7px; display: flex; justify-content: space-between; margin-bottom: 3px;">
                                    <span>Used Area:</span>
                                    <span class="mono">{{ number_format($data['stats']['used_area'] ?? 0, 1) }} sq in</span>
                                </div>
                                <div style="font-size: 7px; display: flex; justify-content: space-between; margin-bottom: 3px;">
                                    <span>Waste Area:</span>
                                    <span class="mono">{{ number_format($data['stats']['waste_area'] ?? 0, 1) }} sq in</span>
                                </div>
                                <div style="font-size: 7px; display: flex; justify-content: space-between;">
                                    <span>Pieces:</span>
                                    <span class="mono">{{ count($sheet['placements']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            @endforeach
        </div>

        <!-- Summary Stats -->
        <div style="display: flex; gap: 20px; padding: 8px 12px; background: #f5f5f5; border-radius: 4px; margin-top: 8px;">
            @php
                $ply34SqFt = 0; $ply12SqFt = 0; $ply14SqFt = 0;
                foreach ($audit['cut_list'] as $section => $data) {
                    if ($section === 'machining_operations') continue;
                    foreach ($data['pieces'] ?? [] as $piece) {
                        $sqFt = ($piece['width'] * $piece['length'] * $piece['qty']) / 144;
                        $t = $piece['thickness'] ?? 0.75;
                        if ($t == 0.75) $ply34SqFt += $sqFt;
                        elseif ($t == 0.5) $ply12SqFt += $sqFt;
                        elseif ($t == 0.25) $ply14SqFt += $sqFt;
                    }
                }
            @endphp
            <div style="font-size: 8px;">
                <strong>Material Required:</strong>
            </div>
            <div style="font-size: 8px;">
                3/4" Plywood: <span class="mono">{{ number_format($ply34SqFt, 1) }} sf ({{ ceil($ply34SqFt / 32) }} sheets)</span>
            </div>
            <div style="font-size: 8px;">
                1/2" Plywood: <span class="mono">{{ number_format($ply12SqFt, 1) }} sf ({{ ceil($ply12SqFt / 32) }} sheets)</span>
            </div>
            <div style="font-size: 8px;">
                1/4" Plywood: <span class="mono">{{ number_format($ply14SqFt, 1) }} sf ({{ ceil($ply14SqFt / 32) }} sheets)</span>
            </div>
            <div style="font-size: 8px; margin-left: auto;">
                <strong>Avg Efficiency:</strong> <span class="mono {{ $avgEff >= 70 ? 'gap-ok' : 'gap-warn' }}">{{ number_format($avgEff, 1) }}%</span>
            </div>
        </div>

        <!-- Footer Bar -->
        <div class="footer-bar">
            <div class="footer-notes">
                <span>TCS Woodwork</span>
                <span>Sheet Nesting - MaxRects Bin Packing</span>
            </div>
            <div>Standard 4×8 sheets (48" × 96")</div>
            <div>Sheet 3 of 3</div>
        </div>
    </div>
    @endif
</body>
</html>
