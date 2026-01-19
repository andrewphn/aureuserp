/**
 * Location Tools - CRUD operations for room locations
 */

import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';

export const locationTools: Tool[] = [
  {
    name: 'list_locations',
    description: 'List room locations, optionally filtered by room.',
    inputSchema: {
      type: 'object',
      properties: {
        room_id: { type: 'number', description: 'Filter by room ID' },
      },
    },
  },
  {
    name: 'get_location',
    description: 'Get location details by ID, including cabinet runs.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Location ID' },
      },
      required: ['id'],
    },
  },
  {
    name: 'create_location',
    description: 'Create a new location in a room (e.g., North Wall, Island).',
    inputSchema: {
      type: 'object',
      properties: {
        room_id: { type: 'number', description: 'Parent room ID' },
        name: { type: 'string', description: 'Location name' },
        sort_order: { type: 'number', description: 'Display order' },
      },
      required: ['room_id', 'name'],
    },
  },
  {
    name: 'update_location',
    description: 'Update an existing location.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Location ID to update' },
        name: { type: 'string', description: 'New location name' },
        sort_order: { type: 'number', description: 'New sort order' },
      },
      required: ['id'],
    },
  },
  {
    name: 'delete_location',
    description: 'Delete a location and all its cabinet runs.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Location ID to delete' },
      },
      required: ['id'],
    },
  },
];

export async function handleLocationTool(
  client: TcsErpApiClient,
  toolName: string,
  args: Record<string, unknown>
): Promise<unknown> {
  switch (toolName) {
    case 'list_locations':
      return client.listLocations(args.room_id as number | undefined);
    case 'get_location':
      return client.getLocation(args.id as number);
    case 'create_location': {
      const { room_id, ...data } = args;
      return client.createLocation(room_id as number, data);
    }
    case 'update_location': {
      const { id, ...data } = args;
      return client.updateLocation(id as number, data);
    }
    case 'delete_location':
      return client.deleteLocation(args.id as number);
    default:
      throw new Error(`Unknown location tool: ${toolName}`);
  }
}
