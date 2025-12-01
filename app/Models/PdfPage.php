<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Project\Models\Room;
use Webkul\Chatter\Traits\HasChatter;

/**
 * Pdf Page Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $document_id
 * @property string|null $page_number
 * @property string|null $page_type
 * @property string|null $width
 * @property string|null $height
 * @property string|null $rotation
 * @property string|null $thumbnail_path
 * @property string|null $extracted_text
 * @property array $page_metadata
 * @property-read \Illuminate\Database\Eloquent\Collection $rooms
 * @property-read \Illuminate\Database\Eloquent\Collection $annotations
 * @property-read \Illuminate\Database\Eloquent\Model|null $pdfDocument
 *
 */
class PdfPage extends Model
{
    use HasFactory, HasChatter, SoftDeletes;

    protected $table = 'pdf_pages';

    protected $fillable = [
        'document_id',
        'page_number',
        'page_type',
        'width',
        'height',
        'rotation',
        'thumbnail_path',
        'extracted_text',
        'page_metadata',
    ];

    protected $casts = [
        'page_metadata' => 'array',
    ];

    /**
     * Get the PDF document this page belongs to
     */
    public function pdfDocument()
    {
        return $this->belongsTo(PdfDocument::class, 'document_id');
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

    /**
     * Get the page type
     *
     * @return string|null
     */
    public function getPageType(): ?string
    {
        return $this->page_type;
    }

    /**
     * Check if this is a cover page
     *
     * @return bool
     */
    public function isCoverPage(): bool
    {
        return $this->page_type === 'cover_page';
    }

    /**
     * Check if this is a floor plan page
     *
     * @return bool
     */
    public function isFloorPlan(): bool
    {
        return $this->page_type === 'floor_plan';
    }

    /**
     * Check if this is an elevation page
     *
     * @return bool
     */
    public function isElevation(): bool
    {
        return $this->page_type === 'elevation';
    }
}
