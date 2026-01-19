/**
 * Bill of Materials (BOM) Tools - CRUD and generation operations for BOMs
 */
export const bomTools = [
    {
        name: 'list_bom',
        description: 'List bill of materials items with filters (project, cabinet, material type, status).',
        inputSchema: {
            type: 'object',
            properties: {
                project_id: { type: 'number', description: 'Filter by project ID' },
                cabinet_id: { type: 'number', description: 'Filter by cabinet ID' },
                material_type: { type: 'string', description: 'Filter by material type (sheet_good, hardware, edge_banding, finish, other)' },
                status: { type: 'string', description: 'Filter by status (pending, ordered, received, issued)' },
                search: { type: 'string', description: 'Search by name or description' },
                page: { type: 'number', description: 'Page number for pagination' },
                per_page: { type: 'number', description: 'Items per page (max 100)' },
            },
        },
    },
    {
        name: 'get_bom',
        description: 'Get BOM item details by ID.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'BOM item ID' },
            },
            required: ['id'],
        },
    },
    {
        name: 'create_bom',
        description: 'Create a new BOM item.',
        inputSchema: {
            type: 'object',
            properties: {
                project_id: { type: 'number', description: 'Project ID' },
                cabinet_id: { type: 'number', description: 'Cabinet ID (optional)' },
                product_id: { type: 'number', description: 'Product ID from inventory' },
                name: { type: 'string', description: 'Item name' },
                description: { type: 'string', description: 'Item description' },
                material_type: { type: 'string', description: 'Material type (sheet_good, hardware, edge_banding, finish, other)' },
                quantity: { type: 'number', description: 'Quantity needed' },
                uom: { type: 'string', description: 'Unit of measure (each, sqft, lnft, pair)' },
                unit_cost: { type: 'number', description: 'Unit cost' },
                status: { type: 'string', description: 'Status (pending, ordered, received, issued)' },
            },
            required: ['project_id', 'name', 'quantity'],
        },
    },
    {
        name: 'update_bom',
        description: 'Update a BOM item.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'BOM item ID to update' },
                name: { type: 'string', description: 'New name' },
                description: { type: 'string', description: 'New description' },
                quantity: { type: 'number', description: 'New quantity' },
                unit_cost: { type: 'number', description: 'New unit cost' },
                status: { type: 'string', description: 'New status' },
            },
            required: ['id'],
        },
    },
    {
        name: 'delete_bom',
        description: 'Delete a BOM item.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'BOM item ID to delete' },
            },
            required: ['id'],
        },
    },
    {
        name: 'get_bom_by_project',
        description: 'Get all BOM items for a project, grouped by material type with totals.',
        inputSchema: {
            type: 'object',
            properties: {
                project_id: { type: 'number', description: 'Project ID' },
            },
            required: ['project_id'],
        },
    },
    {
        name: 'get_bom_by_cabinet',
        description: 'Get BOM items for a specific cabinet.',
        inputSchema: {
            type: 'object',
            properties: {
                cabinet_id: { type: 'number', description: 'Cabinet ID' },
            },
            required: ['cabinet_id'],
        },
    },
    {
        name: 'generate_bom',
        description: 'Generate BOM items for a project based on its cabinets. Calculates materials needed (sheet goods, hardware, etc.).',
        inputSchema: {
            type: 'object',
            properties: {
                project_id: { type: 'number', description: 'Project ID to generate BOM for' },
                overwrite: { type: 'boolean', description: 'Overwrite existing BOM items (default false)' },
            },
            required: ['project_id'],
        },
    },
    {
        name: 'bulk_update_bom_status',
        description: 'Update status of multiple BOM items at once.',
        inputSchema: {
            type: 'object',
            properties: {
                ids: {
                    type: 'array',
                    items: { type: 'number' },
                    description: 'BOM item IDs to update',
                },
                status: { type: 'string', description: 'New status (pending, ordered, received, issued)' },
            },
            required: ['ids', 'status'],
        },
    },
];
export async function handleBomTool(client, toolName, args) {
    switch (toolName) {
        case 'list_bom':
            return client.listBom(args);
        case 'get_bom':
            return client.getBom(args.id);
        case 'create_bom':
            return client.createBom(args);
        case 'update_bom': {
            const { id, ...data } = args;
            return client.updateBom(id, data);
        }
        case 'delete_bom':
            return client.deleteBom(args.id);
        case 'get_bom_by_project':
            return client.getBomByProject(args.project_id);
        case 'get_bom_by_cabinet':
            return client.getBomByCabinet(args.cabinet_id);
        case 'generate_bom':
            return client.generateBom(args.project_id, args.overwrite);
        case 'bulk_update_bom_status':
            return client.bulkUpdateBomStatus(args.ids, args.status);
        default:
            throw new Error(`Unknown BOM tool: ${toolName}`);
    }
}
