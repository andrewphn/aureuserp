/**
 * Batch Tools - Batch operations for multiple records
 */

import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';

export const batchTools: Tool[] = [
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

export async function handleBatchTool(
  client: TcsErpApiClient,
  toolName: string,
  args: Record<string, unknown>
): Promise<unknown> {
  switch (toolName) {
    case 'batch_create':
      return client.batchCreate(
        args.entity_type as string,
        args.records as Record<string, unknown>[],
        args.parent_id as number | undefined
      );
    case 'batch_update':
      return client.batchUpdate(
        args.entity_type as string,
        args.updates as Array<{ id: number; data: Record<string, unknown> }>
      );
    case 'batch_delete':
      return client.batchDelete(args.entity_type as string, args.ids as number[]);
    default:
      throw new Error(`Unknown batch tool: ${toolName}`);
  }
}
