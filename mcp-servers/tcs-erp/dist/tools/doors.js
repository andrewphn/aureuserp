/**
 * Door Tools - CRUD operations for doors
 */
export const doorTools = [
    {
        name: 'list_doors',
        description: 'List doors for a section.',
        inputSchema: {
            type: 'object',
            properties: {
                section_id: { type: 'number', description: 'Filter by section ID' },
                cabinet_id: { type: 'number', description: 'Filter by cabinet ID' },
            },
        },
    },
    {
        name: 'get_door',
        description: 'Get door details by ID.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Door ID' },
            },
            required: ['id'],
        },
    },
    {
        name: 'create_door',
        description: 'Create a new door.',
        inputSchema: {
            type: 'object',
            properties: {
                section_id: { type: 'number', description: 'Parent section ID' },
                width_inches: { type: 'number', description: 'Door width in inches' },
                height_inches: { type: 'number', description: 'Door height in inches' },
                door_style: { type: 'string', description: 'Door style (shaker, slab, raised_panel)' },
                hinge_side: { type: 'string', description: 'Hinge side (left, right)' },
                sort_order: { type: 'number', description: 'Display order' },
            },
            required: ['section_id', 'width_inches', 'height_inches'],
        },
    },
    {
        name: 'update_door',
        description: 'Update door dimensions or style.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Door ID to update' },
                width_inches: { type: 'number', description: 'New width in inches' },
                height_inches: { type: 'number', description: 'New height in inches' },
                door_style: { type: 'string', description: 'New door style' },
                hinge_side: { type: 'string', description: 'New hinge side' },
                sort_order: { type: 'number', description: 'New sort order' },
            },
            required: ['id'],
        },
    },
    {
        name: 'delete_door',
        description: 'Delete a door.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Door ID to delete' },
            },
            required: ['id'],
        },
    },
];
export async function handleDoorTool(client, toolName, args) {
    switch (toolName) {
        case 'list_doors':
            return client.listDoors(args.section_id, args.cabinet_id);
        case 'get_door':
            return client.getDoor(args.id);
        case 'create_door': {
            const { section_id, ...data } = args;
            return client.createDoor(section_id, data);
        }
        case 'update_door': {
            const { id, ...data } = args;
            return client.updateDoor(id, data);
        }
        case 'delete_door':
            return client.deleteDoor(args.id);
        default:
            throw new Error(`Unknown door tool: ${toolName}`);
    }
}
