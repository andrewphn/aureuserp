/**
 * Product Tools - CRUD operations for products
 */
export const productTools = [
    {
        name: 'list_products',
        description: 'List products with filters (category, vendor).',
        inputSchema: {
            type: 'object',
            properties: {
                category_id: { type: 'number', description: 'Filter by category ID' },
                vendor_id: { type: 'number', description: 'Filter by vendor ID' },
                search: { type: 'string', description: 'Search by name or SKU' },
                page: { type: 'number', description: 'Page number for pagination' },
                per_page: { type: 'number', description: 'Items per page (max 100)' },
            },
        },
    },
    {
        name: 'get_product',
        description: 'Get product details by ID with inventory.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Product ID' },
            },
            required: ['id'],
        },
    },
    {
        name: 'create_product',
        description: 'Create a new product.',
        inputSchema: {
            type: 'object',
            properties: {
                name: { type: 'string', description: 'Product name' },
                sku: { type: 'string', description: 'Stock keeping unit' },
                category_id: { type: 'number', description: 'Category ID' },
                vendor_id: { type: 'number', description: 'Vendor/supplier ID' },
                price: { type: 'number', description: 'Unit price' },
                cost: { type: 'number', description: 'Unit cost' },
                description: { type: 'string', description: 'Product description' },
                unit_of_measure: { type: 'string', description: 'Unit of measure (each, sqft, lnft)' },
            },
            required: ['name'],
        },
    },
    {
        name: 'update_product',
        description: 'Update product fields.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Product ID to update' },
                name: { type: 'string', description: 'New product name' },
                sku: { type: 'string', description: 'New SKU' },
                category_id: { type: 'number', description: 'New category ID' },
                vendor_id: { type: 'number', description: 'New vendor ID' },
                price: { type: 'number', description: 'New price' },
                cost: { type: 'number', description: 'New cost' },
                description: { type: 'string', description: 'New description' },
                unit_of_measure: { type: 'string', description: 'New unit of measure' },
            },
            required: ['id'],
        },
    },
    {
        name: 'delete_product',
        description: 'Delete a product.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Product ID to delete' },
            },
            required: ['id'],
        },
    },
    {
        name: 'search_products',
        description: 'Search products by name or SKU.',
        inputSchema: {
            type: 'object',
            properties: {
                query: { type: 'string', description: 'Search query' },
                limit: { type: 'number', description: 'Max results to return (default 20)' },
            },
            required: ['query'],
        },
    },
];
export async function handleProductTool(client, toolName, args) {
    switch (toolName) {
        case 'list_products':
            return client.listProducts(args);
        case 'get_product':
            return client.getProduct(args.id);
        case 'create_product':
            return client.createProduct(args);
        case 'update_product': {
            const { id, ...data } = args;
            return client.updateProduct(id, data);
        }
        case 'delete_product':
            return client.deleteProduct(args.id);
        case 'search_products':
            return client.searchProducts(args.query, args.limit);
        default:
            throw new Error(`Unknown product tool: ${toolName}`);
    }
}
