<?php

namespace App\Services\Footer\Contexts;

use App\Services\Footer\Contracts\ContextProviderInterface;
use App\Services\Footer\ContextFieldBuilder;
use Filament\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\DB;

/**
 * Sale/Order Context Provider
 *
 * Provides context-specific data and field definitions for Sales Order entities.
 */
class SaleContextProvider implements ContextProviderInterface
{
    public function getContextType(): string
    {
        return 'sale';
    }

    public function getContextName(): string
    {
        return 'Sales Order';
    }

    public function getEmptyLabel(): string
    {
        return 'No Order Selected';
    }

    public function getBorderColor(): string
    {
        return 'rgb(34, 197, 94)'; // Green
    }

    public function getIconPath(): string
    {
        // Shopping cart icon
        return 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z';
    }

    /**
     * Load Context
     *
     * @param int|string $entityId
     * @return array
     */
    public function loadContext(int|string $entityId): array
    {
        // Load sales order data from database
        $order = DB::table('sales_orders')
            ->where('id', $entityId)
            ->first();

        if (!$order) {
            return [];
        }

        $data = (array) $order;

        // Load customer name
        if ($order->partner_id) {
            $partner = DB::table('partners_partners')
                ->where('id', $order->partner_id)
                ->first();

            $data['_customerName'] = $partner->name ?? '—';
        }

        return $data;
    }

    /**
     * Get Field Schema
     *
     * @param array $data The data array
     * @param bool $isMinimized
     * @return array
     */
    public function getFieldSchema(array $data, bool $isMinimized = false): array
    {
        if ($isMinimized) {
            return $this->getMinimizedSchema($data);
        }

        return $this->getExpandedSchema($data);
    }

    /**
     * Get Minimized Schema
     *
     * @param array $data The data array
     * @return array
     */
    protected function getMinimizedSchema(array $data): array
    {
        return [
            ContextFieldBuilder::prominentText('order_number', 'Order #')
                ->state($data['order_number'] ?? $data['quote_number'] ?? '—'),

            ContextFieldBuilder::text('_customerName', 'Customer')
                ->state($data['_customerName'] ?? '—'),
        ];
    }

    /**
     * Get Expanded Schema
     *
     * @param array $data The data array
     * @return array
     */
    protected function getExpandedSchema(array $data): array
    {
        $fields = [
            ContextFieldBuilder::copyable('order_number', 'Order #')
                ->state($data['order_number'] ?? $data['quote_number'] ?? '—'),

            ContextFieldBuilder::text('_customerName', 'Customer')
                ->state($data['_customerName'] ?? '—')
                ->weight(FontWeight::SemiBold),
        ];

        // Add order total
        if (!empty($data['order_total'])) {
            $fields[] = ContextFieldBuilder::currency('order_total', 'Total')
                ->state($data['order_total']);
        }

        // Add order status
        if (!empty($data['order_status'])) {
            $fields[] = ContextFieldBuilder::badge('order_status', 'Status', $this->getStatusColor($data['order_status']))
                ->state(str($data['order_status'])->title()->toString());
        }

        // Add payment status
        if (!empty($data['payment_status'])) {
            $fields[] = ContextFieldBuilder::badge('payment_status', 'Payment', $this->getPaymentStatusColor($data['payment_status']))
                ->state(str($data['payment_status'])->title()->toString());
        }

        // Add order date
        if (!empty($data['order_date'])) {
            $fields[] = ContextFieldBuilder::date('order_date', 'Order Date')
                ->state($data['order_date']);
        }

        // Add expected delivery
        if (!empty($data['expected_delivery'])) {
            $fields[] = ContextFieldBuilder::date('expected_delivery', 'Delivery')
                ->state($data['expected_delivery']);
        }

        return $fields;
    }

    public function getDefaultPreferences(): array
    {
        return [
            'minimized_fields' => ['order_number', '_customerName'],
            'expanded_fields' => [
                'order_number',
                '_customerName',
                'order_total',
                'order_status',
                'payment_status',
                'order_date',
                'expected_delivery',
            ],
            'field_order' => [],
        ];
    }

    public function getApiEndpoints(): array
    {
        return [
            'fetch' => fn($id) => "/api/sales/orders/{$id}",
        ];
    }

    /**
     * Supports Feature
     *
     * @param string $feature
     * @return bool
     */
    public function supportsFeature(string $feature): bool
    {
        return false; // Sales orders don't have tags, timeline alerts, etc.
    }

    /**
     * Get Actions
     *
     * @param array $data The data array
     * @return array
     */
    public function getActions(array $data): array
    {
        $actions = [];

        if (!empty($data['id']) && !request()->is('*/edit')) {
            $actions[] = Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->color('gray')
                ->url(route('filament.admin.resources.sales.sales-orders.edit', ['record' => $data['id']]));
        }

        return $actions;
    }

    /**
     * Get Status Color
     *
     * @param string $status
     * @return string
     */
    protected function getStatusColor(string $status): string
    {
        return match(strtolower($status)) {
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get Payment Status Color
     *
     * @param string $status
     * @return string
     */
    protected function getPaymentStatusColor(string $status): string
    {
        return match(strtolower($status)) {
            'pending' => 'warning',
            'partial' => 'info',
            'paid' => 'success',
            'refunded' => 'danger',
            default => 'gray',
        };
    }
}
