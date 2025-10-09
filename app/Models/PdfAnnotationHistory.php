<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Project\Models\PdfAnnotation;
use Webkul\Project\Models\PdfPage;
use Webkul\User\Models\User;

class PdfAnnotationHistory extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'pdf_annotation_history';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'annotation_id',
        'pdf_page_id',
        'action',
        'user_id',
        'before_data',
        'after_data',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the annotation that this history entry belongs to.
     */
    public function annotation(): BelongsTo
    {
        return $this->belongsTo(PdfAnnotation::class, 'annotation_id');
    }

    /**
     * Get the PDF page that this history entry belongs to.
     */
    public function pdfPage(): BelongsTo
    {
        return $this->belongsTo(PdfPage::class, 'pdf_page_id');
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log an annotation action to history
     *
     * @param int $pdfPageId
     * @param string $action
     * @param array|null $beforeData
     * @param array|null $afterData
     * @param int|null $annotationId
     * @param array $metadata
     * @return self
     */
    public static function logAction(
        int $pdfPageId,
        string $action,
        ?array $beforeData = null,
        ?array $afterData = null,
        ?int $annotationId = null,
        array $metadata = []
    ): self {
        return self::create([
            'annotation_id' => $annotationId,
            'pdf_page_id' => $pdfPageId,
            'action' => $action,
            'user_id' => auth()->id(),
            'before_data' => $beforeData,
            'after_data' => $afterData,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get all history for a specific annotation
     *
     * @param int $annotationId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function forAnnotation(int $annotationId)
    {
        return self::where('annotation_id', $annotationId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all history for a specific PDF page
     *
     * @param int $pdfPageId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function forPage(int $pdfPageId)
    {
        return self::where('pdf_page_id', $pdfPageId)
            ->with(['user', 'annotation'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
