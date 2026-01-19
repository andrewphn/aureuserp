/**
 * Purchase Order Tools - CRUD and workflow operations for purchase orders
 */
export const purchaseOrderTools = [
    {
        name: 'list_purchase_orders',
        description: 'List purchase orders with filters (status, vendor, date range).',
        inputSchema: {
            type: 'object',
            properties: {
                status: { type: 'string', description: 'Filter by status (draft, confirmed, done, cancelled)' },
                partner_id: { type: 'number', description: 'Filter by vendor/supplier ID' },
                date_from: { type: 'string', description: 'Filter orders from this date (YYYY-MM-DD)' },
                date_to: { type: 'string', description: 'Filter orders to this date (YYYY-MM-DD)' },
                search: { type: 'string', description: 'Search by PO number or reference' },
                page: { type: 'number', description: 'Page number for pagination' },
                per_page: { type: 'number', description: 'Items per page (max 100)' },
            },
        },
    },
    {
        name: 'get_purchase_order',
        description: 'Get purchase order details by ID with lines and vendor info.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Purchase order ID' },
                include: {
                    type: 'array',
                    items: { type: 'string' },
                    description: 'Relations to include (lines, partner, bills)',
                },
            },
            required: ['id'],
        },
    },
    {
        name: 'create_purchase_order',
        description: 'Create a new purchase order.',
        inputSchema: {
            type: 'object',
            properties: {
                partner_id: { type: 'number', description: 'Vendor/supplier ID' },
                project_id: { type: 'number', description: 'Related project ID' },
                date_order: { type: 'string', description: 'Order date (YYYY-MM-DD)' },
                date_planned: { type: 'string', description: 'Expected delivery date' },
                payment_term_id: { type: 'number', description: 'Payment terms ID' },
                notes: { type: 'string', description: 'Order notes' },
                lines: {
                    type: 'array',
                    items: {
                        type: 'object',
                        properties: {
                            product_id: { type: 'number' },
                            name: { type: 'string' },
                            quantity: { type: 'number' },
                            price_unit: { type: 'number' },
                        },
                    },
                    description: 'Order line items',
                },
            },
            required: ['partner_id'],
        },
    },
    {
        name: 'update_purchase_order',
        description: 'Update purchase order fields.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Purchase order ID to update' },
                partner_id: { type: 'number', description: 'New vendor/supplier ID' },
                date_planned: { type: 'string', description: 'New expected delivery date' },
                payment_term_id: { type: 'number', description: 'New payment terms ID' },
                notes: { type: 'string', description: 'New notes' },
            },
            required: ['id'],
        },
    },
    {
        name: 'delete_purchase_order',
        description: 'Delete a purchase order (only draft orders can be deleted).',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Purchase order ID to delete' },
            },
            required: ['id'],
        },
    },
    {
        name: 'confirm_purchase_order',
        description: 'Confirm a draft purchase order, sending it to the vendor.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Purchase order ID to confirm' },
            },
            required: ['id'],
        },
    },
    {
        name: 'cancel_purchase_order',
        description: 'Cancel a purchase order.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Purchase order ID to cancel' },
                reason: { type: 'string', description: 'Cancellation reason' },
            },
            required: ['id'],
        },
    },
    {
        name: 'create_purchase_order_bill',
        description: 'Create a vendor bill from a confirmed purchase order.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Purchase order ID to create bill for' },
                bill_date: { type: 'string', description: 'Bill date (YYYY-MM-DD)' },
                reference: { type: 'string', description: 'Vendor bill reference number' },
            },
            required: ['id'],
        },
    },
    {
        name: 'send_purchase_order_email',
        description: 'Send purchase order via email to vendor.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Purchase order ID to send' },
                email: { type: 'string', description: 'Override recipient email' },
                subject: { type: 'string', description: 'Custom email subject' },
                message: { type: 'string', description: 'Custom email message' },
            },
            required: ['id'],
        },
    },
    // Purchase Order Lines
    {
        name: 'list_purchase_order_lines',
        description: 'List line items for a purchase order.',
        inputSchema: {
            type: 'object',
            properties: {
                order_id: { type: 'number', description: 'Purchase order ID' },
            },
            required: ['order_id'],
        },
    },
    {
        name: 'create_purchase_order_line',
        description: 'Add a line item to a purchase order.',
        inputSchema: {
            type: 'object',
            properties: {
                order_id: { type: 'number', description: 'Purchase order ID' },
                product_id: { type: 'number', description: 'Product ID' },
                name: { type: 'string', description: 'Line description' },
                quantity: { type: 'number', description: 'Quantity' },
                price_unit: { type: 'number', description: 'Unit price' },
                tax_ids: { type: 'array', items: { type: 'number' }, description: 'Tax IDs to apply' },
            },
            required: ['order_id', 'quantity', 'price_unit'],
        },
    },
    {
        name: 'update_purchase_order_line',
        description: 'Update a purchase order line item.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Line item ID' },
                quantity: { type: 'number', description: 'New quantity' },
                price_unit: { type: 'number', description: 'New unit price' },
                name: { type: 'string', description: 'New description' },
            },
            required: ['id'],
        },
    },
    {
        name: 'delete_purchase_order_line',
        description: 'Remove a line item from a purchase order.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Line item ID to delete' },
            },
            required: ['id'],
        },
    },
];
export async function handlePurchaseOrderTool(client, toolName, args) {
    switch (toolName) {
        case 'list_purchase_orders':
            return client.listPurchaseOrders(args);
        case 'get_purchase_order':
            return client.getPurchaseOrder(args.id, args.include);
        case 'create_purchase_order':
            return client.createPurchaseOrder(args);
        case 'update_purchase_order': {
            const { id, ...data } = args;
            return client.updatePurchaseOrder(id, data);
        }
        case 'delete_purchase_order':
            return client.deletePurchaseOrder(args.id);
        case 'confirm_purchase_order':
            return client.confirmPurchaseOrder(args.id);
        case 'cancel_purchase_order':
            return client.cancelPurchaseOrder(args.id, args.reason);
        case 'create_purchase_order_bill':
            return client.createPurchaseOrderBill(args.id, args);
        case 'send_purchase_order_email':
            return client.sendPurchaseOrderEmail(args.id, args);
        case 'list_purchase_order_lines':
            return client.listPurchaseOrderLines(args.order_id);
        case 'create_purchase_order_line': {
            const { order_id, ...data } = args;
            return client.createPurchaseOrderLine(order_id, data);
        }
        case 'update_purchase_order_line': {
            const { id, ...data } = args;
            return client.updatePurchaseOrderLine(id, data);
        }
        case 'delete_purchase_order_line':
            return client.deletePurchaseOrderLine(args.id);
        default:
            throw new Error(`Unknown purchase order tool: ${toolName}`);
    }
}
