<?php

namespace App\Http\Controllers;

use App\Models\PdfDocument;
use App\Services\NutrientPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Pdf Preview Controller controller
 *
 */
class PdfPreviewController extends Controller
{
    protected $nutrientService;

    /**
     * Create a new PdfPreviewController instance
     *
     * @param NutrientPdfService $nutrientService
     */
    public function __construct(NutrientPdfService $nutrientService)
    {
        $this->nutrientService = $nutrientService;
    }

    /**
     * Render a specific page of a PDF
     *
     * @param Request $request
     * @param int $pdfId
     * @param int $pageNumber
     * @return \Illuminate\Http\Response
     */
    /**
     * Render Page
     *
     * @param Request $request The incoming request
     * @param int $pdfId
     * @param int $pageNumber
     */
    public function renderPage(Request $request, int $pdfId, int $pageNumber)
    {
        $pdf = PdfDocument::findOrFail($pdfId);

        $width = $request->get('width', 800);

        // Get cached or render new image
        $imageUrl = $this->nutrientService->getCachedOrRenderPage(
            $pdf->file_path,
            $pageNumber,
            $width
        );

        if (!$imageUrl) {
            return response()->json(['error' => 'Failed to render page'], 500);
        }

        return response()->json([
            'url' => $imageUrl,
            'page' => $pageNumber,
            'pdf_id' => $pdfId,
        ]);
    }

    /**
     * Render page as base64 image (for immediate display)
     *
     * @param Request $request
     * @param int $pdfId
     * @param int $pageNumber
     * @return \Illuminate\Http\Response
     */
    /**
     * Render Page Base64
     *
     * @param Request $request The incoming request
     * @param int $pdfId
     * @param int $pageNumber
     */
    public function renderPageBase64(Request $request, int $pdfId, int $pageNumber)
    {
        $pdf = PdfDocument::findOrFail($pdfId);

        $width = $request->get('width', 800);

        $imageData = $this->nutrientService->renderPageAsImage(
            $pdf->file_path,
            $pageNumber,
            $width
        );

        if (!$imageData) {
            return response()->json(['error' => 'Failed to render page'], 500);
        }

        return response()->json([
            'image' => 'data:image/png;base64,' . $imageData,
            'page' => $pageNumber,
            'pdf_id' => $pdfId,
        ]);
    }
}
