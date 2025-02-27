<?php

namespace Webkul\Support\Traits;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

trait PDFHandler
{
    protected function generatePDF(string $html, ?string $fileName = null): string
    {
        $html = mb_convert_encoding($html, 'UTF-8', 'auto');

        $fileName = $fileName ? "{$fileName}.pdf" : Str::uuid() . '.pdf';

        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->set_option('defaultFont', 'Courier');

        $filePath = "pdfs/{$fileName}";

        Storage::disk('public')->put($filePath, $pdf->output());

        return $filePath;
    }

    protected function downloadPDF(string $html, ?string $fileName = null)
    {
        return $this->generatePDF($html, $fileName);
    }
}
