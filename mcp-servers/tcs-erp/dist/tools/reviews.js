/**
 * Review Tools - Rhino extraction review queue management
 */
export const reviewTools = [
    {
        name: 'list_reviews',
        description: 'List pending review items.',
        inputSchema: {
            type: 'object',
            properties: {
                status: { type: 'string', description: 'Filter by status (pending, approved, rejected)' },
                job_id: { type: 'number', description: 'Filter by extraction job ID' },
                min_confidence: { type: 'number', description: 'Minimum confidence score (0-100)' },
                max_confidence: { type: 'number', description: 'Maximum confidence score (0-100)' },
                page: { type: 'number', description: 'Page number for pagination' },
                per_page: { type: 'number', description: 'Items per page (max 100)' },
            },
        },
    },
    {
        name: 'get_review',
        description: 'Get review details with extraction data.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Review ID' },
            },
            required: ['id'],
        },
    },
    {
        name: 'approve_review',
        description: 'Approve extraction with optional corrections.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Review ID to approve' },
                corrections: {
                    type: 'object',
                    description: 'Optional corrections to apply',
                    properties: {
                        length_inches: { type: 'number', description: 'Corrected width' },
                        height_inches: { type: 'number', description: 'Corrected height' },
                        depth_inches: { type: 'number', description: 'Corrected depth' },
                        cabinet_type: { type: 'string', description: 'Corrected cabinet type' },
                        cabinet_number: { type: 'string', description: 'Corrected cabinet number' },
                    },
                },
                notes: { type: 'string', description: 'Reviewer notes' },
            },
            required: ['id'],
        },
    },
    {
        name: 'reject_review',
        description: 'Reject extraction.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'number', description: 'Review ID to reject' },
                reason: { type: 'string', description: 'Rejection reason' },
            },
            required: ['id', 'reason'],
        },
    },
];
export async function handleReviewTool(client, toolName, args) {
    switch (toolName) {
        case 'list_reviews':
            return client.listReviews(args);
        case 'get_review':
            return client.getReview(args.id);
        case 'approve_review':
            return client.approveReview(args.id, args.corrections, args.notes);
        case 'reject_review':
            return client.rejectReview(args.id, args.reason);
        default:
            throw new Error(`Unknown review tool: ${toolName}`);
    }
}
