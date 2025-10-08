<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\CabinetSpecification;
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
        'cabinet_run_id',
        'room_id',
        'cabinet_specification_id',
        'visual_properties',
        'nutrient_annotation_id',
        'nutrient_data',
        'notes',
        'metadata',
        'created_by',
        'creator_id',
    ];

    protected $casts = [
        'x' => 'decimal:6',
        'y' => 'decimal:6',
        'width' => 'decimal:6',
        'height' => 'decimal:6',
        'visual_properties' => 'array',
        'nutrient_data' => 'array',
        'metadata' => 'array',
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
