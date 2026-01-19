/**
 * Room Tools - CRUD operations for rooms
 */
export const roomTools = [
    {
        name: 'list_rooms',
        description: 'List rooms, optionally filtered by project.',
        inputSchema: {
            type: 'object',
            properties: {
                project_id: { type: 'number', description: 'Filter by project ID' },
            },
        },
    },
    {
        name: 'get_room',
        description: 'Get room details by ID, including locations.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Room ID' },
            },
            required: ['id'],
        },
    },
    {
        name: 'create_room',
        description: 'Create a new room in a project.',
        inputSchema: {
            type: 'object',
            properties: {
                project_id: { type: 'number', description: 'Parent project ID' },
                name: { type: 'string', description: 'Room name (e.g., Kitchen, Master Bath)' },
                room_code: { type: 'string', description: 'Room code for labeling' },
                sort_order: { type: 'number', description: 'Display order' },
            },
            required: ['project_id', 'name'],
        },
    },
    {
        name: 'update_room',
        description: 'Update an existing room.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Room ID to update' },
                name: { type: 'string', description: 'New room name' },
                room_code: { type: 'string', description: 'New room code' },
                sort_order: { type: 'number', description: 'New sort order' },
            },
            required: ['id'],
        },
    },
    {
        name: 'delete_room',
        description: 'Delete a room and all its locations/cabinet runs.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Room ID to delete' },
            },
            required: ['id'],
        },
    },
];
export async function handleRoomTool(client, toolName, args) {
    switch (toolName) {
        case 'list_rooms':
            return client.listRooms(args.project_id);
        case 'get_room':
            return client.getRoom(args.id);
        case 'create_room': {
            const { project_id, ...data } = args;
            return client.createRoom(project_id, data);
        }
        case 'update_room': {
            const { id, ...data } = args;
            return client.updateRoom(id, data);
        }
        case 'delete_room':
            return client.deleteRoom(args.id);
        default:
            throw new Error(`Unknown room tool: ${toolName}`);
    }
}
