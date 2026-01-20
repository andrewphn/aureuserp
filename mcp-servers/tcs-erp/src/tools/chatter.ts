/**
 * Chatter Tools - CRUD operations for messages/notes on any resource
 *
 * Chatter is a polymorphic messaging system that can be attached to any model.
 * Use messageable_type shortcuts (project, cabinet, partner, etc.) for convenience.
 */

import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';

export const chatterTools: Tool[] = [
  {
    name: 'list_chatter',
    description: 'List all chatter messages with optional filters. Use filter[messageable_type] and filter[messageable_id] to filter by resource.',
    inputSchema: {
      type: 'object',
      properties: {
        'filter[messageable_type]': { type: 'string', description: 'Filter by resource type (full model class)' },
        'filter[messageable_id]': { type: 'number', description: 'Filter by resource ID' },
        'filter[type]': { type: 'string', description: 'Filter by message type: comment, note, activity, log' },
        'filter[is_internal]': { type: 'boolean', description: 'Filter by internal/external' },
        search: { type: 'string', description: 'Search in subject, body, name, summary' },
        include: { type: 'string', description: 'Include relations: attachments, causer, activityType, assignedTo' },
        page: { type: 'number', description: 'Page number' },
        per_page: { type: 'number', description: 'Items per page (max 100)' },
      },
    },
  },
  {
    name: 'get_chatter',
    description: 'Get a single chatter message by ID.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Message ID' },
        include: { type: 'string', description: 'Include relations: attachments, causer, activityType, assignedTo' },
      },
      required: ['id'],
    },
  },
  {
    name: 'create_chatter',
    description: 'Create a new chatter message attached to any resource.',
    inputSchema: {
      type: 'object',
      properties: {
        messageable_type: { type: 'string', description: 'Full model class (e.g., Webkul\\\\Project\\\\Models\\\\Project)' },
        messageable_id: { type: 'number', description: 'Resource ID to attach message to' },
        type: { type: 'string', enum: ['comment', 'note', 'activity', 'log'], description: 'Message type' },
        subject: { type: 'string', description: 'Message subject' },
        body: { type: 'string', description: 'Message body (HTML supported)' },
        summary: { type: 'string', description: 'Brief summary (max 500 chars)' },
        is_internal: { type: 'boolean', description: 'Internal note (not visible to customers)' },
        date_deadline: { type: 'string', description: 'Deadline date (YYYY-MM-DD)' },
        activity_type_id: { type: 'number', description: 'Activity type ID for activities' },
        assigned_to: { type: 'number', description: 'User ID to assign to' },
      },
      required: ['messageable_type', 'messageable_id'],
    },
  },
  {
    name: 'update_chatter',
    description: 'Update an existing chatter message.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Message ID to update' },
        type: { type: 'string', enum: ['comment', 'note', 'activity', 'log'], description: 'Message type' },
        subject: { type: 'string', description: 'Message subject' },
        body: { type: 'string', description: 'Message body' },
        summary: { type: 'string', description: 'Brief summary' },
        is_internal: { type: 'boolean', description: 'Internal note' },
        date_deadline: { type: 'string', description: 'Deadline date' },
        activity_type_id: { type: 'number', description: 'Activity type ID' },
        assigned_to: { type: 'number', description: 'User ID to assign to' },
      },
      required: ['id'],
    },
  },
  {
    name: 'delete_chatter',
    description: 'Delete a chatter message.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Message ID to delete' },
      },
      required: ['id'],
    },
  },
  {
    name: 'get_chatter_for_resource',
    description: 'Get all chatter messages for a specific resource using shorthand type names.',
    inputSchema: {
      type: 'object',
      properties: {
        type: {
          type: 'string',
          enum: ['project', 'cabinet', 'room', 'partner', 'sales_order', 'purchase_order', 'invoice', 'task', 'employee'],
          description: 'Resource type shorthand',
        },
        id: { type: 'number', description: 'Resource ID' },
        message_type: { type: 'string', enum: ['comment', 'note', 'activity', 'log'], description: 'Filter by message type' },
        is_internal: { type: 'boolean', description: 'Filter by internal/external' },
        include: { type: 'string', description: 'Include relations' },
        page: { type: 'number', description: 'Page number' },
        per_page: { type: 'number', description: 'Items per page' },
      },
      required: ['type', 'id'],
    },
  },
  {
    name: 'add_chatter_to_resource',
    description: 'Add a chatter message to a specific resource using shorthand type names.',
    inputSchema: {
      type: 'object',
      properties: {
        type: {
          type: 'string',
          enum: ['project', 'cabinet', 'room', 'partner', 'sales_order', 'purchase_order', 'invoice', 'task', 'employee'],
          description: 'Resource type shorthand',
        },
        id: { type: 'number', description: 'Resource ID' },
        message_type: { type: 'string', enum: ['comment', 'note', 'activity', 'log'], description: 'Message type' },
        subject: { type: 'string', description: 'Message subject' },
        body: { type: 'string', description: 'Message body (HTML supported)' },
        summary: { type: 'string', description: 'Brief summary' },
        is_internal: { type: 'boolean', description: 'Internal note' },
        date_deadline: { type: 'string', description: 'Deadline date' },
        assigned_to: { type: 'number', description: 'User ID to assign to' },
      },
      required: ['type', 'id'],
    },
  },
  {
    name: 'pin_chatter',
    description: 'Pin a chatter message to the top of the list.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Message ID to pin' },
      },
      required: ['id'],
    },
  },
  {
    name: 'unpin_chatter',
    description: 'Unpin a chatter message.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Message ID to unpin' },
      },
      required: ['id'],
    },
  },
  {
    name: 'get_chatter_types',
    description: 'Get available messageable types and message types for chatter.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },
];

export async function handleChatterTool(
  client: TcsErpApiClient,
  toolName: string,
  args: Record<string, unknown>
): Promise<unknown> {
  switch (toolName) {
    case 'list_chatter':
      return client.listChatter(args);
    case 'get_chatter':
      return client.getChatter(args.id as number, args.include as string | undefined);
    case 'create_chatter':
      return client.createChatter(args);
    case 'update_chatter': {
      const { id, ...data } = args;
      return client.updateChatter(id as number, data);
    }
    case 'delete_chatter':
      return client.deleteChatter(args.id as number);
    case 'get_chatter_for_resource':
      return client.getChatterForResource(
        args.type as string,
        args.id as number,
        args
      );
    case 'add_chatter_to_resource':
      return client.addChatterToResource(
        args.type as string,
        args.id as number,
        args
      );
    case 'pin_chatter':
      return client.pinChatter(args.id as number);
    case 'unpin_chatter':
      return client.unpinChatter(args.id as number);
    case 'get_chatter_types':
      return client.getChatterTypes();
    default:
      throw new Error(`Unknown chatter tool: ${toolName}`);
  }
}
