/**
 * Batch Tools - Batch operations for multiple records
 */
export const batchTools = [
    {
        name: 'batch_create',
        description: 'Batch create multiple records of the same type.',
        inputSchema: {
            type: 'object',
            properties: {
                entity_type: {
                    type: 'string',
                    description: 'Entity type (cabinets, rooms, locations, cabinet_runs, sections, drawers, doors)',
                },
                records: {
                    type: 'array',
                    items: { type: 'object' },
                    description: 'Array of records to create',
                },
                parent_id: { type: 'number', description: 'Parent ID for the records (e.g., cabinet_run_id for cabinets)' },
            },
            required: ['entity_type', 'records'],
        },
    },
    {
        name: 'batch_update',
        description: 'Batch update multiple records.',
        inputSchema: {
            type: 'object',
            properties: {
                entity_type: {
                    type: 'string',
                    description: 'Entity type (cabinets, rooms, locations, cabinet_runs, sections, drawers, doors)',
                },
                updates: {
                    type: 'array',
                    items: {
                        type: 'object',
                        properties: {
                            id: { type: 'number', description: 'Record ID to update' },
                            data: { type: 'object', description: 'Fields to update' },
                        },
                        required: ['id', 'data'],
                    },
                    description: 'Array of {id, data} objects',
                },
            },
            required: ['entity_type', 'updates'],
        },
    },
    {
        name: 'batch_delete',
        description: 'Batch delete multiple records.',
        inputSchema: {
            type: 'object',
            properties: {
                entity_type: {
                    type: 'string',
                    description: 'Entity type (cabinets, rooms, locations, cabinet_runs, sections, drawers, doors)',
                },
                ids: {
                    type: 'array',
                    items: { type: 'number' },
                    description: 'Array of record IDs to delete',
                },
            },
            required: ['entity_type', 'ids'],
        },
    },
];
export async function handleBatchTool(client, toolName, args) {
    switch (toolName) {
        case 'batch_create':
            return client.batchCreate(args.entity_type, args.records, args.parent_id);
        case 'batch_update':
            return client.batchUpdate(args.entity_type, args.updates);
        case 'batch_delete':
            return client.batchDelete(args.entity_type, args.ids);
        default:
            throw new Error(`Unknown batch tool: ${toolName}`);
    }
}
