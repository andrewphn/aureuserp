<?php

namespace App\Filament\Widgets;

use App\Services\Footer\ContextRegistry;
use App\Services\FooterPreferenceService;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

/**
 * Global Context Footer Widget
 *
 * A FilamentPHP v4 compliant widget that displays contextual information
 * at the bottom of all admin pages. Shows active context (project, sale, etc.)
 * with customizable fields and real-time updates.
 *
 * @property-read bool $isMinimized
 * @property-read string|null $contextType
 * @property-read int|string|null $contextId
 * @property-read array $contextData
 */
class GlobalContextFooter extends Widget implements HasSchemas
{
    use InteractsWithSchemas;
    /**
     * Widget view file.
     */
    protected string $view = 'filament.widgets.global-context-footer';

    /**
     * Widget should not be lazy loaded.
     */
    protected static bool $isLazy = false;

    /**
     * Whether the footer is minimized.
     */
    public bool $isMinimized = true;

    /**
     * Current context type.
     */
    public ?string $contextType = null;

    /**
     * Current context entity ID.
     */
    public int|string|null $contextId = null;

    /**
     * Context data cache.
     */
    public array $contextData = [];

    /**
     * User preferences cache.
     */
    public array $userPreferences = [];

    /**
     * Initialize the widget.
     */
    public function mount(): void
    {
        $this->loadActiveContext();
        $this->loadUserPreferences();
    }

    /**
     * Create the schema for displaying context fields.
     */
    public function contextInfolist(Schema $schema): Schema
    {
        $registry = app(ContextRegistry::class);
        $provider = $this->contextType ? $registry->get($this->contextType) : null;

        if (!$provider || !$this->hasActiveContext()) {
            return $schema->components([]);
        }

        $fields = $provider->getFieldSchema($this->contextData, $this->isMinimized);

        return $schema
            ->state($this->contextData)
            ->components($fields);
    }

    /**
     * Get view data for rendering.
     */
    protected function getViewData(): array
    {
        $registry = app(ContextRegistry::class);
        $provider = $this->contextType ? $registry->get($this->contextType) : null;

        return [
            'hasActiveContext' => $this->hasActiveContext(),
            'contextType' => $this->contextType,
            'contextId' => $this->contextId,
            'contextData' => $this->contextData,
            'contextConfig' => $provider ? $this->getContextConfig($provider) : null,
            'actions' => $provider ? $provider->getActions($this->contextData) : [],
            'jsContextConfigs' => $registry->getJavaScriptConfig(),
            'isOnEditPage' => $this->isOnEditPage(),
        ];
    }

    /**
     * Check if there is an active context.
     */
    public function hasActiveContext(): bool
    {
        return $this->contextType !== null && $this->contextId !== null;
    }

    /**
     * Load active context from entity store (via session/localStorage).
     * This will be called by Alpine.js component on mount.
     */
    public function loadActiveContext(): void
    {
        // Check session for active context
        // This will be set by the edit pages when user opens them
        $activeContext = session('active_context');

        if ($activeContext && isset($activeContext['entityType'], $activeContext['entityId'])) {
            $this->contextType = $activeContext['entityType'];
            $this->contextId = $activeContext['entityId'];

            // Load context data
            $registry = app(ContextRegistry::class);
            $provider = $registry->get($this->contextType);

            if ($provider) {
                $this->contextData = $provider->loadContext($this->contextId);
            }
        }
    }

    /**
     * Load user preferences for all contexts.
     */
    protected function loadUserPreferences(): void
    {
        $preferenceService = app(FooterPreferenceService::class);
        $user = auth()->user();

        if ($user) {
            $this->userPreferences = $preferenceService->getAllUserPreferences($user);
        }
    }

    /**
     * Get context configuration for display.
     */
    protected function getContextConfig($provider): array
    {
        return [
            'name' => $provider->getContextName(),
            'emptyLabel' => $provider->getEmptyLabel(),
            'borderColor' => $provider->getBorderColor(),
            'iconPath' => $provider->getIconPath(),
        ];
    }

    /**
     * Toggle minimized state.
     */
    public function toggleMinimized(): void
    {
        $this->isMinimized = !$this->isMinimized;
    }

    /**
     * Set active context (called from JavaScript/Livewire events).
     */
    #[On('set-active-context')]
    /**
     * Set Active Context
     *
     * Accepts either named parameters or an array with entityType/entityId keys
     * to support both Livewire event dispatching patterns.
     *
     * @param string|array $entityType Either the entity type string or an array with entityType/entityId keys
     * @param int|string|null $entityId The entity ID (optional if $entityType is an array)
     * @param ?array $data The data array
     * @return void
     */
    public function setActiveContext(string|array $entityType, int|string|null $entityId = null, ?array $data = null): void
    {
        // Handle array format from JavaScript event dispatch
        if (is_array($entityType)) {
            $data = $entityType['data'] ?? $data;
            $entityId = $entityType['entityId'] ?? null;
            $entityType = $entityType['entityType'] ?? null;
        }

        if (!$entityType || $entityId === null) {
            return;
        }

        $this->contextType = $entityType;
        $this->contextId = $entityId;

        // Store in session for persistence
        session(['active_context' => [
            'entityType' => $entityType,
            'entityId' => $entityId,
            'timestamp' => now()->timestamp,
        ]]);

        // Load context data
        $registry = app(ContextRegistry::class);
        $provider = $registry->get($entityType);

        if ($provider) {
            $this->contextData = $data ?? $provider->loadContext($entityId);
        }

        // Dispatch event to notify JavaScript
        $this->dispatch('active-context-changed', [
            'entityType' => $entityType,
            'entityId' => $entityId,
        ]);
    }

    /**
     * Clear active context.
     */
    /**
     * Clear Context
     *
     * @return void
     */
    public function clearContext(): void
    {
        $this->contextType = null;
        $this->contextId = null;
        $this->contextData = [];

        // Clear session
        session()->forget('active_context');

        // Dispatch event to notify JavaScript
        $this->dispatch('active-context-cleared');
    }

    /**
     * Handle entity updates from JavaScript.
     */
    #[On('entity-updated')]
    /**
     * Handle Entity Update
     *
     * @param array $detail
     * @return void
     */
    public function handleEntityUpdate(array $detail): void
    {
        if (
            isset($detail['entityType'], $detail['entityId']) &&
            $detail['entityType'] === $this->contextType &&
            $detail['entityId'] == $this->contextId
        ) {
            // Reload context data
            $this->loadActiveContext();
        }
    }

    /**
     * Check if currently on an edit page.
     */
    /**
     * Is On Edit Page
     *
     * @return bool
     */
    protected function isOnEditPage(): bool
    {
        return str_contains(request()->path(), '/edit');
    }

    /**
     * Open project selector modal.
     * Dispatches event to JavaScript.
     */
    /**
     * Open Project Selector
     *
     * @return void
     */
    public function openProjectSelector(): void
    {
        $this->dispatch('open-project-selector');
    }

    /**
     * Save current form (triggers Filament's save button).
     * This is handled by Alpine.js component.
     */
    /**
     * Save Current Form
     *
     * @return void
     */
    public function saveCurrentForm(): void
    {
        // This method exists for consistency
        // Actual save is handled by Alpine.js clicking the Filament save button
    }

    /**
     * Get polling interval (disable polling for this widget).
     */
    protected function getPollingInterval(): ?string
    {
        return null; // No polling, updates via events
    }
}
