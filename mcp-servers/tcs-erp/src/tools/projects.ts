/**
 * Project Tools - CRUD operations for projects
 */

import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';

export const projectTools: Tool[] = [
  {
    name: 'list_projects',
    description: 'List projects with optional filters (status, partner, date range). Returns paginated results.',
    inputSchema: {
      type: 'object',
      properties: {
        status: { type: 'string', description: 'Filter by project status' },
        partner_id: { type: 'number', description: 'Filter by partner/client ID' },
        search: { type: 'string', description: 'Search projects by name or number' },
        page: { type: 'number', description: 'Page number for pagination' },
        per_page: { type: 'number', description: 'Items per page (max 100)' },
      },
    },
  },
  {
    name: 'get_project',
    description: 'Get detailed project information by ID, optionally including related data (rooms, cabinets).',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Project ID' },
        include: {
          type: 'array',
          items: { type: 'string' },
          description: 'Related data to include: rooms, cabinets, partner, tasks',
        },
      },
      required: ['id'],
    },
  },
  {
    name: 'create_project',
    description: 'Create a new project with the specified details.',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Project name' },
        project_number: { type: 'string', description: 'Project number/code' },
        partner_id: { type: 'number', description: 'Associated partner/client ID' },
        description: { type: 'string', description: 'Project description' },
        stage_id: { type: 'number', description: 'Project stage ID' },
      },
      required: ['name'],
    },
  },
  {
    name: 'update_project',
    description: 'Update an existing project with new values.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Project ID to update' },
        name: { type: 'string', description: 'New project name' },
        project_number: { type: 'string', description: 'New project number' },
        partner_id: { type: 'number', description: 'New partner ID' },
        description: { type: 'string', description: 'New description' },
        stage_id: { type: 'number', description: 'New stage ID' },
        status: { type: 'string', description: 'New status' },
      },
      required: ['id'],
    },
  },
  {
    name: 'delete_project',
    description: 'Delete a project (soft delete). This will also affect related rooms and cabinets.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Project ID to delete' },
      },
      required: ['id'],
    },
  },
  {
    name: 'get_project_tree',
    description: 'Get full project hierarchy: rooms -> locations -> cabinet runs -> cabinets.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Project ID' },
      },
      required: ['id'],
    },
  },
];

export async function handleProjectTool(
  client: TcsErpApiClient,
  toolName: string,
  args: Record<string, unknown>
): Promise<unknown> {
  switch (toolName) {
    case 'list_projects':
      return client.listProjects(args);
    case 'get_project':
      return client.getProject(args.id as number, args.include as string[] | undefined);
    case 'create_project':
      return client.createProject(args);
    case 'update_project': {
      const { id, ...data } = args;
      return client.updateProject(id as number, data);
    }
    case 'delete_project':
      return client.deleteProject(args.id as number);
    case 'get_project_tree':
      return client.getProject(args.id as number, ['rooms', 'rooms.locations', 'rooms.locations.cabinetRuns', 'rooms.locations.cabinetRuns.cabinets']);
    default:
      throw new Error(`Unknown project tool: ${toolName}`);
  }
}
