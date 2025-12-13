<?php

namespace Webkul\Project\Livewire;

use App\Services\ClockingService;
use App\Services\GeminiCabinetAssistantService;
use App\Services\ProductSearchService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Webkul\Chatter\Models\Message;
use Webkul\Project\Models\Project;

class CabinetAiAssistant extends Component
{
    use WithFileUploads;

    // UI State
    public bool $isMinimized = true;
    public string $mode = 'quick'; // 'quick' or 'guided'
    public string $inputMessage = '';
    public array $messages = [];
    public bool $isProcessing = false;
    public int $unreadCount = 0;

    // Context
    public int $projectId;
    public array $specData = [];
    public string $sessionId;

    // File upload
    public $uploadedImage = null;
    public ?string $imagePreview = null;

    protected $listeners = [
        'spec-data-updated' => 'updateSpecData',
    ];

    public function mount(int $projectId, array $specData = [])
    {
        $this->projectId = $projectId;
        $this->specData = $specData;
        $this->sessionId = 'cabinet_ai_' . $this->projectId . '_' . session()->getId();

        // Load existing history
        $service = new GeminiCabinetAssistantService();
        $history = $service->getConversationHistory($this->sessionId);

        foreach ($history as $msg) {
            $this->messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
                'timestamp' => $msg['timestamp'] ?? now()->toIso8601String(),
            ];
        }

        // Add welcome message if empty
        if (empty($this->messages)) {
            $this->addWelcomeMessage();
        }
    }

    /**
     * Update spec data from CabinetSpecBuilder
     */
    #[On('spec-data-updated')]
    public function updateSpecData(array $data): void
    {
        $this->specData = $data;
    }

    /**
     * Send a message
     */
    public function sendMessage(): void
    {
        $message = trim($this->inputMessage);

        if (empty($message) && !$this->uploadedImage) {
            return;
        }

        $this->isProcessing = true;
        $this->inputMessage = '';

        // Add user message to UI
        $this->messages[] = [
            'role' => 'user',
            'content' => $message ?: '[Image uploaded]',
            'timestamp' => now()->toIso8601String(),
            'image' => $this->imagePreview,
        ];

        try {
            $service = new GeminiCabinetAssistantService();

            if ($this->uploadedImage) {
                // Process with image
                $imageData = base64_encode(file_get_contents($this->uploadedImage->getRealPath()));
                $mimeType = $this->uploadedImage->getMimeType();

                $response = $service->processImage(
                    $imageData,
                    $mimeType,
                    $this->sessionId,
                    $this->specData,
                    $message
                );

                // Clear image after sending
                $this->uploadedImage = null;
                $this->imagePreview = null;
            } else {
                // Process text only
                $response = $service->processMessage(
                    $message,
                    $this->sessionId,
                    $this->specData,
                    $this->mode
                );
            }

            // Add assistant response
            $this->messages[] = [
                'role' => 'assistant',
                'content' => $response['message'] ?? 'I encountered an issue processing your request.',
                'timestamp' => now()->toIso8601String(),
                'commands' => $response['commands'] ?? [],
            ];

            // Execute commands if any
            if (!empty($response['commands'])) {
                $this->executeCommands($response['commands']);
            }

            // Increment unread if minimized
            if ($this->isMinimized) {
                $this->unreadCount++;
            }

        } catch (\Exception $e) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'Sorry, I encountered an error: ' . $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
                'isError' => true,
            ];
        }

        $this->isProcessing = false;
        $this->dispatch('scroll-to-bottom');
    }

    /**
     * Handle image upload
     */
    public function updatedUploadedImage(): void
    {
        if ($this->uploadedImage) {
            $this->imagePreview = $this->uploadedImage->temporaryUrl();
        }
    }

    /**
     * Clear uploaded image
     */
    public function clearImage(): void
    {
        $this->uploadedImage = null;
        $this->imagePreview = null;
    }

    /**
     * Execute AI commands via events to CabinetSpecBuilder
     */
    protected function executeCommands(array $commands): void
    {
        $actionsSummary = [];

        foreach ($commands as $command) {
            $action = $command['action'] ?? null;
            $data = $command['data'] ?? [];

            // Add source tracking to all commands
            $data['source'] = 'ai';

            switch ($action) {
                case 'create_room':
                    $this->dispatch('ai-create-room', data: $data);
                    $actionsSummary[] = "Created room: " . ($data['name'] ?? 'New Room');
                    break;

                case 'create_location':
                    $this->dispatch('ai-create-location', data: $data);
                    $level = $data['cabinet_level'] ?? 2;
                    $actionsSummary[] = "Added location: " . ($data['name'] ?? 'New Location') . " (L{$level})";
                    break;

                case 'create_run':
                    $this->dispatch('ai-create-run', data: $data);
                    $actionsSummary[] = "Added cabinet run: " . ($data['name'] ?? $data['run_type'] ?? 'Base') . " run";
                    break;

                case 'add_cabinet':
                    $this->dispatch('ai-add-cabinet', data: $data);
                    $cabinets = $data['cabinets'] ?? [$data];
                    foreach ($cabinets as $cab) {
                        $qty = ($cab['quantity'] ?? 1) > 1 ? " x" . $cab['quantity'] : '';
                        $actionsSummary[] = "Added cabinet: " . ($cab['name'] ?? 'Cabinet') . $qty;
                    }
                    break;

                case 'delete':
                    $this->dispatch('ai-delete-entity', data: $data);
                    $actionsSummary[] = "Deleted: " . ($data['name'] ?? $data['type'] ?? 'item');
                    break;

                case 'update_pricing':
                    $this->dispatch('ai-update-pricing', data: $data);
                    $level = $data['cabinet_level'] ?? 2;
                    $actionsSummary[] = "Updated pricing to L{$level}";
                    break;

                // Product/Inventory commands
                case 'search_products':
                    $result = $this->executeProductSearch($data);
                    if ($result['success']) {
                        $count = $result['count'] ?? 0;
                        $actionsSummary[] = "Found {$count} product(s) matching \"{$data['query']}\"";
                    } else {
                        $actionsSummary[] = "Product search error: " . ($result['error'] ?? 'Unknown error');
                    }
                    break;

                case 'reserve_product':
                    $result = $this->executeProductReservation($data);
                    if ($result['success']) {
                        $actionsSummary[] = $result['message'];
                    } else {
                        $actionsSummary[] = "Reservation failed: " . ($result['error'] ?? 'Unknown error');
                    }
                    break;

                case 'check_availability':
                    $result = $this->executeAvailabilityCheck($data);
                    if ($result['success']) {
                        $status = $result['available'] ? '✅ Available' : '❌ Insufficient';
                        $actionsSummary[] = "{$status}: {$result['quantity_available']} of {$result['product_name']} in stock";
                    }
                    break;

                case 'get_product_details':
                    $result = $this->executeGetProductDetails($data);
                    if ($result['success']) {
                        $p = $result['product'];
                        $actionsSummary[] = "Product details: {$p['name']} - \${$p['price']} ({$p['available_qty']} available)";
                    }
                    break;

                // Time Clock commands
                case 'clock_in':
                    $result = $this->executeClockIn($data);
                    if ($result['success']) {
                        $actionsSummary[] = "⏰ Clocked in at {$result['clock_in_time']}";
                    } else {
                        $actionsSummary[] = "Clock in failed: " . ($result['error'] ?? 'Unknown error');
                    }
                    break;

                case 'clock_out':
                    $result = $this->executeClockOut($data);
                    if ($result['success']) {
                        $hours = $result['hours_worked'] ?? 0;
                        $actionsSummary[] = "⏰ Clocked out - {$hours}h worked today";
                    } else {
                        $actionsSummary[] = "Clock out failed: " . ($result['error'] ?? 'Unknown error');
                    }
                    break;

                case 'get_clock_status':
                    $result = $this->executeGetClockStatus($data);
                    if ($result['success']) {
                        $status = $result['is_clocked_in'] ? "Clocked in since {$result['clock_in_time']}" : 'Not clocked in';
                        $actionsSummary[] = "📊 Status: {$status} | Today: {$result['today_hours']}h | Week: {$result['weekly_hours']}h";
                    }
                    break;

                case 'get_weekly_hours':
                    $result = $this->executeGetWeeklyHours($data);
                    if ($result['success']) {
                        $actionsSummary[] = "📊 Weekly hours: {$result['total_formatted']} / {$result['target']}h target";
                    }
                    break;

                case 'add_manual_entry':
                    $result = $this->executeAddManualEntry($data);
                    if ($result['success']) {
                        $actionsSummary[] = "📝 Manual entry added for {$data['date']}";
                        if ($result['needs_approval']) {
                            $actionsSummary[] = "⚠️ Entry flagged for supervisor approval";
                        }
                    } else {
                        $actionsSummary[] = "Manual entry failed: " . ($result['error'] ?? 'Unknown error');
                    }
                    break;

                case 'get_team_attendance':
                    $result = $this->executeGetTeamAttendance($data);
                    if ($result['success']) {
                        $actionsSummary[] = "👥 Team: {$result['total_clocked_in']}/{$result['total_employees']} clocked in";
                    }
                    break;

                // Export actions
                case 'export_weekly_timesheet':
                    $result = $this->executeExportWeeklyTimesheet($data);
                    if ($result['success']) {
                        $actionsSummary[] = "📋 Timesheet export ready: {$result['filename']}";
                    }
                    break;

                case 'export_team_timesheet':
                    $result = $this->executeExportTeamTimesheet($data);
                    if ($result['success']) {
                        $actionsSummary[] = "📋 Team export ready: {$result['filename']}";
                    }
                    break;
            }
        }

        // Log to Chatter if we executed any actions
        if (!empty($actionsSummary)) {
            $this->logToChatter($actionsSummary);
        }
    }

    /**
     * Log AI actions to project Chatter for audit trail
     */
    protected function logToChatter(array $actionsSummary): void
    {
        try {
            $project = Project::find($this->projectId);

            if (!$project) {
                Log::warning('CabinetAiAssistant: Project not found for Chatter logging', ['projectId' => $this->projectId]);
                return;
            }

            $user = filament()->auth()->user();

            if (!$user) {
                Log::warning('CabinetAiAssistant: No authenticated user for Chatter logging');
                return;
            }

            // Build the activity message
            $actionsText = implode("\n• ", $actionsSummary);
            $body = "**AI Cabinet Assistant** performed " . count($actionsSummary) . " action(s):\n• " . $actionsText;

            // Create the Chatter message
            Message::create([
                'messageable_type' => $project->getMorphClass(),
                'messageable_id' => $project->id,
                'type' => 'notification',
                'log_name' => 'ai_assistant',
                'event' => 'ai_action',
                'body' => $body,
                'causer_type' => $user->getMorphClass(),
                'causer_id' => $user->id,
                'company_id' => $user->defaultCompany?->id,
                'properties' => [
                    'source' => 'cabinet_ai_assistant',
                    'mode' => $this->mode,
                    'actions' => $actionsSummary,
                    'action_count' => count($actionsSummary),
                ],
            ]);

            Log::info('CabinetAiAssistant: Logged AI actions to Chatter', [
                'projectId' => $this->projectId,
                'actionCount' => count($actionsSummary),
            ]);

        } catch (\Exception $e) {
            Log::error('CabinetAiAssistant: Failed to log to Chatter', [
                'error' => $e->getMessage(),
                'projectId' => $this->projectId,
            ]);
        }
    }

    // =========================================================================
    // Product Command Execution Methods
    // =========================================================================

    /**
     * Execute product search
     */
    protected function executeProductSearch(array $data): array
    {
        $service = new ProductSearchService();
        $result = $service->searchProducts(
            $data['query'] ?? '',
            [
                'category' => $data['category'] ?? null,
                'product_type' => $data['product_type'] ?? null,
                'limit' => $data['limit'] ?? 10,
            ]
        );

        // If we found products, add them to the message for display
        if ($result['success'] && !empty($result['products'])) {
            $this->lastProductSearchResults = $result['products'];

            // Add a follow-up message with search results
            $this->messages[] = [
                'role' => 'assistant',
                'content' => $this->formatProductSearchResults($result['products']),
                'timestamp' => now()->toIso8601String(),
                'isProductList' => true,
                'products' => $result['products'],
            ];
        }

        return $result;
    }

    /**
     * Format product search results for display
     */
    protected function formatProductSearchResults(array $products): string
    {
        if (empty($products)) {
            return "No products found.";
        }

        $count = count($products);
        $lines = ["**Found {$count} product(s):**\n"];

        foreach ($products as $i => $p) {
            $available = $p['available_qty'] ?? 0;
            $price = isset($p['price']) ? '$' . number_format($p['price'], 2) : 'N/A';
            $ref = $p['reference'] ? " ({$p['reference']})" : '';

            $lines[] = sprintf(
                "%d. **%s**%s\n   Price: %s | Available: %d %s",
                $i + 1,
                $p['name'],
                $ref,
                $price,
                $available,
                $p['uom'] ?? 'units'
            );
        }

        $lines[] = "\n*Say \"reserve #X\" or \"hold #X qty Y\" to reserve a product.*";

        return implode("\n", $lines);
    }

    /**
     * Execute product reservation
     */
    protected function executeProductReservation(array $data): array
    {
        $service = new ProductSearchService();
        return $service->reserveProduct(
            $this->projectId,
            $data['product_id'] ?? null,
            $data['product_name'] ?? null,
            $data['quantity'] ?? 1,
            $data['notes'] ?? null
        );
    }

    /**
     * Execute availability check
     */
    protected function executeAvailabilityCheck(array $data): array
    {
        $service = new ProductSearchService();
        return $service->checkAvailability(
            $data['product_id'] ?? null,
            $data['product_name'] ?? null,
            $data['quantity_needed'] ?? 1
        );
    }

    /**
     * Execute get product details
     */
    protected function executeGetProductDetails(array $data): array
    {
        $service = new ProductSearchService();
        return $service->getProductDetails(
            $data['product_id'] ?? null,
            $data['product_name'] ?? null
        );
    }

    /**
     * Store last product search results for quick reservation
     */
    public array $lastProductSearchResults = [];

    /**
     * Quick reserve from search results
     * @param int $index Index from search results (1-based)
     * @param float $quantity Quantity to reserve
     */
    public function quickReserve(int $index, float $quantity = 1): void
    {
        if (empty($this->lastProductSearchResults)) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'No recent search results. Please search for products first.',
                'timestamp' => now()->toIso8601String(),
                'isError' => true,
            ];
            return;
        }

        $productIndex = $index - 1; // Convert to 0-based
        if (!isset($this->lastProductSearchResults[$productIndex])) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => "Invalid product number. Please choose 1-" . count($this->lastProductSearchResults),
                'timestamp' => now()->toIso8601String(),
                'isError' => true,
            ];
            return;
        }

        $product = $this->lastProductSearchResults[$productIndex];

        $result = $this->executeProductReservation([
            'product_id' => $product['id'],
            'product_name' => $product['name'],
            'quantity' => $quantity,
        ]);

        $this->messages[] = [
            'role' => 'assistant',
            'content' => $result['success']
                ? "✅ {$result['message']}"
                : "❌ {$result['error']}",
            'timestamp' => now()->toIso8601String(),
            'isError' => !$result['success'],
        ];

        if ($result['success']) {
            $this->logToChatter([$result['message']]);
        }

        $this->dispatch('scroll-to-bottom');
    }

    /**
     * Set conversation mode
     */
    public function setMode(string $mode): void
    {
        $this->mode = $mode;

        $modeMessage = $mode === 'guided'
            ? "Switched to **guided mode**. I'll walk you through building your cabinet spec step by step. What room are we working on?"
            : "Switched to **quick mode**. Send concise commands like 'Add kitchen with L2 base cabinets' or 'Add B24, SB36 to base run'.";

        $this->messages[] = [
            'role' => 'system',
            'content' => $modeMessage,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Minimize the widget
     */
    public function minimize(): void
    {
        $this->isMinimized = true;
    }

    /**
     * Expand the widget
     */
    public function expand(): void
    {
        $this->isMinimized = false;
        $this->unreadCount = 0;
        $this->dispatch('scroll-to-bottom');
    }

    /**
     * Clear conversation history
     */
    public function clearConversation(): void
    {
        $service = new GeminiCabinetAssistantService();
        $service->clearHistory($this->sessionId);

        $this->messages = [];
        $this->addWelcomeMessage();
    }

    /**
     * Add welcome message based on mode
     */
    protected function addWelcomeMessage(): void
    {
        $service = new GeminiCabinetAssistantService();

        if (!$service->isConfigured()) {
            $this->messages[] = [
                'role' => 'system',
                'content' => '**AI Assistant Not Configured**

Add your Google API key to `.env`:
```
GOOGLE_API_KEY=your_key_here
```',
                'timestamp' => now()->toIso8601String(),
                'isError' => true,
            ];
            return;
        }

        $roomCount = count($this->specData);
        $context = $roomCount > 0
            ? "I see you have {$roomCount} room(s) in your spec."
            : "Your cabinet spec is empty.";

        $this->messages[] = [
            'role' => 'assistant',
            'content' => "Hi! I'm your cabinet spec assistant. {$context}

**Quick Mode** (current): Send commands like:
- \"Add kitchen with L2 base cabinets\"
- \"Add B24, B36 x2, SB36 to base run\"
- Upload a floor plan image

**Product Search**: Find and reserve materials:
- \"Search for soft close hinges\"
- \"Find Blum drawer slides\"
- \"Put 10 hinges on hold for this project\"

**Guided Mode**: I'll walk you through step by step.

How can I help?",
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Quick action: Add a room
     */
    public function quickAddRoom(string $roomName): void
    {
        $this->inputMessage = "Add {$roomName} room";
        $this->sendMessage();
    }

    // =========================================================================
    // Time Clock Command Execution Methods
    // =========================================================================

    /**
     * Execute clock in
     */
    protected function executeClockIn(array $data): array
    {
        $userId = $this->resolveUserId($data['employee_name'] ?? null);
        if (!$userId) {
            return ['success' => false, 'error' => 'Could not identify employee'];
        }

        $service = new ClockingService();
        return $service->clockIn($userId, null, $data['notes'] ?? null);
    }

    /**
     * Execute clock out
     */
    protected function executeClockOut(array $data): array
    {
        $userId = $this->resolveUserId($data['employee_name'] ?? null);
        if (!$userId) {
            return ['success' => false, 'error' => 'Could not identify employee'];
        }

        // Try to find project by name if specified
        $projectId = null;
        if (!empty($data['project_name'])) {
            $project = \Webkul\Project\Models\Project::where('name', 'like', '%' . $data['project_name'] . '%')->first();
            $projectId = $project?->id;
        }

        $service = new ClockingService();
        return $service->clockOut(
            $userId,
            $data['break_duration_minutes'] ?? 60,
            $projectId,
            $data['notes'] ?? null
        );
    }

    /**
     * Execute get clock status
     */
    protected function executeGetClockStatus(array $data): array
    {
        $userId = $this->resolveUserId($data['employee_name'] ?? null);
        if (!$userId) {
            return ['success' => false, 'error' => 'Could not identify employee'];
        }

        $service = new ClockingService();
        $status = $service->getStatus($userId);

        return array_merge(['success' => true], $status);
    }

    /**
     * Execute get weekly hours
     */
    protected function executeGetWeeklyHours(array $data): array
    {
        $userId = $this->resolveUserId($data['employee_name'] ?? null);
        if (!$userId) {
            return ['success' => false, 'error' => 'Could not identify employee'];
        }

        $weekStart = null;
        if (!empty($data['week_start'])) {
            $weekStart = \Carbon\Carbon::parse($data['week_start']);
        }

        $service = new ClockingService();
        $summary = $service->getWeeklySummary($userId, $weekStart);

        return array_merge(['success' => true], $summary);
    }

    /**
     * Execute add manual time entry
     */
    protected function executeAddManualEntry(array $data): array
    {
        $userId = $this->resolveUserId($data['employee_name'] ?? null);
        if (!$userId) {
            return ['success' => false, 'error' => 'Could not identify employee'];
        }

        // Try to find project by name if specified
        $projectId = null;
        if (!empty($data['project_name'])) {
            $project = \Webkul\Project\Models\Project::where('name', 'like', '%' . $data['project_name'] . '%')->first();
            $projectId = $project?->id;
        }

        $service = new ClockingService();
        return $service->addManualEntry(
            $userId,
            $data['date'],
            $data['clock_in_time'],
            $data['clock_out_time'],
            $data['break_duration_minutes'] ?? 60,
            $projectId,
            $data['notes'] ?? 'Manual entry via AI assistant'
        );
    }

    /**
     * Execute get team attendance
     */
    protected function executeGetTeamAttendance(array $data): array
    {
        $service = new ClockingService();
        $attendance = $service->getTodayAttendance();

        // Add formatted message with team status
        $this->messages[] = [
            'role' => 'assistant',
            'content' => $this->formatTeamAttendance($attendance),
            'timestamp' => now()->toIso8601String(),
            'isAttendance' => true,
        ];

        return array_merge(['success' => true], $attendance);
    }

    /**
     * Format team attendance for display
     */
    protected function formatTeamAttendance(array $attendance): string
    {
        $employees = $attendance['employees'] ?? [];
        $date = $attendance['date'] ?? now()->format('l, M j, Y');

        $lines = ["**Team Attendance - {$date}**\n"];

        $clockedIn = array_filter($employees, fn($e) => $e['is_clocked_in']);
        $notIn = array_filter($employees, fn($e) => !$e['is_clocked_in']);

        if (!empty($clockedIn)) {
            $lines[] = "**Clocked In:**";
            foreach ($clockedIn as $emp) {
                $late = $emp['is_late'] ? ' ⚠️ Late' : '';
                $lines[] = "• {$emp['name']} - In at {$emp['clock_in_time']}{$late} ({$emp['running_hours']}h running)";
            }
            $lines[] = "";
        }

        if (!empty($notIn)) {
            $lines[] = "**Not In:**";
            foreach ($notIn as $emp) {
                $hours = $emp['today_hours'] > 0 ? " ({$emp['today_hours']}h worked)" : '';
                $lines[] = "• {$emp['name']}{$hours}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Resolve user ID from employee name or use current user
     */
    protected function resolveUserId(?string $employeeName): ?int
    {
        // If no name specified, use current authenticated user
        if (empty($employeeName)) {
            return filament()->auth()->id();
        }

        // Try to find employee by name
        $employee = \Webkul\Employee\Models\Employee::where('name', 'like', '%' . $employeeName . '%')
            ->whereNotNull('user_id')
            ->first();

        return $employee?->user_id;
    }

    // =========================================================================
    // EXPORT EXECUTION METHODS
    // =========================================================================

    /**
     * Execute export weekly timesheet command
     */
    protected function executeExportWeeklyTimesheet(array $data): array
    {
        $exportService = app(\App\Services\TimesheetExportService::class);

        $userId = $this->resolveUserId($data['employee_name'] ?? null);
        if (!$userId) {
            return ['success' => false, 'error' => 'Employee not found'];
        }

        $weekStart = !empty($data['week_start'])
            ? \Carbon\Carbon::parse($data['week_start'])
            : null;

        $format = $data['format'] ?? 'html';

        $result = $exportService->exportWeeklyTimesheet($userId, $weekStart, $format);

        if ($result['success']) {
            // Dispatch event to open export in new tab/download
            $this->dispatch('timesheet-export-ready', [
                'format' => $format,
                'filename' => $result['filename'],
                'content' => $format === 'html' ? $result['html'] : $result['csv'],
            ]);
        }

        return $result;
    }

    /**
     * Execute export team timesheet command
     */
    protected function executeExportTeamTimesheet(array $data): array
    {
        $exportService = app(\App\Services\TimesheetExportService::class);

        $weekStart = !empty($data['week_start'])
            ? \Carbon\Carbon::parse($data['week_start'])
            : null;

        $format = $data['format'] ?? 'csv';

        $result = $exportService->exportTeamWeeklySummary($weekStart, $format);

        if ($result['success']) {
            // Dispatch event to open export in new tab/download
            $this->dispatch('timesheet-export-ready', [
                'format' => $format,
                'filename' => $result['filename'],
                'content' => $result['csv'],
            ]);
        }

        return $result;
    }

    public function render()
    {
        return view('webkul-project::livewire.cabinet-ai-assistant');
    }
}
