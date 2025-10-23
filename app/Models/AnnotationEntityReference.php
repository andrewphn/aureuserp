<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AnnotationEntityReference Model
 *
 * Pivot table that allows annotations to reference multiple parent entities.
 * This enables complex relationships like:
 * - An end panel annotation referencing both a cabinet AND a cabinet_run
 * - A section view referencing multiple cabinets and locations
 * - Detail callouts that show context from multiple entities
 *
 * @property int $id
 * @property int $annotation_id
 * @property string $entity_type ('room', 'location', 'cabinet_run', 'cabinet')
 * @property int $entity_id
 * @property string $reference_type ('primary', 'secondary', 'context')
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class AnnotationEntityReference extends Model
{
    protected $table = 'annotation_entity_references';

    protected $fillable = [
        'annotation_id',
        'entity_type',
        'entity_id',
        'reference_type',
    ];

    /**
     * The annotation that references entities
     */
    public function annotation(): BelongsTo
    {
        return $this->belongsTo(PdfPageAnnotation::class, 'annotation_id');
    }

    /**
     * Get the referenced entity (polymorphic relationship)
     *
     * @return Model|null
     */
    public function getEntity(): ?Model
    {
        return match ($this->entity_type) {
            'room' => \Webkul\Project\Models\Room::find($this->entity_id),
            'location' => \Webkul\Project\Models\RoomLocation::find($this->entity_id),
            'cabinet_run' => \Webkul\Project\Models\CabinetRun::find($this->entity_id),
            'cabinet' => \Webkul\Project\Models\CabinetSpecification::find($this->entity_id),
            default => null,
        };
    }

    /**
     * Scopes
     */

    /**
     * Get references for a specific annotation
     */
    public function scopeForAnnotation($query, int $annotationId)
    {
        return $query->where('annotation_id', $annotationId);
    }

    /**
     * Get primary references only
     */
    public function scopePrimary($query)
    {
        return $query->where('reference_type', 'primary');
    }

    /**
     * Get secondary references only
     */
    public function scopeSecondary($query)
    {
        return $query->where('reference_type', 'secondary');
    }

    /**
     * Get context references only
     */
    public function scopeContext($query)
    {
        return $query->where('reference_type', 'context');
    }

    /**
     * Get references for a specific entity
     */
    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }

    /**
     * Helper Methods
     */

    /**
     * Check if this is a primary reference
     */
    public function isPrimary(): bool
    {
        return $this->reference_type === 'primary';
    }

    /**
     * Check if this is a secondary reference
     */
    public function isSecondary(): bool
    {
        return $this->reference_type === 'secondary';
    }

    /**
     * Check if this is a context reference
     */
    public function isContext(): bool
    {
        return $this->reference_type === 'context';
    }

    /**
     * Get a human-readable label for the reference type
     */
    public function getReferenceTypeLabel(): string
    {
        return match ($this->reference_type) {
            'primary' => 'Primary Entity',
            'secondary' => 'Related Entity',
            'context' => 'Context Information',
            default => 'Unknown',
        };
    }

    /**
     * Get a human-readable label for the entity type
     */
    public function getEntityTypeLabel(): string
    {
        return match ($this->entity_type) {
            'room' => 'Room',
            'location' => 'Location',
            'cabinet_run' => 'Cabinet Run',
            'cabinet' => 'Cabinet',
            default => 'Unknown Entity',
        };
    }

    /**
     * Create a batch of entity references for an annotation
     *
     * @param int $annotationId
     * @param array $references Array of ['entity_type' => string, 'entity_id' => int, 'reference_type' => string]
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function createBatch(int $annotationId, array $references)
    {
        $created = collect();

        foreach ($references as $ref) {
            $created->push(self::create([
                'annotation_id' => $annotationId,
                'entity_type' => $ref['entity_type'],
                'entity_id' => $ref['entity_id'],
                'reference_type' => $ref['reference_type'] ?? 'primary',
            ]));
        }

        return $created;
    }

    /**
     * Update references for an annotation (replaces existing)
     *
     * @param int $annotationId
     * @param array $references
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function syncForAnnotation(int $annotationId, array $references)
    {
        // Delete existing references
        self::where('annotation_id', $annotationId)->delete();

        // Create new ones
        return self::createBatch($annotationId, $references);
    }
}
