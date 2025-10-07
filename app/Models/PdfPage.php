<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Project\Models\Room;

class PdfPage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pdf_page_metadata';

    protected $fillable = [
        'pdf_document_id',
        'page_number',
        'page_type',
        'room_id',
        'room_name',
        'room_type',
        'detail_number',
        'notes',
        'metadata',
        'creator_id',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the PDF document this page belongs to
     */
    public function pdfDocument()
    {
        return $this->belongsTo(PdfDocument::class);
    }

    /**
     * Get the room this page is associated with
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the user who created this page record
     */
    public function creator()
    {
        return $this->belongsTo(\Webkul\User\Models\User::class, 'creator_id');
    }
}
