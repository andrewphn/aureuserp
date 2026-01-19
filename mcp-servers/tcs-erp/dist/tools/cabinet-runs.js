/**
 * Cabinet Run Tools - CRUD operations for cabinet runs
 */
export const cabinetRunTools = [
    {
        name: 'list_cabinet_runs',
        description: 'List cabinet runs, optionally filtered by location.',
        inputSchema: {
            type: 'object',
            properties: {
                location_id: { type: 'number', description: 'Filter by location ID' },
            },
        },
    },
    {
        name: 'get_cabinet_run',
        description: 'Get cabinet run details by ID, including cabinets.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Cabinet run ID' },
            },
            required: ['id'],
        },
    },
    {
        name: 'create_cabinet_run',
        description: 'Create a new cabinet run in a location.',
        inputSchema: {
            type: 'object',
            properties: {
                location_id: { type: 'number', description: 'Parent location ID' },
                name: { type: 'string', description: 'Run name (e.g., Upper Left, Base Right)' },
                sort_order: { type: 'number', description: 'Display order' },
            },
            required: ['location_id', 'name'],
        },
    },
    {
        name: 'update_cabinet_run',
        description: 'Update an existing cabinet run.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Cabinet run ID to update' },
                name: { type: 'string', description: 'New run name' },
                sort_order: { type: 'number', description: 'New sort order' },
            },
            required: ['id'],
        },
    },
    {
        name: 'delete_cabinet_run',
        description: 'Delete a cabinet run and all its cabinets.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Cabinet run ID to delete' },
            },
            required: ['id'],
        },
    },
];
export async function handleCabinetRunTool(client, toolName, args) {
    switch (toolName) {
        case 'list_cabinet_runs':
            return client.listCabinetRuns(args.location_id);
        case 'get_cabinet_run':
            return client.getCabinetRun(args.id);
        case 'create_cabinet_run': {
            const { location_id, ...data } = args;
            return client.createCabinetRun(location_id, data);
        }
        case 'update_cabinet_run': {
            const { id, ...data } = args;
            return client.updateCabinetRun(id, data);
        }
        case 'delete_cabinet_run':
            return client.deleteCabinetRun(args.id);
        default:
            throw new Error(`Unknown cabinet run tool: ${toolName}`);
    }
}
