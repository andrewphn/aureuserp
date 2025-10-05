# PRD 3: Tag Selector - Current Phase Section

## Problem
Bryan needs relevant tags for the project's current stage shown first. Production projects need production tags immediately visible.

## Solution
Always-visible section showing tags for current project stage.

## Requirements

### Stage Detection
Map project `stage_id` to tag type:
- Stage 13 (Discovery) → `phase_discovery` tags
- Stage 14 (Design) → `phase_design` tags
- Stage 15 (Sourcing) → `phase_sourcing` tags
- Stage 16 (Production) → `phase_production` tags
- Stage 17 (Delivery) → `phase_delivery` tags
- No stage → Show `priority` + `health` tags

### Section Display
- Header: "⭐ CURRENT PHASE → [Icon] [Phase Name]"
- Header color: Matches phase color
- Always expanded (no collapse)
- Position: Below search, above other sections

### Tag Count by Phase
- Discovery: 10 tags
- Design: 13 tags
- Sourcing: 24 tags
- Production: 18 tags
- Delivery: 14 tags

## Technical Details

### Blade Logic
```php
@php
    $stageId = $record->stage_id ?? $data['stage_id'] ?? null;

    $stageToType = [
        13 => 'phase_discovery',
        14 => 'phase_design',
        15 => 'phase_sourcing',
        16 => 'phase_production',
        17 => 'phase_delivery',
    ];

    $currentPhaseType = $stageToType[$stageId] ?? 'priority';
    $currentPhaseTags = $allTags->where('type', $currentPhaseType);
@endphp
```

### Visual Design
- Phase icon from emoji mapping
- Color from projects_project_stages table
- Tag pills use phase color family

## Success Criteria
- Correct tags show for each stage
- Works on create page (no stage yet)
- Works on edit page (has stage)
- Updates when stage changes
