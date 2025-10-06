<?php

namespace App\Services;

use App\Models\PdfDocument;
use Illuminate\Support\Facades\Log;

/**
 * PDF Data Extraction Service
 *
 * Semi-assisted extraction of structured data from PDF text
 * Uses regex patterns to identify common fields in architectural drawings
 */
class PdfDataExtractor
{
    /**
     * Extract structured metadata from a PDF document
     *
     * @param PdfDocument $document
     * @return array
     */
    public function extractMetadata(PdfDocument $document): array
    {
        // Get all extracted text from pages
        $allText = $document->pages()
            ->whereNotNull('extracted_text')
            ->orderBy('page_number')
            ->pluck('extracted_text')
            ->implode("\n");

        if (empty($allText)) {
            return [];
        }

        Log::info("Extracting metadata from PDF", [
            'document_id' => $document->id,
            'text_length' => strlen($allText),
        ]);

        $metadata = [
            'project' => $this->extractProjectInfo($allText),
            'client' => $this->extractClientInfo($allText),
            'document' => $this->extractDocumentInfo($allText),
            'measurements' => $this->extractMeasurements($allText),
            'equipment' => $this->extractEquipment($allText),
            'materials' => $this->extractMaterials($allText),
        ];

        // Remove empty sections
        $metadata = array_filter($metadata, function ($section) {
            return !empty($section);
        });

        Log::info("Metadata extraction completed", [
            'document_id' => $document->id,
            'sections' => array_keys($metadata),
        ]);

        return $metadata;
    }

    /**
     * Extract project information (address, name, type)
     */
    protected function extractProjectInfo(string $text): array
    {
        $project = [];

        // Extract address patterns
        if (preg_match('/(?:at:|Renovations at:|Project:)\s*([^\n]+(?:Lane|Road|Street|Ave|Drive|Rd|St|Ln)[^\n]+)/i', $text, $matches)) {
            $project['address'] = trim($matches[1]);
        }

        // Extract city, state, zip
        if (preg_match('/([A-Za-z\s]+),\s*([A-Z]{2})\s*(\d{5})/i', $text, $matches)) {
            $project['city'] = trim($matches[1]);
            $project['state'] = $matches[2];
            $project['zip'] = $matches[3];
        }

        // Extract project type
        if (preg_match('/(?:Kitchen|Bathroom|Living Room|Bedroom|Office|Basement)\s+(?:Cabinetry|Renovation|Remodel)/i', $text, $matches)) {
            $project['type'] = trim($matches[0]);
        }

        return $project;
    }

    /**
     * Extract client/owner information
     */
    protected function extractClientInfo(string $text): array
    {
        $client = [];

        // Extract owner name
        if (preg_match('/Owner:\s*([A-Za-z\s\.]+(?:\n|$))/i', $text, $matches)) {
            $client['name'] = trim($matches[1]);
        }

        // Extract email
        if (preg_match('/(?:Email:)?\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $text, $matches)) {
            $client['email'] = trim($matches[1]);
        }

        // Extract phone
        if (preg_match('/(?:Phone:)?\s*(\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4})/i', $text, $matches)) {
            $client['phone'] = trim($matches[1]);
        }

        // Extract website
        if (preg_match('/(www\.[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $text, $matches)) {
            $client['website'] = trim($matches[1]);
        }

        // Extract company name
        if (preg_match('/(?:of|by)\s+([A-Za-z\s]+(?:Woodworking|Construction|Builders|Design|Inc|LLC))/i', $text, $matches)) {
            $client['company'] = trim($matches[1]);
        }

        return $client;
    }

    /**
     * Extract document metadata (revision, dates, drawn by)
     */
    protected function extractDocumentInfo(string $text): array
    {
        $document = [];

        // Extract drawing file name
        if (preg_match('/([A-Za-z0-9_]+\.dwg)/i', $text, $matches)) {
            $document['drawing_file'] = $matches[1];
        }

        // Extract drawn by
        if (preg_match('/Drawn\s+By:\s*([A-Za-z\s\.]+)/i', $text, $matches)) {
            $document['drawn_by'] = trim($matches[1]);
        }

        // Extract revision history
        $revisions = [];
        if (preg_match_all('/Revision\s+(\d+).*?(\d{1,2}\/\d{1,2}\/\d{2,4})/i', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $revisions[] = [
                    'number' => $match[1],
                    'date' => $match[2],
                ];
            }
        }
        if (!empty($revisions)) {
            $document['revisions'] = $revisions;
        }

        // Extract approval date
        if (preg_match('/Approved\s+By:.*?Date:\s*(\d{1,2}\/\d{1,2}\/\d{2,4})/i', $text, $matches)) {
            $document['approved_date'] = $matches[1];
        }

        return $document;
    }

    /**
     * Extract measurements and calculations (TCS-specific Linear Feet)
     */
    protected function extractMeasurements(string $text): array
    {
        $measurements = [];

        // Extract Tier cabinetry measurements
        if (preg_match_all('/Tier\s+(\d+)\s+Cabinetry:\s*([\d.]+)\s*LF/i', $text, $matches, PREG_SET_ORDER)) {
            $tiers = [];
            foreach ($matches as $match) {
                $tiers[] = [
                    'tier' => (int) $match[1],
                    'linear_feet' => (float) $match[2],
                ];
            }
            $measurements['tiers'] = $tiers;
        }

        // Extract floating shelves
        if (preg_match('/Floating\s+Shelves:\s*([\d.]+)\s*LF/i', $text, $matches)) {
            $measurements['floating_shelves_lf'] = (float) $matches[1];
        }

        // Extract countertops
        if (preg_match('/(?:Millwork\s+)?Countertops:\s*([\d.]+)\s*SF/i', $text, $matches)) {
            $measurements['countertops_sf'] = (float) $matches[1];
        }

        return $measurements;
    }

    /**
     * Extract equipment and appliances with model numbers
     */
    protected function extractEquipment(string $text): array
    {
        $equipment = [];

        // Common appliance brands and patterns
        $patterns = [
            'SubZero' => '/SubZero\s+([A-Z0-9]+)/i',
            'Wolf' => '/Wolf\s+(?:Gas\s+Range\s+)?([A-Z0-9]+)/i',
            'Miele' => '/Miele\s+(?:DW\s+)?([A-Z0-9\s]+)/i',
            'Zephyr' => '/Zephyr\s+([A-Z0-9\s"]+)/i',
            'Anderson' => '/Anderson\s+([A-Z0-9]+)/i',
            'Shaws' => '/Shaws\s+"([^"]+)"\s+([A-Z0-9-]+)/i',
        ];

        foreach ($patterns as $brand => $pattern) {
            if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $equipment[] = [
                        'brand' => $brand,
                        'model' => trim($match[1]),
                    ];
                }
            }
        }

        return $equipment;
    }

    /**
     * Extract materials and finishes
     */
    protected function extractMaterials(string $text): array
    {
        $materials = [];

        // Extract wood types
        if (preg_match_all('/(White Oak|Red Oak|Maple|Cherry|Walnut|Mahogany)(?:\s+([^\n,]+))?/i', $text, $matches, PREG_SET_ORDER)) {
            $woods = [];
            foreach ($matches as $match) {
                $woods[] = trim($match[0]);
            }
            $materials['wood_types'] = array_unique($woods);
        }

        // Extract finish types
        if (preg_match_all('/(Paint\s+Grade|Natural\s+Finish|Stained|Lacquered|Varnished)/i', $text, $matches)) {
            $materials['finishes'] = array_unique($matches[0]);
        }

        // Extract hardware
        if (preg_match('/Hardware:\s*([^\n]+)/i', $text, $matches)) {
            $materials['hardware'] = trim($matches[1]);
        }

        return $materials;
    }
}
