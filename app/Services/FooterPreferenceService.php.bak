<?php

namespace App\Services;

use App\Models\FooterPreference;
use App\Models\FooterTemplate;
use Webkul\Security\Models\User;

class FooterPreferenceService
{
    public function __construct(
        protected FooterFieldRegistry $fieldRegistry
    ) {}

    /**
     * Get user preferences for a specific context.
     */
    public function getUserPreferences(User $user, string $contextType): array
    {
        $preference = FooterPreference::getForUser($user, $contextType);

        if (!$preference) {
            return $this->getDefaultPreferences($contextType);
        }

        return [
            'minimized_fields' => $preference->minimized_fields ?? [],
            'expanded_fields' => $preference->expanded_fields ?? [],
            'field_order' => $preference->field_order ?? [],
        ];
    }

    /**
     * Get all user preferences for all contexts.
     */
    public function getAllUserPreferences(User $user): array
    {
        $contexts = ['project', 'sale', 'inventory', 'production'];
        $preferences = [];

        foreach ($contexts as $context) {
            $preferences[$context] = $this->getUserPreferences($user, $context);
        }

        return $preferences;
    }

    /**
     * Save user preferences for a specific context.
     */
    public function saveUserPreferences(User $user, string $contextType, array $data): FooterPreference
    {
        return FooterPreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'context_type' => $contextType
            ],
            [
                'minimized_fields' => $data['minimized_fields'] ?? [],
                'expanded_fields' => $data['expanded_fields'] ?? [],
                'field_order' => $data['field_order'] ?? [],
                'is_active' => $data['is_active'] ?? true,
            ]
        );
    }

    /**
     * Delete user preferences for a specific context.
     */
    public function deleteUserPreferences(User $user, string $contextType): bool
    {
        return FooterPreference::where('user_id', $user->id)
            ->where('context_type', $contextType)
            ->delete() > 0;
    }

    /**
     * Reset to default preferences for a context.
     */
    public function resetToDefaults(User $user, string $contextType): FooterPreference
    {
        $defaults = $this->getDefaultPreferences($contextType);

        return $this->saveUserPreferences($user, $contextType, $defaults);
    }

    /**
     * Get default preferences for a context type.
     */
    public function getDefaultPreferences(string $contextType): array
    {
        return match ($contextType) {
            'project' => [
                'minimized_fields' => ['customer_name', 'project_type'],
                'expanded_fields' => [
                    'project_number',
                    'customer_name',
                    'project_type',
                    'linear_feet',
                    'estimate_hours',
                    'estimate_days',
                    'estimate_weeks',
                    'estimate_months',
                    'timeline_alert',
                    'tags',
                ],
                'field_order' => [],
            ],
            'sale' => [
                'minimized_fields' => ['order_number', 'customer_name'],
                'expanded_fields' => [
                    'order_number',
                    'customer_name',
                    'order_total',
                    'order_status',
                    'payment_status',
                ],
                'field_order' => [],
            ],
            'inventory' => [
                'minimized_fields' => ['item_name', 'quantity'],
                'expanded_fields' => [
                    'item_name',
                    'sku',
                    'quantity',
                    'unit',
                    'location',
                    'reorder_level',
                ],
                'field_order' => [],
            ],
            'production' => [
                'minimized_fields' => ['job_number', 'customer_name'],
                'expanded_fields' => [
                    'job_number',
                    'project_name',
                    'customer_name',
                    'production_status',
                    'assigned_to',
                    'due_date',
                ],
                'field_order' => [],
            ],
            default => [
                'minimized_fields' => [],
                'expanded_fields' => [],
                'field_order' => [],
            ]
        };
    }

    /**
     * Get persona-based default preferences from database templates.
     */
    public function getPersonaDefaults(string $persona, string $contextType): array
    {
        // Try to load from database first
        $template = FooterTemplate::where('slug', $persona)
            ->where('is_active', true)
            ->first();

        if ($template) {
            return $template->getContextConfig($contextType);
        }

        // Fallback to hardcoded defaults for backward compatibility
        $defaults = [
            // Owner - High-level KPIs only, minimal view
            'owner' => [
                'project' => [
                    'minimized_fields' => ['project_number', 'timeline_alert'],
                    'expanded_fields' => ['project_number', 'customer_name', 'timeline_alert', 'completion_date', 'estimate_hours'],
                    'field_order' => [],
                ],
                'sale' => [
                    'minimized_fields' => ['order_number', 'order_total'],
                    'expanded_fields' => ['order_number', 'customer_name', 'order_total', 'order_status'],
                    'field_order' => [],
                ],
            ],

            // David (PM) - Detailed project tracking
            'project_manager' => [
                'project' => [
                    'minimized_fields' => ['project_number', 'customer_name'],
                    'expanded_fields' => ['project_number', 'customer_name', 'linear_feet', 'estimate_days', 'completion_date', 'timeline_alert', 'tags'],
                    'field_order' => [],
                ],
            ],

            // Trott (Remote Sales) - Minimal, fast access
            'sales' => [
                'sale' => [
                    'minimized_fields' => ['order_number', 'customer_name'],
                    'expanded_fields' => ['order_number', 'customer_name', 'order_total', 'order_status'],
                    'field_order' => [],
                ],
            ],

            // Ricky (Shop Lead) - Material-focused, simple
            'inventory' => [
                'inventory' => [
                    'minimized_fields' => ['item_name', 'quantity'],
                    'expanded_fields' => ['item_name', 'quantity', 'location', 'reorder_level'],
                    'field_order' => [],
                ],
                'production' => [
                    'minimized_fields' => ['job_number', 'production_status'],
                    'expanded_fields' => ['job_number', 'production_status', 'assigned_to', 'due_date'],
                    'field_order' => [],
                ],
            ],
        ];

        return $defaults[$persona][$contextType] ?? $this->getDefaultPreferences($contextType);
    }

    /**
     * Get all available templates from database
     */
    public function getAvailableTemplates(): \Illuminate\Support\Collection
    {
        return FooterTemplate::active()->orderBy('name')->get();
    }

    /**
     * Apply persona template to user.
     */
    public function applyPersonaTemplate(User $user, string $persona): array
    {
        $contexts = ['project', 'sale', 'inventory', 'production'];
        $applied = [];

        foreach ($contexts as $context) {
            $defaults = $this->getPersonaDefaults($persona, $context);
            if (!empty($defaults['minimized_fields']) || !empty($defaults['expanded_fields'])) {
                $this->saveUserPreferences($user, $context, $defaults);
                $applied[] = $context;
            }
        }

        return $applied;
    }
}
