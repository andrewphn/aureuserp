/**
 * Cabinet Tools - CRUD operations for cabinets
 */
export const cabinetTools = [
    {
        name: 'list_cabinets',
        description: 'List cabinets with optional filters (type, status, project, cabinet_run).',
        inputSchema: {
            type: 'object',
            properties: {
                project_id: { type: 'number', description: 'Filter by project ID' },
                cabinet_run_id: { type: 'number', description: 'Filter by cabinet run ID' },
                cabinet_type: { type: 'string', description: 'Filter by cabinet type (base, wall, tall, etc.)' },
                status: { type: 'string', description: 'Filter by status' },
                page: { type: 'number', description: 'Page number for pagination' },
                per_page: { type: 'number', description: 'Items per page (max 100)' },
            },
        },
    },
    {
        name: 'get_cabinet',
        description: 'Get cabinet details by ID, including sections and components.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Cabinet ID' },
                include: {
                    type: 'array',
                    items: { type: 'string' },
                    description: 'Related data to include: sections, drawers, doors, calculations',
                },
            },
            required: ['id'],
        },
    },
    {
        name: 'create_cabinet',
        description: 'Create a new cabinet with dimensions and specifications.',
        inputSchema: {
            type: 'object',
            properties: {
                cabinet_run_id: { type: 'number', description: 'Parent cabinet run ID' },
                cabinet_number: { type: 'string', description: 'Cabinet number/label' },
                cabinet_type: { type: 'string', description: 'Cabinet type (base, wall, tall, drawer_base, sink_base)' },
                length_inches: { type: 'number', description: 'Cabinet width in inches' },
                height_inches: { type: 'number', description: 'Cabinet height in inches' },
                depth_inches: { type: 'number', description: 'Cabinet depth in inches' },
                drawer_count: { type: 'number', description: 'Number of drawers' },
                door_count: { type: 'number', description: 'Number of doors' },
                has_face_frame: { type: 'boolean', description: 'Whether cabinet has face frame' },
                sort_order: { type: 'number', description: 'Display order' },
            },
            required: ['cabinet_run_id', 'cabinet_number', 'cabinet_type', 'length_inches', 'height_inches', 'depth_inches'],
        },
    },
    {
        name: 'update_cabinet',
        description: 'Update an existing cabinet dimensions or specifications.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Cabinet ID to update' },
                cabinet_number: { type: 'string', description: 'New cabinet number' },
                cabinet_type: { type: 'string', description: 'New cabinet type' },
                length_inches: { type: 'number', description: 'New width in inches' },
                height_inches: { type: 'number', description: 'New height in inches' },
                depth_inches: { type: 'number', description: 'New depth in inches' },
                drawer_count: { type: 'number', description: 'New drawer count' },
                door_count: { type: 'number', description: 'New door count' },
                has_face_frame: { type: 'boolean', description: 'Whether cabinet has face frame' },
                sort_order: { type: 'number', description: 'New sort order' },
            },
            required: ['id'],
        },
    },
    {
        name: 'delete_cabinet',
        description: 'Delete a cabinet and all its sections/components.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Cabinet ID to delete' },
            },
            required: ['id'],
        },
    },
    {
        name: 'calculate_cabinet',
        description: 'Run full calculation for a cabinet (cut list, face frame, etc.).',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Cabinet ID to calculate' },
            },
            required: ['id'],
        },
    },
    {
        name: 'get_cabinet_cut_list',
        description: 'Get detailed cut list for a cabinet for manufacturing.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Cabinet ID' },
                format: { type: 'string', description: 'Output format: json, csv, pdf' },
            },
            required: ['id'],
        },
    },
];
export async function handleCabinetTool(client, toolName, args) {
    switch (toolName) {
        case 'list_cabinets':
            return client.listCabinets(args);
        case 'get_cabinet':
            return client.getCabinet(args.id, args.include);
        case 'create_cabinet': {
            const { cabinet_run_id, ...data } = args;
            return client.createCabinet(cabinet_run_id, data);
        }
        case 'update_cabinet': {
            const { id, ...data } = args;
            return client.updateCabinet(id, data);
        }
        case 'delete_cabinet':
            return client.deleteCabinet(args.id);
        case 'calculate_cabinet':
            return client.calculateCabinet(args.id);
        case 'get_cabinet_cut_list':
            return client.getCabinetCutList(args.id, args.format);
        default:
            throw new Error(`Unknown cabinet tool: ${toolName}`);
    }
}
