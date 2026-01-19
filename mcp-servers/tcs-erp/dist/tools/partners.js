/**
 * Partner Tools - CRUD operations for partners (customers, vendors)
 */
export const partnerTools = [
    {
        name: 'list_partners',
        description: 'List partners (customers, vendors).',
        inputSchema: {
            type: 'object',
            properties: {
                type: { type: 'string', description: 'Filter by type: customer, vendor, both' },
                search: { type: 'string', description: 'Search by name or email' },
                page: { type: 'number', description: 'Page number for pagination' },
                per_page: { type: 'number', description: 'Items per page (max 100)' },
            },
        },
    },
    {
        name: 'get_partner',
        description: 'Get partner details by ID.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Partner ID' },
            },
            required: ['id'],
        },
    },
    {
        name: 'create_partner',
        description: 'Create a new partner.',
        inputSchema: {
            type: 'object',
            properties: {
                name: { type: 'string', description: 'Partner name' },
                email: { type: 'string', description: 'Email address' },
                phone: { type: 'string', description: 'Phone number' },
                type: { type: 'string', description: 'Partner type: customer, vendor, both' },
                address: { type: 'string', description: 'Street address' },
                city: { type: 'string', description: 'City' },
                state: { type: 'string', description: 'State/province' },
                zip: { type: 'string', description: 'ZIP/postal code' },
                country: { type: 'string', description: 'Country' },
            },
            required: ['name'],
        },
    },
    {
        name: 'update_partner',
        description: 'Update partner fields.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Partner ID to update' },
                name: { type: 'string', description: 'New name' },
                email: { type: 'string', description: 'New email' },
                phone: { type: 'string', description: 'New phone' },
                type: { type: 'string', description: 'New type' },
                address: { type: 'string', description: 'New address' },
                city: { type: 'string', description: 'New city' },
                state: { type: 'string', description: 'New state' },
                zip: { type: 'string', description: 'New ZIP' },
                country: { type: 'string', description: 'New country' },
            },
            required: ['id'],
        },
    },
    {
        name: 'delete_partner',
        description: 'Delete a partner.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Partner ID to delete' },
            },
            required: ['id'],
        },
    },
];
export async function handlePartnerTool(client, toolName, args) {
    switch (toolName) {
        case 'list_partners':
            return client.listPartners(args);
        case 'get_partner':
            return client.getPartner(args.id);
        case 'create_partner':
            return client.createPartner(args);
        case 'update_partner': {
            const { id, ...data } = args;
            return client.updatePartner(id, data);
        }
        case 'delete_partner':
            return client.deletePartner(args.id);
        default:
            throw new Error(`Unknown partner tool: ${toolName}`);
    }
}
