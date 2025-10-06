<?php

namespace App\Services;

use App\Models\PdfDocument;
use Illuminate\Support\Facades\Log;

/**
 * PDF Data Extraction Service
 *
 * Semi-assisted extraction of structured data from PDF text
 * Uses regex patterns to identify common fields in architectural drawings
 * Includes confidence scoring for each extracted field
 */
class PdfDataExtractor
{
    /**
     * Confidence levels
     */
    const CONFIDENCE_HIGH = 'high';      // 80-100% confidence
    const CONFIDENCE_MEDIUM = 'medium';  // 50-79% confidence
    const CONFIDENCE_LOW = 'low';        // 0-49% confidence

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
     * Create a field with value and confidence score
     *
     * @param mixed $value
     * @param string $confidence
     * @return array
     */
    protected function field($value, string $confidence = self::CONFIDENCE_MEDIUM): array
    {
        return [
            'value' => $value,
            'confidence' => $confidence,
        ];
    }

    /**
     * Validate email and adjust confidence
     */
    protected function validateEmail(string $email): string
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? self::CONFIDENCE_HIGH : self::CONFIDENCE_MEDIUM;
    }

    /**
     * Validate phone format and adjust confidence
     */
    protected function validatePhone(string $phone): string
    {
        // US phone format validation
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        return (strlen($cleaned) === 10 || strlen($cleaned) === 11) ? self::CONFIDENCE_HIGH : self::CONFIDENCE_LOW;
    }

    /**
     * Calculate confidence based on pattern specificity
     */
    protected function calculateConfidence(bool $hasLabel, bool $isValidated, int $occurrences = 1): string
    {
        $score = 50; // Base score

        if ($hasLabel) $score += 20;        // Found with clear label
        if ($isValidated) $score += 20;     // Validated format
        if ($occurrences > 1) $score += 10; // Multiple occurrences

        if ($score >= 80) return self::CONFIDENCE_HIGH;
        if ($score >= 50) return self::CONFIDENCE_MEDIUM;
        return self::CONFIDENCE_LOW;
    }

    /**
     * Extract project information (address, name, type)
     */
    protected function extractProjectInfo(string $text): array
    {
        $project = [];

        // Strategy 1: Look for "Renovations at:" pattern (most reliable for PDFs with labeled addresses)
        if (preg_match('/(?:Renovations at:|Project at:)\s*([^\n]+?)(?:Approved|Revision|Drawn|\n)/i', $text, $matches)) {
            $addressLine = trim($matches[1]);

            // Try to parse components from this line
            if (preg_match('/(.+?)\s+([A-Za-z\s]+),\s*([A-Z]{2})\s*(\d{5})?/i', $addressLine, $parts)) {
                $project['street_address'] = trim($parts[1]);
                $project['city'] = trim($parts[2]);
                $project['state'] = trim($parts[3]);
                if (!empty($parts[4])) {
                    $project['zip'] = $parts[4];
                }

                // Build full address
                $project['address'] = trim($addressLine);
            } else {
                $project['address'] = $addressLine;
            }
        }
        // Strategy 2: Find all complete address patterns and take the shortest street number
        // (to avoid PDF concatenation issues like "2525" instead of "25")
        elseif (preg_match_all('/\b(\d{1,5})\s+([A-Za-z\s]+?(?:Lane|Road|Street|Ave|Avenue|Drive|Boulevard|Blvd|Rd|St|Ln))\s*,\s*([A-Za-z\s]+?),\s*([A-Z]{2})\b/i', $text, $matches, PREG_SET_ORDER)) {
            // Find match with shortest street number (most likely to be correct, avoiding concatenation)
            $shortestMatch = null;
            $shortestLength = PHP_INT_MAX;

            foreach ($matches as $match) {
                $numLength = strlen($match[1]);
                if ($numLength < $shortestLength) {
                    $shortestLength = $numLength;
                    $shortestMatch = $match;
                }
            }

            if ($shortestMatch) {
                $project['street_address'] = trim($shortestMatch[1] . ' ' . $shortestMatch[2]);
                $project['city'] = trim($shortestMatch[3]);
                $project['state'] = trim($shortestMatch[4]);

                // Build full address
                $project['address'] = "{$project['street_address']}, {$project['city']}, {$project['state']}";
            }
        }

        // Extract ZIP code separately if not found
        if (empty($project['zip']) && preg_match('/\b(\d{5})(?:-\d{4})?\b/', $text, $matches)) {
            $project['zip'] = $matches[1];
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
            $name = trim($matches[1]);
            $client['name'] = $this->field($name, self::CONFIDENCE_HIGH); // Has label "Owner:"
        }

        // Extract email
        if (preg_match('/(?:Email:)?\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $text, $matches)) {
            $email = trim($matches[1]);
            $hasLabel = stripos($text, 'Email:') !== false;
            $client['email'] = $this->field($email, $this->calculateConfidence(
                $hasLabel,
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            ));
        }

        // Extract phone
        if (preg_match('/(?:Phone:)?\s*(\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4})/i', $text, $matches)) {
            $phone = trim($matches[1]);
            $hasLabel = stripos($text, 'Phone:') !== false;
            $cleaned = preg_replace('/[^0-9]/', '', $phone);
            $isValid = (strlen($cleaned) === 10 || strlen($cleaned) === 11);
            $client['phone'] = $this->field($phone, $this->calculateConfidence($hasLabel, $isValid));
        }

        // Extract website
        if (preg_match('/(www\.[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $text, $matches)) {
            $website = trim($matches[1]);
            // Websites without http:// are medium confidence
            $client['website'] = $this->field($website, self::CONFIDENCE_MEDIUM);
        }

        // Extract company name
        if (preg_match('/(?:of|by)\s+([A-Za-z\s]+(?:Woodworking|Construction|Builders|Design|Inc|LLC))/i', $text, $matches)) {
            $company = trim($matches[1]);
            // Strong pattern with business suffix = high confidence
            $client['company'] = $this->field($company, self::CONFIDENCE_HIGH);
        }

        return $client;
    }

    /**
     * Extract document metadata (revision, dates, drawn by)
     */
    protected function extractDocumentInfo(string $text): array
    {
        $document = [];

        // Extract drawing file name (with optional numeric prefix)
        if (preg_match('/([A-Za-z0-9_]+\.dwg)/i', $text, $matches)) {
            $document['drawing_file'] = $matches[1];
        }

        // Extract drawn by - more flexible pattern for names
        if (preg_match('/Drawn\s+By:\s*([A-Z]\.?\s*[A-Za-z]+|[A-Z][a-z]+\s+[A-Z]\.?\s*[A-Za-z]+)/i', $text, $matches)) {
            $document['drawn_by'] = trim($matches[1]);
        }

        // Extract revision history
        $revisions = [];
        $seenRevisions = []; // Track unique revision numbers to avoid duplicates

        // Pattern: Revision number with date on same line (e.g., "Revision 4 9/27/25")
        if (preg_match_all('/Revision\s+(\d+)\s+(\d{1,2}\/\d{1,2}\/\d{2,4})/i', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $revNum = $match[1];
                // Only add if we haven't seen this revision number yet
                if (!isset($seenRevisions[$revNum])) {
                    $revisions[] = [
                        'number' => $revNum,
                        'date' => $match[2],
                    ];
                    $seenRevisions[$revNum] = true;
                }
            }
        }

        // Look for dates near "Drawn By" line that might indicate revision dates
        if (preg_match('/Drawn\s+By:.*?(\d{1,2}\/\d{1,2}\/\d{2,4})/i', $text, $matches)) {
            // This might be the initial draft date
            $document['initial_draft_date'] = $matches[1];
        }

        if (!empty($revisions)) {
            $document['revisions'] = $revisions;
        }

        // Extract approval date
        if (preg_match('/Approved\s+By:.*?Date:\s*(\d{1,2}\/\d{1,2}\/\d{2,4})/i', $text, $matches)) {
            $document['approved_date'] = $matches[1];
        }

        // Extract project/drawing ID if present (skip "of [Company]" pattern)
        if (preg_match('/ID#\s*([A-Z0-9-]+)/i', $text, $matches)) {
            $document['project_id'] = trim($matches[1]);
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
                    'tier' => $this->field((int) $match[1], self::CONFIDENCE_HIGH),
                    'linear_feet' => $this->field((float) $match[2], self::CONFIDENCE_HIGH),
                    // Exact pattern with label and unit = highest confidence
                ];
            }
            $measurements['tiers'] = $tiers;
        }

        // Extract floating shelves
        if (preg_match('/Floating\s+Shelves:\s*([\d.]+)\s*LF/i', $text, $matches)) {
            // Clear label + unit + numeric = high confidence
            $measurements['floating_shelves_lf'] = $this->field((float) $matches[1], self::CONFIDENCE_HIGH);
        }

        // Extract countertops
        if (preg_match('/(?:Millwork\s+)?Countertops:\s*([\d.]+)\s*SF/i', $text, $matches)) {
            // Clear label + unit + numeric = high confidence
            $measurements['countertops_sf'] = $this->field((float) $matches[1], self::CONFIDENCE_HIGH);
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
