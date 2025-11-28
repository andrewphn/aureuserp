<?php

namespace Database\Seeders;

use App\Models\FooterTemplate;
use Illuminate\Database\Seeder;

class FooterTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Owner',
                'slug' => 'owner',
                'description' => 'High-level KPIs only. Minimal, focused information with timeline alerts.',
                'icon' => 'heroicon-o-star',
                'color' => 'amber',
                'is_active' => true,
                'is_system' => true,
                'contexts' => [
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
                    'inventory' => [],
                    'production' => [],
                ],
            ],
            [
                'name' => 'Project Manager',
                'slug' => 'project_manager',
                'description' => 'Comprehensive project details, timelines, linear feet, and completion tracking.',
                'icon' => 'heroicon-o-briefcase',
                'color' => 'blue',
                'is_active' => true,
                'is_system' => true,
                'contexts' => [
                    'project' => [
                        'minimized_fields' => ['project_number', 'customer_name'],
                        'expanded_fields' => ['project_number', 'customer_name', 'linear_feet', 'estimate_days', 'completion_date', 'timeline_alert', 'tags'],
                        'field_order' => [],
                    ],
                    'sale' => [],
                    'inventory' => [],
                    'production' => [],
                ],
            ],
            [
                'name' => 'Sales',
                'slug' => 'sales',
                'description' => 'Minimal order info for quick customer lookups and status checks.',
                'icon' => 'heroicon-o-shopping-cart',
                'color' => 'green',
                'is_active' => true,
                'is_system' => true,
                'contexts' => [
                    'project' => [],
                    'sale' => [
                        'minimized_fields' => ['order_number', 'customer_name'],
                        'expanded_fields' => ['order_number', 'customer_name', 'order_total', 'order_status'],
                        'field_order' => [],
                    ],
                    'inventory' => [],
                    'production' => [],
                ],
            ],
            [
                'name' => 'Shop Lead',
                'slug' => 'inventory',
                'description' => 'Simple inventory and production tracking with quantities and locations.',
                'icon' => 'heroicon-o-cube',
                'color' => 'purple',
                'is_active' => true,
                'is_system' => true,
                'contexts' => [
                    'project' => [],
                    'sale' => [],
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
            ],
        ];

        foreach ($templates as $template) {
            FooterTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }

        $this->command->info('Footer templates seeded successfully!');
    }
}
