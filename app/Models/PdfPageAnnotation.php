<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\CabinetSpecification;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Security\Models\User;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;

class PdfPageAnnotation extends Model
{
    use SoftDeletes, HasChatter, HasLogActivity;

    protected $table = 'pdf_page_annotations';

    protected $fillable = [
        'pdf_page_id',
        'parent_annotation_id',
        'annotation_type',
        'label',
        'x',
        'y',
        'width',
        'height',
        'room_type',
        'color',
        'room_id',
        'room_location_id',
        'cabinet_run_id',
        'cabinet_specification_id',
        'visual_properties',
        'nutrient_annotation_id',
        'nutrient_data',
        'notes',
        'metadata',
        'created_by',
        'creator_id',
        // View types and multi-parent support
        'view_type',
        'view_orientation',
        'view_scale',
        'inferred_position',
        'vertical_zone',
    ];

    protected $casts = [
        'x' => 'decimal:6',
        'y' => 'decimal:6',
        'width' => 'decimal:6',
        'height' => 'decimal:6',
        'visual_properties' => 'array',
        'nutrient_data' => 'array',
        'metadata' => 'array',
        'view_scale' => 'decimal:4',
    ];

    /**
     * Attributes to log for Chatter activity tracking
     */
    protected $logAttributes = [
        'annotation_type' => 'Annotation Type',
        'label' => 'Label',
        'room_type' => 'Room Type',
        'x' => 'X Position',
        'y' => 'Y Position',
        'width' => 'Width',
        'height' => 'Height',
        'cabinet_run_id' => 'Cabinet Run',
        'room_id' => 'Room',
        'cabinet_specification_id' => 'Cabinet',
        'notes' => 'Notes',
    ];

    /**
     * Relationships
     */
    public function pdfPage(): BelongsTo
    {
        return $this->belongsTo(PdfPage::class, 'pdf_page_id');
    }

    public function parentAnnotation(): BelongsTo
    {
        return $this->belongsTo(PdfPageAnnotation::class, 'parent_annotation_id');
    }

    public function childAnnotations(): HasMany
    {
        return $this->hasMany(PdfPageAnnotation::class, 'parent_annotation_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function roomLocation(): BelongsTo
    {
        return $this->belongsTo(RoomLocation::class, 'room_location_id');
    }

    public function cabinetRun(): BelongsTo
    {
        return $this->belongsTo(CabinetRun::class, 'cabinet_run_id');
    }

    public function cabinetSpecification(): BelongsTo
    {
        return $this->belongsTo(CabinetSpecification::class, 'cabinet_specification_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Scopes
     */
    public function scopeCabinetRunAnnotations($query)
    {
        return $query->where('annotation_type', 'cabinet_run')
            ->whereNull('parent_annotation_id');
    }

    public function scopeCabinetAnnotations($query)
    {
        return $query->where('annotation_type', 'cabinet')
            ->whereNotNull('parent_annotation_id');
    }

    public function scopeByPage($query, int $pdfPageId)
    {
        return $query->where('pdf_page_id', $pdfPageId);
    }

    /**
     * Helper Methods
     */

    /**
     * Check if this is a top-level (cabinet run) annotation
     */
    public function isTopLevel(): bool
    {
        return $this->parent_annotation_id === null;
    }

    /**
     * Check if this annotation is a plan view
     */
    public function isPlanView(): bool
    {
        return $this->view_type === 'plan';
    }

    /**
     * Check if this annotation is an elevation view
     */
    public function isElevationView(): bool
    {
        return $this->view_type === 'elevation';
    }

    /**
     * Check if this annotation is a section view
     */
    public function isSectionView(): bool
    {
        return $this->view_type === 'section';
    }

    /**
     * Check if this annotation is a detail view
     */
    public function isDetailView(): bool
    {
        return $this->view_type === 'detail';
    }

    /**
     * Get all descendant annotations (recursive)
     */
    public function getAllDescendants()
    {
        return $this->childAnnotations()
            ->with('childAnnotations')
            ->get()
            ->flatMap(function ($child) {
                return collect([$child])->merge($child->getAllDescendants());
            });
    }

    /**
     * Get entity references as collection (uses existing foreign key columns)
     * Returns format compatible with form: [['entity_type' => 'room', 'entity_id' => 5, 'reference_type' => 'primary'], ...]
     */
    public function entityReferences()
    {
        $references = collect();

        // Add room reference if set
        if ($this->room_id) {
            $references->push((object)[
                'entity_type' => 'room',
                'entity_id' => $this->room_id,
                'reference_type' => 'primary',
            ]);
        }

        // Add location reference if set
        if ($this->room_location_id) {
            $references->push((object)[
                'entity_type' => 'location',
                'entity_id' => $this->room_location_id,
                'reference_type' => $this->room_id ? 'secondary' : 'primary',
            ]);
        }

        // Add cabinet run reference if set
        if ($this->cabinet_run_id) {
            $references->push((object)[
                'entity_type' => 'cabinet_run',
                'entity_id' => $this->cabinet_run_id,
                'reference_type' => 'primary',
            ]);
        }

        // Add cabinet specification reference if set
        if ($this->cabinet_specification_id) {
            $references->push((object)[
                'entity_type' => 'cabinet',
                'entity_id' => $this->cabinet_specification_id,
                'reference_type' => 'primary',
            ]);
        }

        return $references;
    }

    /**
     * Sync entity references from form data (updates foreign key columns)
     * @param array $references Format: [['entity_type' => 'room', 'entity_id' => 5], ...]
     */
    public function syncEntityReferences(array $references): void
    {
        // Clear all entity references first
        $this->room_id = null;
        $this->room_location_id = null;
        $this->cabinet_run_id = null;
        $this->cabinet_specification_id = null;

        // Set new references from input
        foreach ($references as $ref) {
            $entityType = $ref['entity_type'] ?? null;
            $entityId = $ref['entity_id'] ?? null;

            if (!$entityType || !$entityId) {
                continue;
            }

            match ($entityType) {
                'room' => $this->room_id = $entityId,
                'location' => $this->room_location_id = $entityId,
                'cabinet_run' => $this->cabinet_run_id = $entityId,
                'cabinet' => $this->cabinet_specification_id = $entityId,
                default => null,
            };
        }

        $this->save();
    }

    /**
     * Get primary entity references
     */
    public function getPrimaryReferences()
    {
        return $this->entityReferences()->filter(fn($ref) => $ref->reference_type === 'primary');
    }

    /**
     * Get secondary entity references
     */
    public function getSecondaryReferences()
    {
        return $this->entityReferences()->filter(fn($ref) => $ref->reference_type === 'secondary');
    }

    /**
     * Get context entity references
     */
    public function getContextReferences()
    {
        return $this->entityReferences()->filter(fn($ref) => $ref->reference_type === 'context');
    }

    /**
     * Add a single entity reference (updates appropriate foreign key column)
     */
    public function addEntityReference(string $entityType, int $entityId, string $referenceType = 'primary'): void
    {
        match ($entityType) {
            'room' => $this->room_id = $entityId,
            'location' => $this->room_location_id = $entityId,
            'cabinet_run' => $this->cabinet_run_id = $entityId,
            'cabinet' => $this->cabinet_specification_id = $entityId,
            default => null,
        };

        $this->save();
    }

    /**
     * Export to Nutrient Instant JSON format with custom data
     */
    public function toNutrientAnnotation(): array
    {
        return [
            'id' => $this->nutrient_annotation_id ?? 'annotation_' . $this->id,
            'type' => 'pspdfkit/shape/rectangle',
            'pageIndex' => $this->pdfPage->page_number - 1, // Nutrient uses 0-based
            'bbox' => [
                (float) $this->x,
                (float) $this->y,
                (float) $this->width,
                (float) $this->height,
            ],
            'strokeColor' => $this->visual_properties['strokeColor'] ?? '#FF0000',
            'strokeWidth' => $this->visual_properties['strokeWidth'] ?? 2,
            'opacity' => $this->visual_properties['opacity'] ?? 1,
            'customData' => [
                'db_id' => $this->id,
                'annotation_type' => $this->annotation_type,
                'label' => $this->label,
                'parent_id' => $this->parent_annotation_id,
                'cabinet_run_id' => $this->cabinet_run_id,
                'cabinet_specification_id' => $this->cabinet_specification_id,
            ],
        ];
    }

    /**
     * Create from Nutrient annotation data
     */
    public static function createFromNutrient(int $pdfPageId, array $nutrientAnnotation, ?int $creatorId = null): self
    {
        $customData = $nutrientAnnotation['customData'] ?? [];

        return self::create([
            'pdf_page_id' => $pdfPageId,
            'parent_annotation_id' => $customData['parent_id'] ?? null,
            'annotation_type' => $customData['annotation_type'] ?? 'cabinet_run',
            'label' => $customData['label'] ?? null,
            'x' => $nutrientAnnotation['bbox'][0],
            'y' => $nutrientAnnotation['bbox'][1],
            'width' => $nutrientAnnotation['bbox'][2],
            'height' => $nutrientAnnotation['bbox'][3],
            'cabinet_run_id' => $customData['cabinet_run_id'] ?? null,
            'cabinet_specification_id' => $customData['cabinet_specification_id'] ?? null,
            'visual_properties' => [
                'strokeColor' => $nutrientAnnotation['strokeColor'] ?? '#FF0000',
                'strokeWidth' => $nutrientAnnotation['strokeWidth'] ?? 2,
                'opacity' => $nutrientAnnotation['opacity'] ?? 1,
            ],
            'nutrient_annotation_id' => $nutrientAnnotation['id'] ?? null,
            'nutrient_data' => $nutrientAnnotation,
            'creator_id' => $creatorId,
        ]);
    }
}
