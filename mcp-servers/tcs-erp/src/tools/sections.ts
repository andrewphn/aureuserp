/**
 * Section Tools - CRUD operations for cabinet sections
 */

import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';

export const sectionTools: Tool[] = [
  {
    name: 'list_sections',
    description: 'List sections for a cabinet.',
    inputSchema: {
      type: 'object',
      properties: {
        cabinet_id: { type: 'number', description: 'Filter by cabinet ID' },
      },
    },
  },
  {
    name: 'get_section',
    description: 'Get section details by ID.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Section ID' },
      },
      required: ['id'],
    },
  },
  {
    name: 'create_section',
    description: 'Create a new section in a cabinet (drawer, door, shelf).',
    inputSchema: {
      type: 'object',
      properties: {
        cabinet_id: { type: 'number', description: 'Parent cabinet ID' },
        section_type: { type: 'string', description: 'Section type (drawer, door, shelf, open)' },
        height_inches: { type: 'number', description: 'Section height in inches' },
        sort_order: { type: 'number', description: 'Display order (top to bottom)' },
      },
      required: ['cabinet_id', 'section_type', 'height_inches'],
    },
  },
  {
    name: 'update_section',
    description: 'Update an existing section.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Section ID to update' },
        section_type: { type: 'string', description: 'New section type' },
        height_inches: { type: 'number', description: 'New height in inches' },
        sort_order: { type: 'number', description: 'New sort order' },
      },
      required: ['id'],
    },
  },
  {
    name: 'delete_section',
    description: 'Delete a section.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Section ID to delete' },
      },
      required: ['id'],
    },
  },
];

export async function handleSectionTool(
  client: TcsErpApiClient,
  toolName: string,
  args: Record<string, unknown>
): Promise<unknown> {
  switch (toolName) {
    case 'list_sections':
      return client.listSections(args.cabinet_id as number | undefined);
    case 'get_section':
      return client.getSection(args.id as number);
    case 'create_section': {
      const { cabinet_id, ...data } = args;
      return client.createSection(cabinet_id as number, data);
    }
    case 'update_section': {
      const { id, ...data } = args;
      return client.updateSection(id as number, data);
    }
    case 'delete_section':
      return client.deleteSection(args.id as number);
    default:
      throw new Error(`Unknown section tool: ${toolName}`);
  }
}
