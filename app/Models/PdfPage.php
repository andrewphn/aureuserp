<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\Project;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Security\Models\User;

/**
 * Pdf Page Eloquent model
 *
 * Represents a page from a PDF document with rich classification data.
 * Pages are REFERENCES to entities, not data storage - entity data lives in entity tables.
 *
 * Design Philosophy:
 * - Polymorphic entity linking allows pages to link to ANY entity type
 * - Entity data (Room, RoomLocation, etc.) lives in its own tables
 * - This model bridges documents ↔ entities without duplicating data
 * - Supports progressive entity creation during wizard flow
 *
 * Based on architectural drawing analysis (e.g., 25 Friendship Lane):
 * - Pages can be "location detail" pages with full fabrication info
 * - Pages can show multiple locations (multi_location)
 * - Pages contain various view types (elevation, plan view, sections)
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $document_id
 * @property int|null $project_id
 * @property int|null $page_number
 * @property string|null $page_type (legacy)
 * @property string|null $primary_purpose
 * @property string|null $page_label
 * @property string|null $drawing_number
 * @property array|null $view_types
 * @property array|null $section_labels
 * @property bool $has_hardware_schedule
 * @property bool $has_material_spec
 * @property array|null $locations_documented
 * @property array|null $appliances
 * @property string|null $face_frame_material
 * @property string|null $interior_material
 * @property bool $is_location_detail
 * @property string|null $page_notes
 * @property int|null $width
 * @property int|null $height
 * @property int|null $rotation
 * @property string|null $thumbnail_path
 * @property string|null $extracted_text
 * @property array $page_metadata
 * @property string|null $linked_entity_type
 * @property int|null $linked_entity_id
 * @property array|null $additional_entity_links
 * @property string $processing_status
 * @property int|null $classified_by
 * @property \Carbon\Carbon|null $classified_at
 * @property int $annotation_count
 * @property \Carbon\Carbon|null $last_annotated_at
 * @property int|null $creator_id
 * @property-read \Illuminate\Database\Eloquent\Collection $rooms
 * @property-read \Illuminate\Database\Eloquent\Collection $annotations
 * @property-read \Illuminate\Database\Eloquent\Model|null $pdfDocument
 * @property-read \Illuminate\Database\Eloquent\Model|null $linkedEntity
 * @property-read Project|null $project
 */
class PdfPage extends Model
{
    use HasFactory, HasChatter, SoftDeletes;

    /**
     * Processing status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_CLASSIFIED = 'classified';
    public const STATUS_ENTITY_LINKED = 'entity_linked';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_COMPLETE = 'complete';

    /**
     * Entity type mapping for polymorphic relationships
     * Maps short names to fully qualified class names
     */
    public const ENTITY_TYPE_MAP = [
        'project' => Project::class,
        'room' => Room::class,
        'room_location' => RoomLocation::class,
        'cabinet_run' => CabinetRun::class,
        'cabinet' => Cabinet::class,
    ];

    protected $table = 'pdf_pages';

    protected $fillable = [
        'document_id',
        'project_id',
        'page_number',
        'page_type',
        'primary_purpose',
        'page_label',
        'drawing_number',
        'view_types',
        'section_labels',
        'has_hardware_schedule',
        'has_material_spec',
        'locations_documented',
        'appliances',
        'face_frame_material',
        'interior_material',
        'is_location_detail',
        'page_notes',
        'width',
        'height',
        'rotation',
        'thumbnail_path',
        'extracted_text',
        'page_metadata',
        // Entity linking fields
        'linked_entity_type',
        'linked_entity_id',
        'additional_entity_links',
        'processing_status',
        'classified_by',
        'classified_at',
        'annotation_count',
        'last_annotated_at',
        'creator_id',
    ];

    protected $casts = [
        'page_metadata' => 'array',
        'view_types' => 'array',
        'section_labels' => 'array',
        'locations_documented' => 'array',
        'appliances' => 'array',
        'additional_entity_links' => 'array',
        'has_hardware_schedule' => 'boolean',
        'has_material_spec' => 'boolean',
        'is_location_detail' => 'boolean',
        'classified_at' => 'datetime',
        'last_annotated_at' => 'datetime',
    ];

    protected $attributes = [
        'processing_status' => self::STATUS_PENDING,
        'annotation_count' => 0,
    ];

    /**
     * Primary purpose options for PDF pages
     * Based on actual architectural drawing packages - what users SEE
     */
    public const PRIMARY_PURPOSES = [
        'cover' => 'Cover Page',
        'floor_plan' => 'Floor Plan',
        'elevations' => 'Elevations',           // Most common - wall views with cabinets
        'countertops' => 'Countertops',         // Counter specs, edge details
        'reference' => 'Reference Photos',       // Inspiration, existing conditions
        'other' => 'Other',
    ];

    /**
     * View types that can appear on a page
     */
    public const VIEW_TYPES = [
        'elevation' => 'Elevation View',
        'upper_plan' => 'Upper Cabinets Plan View',
        'lower_plan' => 'Lower Cabinets Plan View',
        'section' => 'Section/Cut View',
        'detail' => 'Detail View',
        'plan' => 'Floor Plan',
        'perspective' => 'Perspective/3D',
        'isometric' => 'Isometric View',
    ];

    /**
     * Common face frame materials
     */
    public const FACE_FRAME_MATERIALS = [
        'paint_grade_maple' => 'Paint Grade: Maple/Medex',
        'paint_grade_mdf' => 'Paint Grade: MDF',
        'white_oak' => 'White Oak',
        'flat_sawn_white_oak' => 'Flat Sawn White Oak',
        'rift_sawn_white_oak' => 'Rift Sawn White Oak',
        'walnut' => 'Walnut',
        'cherry' => 'Cherry',
        'maple' => 'Maple',
        'alder' => 'Alder',
        'other' => 'Other',
    ];

    /**
     * Common interior materials
     */
    public const INTERIOR_MATERIALS = [
        'prefinished_maple_birch' => 'Prefinished Maple/Birch',
        'prefinished_birch' => 'Prefinished Birch',
        'white_melamine' => 'White Melamine',
        'flat_sawn_white_oak' => 'Flat Sawn White Oak',
        'paint_grade' => 'Paint Grade',
        'other' => 'Other',
    ];

    /**
     * Get the PDF document this page belongs to
     */
    public function pdfDocument()
    {
        return $this->belongsTo(PdfDocument::class, 'document_id');
    }

    /**
     * Get the project this page belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Primary linked entity (polymorphic)
     * Can be: Project, Room, RoomLocation, CabinetRun, Cabinet, or any future entity
     */
    public function linkedEntity(): MorphTo
    {
        return $this->morphTo('linkedEntity', 'linked_entity_type', 'linked_entity_id');
    }

    /**
     * User who classified this page
     */
    public function classifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'classified_by');
    }

    /**
     * User who created this record
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get all rooms associated with this page (via pivot table)
     */
    public function rooms()
    {
        return $this->hasMany(PdfPageRoom::class, 'pdf_page_id');
    }

    /**
     * Get all annotations on this page
     */
    public function annotations()
    {
        return $this->hasMany(PdfPageAnnotation::class, 'pdf_page_id');
    }

    // =========================================================================
    // ENTITY LINKING METHODS
    // =========================================================================

    /**
     * Link this page to an entity
     *
     * @param Model $entity The entity to link (Room, RoomLocation, etc.)
     * @return self
     */
    public function linkToEntity(Model $entity): self
    {
        $this->linked_entity_type = get_class($entity);
        $this->linked_entity_id = $entity->getKey();
        $this->processing_status = self::STATUS_ENTITY_LINKED;
        $this->save();

        return $this;
    }

    /**
     * Add an additional entity link (for pages showing multiple entities)
     *
     * @param Model $entity
     * @return self
     */
    public function addAdditionalEntityLink(Model $entity): self
    {
        $links = $this->additional_entity_links ?? [];
        $links[] = [
            'type' => get_class($entity),
            'id' => $entity->getKey(),
        ];
        $this->additional_entity_links = $links;
        $this->save();

        return $this;
    }

    /**
     * Get all linked entities (primary + additional)
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllLinkedEntities(): \Illuminate\Support\Collection
    {
        $entities = collect();

        // Primary entity
        if ($this->linkedEntity) {
            $entities->push($this->linkedEntity);
        }

        // Additional entities
        foreach ($this->additional_entity_links ?? [] as $link) {
            $model = app($link['type'])->find($link['id']);
            if ($model) {
                $entities->push($model);
            }
        }

        return $entities;
    }

    /**
     * Check if page is linked to any entity
     */
    public function hasLinkedEntity(): bool
    {
        return $this->linked_entity_type && $this->linked_entity_id;
    }

    /**
     * Get the short entity type name
     */
    public function getEntityTypeShortNameAttribute(): ?string
    {
        if (!$this->linked_entity_type) {
            return null;
        }

        return array_search($this->linked_entity_type, self::ENTITY_TYPE_MAP) ?: class_basename($this->linked_entity_type);
    }

    /**
     * Get linked entity display name
     */
    public function getLinkedEntityNameAttribute(): ?string
    {
        if (!$this->linkedEntity) {
            return null;
        }

        return $this->linkedEntity->name ?? $this->linkedEntity->title ?? "#{$this->linked_entity_id}";
    }

    /**
     * Classify this page with a type and optionally link to entity
     *
     * @param string $type One of the PRIMARY_PURPOSES keys
     * @param string|null $label Optional label
     * @param int|null $userId User performing classification
     * @return self
     */
    public function classify(string $type, ?string $label = null, ?int $userId = null): self
    {
        $this->primary_purpose = $type;
        $this->page_label = $label;
        $this->processing_status = self::STATUS_CLASSIFIED;
        $this->classified_by = $userId ?? auth()->id();
        $this->classified_at = now();
        $this->save();

        return $this;
    }

    /**
     * Mark page as reviewed
     */
    public function markAsReviewed(): self
    {
        $this->processing_status = self::STATUS_REVIEWED;
        $this->save();

        return $this;
    }

    /**
     * Mark page as complete
     */
    public function markAsComplete(): self
    {
        $this->processing_status = self::STATUS_COMPLETE;
        $this->save();

        return $this;
    }

    /**
     * Get status badge color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->processing_status) {
            self::STATUS_PENDING => 'gray',
            self::STATUS_CLASSIFIED => 'blue',
            self::STATUS_ENTITY_LINKED => 'green',
            self::STATUS_REVIEWED => 'purple',
            self::STATUS_COMPLETE => 'success',
            default => 'gray',
        };
    }

    /**
     * Get suggested entity type based on page classification
     */
    public function getSuggestedEntityTypeAttribute(): ?string
    {
        $purpose = $this->getPrimaryPurpose();
        return match ($purpose) {
            'cover' => 'project',
            'floor_plan' => 'room',
            'elevations' => 'room_location',
            'countertops' => 'room_location',
            default => null,
        };
    }

    /**
     * Get the page type (legacy support)
     */
    public function getPageType(): ?string
    {
        return $this->page_type;
    }

    /**
     * Get the primary purpose with fallback to page_type
     */
    public function getPrimaryPurpose(): ?string
    {
        return $this->primary_purpose ?? $this->page_type;
    }

    /**
     * Get human-readable primary purpose label
     */
    public function getPrimaryPurposeLabelAttribute(): string
    {
        $purpose = $this->getPrimaryPurpose();
        return self::PRIMARY_PURPOSES[$purpose] ?? ucwords(str_replace('_', ' ', $purpose ?? 'Unknown'));
    }

    /**
     * Check if this is a cover page
     */
    public function isCoverPage(): bool
    {
        return $this->primary_purpose === 'cover' || $this->page_type === 'cover_page';
    }

    /**
     * Check if this is a floor plan page
     */
    public function isFloorPlan(): bool
    {
        return $this->primary_purpose === 'plan_view' || $this->page_type === 'floor_plan';
    }

    /**
     * Check if this is an elevation page
     */
    public function isElevation(): bool
    {
        return $this->page_type === 'elevation' ||
               (is_array($this->view_types) && in_array('elevation', $this->view_types));
    }

    /**
     * Check if this page documents specific locations
     */
    public function hasLocations(): bool
    {
        return !empty($this->locations_documented);
    }

    /**
     * Get the location names documented on this page
     */
    public function getLocationNames(): array
    {
        if (empty($this->locations_documented)) {
            return [];
        }

        return array_column($this->locations_documented, 'name');
    }

    /**
     * Get total linear feet from all locations on this page
     */
    public function getTotalLinearFeetAttribute(): float
    {
        if (empty($this->locations_documented)) {
            return 0;
        }

        return array_sum(array_column($this->locations_documented, 'linear_feet'));
    }

    /**
     * Check if page has specific view type
     */
    public function hasViewType(string $viewType): bool
    {
        return is_array($this->view_types) && in_array($viewType, $this->view_types);
    }

    /**
     * Get a formatted summary of the page
     */
    public function getSummaryAttribute(): string
    {
        $parts = [];

        if ($this->page_label) {
            $parts[] = $this->page_label;
        }

        if ($this->drawing_number) {
            $parts[] = "({$this->drawing_number})";
        }

        $locationNames = $this->getLocationNames();
        if (!empty($locationNames)) {
            $parts[] = '- ' . implode(', ', $locationNames);
        }

        if ($this->has_hardware_schedule) {
            $parts[] = '[HW]';
        }

        return !empty($parts) ? implode(' ', $parts) : "Page {$this->page_number}";
    }

    /**
     * Add a location to this page's documentation
     */
    public function addLocation(string $name, ?float $linearFeet = null, ?int $pricingTier = null): void
    {
        $locations = $this->locations_documented ?? [];

        $locations[] = array_filter([
            'name' => $name,
            'linear_feet' => $linearFeet,
            'pricing_tier' => $pricingTier,
        ], fn($v) => $v !== null);

        $this->locations_documented = $locations;
    }

    /**
     * Set view types for this page
     */
    public function setViewTypes(array $types): void
    {
        $this->view_types = array_values(array_unique($types));
    }

    /**
     * Add a view type to this page
     */
    public function addViewType(string $type): void
    {
        $types = $this->view_types ?? [];
        if (!in_array($type, $types)) {
            $types[] = $type;
            $this->view_types = $types;
        }
    }

    /**
     * Scope to get only location detail pages
     */
    public function scopeLocationDetails($query)
    {
        return $query->where(function ($q) {
            $q->where('is_location_detail', true)
              ->orWhere('primary_purpose', 'location_detail')
              ->orWhere('primary_purpose', 'multi_location');
        });
    }

    /**
     * Scope to get pages with hardware schedules
     */
    public function scopeWithHardwareSchedule($query)
    {
        return $query->where('has_hardware_schedule', true);
    }

    /**
     * Scope to get pages by primary purpose
     */
    public function scopeByPurpose($query, string $purpose)
    {
        return $query->where('primary_purpose', $purpose);
    }
}
