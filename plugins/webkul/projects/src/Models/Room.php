<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Security\Models\User;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;

class Room extends Model
{
    use SoftDeletes, HasChatter, HasLogActivity;

    protected $table = 'projects_rooms';

    protected $fillable = [
        'project_id',
        'name',
        'room_type',
        'floor_number',
        'pdf_page_number',
        'pdf_room_label',
        'pdf_detail_number',
        'pdf_notes',
        'notes',
        'sort_order',
        'creator_id',
    ];

    protected $casts = [
        'pdf_page_number' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Attributes to log for Chatter activity tracking
     */
    protected $logAttributes = [
        'name' => 'Room Name',
        'room_type' => 'Room Type',
        'floor_number' => 'Floor Number',
        'pdf_page_number' => 'PDF Page Number',
        'pdf_room_label' => 'PDF Room Label',
        'pdf_detail_number' => 'PDF Detail Number',
        'notes' => 'Notes',
    ];

    /**
     * Relationships
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(RoomLocation::class, 'room_id');
    }

    public function cabinets(): HasMany
    {
        return $this->hasMany(CabinetSpecification::class, 'room_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Calculated Attributes
     */

    /**
     * Get total linear feet for all cabinets in this room
     */
    public function getTotalLinearFeetAttribute(): float
    {
        return $this->cabinets()->sum('linear_feet') ?? 0;
    }

    /**
     * Get count of cabinets in this room
     */
    public function getCabinetCountAttribute(): int
    {
        return $this->cabinets()->count();
    }

    /**
     * Get PDF reference display string
     */
    public function getPdfReferenceAttribute(): ?string
    {
        if (!$this->pdf_page_number) {
            return null;
        }

        $parts = ["Page {$this->pdf_page_number}"];

        if ($this->pdf_room_label) {
            $parts[] = $this->pdf_room_label;
        }

        if ($this->pdf_detail_number) {
            $parts[] = "Detail {$this->pdf_detail_number}";
        }

        return implode(' - ', $parts);
    }

    /**
     * Scopes
     */

    /**
     * Scope: Order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope: Filter by room type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('room_type', $type);
    }

    /**
     * Scope: Filter by floor number
     */
    public function scopeByFloor($query, string $floor)
    {
        return $query->where('floor_number', $floor);
    }

    /**
     * Scope: Rooms on specific PDF page
     */
    public function scopeOnPage($query, int $pageNumber)
    {
        return $query->where('pdf_page_number', $pageNumber);
    }

    /**
     * Scope: With cabinet and location counts
     */
    public function scopeWithCounts($query)
    {
        return $query->withCount(['cabinets', 'locations']);
    }
}
