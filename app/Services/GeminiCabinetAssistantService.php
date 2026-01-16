<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI-powered cabinet specification assistant using Google Gemini
 * Supports natural language commands, guided conversations, and image analysis
 */
class GeminiCabinetAssistantService
{
    protected ?string $geminiKey;
    protected string $model = 'gemini-2.0-flash';

    // Pricing tiers per linear foot
    public const PRICING_TIERS = [
        1 => ['name' => 'Basic', 'price' => 225],
        2 => ['name' => 'Standard', 'price' => 298],
        3 => ['name' => 'Enhanced', 'price' => 348],
        4 => ['name' => 'Premium', 'price' => 425],
        5 => ['name' => 'Custom', 'price' => 550],
    ];

    // Cabinet type detection patterns
    protected const CABINET_PATTERNS = [
        // Base cabinets
        'B' => ['type' => 'base', 'default_height' => 34.5, 'default_depth' => 24],
        'SB' => ['type' => 'sink_base', 'default_height' => 34.5, 'default_depth' => 24],
        'DB' => ['type' => 'drawer_base', 'default_height' => 34.5, 'default_depth' => 24],
        'BBC' => ['type' => 'blind_base_corner', 'default_height' => 34.5, 'default_depth' => 24],
        'LS' => ['type' => 'lazy_susan', 'default_height' => 34.5, 'default_depth' => 24],

        // Wall cabinets
        'W' => ['type' => 'wall', 'default_height' => 30, 'default_depth' => 12],
        'WDC' => ['type' => 'wall_diagonal_corner', 'default_height' => 30, 'default_depth' => 12],
        'WBC' => ['type' => 'wall_blind_corner', 'default_height' => 30, 'default_depth' => 12],

        // Tall cabinets
        'T' => ['type' => 'tall', 'default_height' => 84, 'default_depth' => 24],
        'TP' => ['type' => 'tall_pantry', 'default_height' => 84, 'default_depth' => 24],
        'TO' => ['type' => 'tall_oven', 'default_height' => 84, 'default_depth' => 24],

        // Vanity cabinets
        'V' => ['type' => 'vanity', 'default_height' => 32, 'default_depth' => 21],
        'VD' => ['type' => 'vanity_drawer', 'default_height' => 32, 'default_depth' => 21],
        'VS' => ['type' => 'vanity_sink', 'default_height' => 32, 'default_depth' => 21],
    ];

    public function __construct()
    {
        $this->geminiKey = config('services.google.api_key') ?? env('GOOGLE_API_KEY') ?? env('GEMINI_API_KEY');
    }

    /**
     * Process a text message from the user
     */
    public function processMessage(
        string $message,
        string $sessionId,
        array $currentSpecData,
        string $mode = 'quick'
    ): array {
        if (empty($this->geminiKey)) {
            return $this->errorResponse('Gemini API key not configured');
        }

        // Add message to history
        $this->addToHistory($sessionId, 'user', $message);

        // Build conversation context
        $systemPrompt = $this->buildSystemPrompt($currentSpecData, $mode);
        $history = $this->getConversationHistory($sessionId);

        try {
            $response = $this->callGeminiApi($systemPrompt, $history, null);

            if (isset($response['error'])) {
                return $response;
            }

            // Parse the response for commands and text
            $result = $this->parseAssistantResponse($response);

            // Add assistant response to history
            $this->addToHistory($sessionId, 'assistant', $result['message']);

            return $result;

        } catch (\Exception $e) {
            Log::error('GeminiCabinetAssistant error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Process an uploaded image (floor plan, elevation, cabinet photo)
     */
    public function processImage(
        string $imageBase64,
        string $mimeType,
        string $sessionId,
        array $currentSpecData,
        ?string $userMessage = null
    ): array {
        if (empty($this->geminiKey)) {
            return $this->errorResponse('Gemini API key not configured');
        }

        // Add to history
        $historyMessage = $userMessage ?? '[Image uploaded]';
        $this->addToHistory($sessionId, 'user', $historyMessage);

        $systemPrompt = $this->buildImageAnalysisPrompt($currentSpecData);
        $history = $this->getConversationHistory($sessionId);

        try {
            $response = $this->callGeminiApi($systemPrompt, $history, [
                'mimeType' => $mimeType,
                'data' => $imageBase64,
            ]);

            if (isset($response['error'])) {
                return $response;
            }

            $result = $this->parseAssistantResponse($response);
            $this->addToHistory($sessionId, 'assistant', $result['message']);

            return $result;

        } catch (\Exception $e) {
            Log::error('GeminiCabinetAssistant image error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Call the Gemini API
     */
    protected function callGeminiApi(string $systemPrompt, array $history, ?array $image): array
    {
        $contents = [];

        // Add conversation history
        foreach ($history as $msg) {
            $parts = [['text' => $msg['content']]];

            $contents[] = [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => $parts,
            ];
        }

        // If we have an image, add it to the last user message
        if ($image && !empty($contents)) {
            $lastIdx = count($contents) - 1;
            if ($contents[$lastIdx]['role'] === 'user') {
                $contents[$lastIdx]['parts'][] = [
                    'inlineData' => $image,
                ];
            }
        }

        // Prepend system instruction
        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => $contents,
            'tools' => [
                [
                    'functionDeclarations' => $this->getToolDefinitions(),
                ],
            ],
            // Enable parallel function calling for multi-action support
            'toolConfig' => [
                'functionCallingConfig' => [
                    'mode' => 'AUTO', // Allow model to call multiple functions in one response
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topP' => 0.95,
                'maxOutputTokens' => 2048,
            ],
        ];

        $maxRetries = 3;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::timeout(60)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->geminiKey}",
                    $payload
                );

                if ($response->successful()) {
                    return $this->extractResponse($response->json());
                }

                $statusCode = $response->status();
                if ($statusCode === 429 && $attempt < $maxRetries) {
                    sleep(pow(2, $attempt));
                    continue;
                }

                return $this->errorResponse('API error: ' . ($response->json('error.message') ?? 'Unknown error'));

            } catch (\Exception $e) {
                if ($attempt < $maxRetries) {
                    sleep(pow(2, $attempt));
                    continue;
                }
                throw $e;
            }
        }

        return $this->errorResponse('All API attempts failed');
    }

    /**
     * Extract response from Gemini API
     */
    protected function extractResponse(array $response): array
    {
        $candidate = $response['candidates'][0] ?? null;
        if (!$candidate) {
            return $this->errorResponse('No response generated');
        }

        $content = $candidate['content'] ?? [];
        $parts = $content['parts'] ?? [];

        $textResponse = '';
        $functionCalls = [];

        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $textResponse .= $part['text'];
            }
            if (isset($part['functionCall'])) {
                $functionCalls[] = [
                    'name' => $part['functionCall']['name'],
                    'args' => $part['functionCall']['args'] ?? [],
                ];
            }
        }

        return [
            'text' => $textResponse,
            'functionCalls' => $functionCalls,
        ];
    }

    /**
     * Parse assistant response into commands and message
     */
    protected function parseAssistantResponse(array $response): array
    {
        $commands = [];
        $message = $response['text'] ?? '';

        // Process function calls
        foreach ($response['functionCalls'] ?? [] as $call) {
            $commands[] = $this->convertFunctionCallToCommand($call);
        }

        // Also check for inline commands in text (fallback)
        if (empty($commands) && !empty($message)) {
            $commands = $this->extractInlineCommands($message);
        }

        // If we have commands but no text response, generate a summary
        if (!empty($commands) && empty(trim($message))) {
            $message = $this->generateCommandSummary($commands);
        }

        return [
            'message' => $message,
            'commands' => $commands,
            'success' => true,
        ];
    }

    /**
     * Generate a summary message when AI only returns function calls
     */
    protected function generateCommandSummary(array $commands): string
    {
        $actions = [];
        $totalLF = 0;
        $pricingLevel = 2; // Default L2

        foreach ($commands as $cmd) {
            $action = $cmd['action'] ?? '';
            $data = $cmd['data'] ?? [];

            switch ($action) {
                case 'create_room':
                    $actions[] = "Created **{$data['name']}** room";
                    break;

                case 'create_location':
                    $level = $data['cabinet_level'] ?? 2;
                    $pricingLevel = (int) $level;
                    $price = self::PRICING_TIERS[$pricingLevel]['price'] ?? 298;
                    $actions[] = "Added **{$data['name']}** location (L{$level} @ \${$price}/LF)";
                    break;

                case 'create_run':
                    $runType = ucfirst($data['run_type'] ?? 'base');
                    $actions[] = "Added **{$runType} Run**";
                    break;

                case 'add_cabinet':
                    $cabinets = $data['cabinets'] ?? [];
                    foreach ($cabinets as $cab) {
                        $width = $cab['length_inches'] ?? 0;
                        $qty = $cab['quantity'] ?? 1;
                        $lf = ($width * $qty) / 12;
                        $totalLF += $lf;
                        $qtyStr = $qty > 1 ? " x{$qty}" : '';
                        $actions[] = "Added **{$cab['name']}**{$qtyStr} ({$lf}' LF)";
                    }
                    break;

                case 'delete':
                    $actions[] = "Deleted **{$data['name']}**";
                    break;

                case 'search_products':
                    $actions[] = "🔍 Searching products: **{$data['query']}**";
                    break;

                case 'reserve_product':
                    $productName = $data['product_name'] ?? "Product #{$data['product_id']}";
                    $qty = $data['quantity'] ?? 1;
                    $actions[] = "📦 Reserved **{$qty}x {$productName}**";
                    break;

                case 'check_availability':
                    $productName = $data['product_name'] ?? "Product #{$data['product_id']}";
                    $actions[] = "📋 Checking availability for **{$productName}**";
                    break;

                case 'get_product_details':
                    $productName = $data['product_name'] ?? "Product #{$data['product_id']}";
                    $actions[] = "📄 Fetching details for **{$productName}**";
                    break;

                // Time Clock actions
                case 'clock_in':
                    $name = $data['employee_name'] ?? 'you';
                    $actions[] = "⏰ Clocking **{$name}** in";
                    break;

                case 'clock_out':
                    $name = $data['employee_name'] ?? 'you';
                    $breakMins = $data['break_duration_minutes'] ?? 60;
                    $actions[] = "⏰ Clocking **{$name}** out ({$breakMins} min lunch)";
                    break;

                case 'get_clock_status':
                    $name = $data['employee_name'] ?? 'your';
                    $actions[] = "📊 Getting **{$name}** clock status";
                    break;

                case 'get_weekly_hours':
                    $name = $data['employee_name'] ?? 'your';
                    $actions[] = "📊 Getting **{$name}** weekly hours";
                    break;

                case 'add_manual_entry':
                    $name = $data['employee_name'] ?? 'you';
                    $date = $data['date'] ?? 'unknown date';
                    $actions[] = "📝 Adding manual entry for **{$name}** on {$date}";
                    break;

                case 'get_team_attendance':
                    $actions[] = "👥 Getting today's team attendance";
                    break;

                // Export actions
                case 'export_weekly_timesheet':
                    $name = $data['employee_name'] ?? 'your';
                    $format = $data['format'] ?? 'html';
                    $actions[] = "📋 Exporting **{$name}** weekly timesheet ({$format})";
                    break;

                case 'export_team_timesheet':
                    $format = $data['format'] ?? 'csv';
                    $actions[] = "📋 Exporting team weekly summary ({$format})";
                    break;
            }
        }

        $summary = implode("\n", array_map(fn($a) => "• {$a}", $actions));

        // Add pricing summary if we added cabinets
        if ($totalLF > 0) {
            $price = self::PRICING_TIERS[$pricingLevel]['price'] ?? 298;
            $total = round($totalLF * $price, 2);
            $summary .= "\n\n**Total:** {$totalLF}' LF × \${$price}/LF = **\${$total}**";
        }

        return $summary ?: "Actions executed successfully.";
    }

    /**
     * Convert Gemini function call to spec command
     */
    protected function convertFunctionCallToCommand(array $call): array
    {
        $name = $call['name'];
        $args = $call['args'];

        return match ($name) {
            'add_room' => [
                'action' => 'create_room',
                'data' => [
                    'name' => $args['name'] ?? 'New Room',
                    'room_type' => $args['room_type'] ?? 'kitchen',
                    'floor_number' => $args['floor_number'] ?? 1,
                ],
            ],
            'add_location' => [
                'action' => 'create_location',
                'data' => [
                    'room_name' => $args['room_name'] ?? null,
                    'room_path' => $args['room_path'] ?? null,
                    'name' => $args['name'] ?? 'New Location',
                    'location_type' => $args['location_type'] ?? 'wall',
                    'cabinet_level' => $args['cabinet_level'] ?? 2,
                ],
            ],
            'add_cabinet_run' => [
                'action' => 'create_run',
                'data' => [
                    'location_path' => $args['location_path'] ?? null,
                    'location_name' => $args['location_name'] ?? null,
                    'name' => $args['name'] ?? 'Cabinet Run',
                    'run_type' => $args['run_type'] ?? 'base',
                ],
            ],
            'add_cabinet', 'add_cabinets' => [
                'action' => 'add_cabinet',
                'data' => $this->normalizeCabinetData($args),
            ],
            'delete_entity' => [
                'action' => 'delete',
                'data' => [
                    'path' => $args['path'] ?? null,
                    'name' => $args['name'] ?? null,
                    'type' => $args['type'] ?? null,
                ],
            ],
            'update_pricing' => [
                'action' => 'update_pricing',
                'data' => [
                    'path' => $args['path'] ?? null,
                    'cabinet_level' => $args['cabinet_level'] ?? 2,
                ],
            ],
            'get_price_estimate' => [
                'action' => 'get_estimate',
                'data' => [],
            ],
            'suggest_layout' => [
                'action' => 'suggest_layout',
                'data' => [
                    'room_dimensions' => $args['room_dimensions'] ?? null,
                    'layout_type' => $args['layout_type'] ?? 'L-shaped',
                ],
            ],
            // Product/Inventory commands
            'search_products' => [
                'action' => 'search_products',
                'data' => [
                    'query' => $args['query'] ?? '',
                    'category' => $args['category'] ?? null,
                    'product_type' => $args['product_type'] ?? null,
                    'limit' => $args['limit'] ?? 10,
                ],
            ],
            'reserve_product' => [
                'action' => 'reserve_product',
                'data' => [
                    'product_id' => $args['product_id'] ?? null,
                    'product_name' => $args['product_name'] ?? null,
                    'quantity' => $args['quantity'] ?? 1,
                    'notes' => $args['notes'] ?? null,
                ],
            ],
            'check_product_availability' => [
                'action' => 'check_availability',
                'data' => [
                    'product_id' => $args['product_id'] ?? null,
                    'product_name' => $args['product_name'] ?? null,
                    'quantity_needed' => $args['quantity_needed'] ?? 1,
                ],
            ],
            'get_product_details' => [
                'action' => 'get_product_details',
                'data' => [
                    'product_id' => $args['product_id'] ?? null,
                    'product_name' => $args['product_name'] ?? null,
                ],
            ],
            // Time Clock commands
            'clock_in' => [
                'action' => 'clock_in',
                'data' => [
                    'employee_name' => $args['employee_name'] ?? null,
                    'notes' => $args['notes'] ?? null,
                ],
            ],
            'clock_out' => [
                'action' => 'clock_out',
                'data' => [
                    'employee_name' => $args['employee_name'] ?? null,
                    'break_duration_minutes' => $args['break_duration_minutes'] ?? 60,
                    'project_name' => $args['project_name'] ?? null,
                    'notes' => $args['notes'] ?? null,
                ],
            ],
            'get_clock_status' => [
                'action' => 'get_clock_status',
                'data' => [
                    'employee_name' => $args['employee_name'] ?? null,
                ],
            ],
            'get_weekly_hours' => [
                'action' => 'get_weekly_hours',
                'data' => [
                    'employee_name' => $args['employee_name'] ?? null,
                    'week_start' => $args['week_start'] ?? null,
                ],
            ],
            'add_manual_time_entry' => [
                'action' => 'add_manual_entry',
                'data' => [
                    'employee_name' => $args['employee_name'] ?? null,
                    'date' => $args['date'] ?? null,
                    'clock_in_time' => $args['clock_in_time'] ?? null,
                    'clock_out_time' => $args['clock_out_time'] ?? null,
                    'break_duration_minutes' => $args['break_duration_minutes'] ?? 60,
                    'project_name' => $args['project_name'] ?? null,
                    'notes' => $args['notes'] ?? 'Manual entry via AI assistant',
                ],
            ],
            'get_team_attendance' => [
                'action' => 'get_team_attendance',
                'data' => [],
            ],
            // Export tools
            'export_weekly_timesheet' => [
                'action' => 'export_weekly_timesheet',
                'data' => [
                    'employee_name' => $args['employee_name'] ?? null,
                    'week_start' => $args['week_start'] ?? null,
                    'format' => $args['format'] ?? 'html',
                ],
            ],
            'export_team_timesheet' => [
                'action' => 'export_team_timesheet',
                'data' => [
                    'week_start' => $args['week_start'] ?? null,
                    'format' => $args['format'] ?? 'csv',
                ],
            ],
            default => [
                'action' => 'unknown',
                'data' => $args,
            ],
        };
    }

    /**
     * Normalize cabinet data from AI response
     */
    protected function normalizeCabinetData(array $args): array
    {
        $cabinets = [];

        // Handle single cabinet or array of cabinets
        $items = isset($args['cabinets']) ? $args['cabinets'] : [$args];

        foreach ($items as $item) {
            $name = $item['name'] ?? '';
            $parsed = $this->parseCabinetName($name);

            $cabinets[] = [
                'run_path' => $item['run_path'] ?? null,
                'run_name' => $item['run_name'] ?? null,
                'name' => $name,
                'cabinet_type' => $parsed['type'] ?? ($item['cabinet_type'] ?? 'base'),
                'length_inches' => $parsed['width'] ?? ($item['length_inches'] ?? $item['width'] ?? 24),
                'depth_inches' => $item['depth_inches'] ?? ($parsed['default_depth'] ?? 24),
                'height_inches' => $item['height_inches'] ?? ($parsed['default_height'] ?? 34.5),
                'quantity' => $item['quantity'] ?? 1,
            ];
        }

        return ['cabinets' => $cabinets];
    }

    /**
     * Parse cabinet name to extract type and dimensions
     * Examples: B24, SB36, W3012, DB18, etc.
     */
    public function parseCabinetName(string $name): array
    {
        $name = strtoupper(trim($name));

        // Match pattern: PREFIX + WIDTH + optional HEIGHT
        // Examples: B24, SB36, W3012 (30" wide, 12" tall for wall), BBC42
        if (preg_match('/^([A-Z]+)(\d{2,3})(\d{2})?$/', $name, $matches)) {
            $prefix = $matches[1];
            $width = (int) $matches[2];
            $height = isset($matches[3]) ? (int) $matches[3] : null;

            $config = self::CABINET_PATTERNS[$prefix] ?? null;

            if ($config) {
                return [
                    'prefix' => $prefix,
                    'type' => $config['type'],
                    'width' => $width,
                    'height' => $height ?? $config['default_height'],
                    'default_depth' => $config['default_depth'],
                    'default_height' => $config['default_height'],
                ];
            }
        }

        return [
            'prefix' => null,
            'type' => 'base',
            'width' => null,
        ];
    }

    /**
     * Extract inline commands from text response (fallback)
     */
    protected function extractInlineCommands(string $text): array
    {
        $commands = [];

        // Look for JSON command blocks
        if (preg_match_all('/```json\s*(\{[^`]+\})\s*```/s', $text, $matches)) {
            foreach ($matches[1] as $json) {
                $decoded = json_decode($json, true);
                if ($decoded && isset($decoded['action'])) {
                    $commands[] = $decoded;
                }
            }
        }

        return $commands;
    }

    /**
     * Get conversation history for a session
     */
    public function getConversationHistory(string $sessionId): array
    {
        $key = "cabinet_ai_history_{$sessionId}";
        return Cache::get($key, []);
    }

    /**
     * Add message to conversation history
     */
    public function addToHistory(string $sessionId, string $role, string $content): void
    {
        $key = "cabinet_ai_history_{$sessionId}";
        $history = Cache::get($key, []);

        $history[] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => now()->toIso8601String(),
        ];

        // Keep last 20 messages
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        Cache::put($key, $history, now()->addHours(4));
    }

    /**
     * Clear conversation history
     */
    public function clearHistory(string $sessionId): void
    {
        Cache::forget("cabinet_ai_history_{$sessionId}");
    }

    /**
     * Clear conversation history (alias for API consistency)
     */
    public function clearConversationHistory(string $sessionId): void
    {
        $this->clearHistory($sessionId);
    }

    /**
     * Build system prompt based on mode and current spec
     */
    protected function buildSystemPrompt(array $currentSpecData, string $mode): string
    {
        $specSummary = $this->summarizeSpecData($currentSpecData);
        $contextHints = $this->buildContextHints($currentSpecData);
        $pricingInfo = $this->buildPricingInfo();

        $modeInstructions = match ($mode) {
            'guided' => $this->getGuidedModeInstructions(),
            default => $this->getQuickModeInstructions(),
        };

        return <<<PROMPT
You are a cabinet specification assistant for TCS Woodwork. Be SMART about understanding what users want.

{$modeInstructions}

CABINET SHORTHAND (parse these automatically):
- B24 = Base cabinet 24" wide
- SB36 = Sink base 36" wide
- DB18 = Drawer base 18" wide
- W3012 = Wall cabinet 30" wide, 12" tall
- BBC42 = Blind base corner 42"
- TP24 = Tall pantry 24" wide
- VS48 = Vanity sink 48" wide
- "x2" or "×2" = quantity 2 (e.g., "B36 x2" = two B36 cabinets)

PRICING (per linear foot):
{$pricingInfo}

CURRENT SPEC STATE:
{$specSummary}

CONTEXT HINTS (use these to make smart decisions):
{$contextHints}

SMART INFERENCE RULES - BE FLEXIBLE:
1. If user says "add kitchen" or just "kitchen" → create a Kitchen room
2. If user says "add vanity" without a room → ask which room OR create one if obvious from context
3. If user gives cabinet codes (B24, SB36) without context → add to MOST RECENT run, or create structure if none exists
4. If user mentions pricing level (L1-L5) → remember it for subsequent locations
5. "Base cabinets" or "base run" → create base cabinet run
6. "Wall cabinets" or "wall run" → create wall cabinet run
7. If spec is EMPTY and user gives cabinets → create sensible defaults (Kitchen → Base Location L2 → Base Run)
8. ALWAYS parse cabinet shorthand: "B24, B36 x2, SB36" = 4 cabinets total

HIERARCHY UNDERSTANDING:
Room → Location (with pricing level) → Cabinet Run (base/wall/tall) → Individual Cabinets

When creating structure:
- Room: just needs a name
- Location: needs a name + cabinet_level (default L2)
- Run: needs run_type (base, wall, tall)
- Cabinet: needs name code (like B24)

CRITICAL - EXECUTE ALL ACTIONS:
You MUST call ALL required functions to complete the user's request. DO NOT just describe what you'll do - ACTUALLY DO IT.

For a full kitchen request like "Kitchen with sink wall (upper and lower runs with cabinets)":
1. Call add_room for Kitchen
2. Call add_location for Sink Wall
3. Call add_cabinet_run for Lower Run
4. Call add_cabinets with ALL lower cabinets
5. Call add_cabinet_run for Upper Run
6. Call add_cabinets with ALL upper cabinets

Make ALL these function calls IN THE SAME RESPONSE. Do not stop after the first one.
Do not say "I'll do this" - just do it by making the function calls.

PRODUCT SEARCH & INVENTORY:
You can also help users find and reserve products:
- "find soft close hinges" → search_products(query="soft close hinges")
- "search for Blum slides" → search_products(query="Blum slides")
- "put 10 hinges on hold" → reserve_product(product_name="...", quantity=10)
- "check if we have plywood in stock" → check_product_availability or search_products
- "need 5 sheets of maple plywood for this project" → search then reserve

When reserving products, ALWAYS confirm with the user first by showing:
1. Product name and SKU
2. Current price
3. Available quantity
4. Ask "Should I reserve this?"

TIME CLOCK CAPABILITIES:
You can help employees with time tracking:
- "clock me in" or "start my day" → clock_in()
- "clock me out" or "I'm done" → clock_out(break_duration_minutes=60)
- "am I clocked in?" or "what's my status?" → get_clock_status()
- "how many hours this week?" → get_weekly_hours()
- "who's in today?" or "team attendance" → get_team_attendance()
- "I forgot to clock in yesterday" → add_manual_time_entry(...)

TCS Schedule: Mon-Thu 8am-5pm, 1 hour lunch, 32 hours/week target

When handling time clock requests:
1. If employee name not specified, assume it's the current user
2. For clock out, confirm lunch break duration (default 60 min)
3. For manual entries, always include a note explaining why
4. After clock actions, show current status (hours today, weekly total)

ALWAYS in your response text (AFTER the function calls):
- Summarize what you created/searched/reserved
- For cabinets: Show linear feet calculation (inches ÷ 12) and price estimate (LF × $/LF)
- For products: Show product details and availability
PROMPT;
    }

    /**
     * Build context hints for smarter inference
     */
    protected function buildContextHints(array $specData): string
    {
        if (empty($specData)) {
            return "- Spec is EMPTY - if user gives cabinets, create a sensible default structure first\n- Default to 'Kitchen' room if not specified\n- Default to L2 pricing (\$298/LF)";
        }

        $hints = [];

        // Find most recent room
        $lastRoom = end($specData);
        if ($lastRoom) {
            $hints[] = "- Most recent room: \"{$lastRoom['name']}\" (use this if user doesn't specify)";

            // Find most recent location in that room
            $locations = $lastRoom['children'] ?? [];
            if (!empty($locations)) {
                $lastLocation = end($locations);
                $level = $lastLocation['cabinet_level'] ?? 2;
                $hints[] = "- Most recent location: \"{$lastLocation['name']}\" (L{$level})";

                // Find most recent run
                $runs = $lastLocation['children'] ?? [];
                if (!empty($runs)) {
                    $lastRun = end($runs);
                    $hints[] = "- Most recent run: \"{$lastRun['name']}\" - ADD CABINETS HERE if user doesn't specify";
                } else {
                    $hints[] = "- No runs yet in \"{$lastLocation['name']}\" - create one if user gives cabinets";
                }
            } else {
                $hints[] = "- No locations yet in \"{$lastRoom['name']}\" - create location + run if user gives cabinets";
            }
        }

        $roomCount = count($specData);
        $hints[] = "- Total rooms: {$roomCount}";

        return implode("\n", $hints);
    }

    /**
     * Build system prompt for image analysis
     */
    protected function buildImageAnalysisPrompt(array $currentSpecData): string
    {
        $specSummary = $this->summarizeSpecData($currentSpecData);
        $pricingInfo = $this->buildPricingInfo();

        return <<<PROMPT
You are a cabinet specification assistant analyzing an image for TCS Woodwork.

Analyze the uploaded image. It could be:
1. Floor plan - identify room dimensions and cabinet layout opportunities
2. Elevation drawing - identify specific cabinet placements and sizes
3. Cabinet photo - identify cabinet types and approximate dimensions
4. Sketch or note - interpret hand-drawn layouts

For floor plans and elevations:
- Estimate linear feet of cabinets visible
- Identify base, wall, and tall cabinet runs
- Note any special cabinets (corners, sinks, appliance openings)

For cabinet photos:
- Identify cabinet types and estimate widths
- Note door styles, hardware, finish if visible

PRICING TIERS (per linear foot):
{$pricingInfo}

CURRENT SPECIFICATION:
{$specSummary}

After analysis, suggest specific additions to the cabinet spec.
Use function calls to add items if the user confirms.
Always ask before making changes.
PROMPT;
    }

    /**
     * Process CAD elevation image specifically for face frame analysis
     */
    public function analyzeFaceFrame(
        string $imageBase64,
        string $mimeType,
        string $sessionId
    ): array {
        if (empty($this->geminiKey)) {
            return $this->errorResponse('Gemini API key not configured');
        }

        $systemPrompt = $this->buildFaceFrameAnalysisPrompt();
        $history = [['role' => 'user', 'content' => 'Analyze this CAD elevation for face frame configuration.']];

        try {
            $response = $this->callGeminiApiForFaceFrame($systemPrompt, $history, [
                'mimeType' => $mimeType,
                'data' => $imageBase64,
            ]);

            if (isset($response['error'])) {
                return $response;
            }

            // Parse JSON from response
            $result = $this->parseFaceFrameResponse($response);

            return $result;

        } catch (\Exception $e) {
            Log::error('GeminiCabinetAssistant face frame analysis error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Build prompt specifically for face frame analysis from CAD elevation
     */
    protected function buildFaceFrameAnalysisPrompt(): string
    {
        return <<<'PROMPT'
You are a cabinet construction expert analyzing a CAD front elevation drawing.
Analyze this image and extract the FACE FRAME configuration.

## What to Look For

### 1. FACE FRAME PRESENCE
Look for vertical and horizontal frame members visible around the cabinet opening:
- **STILES**: Vertical members on left and right edges of the cabinet face
- **TOP RAIL**: Horizontal member at the top of the face frame
- **BOTTOM RAIL**: Horizontal member at the bottom (MAY BE ABSENT for sink cabinets)
- **INTERMEDIATE RAILS**: Horizontal dividers between drawer/door openings

### 2. CABINET TYPE INDICATORS
Determine the cabinet type by looking for:
- **SINK BASE**: Plumbing cutout, U-shaped drawer around sink, NO bottom rail
- **VANITY SINK**: Similar to sink base, bathroom context
- **NORMAL BASE**: Has bottom rail, standard drawer/door configuration
- **DRAWER BASE**: Multiple drawer openings stacked vertically

### 3. OPENING CONFIGURATION
Count and identify:
- Number of drawer openings (horizontal openings with drawer pulls)
- Number of door openings (larger rectangular areas, may have hinges indicated)
- False fronts (drawer-like fronts that don't open, often at top of sink bases)
- U-shaped openings (indicates sink cutout behind)

### 4. DIMENSIONS TO EXTRACT (if visible)
- Overall cabinet width
- Overall cabinet height
- Individual drawer/door heights
- Face frame stile width (typically 1.5" - 1.75")
- Face frame rail width (typically 1.5")

## TCS Woodwork Rules

1. **SINK BASE RULE**: If you see a U-shaped drawer or plumbing cutout:
   - `has_bottom_rail` = false
   - `cabinet_type` = "sink_base" or "vanity_sink"

2. **BOTTOM RAIL**: Only true if you clearly see a horizontal frame member at the BOTTOM (above toe kick).

3. **INTERMEDIATE RAILS**: Count horizontal rails BETWEEN openings (not top/bottom rails).
   - 2 openings = 1 intermediate rail
   - 3 openings = 2 intermediate rails

## Response Format

Respond ONLY with valid JSON (no markdown, no explanation before/after):

{
  "has_face_frame": true,
  "cabinet_type": "vanity_sink",
  "face_frame_config": {
    "has_left_stile": true,
    "has_right_stile": true,
    "has_top_rail": true,
    "has_bottom_rail": false,
    "intermediate_rail_count": 1,
    "center_stile_count": 0,
    "stile_width_inches": 1.75,
    "rail_width_inches": 1.5
  },
  "openings": [
    {
      "type": "u_shaped_drawer",
      "position": "top",
      "height_inches": null,
      "width_inches": 37.8125,
      "notes": "U-shaped drawer around sink"
    },
    {
      "type": "drawer",
      "position": "bottom",
      "height_inches": null,
      "width_inches": 37.8125,
      "notes": "Standard drawer"
    }
  ],
  "dimensions": {
    "overall_width_inches": 41.3125,
    "overall_height_inches": 32.75,
    "toe_kick_height_inches": 4.0
  },
  "reasoning": "Brief explanation of why you determined this configuration"
}
PROMPT;
    }

    /**
     * Call Gemini API for face frame analysis (simpler, no function calling)
     */
    protected function callGeminiApiForFaceFrame(string $systemPrompt, array $history, ?array $image): array
    {
        $contents = [];

        foreach ($history as $msg) {
            $parts = [['text' => $msg['content']]];
            $contents[] = [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => $parts,
            ];
        }

        // Add image to the last user message
        if ($image && !empty($contents)) {
            $lastIdx = count($contents) - 1;
            if ($contents[$lastIdx]['role'] === 'user') {
                $contents[$lastIdx]['parts'][] = [
                    'inlineData' => $image,
                ];
            }
        }

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.2, // Lower for more deterministic JSON output
                'topP' => 0.8,
                'maxOutputTokens' => 2048,
            ],
        ];

        try {
            $response = Http::timeout(60)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->geminiKey}",
                $payload
            );

            if ($response->successful()) {
                $data = $response->json();
                $candidate = $data['candidates'][0] ?? null;
                if (!$candidate) {
                    return $this->errorResponse('No response generated');
                }

                $content = $candidate['content'] ?? [];
                $parts = $content['parts'] ?? [];
                $textResponse = '';
                foreach ($parts as $part) {
                    if (isset($part['text'])) {
                        $textResponse .= $part['text'];
                    }
                }

                return ['text' => $textResponse];
            }

            return $this->errorResponse('API error: ' . ($response->json('error.message') ?? 'Unknown error'));

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Parse face frame analysis JSON response
     */
    protected function parseFaceFrameResponse(array $response): array
    {
        $text = $response['text'] ?? '';

        // Try to extract JSON from the response
        // Remove any markdown code blocks if present
        $text = preg_replace('/```json\s*/i', '', $text);
        $text = preg_replace('/```\s*/i', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Failed to parse JSON response: ' . json_last_error_msg(),
                'raw_response' => $text,
            ];
        }

        // Validate against TCS rules
        $validation = $this->validateFaceFrameAnalysis($decoded);

        return [
            'success' => true,
            'analysis' => $decoded,
            'validation' => $validation,
        ];
    }

    /**
     * Validate face frame analysis against TCS construction rules
     */
    protected function validateFaceFrameAnalysis(array $analysis): array
    {
        $violations = [];
        $warnings = [];

        $cabinetType = $analysis['cabinet_type'] ?? '';
        $hasBottomRail = $analysis['face_frame_config']['has_bottom_rail'] ?? true;

        $sinkTypes = ['sink_base', 'vanity_sink', 'kitchen_sink', 'base_sink'];

        // Rule 1: Sink cabinets should NOT have bottom rail
        if (in_array($cabinetType, $sinkTypes) && $hasBottomRail) {
            $violations[] = [
                'rule' => 'SINK_NO_BOTTOM_RAIL',
                'message' => "Sink cabinet type '{$cabinetType}' should NOT have a bottom rail",
                'suggestion' => 'Set has_bottom_rail to false for sink cabinets',
            ];
        }

        // Rule 2: Non-sink cabinets typically have bottom rail
        if (!in_array($cabinetType, $sinkTypes) && !$hasBottomRail) {
            $warnings[] = [
                'rule' => 'BASE_HAS_BOTTOM_RAIL',
                'message' => "Non-sink cabinet type '{$cabinetType}' typically has a bottom rail",
                'suggestion' => 'Verify this is not a sink cabinet',
            ];
        }

        // Rule 3: U-shaped drawer = sink cabinet
        $openings = $analysis['openings'] ?? [];
        $hasUShapedDrawer = false;
        foreach ($openings as $opening) {
            if (($opening['type'] ?? '') === 'u_shaped_drawer') {
                $hasUShapedDrawer = true;
                break;
            }
        }

        if ($hasUShapedDrawer && !in_array($cabinetType, $sinkTypes)) {
            $violations[] = [
                'rule' => 'U_SHAPED_IS_SINK',
                'message' => 'U-shaped drawer detected but cabinet not marked as sink type',
                'suggestion' => "Change cabinet_type to 'sink_base' or 'vanity_sink'",
            ];
        }

        // Rule 4: Stile width should be 1.25" - 2.5"
        $stileWidth = $analysis['face_frame_config']['stile_width_inches'] ?? null;
        if ($stileWidth !== null && ($stileWidth < 1.25 || $stileWidth > 2.5)) {
            $warnings[] = [
                'rule' => 'STILE_WIDTH_RANGE',
                'message' => "Stile width {$stileWidth}\" is outside typical range (1.25\" - 2.5\")",
                'suggestion' => 'TCS standard is 1.5" or 1.75" stiles',
            ];
        }

        // Rule 5: Intermediate rails = openings - 1
        $intermediateRails = $analysis['face_frame_config']['intermediate_rail_count'] ?? 0;
        $expectedRails = max(0, count($openings) - 1);

        if ($intermediateRails !== $expectedRails) {
            $warnings[] = [
                'rule' => 'INTERMEDIATE_RAIL_COUNT',
                'message' => "Intermediate rail count ({$intermediateRails}) doesn't match expected ({$expectedRails}) for " . count($openings) . " openings",
                'suggestion' => 'Intermediate rails = opening count - 1',
            ];
        }

        return [
            'valid' => empty($violations),
            'violations' => $violations,
            'warnings' => $warnings,
        ];
    }

    /**
     * Convert face frame analysis to configurator input format
     */
    public static function faceFrameAnalysisToConfig(array $analysis): array
    {
        $config = $analysis['face_frame_config'] ?? [];
        $dimensions = $analysis['dimensions'] ?? [];

        return [
            'has_face_frame' => $analysis['has_face_frame'] ?? true,
            'cabinet_type' => $analysis['cabinet_type'] ?? 'base',
            'stile_width' => $config['stile_width_inches'] ?? 1.5,
            'rail_width' => $config['rail_width_inches'] ?? 1.5,
            'has_bottom_rail' => $config['has_bottom_rail'] ?? true,
            'has_top_rail' => $config['has_top_rail'] ?? true,
            'intermediate_rail_count' => $config['intermediate_rail_count'] ?? 0,
            'center_stile_count' => $config['center_stile_count'] ?? 0,
            'openings' => array_map(function ($opening) {
                return [
                    'type' => $opening['type'] ?? 'drawer',
                    'position' => $opening['position'] ?? 'middle',
                    'height_inches' => $opening['height_inches'],
                    'width_inches' => $opening['width_inches'],
                ];
            }, $analysis['openings'] ?? []),
            'cabinet_width_inches' => $dimensions['overall_width_inches'],
            'cabinet_height_inches' => $dimensions['overall_height_inches'],
            'toe_kick_height_inches' => $dimensions['toe_kick_height_inches'] ?? 4.0,
        ];
    }

    /**
     * Summarize current spec data for context
     */
    protected function summarizeSpecData(array $specData): string
    {
        if (empty($specData)) {
            return "No rooms or cabinets configured yet.";
        }

        $lines = [];
        $totalLF = 0;
        $totalPrice = 0;

        foreach ($specData as $room) {
            $roomLF = $room['linear_feet'] ?? 0;
            $roomPrice = $room['estimated_price'] ?? 0;
            $totalLF += $roomLF;
            $totalPrice += $roomPrice;

            $locationCount = count($room['children'] ?? []);
            $lines[] = "- {$room['name']}: {$locationCount} location(s), {$roomLF} LF, \${$roomPrice}";
        }

        $lines[] = "";
        $lines[] = "TOTALS: {$totalLF} LF, \${$totalPrice} estimated";

        return implode("\n", $lines);
    }

    /**
     * Build pricing tier info string
     */
    protected function buildPricingInfo(): string
    {
        $lines = [];
        foreach (self::PRICING_TIERS as $level => $info) {
            $lines[] = "L{$level} ({$info['name']}): \${$info['price']}/LF";
        }
        return implode("\n", $lines);
    }

    /**
     * Quick mode instructions
     */
    protected function getQuickModeInstructions(): string
    {
        return <<<TEXT
MODE: Quick Command - EXECUTE EVERYTHING IN ONE RESPONSE

CRITICAL: When a user gives you a multi-part request, you MUST make ALL function calls in a single response.
DO NOT describe what you'll do - EXECUTE IT by calling the functions.

NATURAL LANGUAGE → CABINET CODES:
- "drawer base" or "cabinet with drawers" → DB
- "base cabinet" or "shelf with door" → B
- "sink" → SB
- "corner" → BBC
- "vanity" → V or VS
- "pantry" or "tall pantry" → TP
- "wall cabinet" → W

SIZE DEFAULTS (if not specified):
- DB = 18", B = 24", SB = 36", BBC = 42", W = 30", TP = 24"

EXECUTION ORDER for multi-part requests:
1. add_room (create the room first)
2. add_location (for EACH location, with cabinet_level)
3. add_cabinet_run (for EACH run, specifying which location by name)
4. add_cabinets (for EACH set of cabinets, specifying which run by name)

EXAMPLE - User says: "Kitchen with L3 pricing, sink wall with base run containing SB36, B24"
YOU MUST CALL ALL 4 FUNCTIONS:
- add_room(name="Kitchen")
- add_location(room_name="Kitchen", name="Sink Wall", cabinet_level=3)
- add_cabinet_run(location_name="Sink Wall", name="Base Run", run_type="base")
- add_cabinets(run_name="Base Run", cabinets=[{name:"SB36", length_inches:36}, {name:"B24", length_inches:24}])

After all function calls, your text response should summarize:
- What was created
- Total linear feet
- Price estimate at the specified level
TEXT;
    }

    /**
     * Guided mode instructions
     */
    protected function getGuidedModeInstructions(): string
    {
        return <<<TEXT
MODE: Guided Conversation
Walk the user through building specs step by step while ALWAYS providing pricing context.

Flow:
1. Ask what room they're working on
2. Ask about layout (which walls have cabinets)
3. For each location, ask about:
   - Cabinet level (pricing tier) - explain the price difference!
   - Types of cabinets (base, wall, tall)
4. Help them specify individual cabinets
5. After each step, show a running total of linear feet and estimated price

ALWAYS include pricing in your responses:
- "That 72" vanity at L3 pricing would be 6 LF × $348 = $2,088"
- "Running total: 12 LF = $3,576 at Level 2 pricing"

Be helpful and explain options. When in doubt, suggest L2 (Standard) pricing.
You CAN execute multiple function calls in one response when the user provides enough info.
TEXT;
    }

    /**
     * Get tool/function definitions for Gemini
     */
    protected function getToolDefinitions(): array
    {
        return [
            [
                'name' => 'add_room',
                'description' => 'Add a new room to the cabinet specification',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'name' => [
                            'type' => 'STRING',
                            'description' => 'Room name (e.g., Kitchen, Master Bath, Laundry)',
                        ],
                        'room_type' => [
                            'type' => 'STRING',
                            'description' => 'Type of room: kitchen, bathroom, laundry, garage, closet, other',
                        ],
                        'floor_number' => [
                            'type' => 'INTEGER',
                            'description' => 'Floor number (1 = ground floor)',
                        ],
                    ],
                    'required' => ['name'],
                ],
            ],
            [
                'name' => 'add_location',
                'description' => 'Add a location (wall/area) within a room for cabinets',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'room_name' => [
                            'type' => 'STRING',
                            'description' => 'Name of the room to add location to',
                        ],
                        'room_path' => [
                            'type' => 'STRING',
                            'description' => 'Path to room (e.g., "0" for first room)',
                        ],
                        'name' => [
                            'type' => 'STRING',
                            'description' => 'Location name (e.g., North Wall, Sink Wall, Island)',
                        ],
                        'location_type' => [
                            'type' => 'STRING',
                            'description' => 'Type: wall, island, peninsula, corner',
                        ],
                        'cabinet_level' => [
                            'type' => 'INTEGER',
                            'description' => 'Pricing tier 1-5 (L1=Basic $225/LF to L5=Custom $550/LF)',
                        ],
                    ],
                    'required' => ['name', 'cabinet_level'],
                ],
            ],
            [
                'name' => 'add_cabinet_run',
                'description' => 'Add a cabinet run (base, wall, or tall) to a location',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'location_path' => [
                            'type' => 'STRING',
                            'description' => 'Path to location (e.g., "0.children.0")',
                        ],
                        'location_name' => [
                            'type' => 'STRING',
                            'description' => 'Name of location to add run to',
                        ],
                        'name' => [
                            'type' => 'STRING',
                            'description' => 'Run name (e.g., Base Run, Wall Run)',
                        ],
                        'run_type' => [
                            'type' => 'STRING',
                            'description' => 'Type: base, wall, tall',
                        ],
                    ],
                    'required' => ['name', 'run_type'],
                ],
            ],
            [
                'name' => 'add_cabinets',
                'description' => 'Add one or more cabinets to a run',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'run_path' => [
                            'type' => 'STRING',
                            'description' => 'Path to cabinet run',
                        ],
                        'run_name' => [
                            'type' => 'STRING',
                            'description' => 'Name of run to add cabinets to',
                        ],
                        'cabinets' => [
                            'type' => 'ARRAY',
                            'description' => 'List of cabinets to add',
                            'items' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'name' => [
                                        'type' => 'STRING',
                                        'description' => 'Cabinet code (e.g., B24, SB36, W3012)',
                                    ],
                                    'cabinet_type' => [
                                        'type' => 'STRING',
                                        'description' => 'Type: base, sink_base, wall, tall, vanity, etc.',
                                    ],
                                    'length_inches' => [
                                        'type' => 'NUMBER',
                                        'description' => 'Width in inches',
                                    ],
                                    'quantity' => [
                                        'type' => 'INTEGER',
                                        'description' => 'Number of this cabinet (default 1)',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'required' => ['cabinets'],
                ],
            ],
            [
                'name' => 'delete_entity',
                'description' => 'Delete a room, location, run, or cabinet',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'path' => [
                            'type' => 'STRING',
                            'description' => 'Path to entity to delete',
                        ],
                        'name' => [
                            'type' => 'STRING',
                            'description' => 'Name of entity to delete (alternative to path)',
                        ],
                        'type' => [
                            'type' => 'STRING',
                            'description' => 'Type of entity: room, location, run, cabinet',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'update_pricing',
                'description' => 'Update the pricing tier (cabinet level) for a location',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'path' => [
                            'type' => 'STRING',
                            'description' => 'Path to location',
                        ],
                        'cabinet_level' => [
                            'type' => 'INTEGER',
                            'description' => 'New pricing tier 1-5',
                        ],
                    ],
                    'required' => ['cabinet_level'],
                ],
            ],
            [
                'name' => 'get_price_estimate',
                'description' => 'Get current total price estimate for the specification',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => (object)[],
                ],
            ],
            [
                'name' => 'suggest_layout',
                'description' => 'Suggest a cabinet layout based on room dimensions',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'room_dimensions' => [
                            'type' => 'STRING',
                            'description' => 'Room dimensions (e.g., "12x14 feet")',
                        ],
                        'layout_type' => [
                            'type' => 'STRING',
                            'description' => 'Layout style: galley, L-shaped, U-shaped, island',
                        ],
                    ],
                ],
            ],
            // Product/Inventory tools
            [
                'name' => 'search_products',
                'description' => 'Search for products in inventory by name, reference, barcode, or category. Use this when user wants to find products to purchase or reserve.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'query' => [
                            'type' => 'STRING',
                            'description' => 'Search term (product name, SKU, barcode, or description)',
                        ],
                        'category' => [
                            'type' => 'STRING',
                            'description' => 'Optional category filter (e.g., "hinges", "slides", "plywood", "hardware")',
                        ],
                        'product_type' => [
                            'type' => 'STRING',
                            'description' => 'Type filter: goods, service, consumable',
                        ],
                        'limit' => [
                            'type' => 'INTEGER',
                            'description' => 'Maximum results to return (default 10)',
                        ],
                    ],
                    'required' => ['query'],
                ],
            ],
            [
                'name' => 'reserve_product',
                'description' => 'Reserve/hold a product for the current project. This puts inventory on hold so it won\'t be used elsewhere.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'product_id' => [
                            'type' => 'INTEGER',
                            'description' => 'Product ID to reserve',
                        ],
                        'product_name' => [
                            'type' => 'STRING',
                            'description' => 'Product name (alternative to ID)',
                        ],
                        'quantity' => [
                            'type' => 'NUMBER',
                            'description' => 'Quantity to reserve',
                        ],
                        'notes' => [
                            'type' => 'STRING',
                            'description' => 'Optional notes for the reservation',
                        ],
                    ],
                    'required' => ['quantity'],
                ],
            ],
            [
                'name' => 'check_product_availability',
                'description' => 'Check if a product has sufficient inventory available',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'product_id' => [
                            'type' => 'INTEGER',
                            'description' => 'Product ID to check',
                        ],
                        'product_name' => [
                            'type' => 'STRING',
                            'description' => 'Product name (alternative to ID)',
                        ],
                        'quantity_needed' => [
                            'type' => 'NUMBER',
                            'description' => 'Quantity to check availability for',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_product_details',
                'description' => 'Get detailed information about a specific product including price, description, and inventory',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'product_id' => [
                            'type' => 'INTEGER',
                            'description' => 'Product ID',
                        ],
                        'product_name' => [
                            'type' => 'STRING',
                            'description' => 'Product name (alternative to ID)',
                        ],
                    ],
                ],
            ],
            // Time Clock tools
            [
                'name' => 'clock_in',
                'description' => 'Clock in an employee. Use when someone says "clock me in", "I\'m starting work", "punch in", etc.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'employee_name' => [
                            'type' => 'STRING',
                            'description' => 'Employee name (optional - uses current user if not specified)',
                        ],
                        'notes' => [
                            'type' => 'STRING',
                            'description' => 'Optional notes for this clock entry',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'clock_out',
                'description' => 'Clock out an employee. Use when someone says "clock me out", "I\'m done for the day", "punch out", etc.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'employee_name' => [
                            'type' => 'STRING',
                            'description' => 'Employee name (optional - uses current user if not specified)',
                        ],
                        'break_duration_minutes' => [
                            'type' => 'INTEGER',
                            'description' => 'Break/lunch duration in minutes (default 60)',
                        ],
                        'project_name' => [
                            'type' => 'STRING',
                            'description' => 'Project to assign time to (optional)',
                        ],
                        'notes' => [
                            'type' => 'STRING',
                            'description' => 'Optional notes for this clock entry',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_clock_status',
                'description' => 'Get current clock status for an employee. Shows if clocked in, today\'s hours, weekly hours, etc.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'employee_name' => [
                            'type' => 'STRING',
                            'description' => 'Employee name (optional - uses current user if not specified)',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_weekly_hours',
                'description' => 'Get weekly timesheet summary for an employee. Shows hours worked each day (Mon-Thu) and totals.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'employee_name' => [
                            'type' => 'STRING',
                            'description' => 'Employee name (optional - uses current user if not specified)',
                        ],
                        'week_start' => [
                            'type' => 'STRING',
                            'description' => 'Start of week date (YYYY-MM-DD format, defaults to current week)',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'add_manual_time_entry',
                'description' => 'Add a manual time entry for a missed clock. Use when employee forgot to clock in/out.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'employee_name' => [
                            'type' => 'STRING',
                            'description' => 'Employee name (optional - uses current user if not specified)',
                        ],
                        'date' => [
                            'type' => 'STRING',
                            'description' => 'Date of the entry (YYYY-MM-DD format)',
                        ],
                        'clock_in_time' => [
                            'type' => 'STRING',
                            'description' => 'Clock in time (HH:MM format, e.g., "08:00")',
                        ],
                        'clock_out_time' => [
                            'type' => 'STRING',
                            'description' => 'Clock out time (HH:MM format, e.g., "17:00")',
                        ],
                        'break_duration_minutes' => [
                            'type' => 'INTEGER',
                            'description' => 'Break/lunch duration in minutes (default 60)',
                        ],
                        'project_name' => [
                            'type' => 'STRING',
                            'description' => 'Project to assign time to (optional)',
                        ],
                        'notes' => [
                            'type' => 'STRING',
                            'description' => 'Reason for manual entry',
                        ],
                    ],
                    'required' => ['date', 'clock_in_time', 'clock_out_time'],
                ],
            ],
            [
                'name' => 'get_team_attendance',
                'description' => 'Get today\'s attendance for all employees. Shows who is clocked in, who is late, who hasn\'t arrived.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => (object)[],
                ],
            ],
            // Export tools
            [
                'name' => 'export_weekly_timesheet',
                'description' => 'Export/print a weekly timesheet for an employee. Generates a printable HTML or CSV timesheet.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'employee_name' => [
                            'type' => 'STRING',
                            'description' => 'Employee name (optional - uses current user if not specified)',
                        ],
                        'week_start' => [
                            'type' => 'STRING',
                            'description' => 'Start of week date (YYYY-MM-DD format, defaults to current week)',
                        ],
                        'format' => [
                            'type' => 'STRING',
                            'description' => 'Export format: "html" for printable, "csv" for spreadsheet (default: html)',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'export_team_timesheet',
                'description' => 'Export team weekly timesheet summary. Shows all employees hours for payroll.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'week_start' => [
                            'type' => 'STRING',
                            'description' => 'Start of week date (YYYY-MM-DD format, defaults to current week)',
                        ],
                        'format' => [
                            'type' => 'STRING',
                            'description' => 'Export format: "csv" for spreadsheet, "json" for data (default: csv)',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create error response
     */
    protected function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'commands' => [],
            'error' => $message,
        ];
    }

    /**
     * Check if service is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->geminiKey);
    }

    /**
     * Calculate linear feet for a cabinet
     */
    public static function calculateLinearFeet(float $widthInches, int $quantity = 1): float
    {
        return round(($widthInches / 12) * $quantity, 2);
    }

    /**
     * Calculate price for linear feet at a given level
     */
    public static function calculatePrice(float $linearFeet, int $cabinetLevel): float
    {
        $pricePerLF = self::PRICING_TIERS[$cabinetLevel]['price'] ?? self::PRICING_TIERS[2]['price'];
        return round($linearFeet * $pricePerLF, 2);
    }
}
