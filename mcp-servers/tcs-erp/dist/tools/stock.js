/**
 * Stock/Inventory Tools - Product quantities and stock operations
 */
export const stockTools = [
    {
        name: 'list_stock',
        description: 'List stock/inventory quantities with filters (product, location, warehouse).',
        inputSchema: {
            type: 'object',
            properties: {
                product_id: { type: 'number', description: 'Filter by product ID' },
                location_id: { type: 'number', description: 'Filter by location ID' },
                warehouse_id: { type: 'number', description: 'Filter by warehouse ID' },
                low_stock: { type: 'boolean', description: 'Only show items below minimum quantity' },
                search: { type: 'string', description: 'Search by product name or SKU' },
                page: { type: 'number', description: 'Page number for pagination' },
                per_page: { type: 'number', description: 'Items per page (max 100)' },
            },
        },
    },
    {
        name: 'get_stock',
        description: 'Get stock quantity details by ID.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Stock quantity ID' },
            },
            required: ['id'],
        },
    },
    {
        name: 'get_stock_by_product',
        description: 'Get all stock quantities for a specific product across all locations.',
        inputSchema: {
            type: 'object',
            properties: {
                product_id: { type: 'number', description: 'Product ID' },
            },
            required: ['product_id'],
        },
    },
    {
        name: 'get_stock_by_location',
        description: 'Get all stock quantities at a specific location.',
        inputSchema: {
            type: 'object',
            properties: {
                location_id: { type: 'number', description: 'Location ID' },
            },
            required: ['location_id'],
        },
    },
    {
        name: 'adjust_stock',
        description: 'Adjust stock quantity for a product at a location (inventory adjustment).',
        inputSchema: {
            type: 'object',
            properties: {
                product_id: { type: 'number', description: 'Product ID' },
                location_id: { type: 'number', description: 'Location ID' },
                quantity: { type: 'number', description: 'New quantity (positive number)' },
                reason: { type: 'string', description: 'Reason for adjustment' },
                lot_id: { type: 'number', description: 'Lot ID if product is tracked by lot' },
            },
            required: ['product_id', 'location_id', 'quantity'],
        },
    },
    {
        name: 'transfer_stock',
        description: 'Transfer stock between locations.',
        inputSchema: {
            type: 'object',
            properties: {
                product_id: { type: 'number', description: 'Product ID' },
                source_location_id: { type: 'number', description: 'Source location ID' },
                dest_location_id: { type: 'number', description: 'Destination location ID' },
                quantity: { type: 'number', description: 'Quantity to transfer' },
                lot_id: { type: 'number', description: 'Lot ID if product is tracked by lot' },
                notes: { type: 'string', description: 'Transfer notes' },
            },
            required: ['product_id', 'source_location_id', 'dest_location_id', 'quantity'],
        },
    },
];
export async function handleStockTool(client, toolName, args) {
    switch (toolName) {
        case 'list_stock':
            return client.listStock(args);
        case 'get_stock':
            return client.getStock(args.id);
        case 'get_stock_by_product':
            return client.getStockByProduct(args.product_id);
        case 'get_stock_by_location':
            return client.getStockByLocation(args.location_id);
        case 'adjust_stock':
            return client.adjustStock(args);
        case 'transfer_stock':
            return client.transferStock(args);
        default:
            throw new Error(`Unknown stock tool: ${toolName}`);
    }
}
