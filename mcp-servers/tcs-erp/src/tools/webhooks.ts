/**
 * Webhook Tools - Webhook subscription management
 */

import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';

export const webhookTools: Tool[] = [
  {
    name: 'list_webhooks',
    description: 'List webhook subscriptions.',
    inputSchema: {
      type: 'object',
      properties: {
        active: { type: 'boolean', description: 'Filter by active status' },
        page: { type: 'number', description: 'Page number for pagination' },
        per_page: { type: 'number', description: 'Items per page (max 100)' },
      },
    },
  },
  {
    name: 'create_webhook',
    description: 'Create a new webhook subscription.',
    inputSchema: {
      type: 'object',
      properties: {
        url: { type: 'string', description: 'Webhook endpoint URL' },
        events: {
          type: 'array',
          items: { type: 'string' },
          description: 'Events to subscribe to (e.g., cabinet.created, rhino.extraction_completed)',
        },
        secret: { type: 'string', description: 'Secret for HMAC signature verification' },
        description: { type: 'string', description: 'Webhook description' },
      },
      required: ['url', 'events'],
    },
  },
  {
    name: 'delete_webhook',
    description: 'Remove a webhook subscription.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Webhook ID to delete' },
      },
      required: ['id'],
    },
  },
  {
    name: 'test_webhook',
    description: 'Send a test payload to a webhook.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Webhook ID to test' },
        event: { type: 'string', description: 'Event type to simulate (optional)' },
      },
      required: ['id'],
    },
  },
];

export async function handleWebhookTool(
  client: TcsErpApiClient,
  toolName: string,
  args: Record<string, unknown>
): Promise<unknown> {
  switch (toolName) {
    case 'list_webhooks':
      return client.listWebhooks(args);
    case 'create_webhook':
      return client.createWebhook(args);
    case 'delete_webhook':
      return client.deleteWebhook(args.id as number);
    case 'test_webhook':
      return client.testWebhook(args.id as number, args.event as string | undefined);
    default:
      throw new Error(`Unknown webhook tool: ${toolName}`);
  }
}
