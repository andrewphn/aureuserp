<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Change Order Line Eloquent model
 *
 * Tracks individual field changes within a change order.
 * Provides before/after audit trail for every modification.
 *
 * @property int $id
 * @property int $change_order_id
 * @property string $entity_type
 * @property int $entity_id
 * @property string $field_name
 * @property string|null $old_value
 * @property string|null $new_value
 * @property float $price_impact
 * @property array|null $bom_impact_json
 * @property bool $is_applied
 * @property \Carbon\Carbon|null $applied_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read ChangeOrder $changeOrder
 */
class ChangeOrderLine extends Model
{
    use HasFactory;

    /**
     * Table name.
     */
    protected $table = 'projects_change_order_lines';

    /**
     * Fillable attributes.
     */
    protected $fillable = [
        'change_order_id',
        'entity_type',
        'entity_id',
        'field_name',
        'old_value',
        'new_value',
        'price_impact',
        'bom_impact_json',
        'is_applied',
        'applied_at',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'price_impact' => 'decimal:2',
        'bom_impact_json' => 'array',
        'is_applied' => 'boolean',
        'applied_at' => 'datetime',
    ];

    /**
     * Get the change order.
     */
    public function changeOrder(): BelongsTo
    {
        return $this->belongsTo(ChangeOrder::class, 'change_order_id');
    }

    /**
     * Get the entity model instance.
     */
    public function getEntity(): ?Model
    {
        $modelClass = $this->resolveEntityClass();
        
        if (!$modelClass || !class_exists($modelClass)) {
            return null;
        }

        return $modelClass::find($this->entity_id);
    }

    /**
     * Resolve the entity class from the type.
     */
    protected function resolveEntityClass(): ?string
    {
        $typeMap = [
            'Cabinet' => Cabinet::class,
            'CabinetSection' => CabinetSection::class,
            'Door' => Door::class,
            'Drawer' => Drawer::class,
            'Shelf' => Shelf::class,
            'Pullout' => Pullout::class,
            'CabinetRun' => CabinetRun::class,
            'Room' => Room::class,
            'BomLine' => CabinetMaterialsBom::class,
        ];

        return $typeMap[$this->entity_type] ?? null;
    }

    /**
     * Apply this change line to the entity.
     */
    public function apply(): bool
    {
        $entity = $this->getEntity();
        
        if (!$entity) {
            return false;
        }

        $entity->{$this->field_name} = $this->new_value;
        $entity->save();

        $this->update([
            'is_applied' => true,
            'applied_at' => now(),
        ]);

        return true;
    }

    /**
     * Check if value actually changed.
     */
    public function hasChanged(): bool
    {
        return $this->old_value !== $this->new_value;
    }

    /**
     * Scope to get unapplied lines.
     */
    public function scopeUnapplied($query)
    {
        return $query->where('is_applied', false);
    }

    /**
     * Scope to filter by entity type.
     */
    public function scopeForEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Get a display label for the change.
     */
    public function getChangeLabel(): string
    {
        return sprintf(
            '%s #%d: %s changed from "%s" to "%s"',
            $this->entity_type,
            $this->entity_id,
            $this->field_name,
            $this->old_value ?? '(empty)',
            $this->new_value ?? '(empty)'
        );
    }
}
