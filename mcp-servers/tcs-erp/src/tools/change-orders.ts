/**
 * Change Order Tools - CRUD and approval operations for change orders
 */

import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';

export const changeOrderTools: Tool[] = [
  {
    name: 'list_change_orders',
    description: 'List change orders with filters (project, status, date range).',
    inputSchema: {
      type: 'object',
      properties: {
        project_id: { type: 'number', description: 'Filter by project ID' },
        status: { type: 'string', description: 'Filter by status (draft, pending, approved, rejected)' },
        date_from: { type: 'string', description: 'Filter from this date (YYYY-MM-DD)' },
        date_to: { type: 'string', description: 'Filter to this date (YYYY-MM-DD)' },
        search: { type: 'string', description: 'Search by change order number or description' },
        page: { type: 'number', description: 'Page number for pagination' },
        per_page: { type: 'number', description: 'Items per page (max 100)' },
      },
    },
  },
  {
    name: 'get_change_order',
    description: 'Get change order details by ID.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Change order ID' },
      },
      required: ['id'],
    },
  },
  {
    name: 'create_change_order',
    description: 'Create a new change order for a project.',
    inputSchema: {
      type: 'object',
      properties: {
        project_id: { type: 'number', description: 'Project ID' },
        title: { type: 'string', description: 'Change order title' },
        description: { type: 'string', description: 'Detailed description of changes' },
        reason: { type: 'string', description: 'Reason for change (customer_request, design_error, site_condition, etc.)' },
        cost_impact: { type: 'number', description: 'Cost impact (positive for additions, negative for credits)' },
        schedule_impact_days: { type: 'number', description: 'Schedule impact in days' },
        affected_areas: { type: 'string', description: 'Areas affected by the change' },
        priority: { type: 'string', description: 'Priority level (low, medium, high, critical)' },
      },
      required: ['project_id', 'title', 'description'],
    },
  },
  {
    name: 'update_change_order',
    description: 'Update a change order (only draft/pending orders can be updated).',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Change order ID to update' },
        title: { type: 'string', description: 'New title' },
        description: { type: 'string', description: 'New description' },
        reason: { type: 'string', description: 'New reason' },
        cost_impact: { type: 'number', description: 'New cost impact' },
        schedule_impact_days: { type: 'number', description: 'New schedule impact' },
        affected_areas: { type: 'string', description: 'New affected areas' },
        priority: { type: 'string', description: 'New priority' },
      },
      required: ['id'],
    },
  },
  {
    name: 'delete_change_order',
    description: 'Delete a change order (only draft orders can be deleted).',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Change order ID to delete' },
      },
      required: ['id'],
    },
  },
  {
    name: 'approve_change_order',
    description: 'Approve a change order.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Change order ID to approve' },
        notes: { type: 'string', description: 'Approval notes' },
        approved_cost: { type: 'number', description: 'Approved cost amount (if different from requested)' },
        approved_schedule_days: { type: 'number', description: 'Approved schedule impact days' },
      },
      required: ['id'],
    },
  },
  {
    name: 'reject_change_order',
    description: 'Reject a change order.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Change order ID to reject' },
        reason: { type: 'string', description: 'Rejection reason' },
      },
      required: ['id', 'reason'],
    },
  },
  {
    name: 'get_change_orders_by_project',
    description: 'Get all change orders for a project with summary statistics.',
    inputSchema: {
      type: 'object',
      properties: {
        project_id: { type: 'number', description: 'Project ID' },
      },
      required: ['project_id'],
    },
  },
];

export async function handleChangeOrderTool(
  client: TcsErpApiClient,
  toolName: string,
  args: Record<string, unknown>
): Promise<unknown> {
  switch (toolName) {
    case 'list_change_orders':
      return client.listChangeOrders(args);
    case 'get_change_order':
      return client.getChangeOrder(args.id as number);
    case 'create_change_order':
      return client.createChangeOrder(args);
    case 'update_change_order': {
      const { id, ...data } = args;
      return client.updateChangeOrder(id as number, data);
    }
    case 'delete_change_order':
      return client.deleteChangeOrder(args.id as number);
    case 'approve_change_order':
      return client.approveChangeOrder(args.id as number, args);
    case 'reject_change_order':
      return client.rejectChangeOrder(args.id as number, args.reason as string);
    case 'get_change_orders_by_project':
      return client.getChangeOrdersByProject(args.project_id as number);
    default:
      throw new Error(`Unknown change order tool: ${toolName}`);
  }
}
