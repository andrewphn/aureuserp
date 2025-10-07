<?php

namespace App\Services;

use App\Models\PdfDocument;
use App\Models\Partner;
use App\Models\Project;
use App\Models\ProjectAddress;
use App\Models\State;
use App\Models\Country;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
     * Extract room/location-specific data from individual pages
     * This preserves duplicate equipment across different rooms
     *
     * @param PdfDocument $document
     * @return array
     */
    public function extractRoomData(PdfDocument $document): array
    {
        $rooms = [];

        // Process each page individually (pages 2+ typically have room-specific data)
        $pages = $document->pages()
            ->whereNotNull('extracted_text')
            ->where('page_number', '>', 1) // Skip cover page
            ->orderBy('page_number')
            ->get();

        foreach ($pages as $page) {
            $text = $page->extracted_text;

            // Identify room/location from page
            $roomName = $this->identifyRoom($text);

            if ($roomName) {
                // Initialize room if not exists
                if (!isset($rooms[$roomName])) {
                    $rooms[$roomName] = [
                        'pages' => [],
                        'equipment' => [],
                        'sections' => [],
                    ];
                }

                // Track which pages contain this room
                $rooms[$roomName]['pages'][] = $page->page_number;

                // Extract equipment specific to this page/room
                $equipment = $this->extractEquipment($text);
                if (!empty($equipment)) {
                    // Merge and deduplicate by brand+model combination
                    foreach ($equipment as $item) {
                        $key = strtolower($item['brand'] . '_' . $item['model']);
                        $rooms[$roomName]['equipment'][$key] = $item;
                    }
                }

                // Extract sections/elevations (e.g., "Section A", "Elevation F")
                if (preg_match_all('/(?:Section|Elevation)\s+([A-Z])/i', $text, $matches)) {
                    $rooms[$roomName]['sections'] = array_unique(array_merge(
                        $rooms[$roomName]['sections'] ?? [],
                        $matches[0]
                    ));
                }
            }
        }

        // Convert equipment from associative array back to indexed array
        foreach ($rooms as $roomName => &$roomData) {
            if (!empty($roomData['equipment'])) {
                $roomData['equipment'] = array_values($roomData['equipment']);
            }
        }

        return $rooms;
    }

    /**
     * Identify room/location name from page text
     *
     * @param string $text
     * @return string|null
     */
    protected function identifyRoom(string $text): ?string
    {
        // Check for common room identifiers
        $roomPatterns = [
            'Island' => '/\bIsland\s+(?:Front\s+)?Elevation/i',
            'Sink Wall' => '/\bSink\s+Wall\s+(?:Overview\s+)?Elevation/i',
            'Fridge Wall' => '/\bFridge\s+Wall\s+(?:Overview\s+)?Elevation/i',
            'Pantry' => '/\bPantry\s+(?:Exterior\s+Wall\s+)?Elevation/i',
            'Kitchen' => '/\bKitchen\s+Plan\s+View/i',
        ];

        foreach ($roomPatterns as $room => $pattern) {
            if (preg_match($pattern, $text)) {
                return $room;
            }
        }

        // Fallback: check for simple mentions
        if (preg_match('/\b(Island|Sink\s+Wall|Fridge\s+Wall|Pantry)\b/i', $text, $match)) {
            return ucwords(strtolower($match[1]));
        }

        return null;
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

            // Try to parse: "[street] [city], [state] [zip]"
            // City is the last word(s) before the comma, everything else is street
            if (preg_match('/^(.+?)\s+([A-Za-z\s]+?),\s*([A-Z]{2})(?:\s+(\d{5}))?$/i', $addressLine, $parts)) {
                // Split what's before the comma into street and city
                // City is likely the last 1-2 words before comma
                $beforeComma = trim($parts[1] . ' ' . $parts[2]);

                // Try to identify city as last word before comma
                if (preg_match('/^(.+)\s+([A-Z][a-z]+)$/i', $beforeComma, $split)) {
                    $project['street_address'] = trim($split[1]);
                    $project['city'] = trim($split[2]);
                } else {
                    // Fallback: everything before comma is street, use state for city lookup later
                    $project['street_address'] = $beforeComma;
                }

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
        $seen = []; // Track unique brand+model combinations

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
                    $model = trim($match[1]);
                    $key = strtolower($brand . '_' . $model);

                    // Only add if not already seen (deduplication)
                    if (!isset($seen[$key])) {
                        $equipment[] = [
                            'brand' => $brand,
                            'model' => $model,
                        ];
                        $seen[$key] = true;
                    }
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

    /**
     * Extract metadata AND save to database tables
     *
     * @param PdfDocument $document
     * @return array ['project' => Project, 'customer' => Partner, 'metadata' => array]
     */
    public function extractAndSaveToDatabase(PdfDocument $document): array
    {
        // Extract metadata first
        $metadata = $this->extractMetadata($document);

        if (empty($metadata)) {
            Log::warning('No metadata extracted, skipping database save', [
                'document_id' => $document->id,
            ]);
            return ['metadata' => []];
        }

        // Save to database
        return $this->saveToDatabase($metadata, $document);
    }

    /**
     * Save extracted metadata to proper database tables
     *
     * @param array $metadata
     * @param PdfDocument $document
     * @return array
     */
    protected function saveToDatabase(array $metadata, PdfDocument $document): array
    {
        return DB::transaction(function () use ($metadata, $document) {
            $results = ['metadata' => $metadata];

            // Flatten confidence-scored values for database insertion
            $flatMetadata = $this->flattenMetadata($metadata);

            // 1. Create/Update Customer (Partner)
            if (!empty($flatMetadata['client'])) {
                $customer = $this->createOrUpdateCustomer($flatMetadata);
                $results['customer'] = $customer;
            }

            // 2. Create Project
            if (!empty($flatMetadata['project'])) {
                $project = $this->createProject($flatMetadata, $customer ?? null);
                $results['project'] = $project;

                // 3. Create Project Address
                if (!empty($flatMetadata['project']['street_address'])) {
                    $address = $this->createProjectAddress($flatMetadata['project'], $project);
                    $results['address'] = $address;
                }

                // 4. Link PDF Document to Project
                $document->update([
                    'module_type' => 'projects',
                    'module_id' => $project->id,
                    'extracted_metadata' => $metadata, // Keep full metadata with confidence scores
                    'processing_status' => 'completed',
                    'extracted_at' => now(),
                ]);

                Log::info('PDF metadata saved to database', [
                    'document_id' => $document->id,
                    'project_id' => $project->id,
                    'customer_id' => $customer->id ?? null,
                ]);
            }

            return $results;
        });
    }

    /**
     * Flatten metadata structure (remove confidence scores for database)
     *
     * @param array $metadata
     * @return array
     */
    protected function flattenMetadata(array $metadata): array
    {
        $flat = [];

        foreach ($metadata as $section => $data) {
            if (is_array($data)) {
                $flat[$section] = [];
                foreach ($data as $key => $value) {
                    // If value has 'value' and 'confidence', extract just the value
                    if (is_array($value) && isset($value['value'])) {
                        $flat[$section][$key] = $value['value'];
                    } else {
                        $flat[$section][$key] = $value;
                    }
                }
            }
        }

        return $flat;
    }

    /**
     * Create or update customer (Partner) record
     *
     * @param array $flatMetadata
     * @return Partner|null
     */
    protected function createOrUpdateCustomer(array $flatMetadata): ?Partner
    {
        $clientData = $flatMetadata['client'] ?? [];
        $projectData = $flatMetadata['project'] ?? [];

        // Need at least name or email to create a customer
        if (empty($clientData['name']) && empty($clientData['email'])) {
            return null;
        }

        // Find or create by email (most unique identifier)
        $searchCriteria = $clientData['email']
            ? ['email' => $clientData['email']]
            : ['name' => $clientData['name']];

        $customer = Partner::updateOrCreate(
            $searchCriteria,
            [
                'name' => $clientData['name'] ?? $clientData['email'],
                'account_type' => 'individual',
                'sub_type' => 'customer',
                'email' => $clientData['email'] ?? null,
                'phone' => $clientData['phone'] ?? null,
                'website' => $clientData['website'] ?? null,
                'company_registry' => $clientData['company'] ?? null,
                // Add address from project if available
                'street1' => $projectData['street_address'] ?? null,
                'city' => $projectData['city'] ?? null,
                'state_id' => isset($projectData['state'])
                    ? $this->getStateId($projectData['state'])
                    : null,
                'zip' => $projectData['zip'] ?? null,
                'country_id' => $this->getCountryId('US'),
                'is_active' => true,
            ]
        );

        return $customer;
    }

    /**
     * Create project record
     *
     * @param array $flatMetadata
     * @param Partner|null $customer
     * @return Project
     */
    protected function createProject(array $flatMetadata, ?Partner $customer): Project
    {
        $projectData = $flatMetadata['project'] ?? [];
        $documentData = $flatMetadata['document'] ?? [];
        $measurements = $flatMetadata['measurements'] ?? [];

        // Calculate total linear feet
        $totalLinearFeet = $this->calculateTotalLinearFeet($measurements);

        // Get latest revision date
        $revisionDate = $this->getLatestRevisionDate($documentData);

        // Build project name
        $projectName = $this->buildProjectName($projectData, $customer);

        $project = Project::create([
            'name' => $projectName,
            'project_type' => $projectData['type'] ?? 'Kitchen Cabinetry',
            'start_date' => $revisionDate,
            'estimated_linear_feet' => $totalLinearFeet,
            'description' => $this->buildProjectDescription($documentData, $measurements),
            'partner_id' => $customer?->id,
            'creator_id' => auth()->id() ?? 1, // Fallback to admin user
            'is_active' => true,
        ]);

        return $project;
    }

    /**
     * Create project address record
     *
     * @param array $projectData
     * @param Project $project
     * @return ProjectAddress
     */
    protected function createProjectAddress(array $projectData, Project $project): ProjectAddress
    {
        return ProjectAddress::create([
            'project_id' => $project->id,
            'type' => 'project',
            'street1' => $projectData['street_address'] ?? null,
            'street2' => $projectData['street2'] ?? null,
            'city' => $projectData['city'] ?? null,
            'state_id' => isset($projectData['state'])
                ? $this->getStateId($projectData['state'])
                : null,
            'zip' => $projectData['zip'] ?? null,
            'country_id' => $this->getCountryId('US'),
            'is_primary' => true,
        ]);
    }

    /**
     * Get state ID from state code (e.g., "MA")
     */
    protected function getStateId(string $stateCode): ?int
    {
        $state = State::where('code', strtoupper($stateCode))->first();
        return $state?->id;
    }

    /**
     * Get country ID from country code (e.g., "US")
     */
    protected function getCountryId(string $countryCode): ?int
    {
        $country = Country::where('code', strtoupper($countryCode))->first();
        return $country?->id ?? 1; // Default to US if not found
    }

    /**
     * Calculate total linear feet from measurements
     */
    protected function calculateTotalLinearFeet(array $measurements): ?float
    {
        $total = 0;

        // Add tier cabinetry
        if (!empty($measurements['tiers'])) {
            foreach ($measurements['tiers'] as $tier) {
                if (isset($tier['linear_feet'])) {
                    $total += is_array($tier['linear_feet'])
                        ? $tier['linear_feet']['value']
                        : $tier['linear_feet'];
                }
            }
        }

        // Add floating shelves
        if (!empty($measurements['floating_shelves_lf'])) {
            $total += is_array($measurements['floating_shelves_lf'])
                ? $measurements['floating_shelves_lf']['value']
                : $measurements['floating_shelves_lf'];
        }

        return $total > 0 ? $total : null;
    }

    /**
     * Get latest revision date from document data
     */
    protected function getLatestRevisionDate(array $documentData): ?Carbon
    {
        if (!empty($documentData['revisions'])) {
            // Get the last revision (highest number)
            $lastRevision = end($documentData['revisions']);
            if (!empty($lastRevision['date'])) {
                try {
                    return Carbon::createFromFormat('n/j/y', $lastRevision['date']);
                } catch (\Exception $e) {
                    Log::warning('Failed to parse revision date', [
                        'date' => $lastRevision['date'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return null;
    }

    /**
     * Build project name from available data
     */
    protected function buildProjectName(array $projectData, ?Partner $customer): string
    {
        $parts = [];

        // Add customer name if available
        if ($customer && $customer->name) {
            $parts[] = $customer->name;
        }

        // Add project type
        if (!empty($projectData['type'])) {
            $parts[] = $projectData['type'];
        }

        // Add address if available
        if (!empty($projectData['address'])) {
            $parts[] = 'at ' . $projectData['address'];
        } elseif (!empty($projectData['street_address'])) {
            $parts[] = 'at ' . $projectData['street_address'];
        }

        return !empty($parts)
            ? implode(' - ', $parts)
            : 'Untitled Project';
    }

    /**
     * Build project description from document and measurements data
     */
    protected function buildProjectDescription(array $documentData, array $measurements): string
    {
        $parts = [];

        // Add drawn by info
        if (!empty($documentData['drawn_by'])) {
            $parts[] = "Drawn by: {$documentData['drawn_by']}";
        }

        // Add revision info
        if (!empty($documentData['revisions'])) {
            $revisionCount = count($documentData['revisions']);
            $parts[] = "Revision {$revisionCount}";
        }

        // Add linear feet breakdown
        if (!empty($measurements['tiers'])) {
            $tierInfo = [];
            foreach ($measurements['tiers'] as $tier) {
                $tierNum = is_array($tier['tier']) ? $tier['tier']['value'] : $tier['tier'];
                $lf = is_array($tier['linear_feet']) ? $tier['linear_feet']['value'] : $tier['linear_feet'];
                $tierInfo[] = "Tier {$tierNum}: {$lf} LF";
            }
            if (!empty($tierInfo)) {
                $parts[] = implode(', ', $tierInfo);
            }
        }

        return !empty($parts) ? implode('. ', $parts) : '';
    }
}
