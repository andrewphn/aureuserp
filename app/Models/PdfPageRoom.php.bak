<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Project\Models\Room;

class PdfPageRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'pdf_page_id',
        'room_id',
        'room_number',
        'room_type',
    ];

    /**
     * Get the PDF page this room belongs to
     */
    public function pdfPage()
    {
        return $this->belongsTo(PdfPage::class, 'pdf_page_id');
    }

    /**
     * Get the project room this is linked to (if any)
     */
    public function projectRoom()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
