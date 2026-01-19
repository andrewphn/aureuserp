/**
 * Drawer Tools - CRUD operations for drawers
 */

import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';

export const drawerTools: Tool[] = [
  {
    name: 'list_drawers',
    description: 'List drawers for a section.',
    inputSchema: {
      type: 'object',
      properties: {
        section_id: { type: 'number', description: 'Filter by section ID' },
        cabinet_id: { type: 'number', description: 'Filter by cabinet ID' },
      },
    },
  },
  {
    name: 'get_drawer',
    description: 'Get drawer details by ID with Blum specs.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Drawer ID' },
      },
      required: ['id'],
    },
  },
  {
    name: 'create_drawer',
    description: 'Create a new drawer.',
    inputSchema: {
      type: 'object',
      properties: {
        section_id: { type: 'number', description: 'Parent section ID' },
        height_inches: { type: 'number', description: 'Drawer front height in inches' },
        drawer_box_height: { type: 'number', description: 'Drawer box height in inches' },
        slide_type: { type: 'string', description: 'Slide type (soft_close, push_to_open, standard)' },
        sort_order: { type: 'number', description: 'Display order (top to bottom)' },
      },
      required: ['section_id', 'height_inches'],
    },
  },
  {
    name: 'update_drawer',
    description: 'Update drawer dimensions.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Drawer ID to update' },
        height_inches: { type: 'number', description: 'New front height in inches' },
        drawer_box_height: { type: 'number', description: 'New box height in inches' },
        slide_type: { type: 'string', description: 'New slide type' },
        sort_order: { type: 'number', description: 'New sort order' },
      },
      required: ['id'],
    },
  },
  {
    name: 'delete_drawer',
    description: 'Delete a drawer.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Drawer ID to delete' },
      },
      required: ['id'],
    },
  },
];

export async function handleDrawerTool(
  client: TcsErpApiClient,
  toolName: string,
  args: Record<string, unknown>
): Promise<unknown> {
  switch (toolName) {
    case 'list_drawers':
      return client.listDrawers(args.section_id as number | undefined, args.cabinet_id as number | undefined);
    case 'get_drawer':
      return client.getDrawer(args.id as number);
    case 'create_drawer': {
      const { section_id, ...data } = args;
      return client.createDrawer(section_id as number, data);
    }
    case 'update_drawer': {
      const { id, ...data } = args;
      return client.updateDrawer(id as number, data);
    }
    case 'delete_drawer':
      return client.deleteDrawer(args.id as number);
    default:
      throw new Error(`Unknown drawer tool: ${toolName}`);
  }
}
