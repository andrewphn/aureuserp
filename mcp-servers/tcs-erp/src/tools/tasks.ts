/**
 * Task Tools - CRUD operations for tasks
 */

import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';

export const taskTools: Tool[] = [
  {
    name: 'list_tasks',
    description: 'List tasks with filters (status, assignee, project).',
    inputSchema: {
      type: 'object',
      properties: {
        project_id: { type: 'number', description: 'Filter by project ID' },
        assignee_id: { type: 'number', description: 'Filter by assignee employee ID' },
        status: { type: 'string', description: 'Filter by status (pending, in_progress, completed, cancelled)' },
        priority: { type: 'string', description: 'Filter by priority (low, medium, high, urgent)' },
        due_before: { type: 'string', description: 'Filter tasks due before date (YYYY-MM-DD)' },
        due_after: { type: 'string', description: 'Filter tasks due after date (YYYY-MM-DD)' },
        page: { type: 'number', description: 'Page number for pagination' },
        per_page: { type: 'number', description: 'Items per page (max 100)' },
      },
    },
  },
  {
    name: 'get_task',
    description: 'Get task details by ID.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Task ID' },
      },
      required: ['id'],
    },
  },
  {
    name: 'create_task',
    description: 'Create a new task.',
    inputSchema: {
      type: 'object',
      properties: {
        title: { type: 'string', description: 'Task title' },
        description: { type: 'string', description: 'Task description' },
        project_id: { type: 'number', description: 'Associated project ID' },
        assignee_id: { type: 'number', description: 'Assigned employee ID' },
        priority: { type: 'string', description: 'Priority (low, medium, high, urgent)' },
        due_date: { type: 'string', description: 'Due date (YYYY-MM-DD)' },
        estimated_hours: { type: 'number', description: 'Estimated hours to complete' },
      },
      required: ['title'],
    },
  },
  {
    name: 'update_task',
    description: 'Update task fields.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Task ID to update' },
        title: { type: 'string', description: 'New title' },
        description: { type: 'string', description: 'New description' },
        assignee_id: { type: 'number', description: 'New assignee ID' },
        status: { type: 'string', description: 'New status' },
        priority: { type: 'string', description: 'New priority' },
        due_date: { type: 'string', description: 'New due date' },
        estimated_hours: { type: 'number', description: 'New estimated hours' },
        actual_hours: { type: 'number', description: 'Actual hours spent' },
      },
      required: ['id'],
    },
  },
  {
    name: 'delete_task',
    description: 'Delete a task.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Task ID to delete' },
      },
      required: ['id'],
    },
  },
  {
    name: 'complete_task',
    description: 'Mark a task as complete.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Task ID to complete' },
        actual_hours: { type: 'number', description: 'Actual hours spent' },
        notes: { type: 'string', description: 'Completion notes' },
      },
      required: ['id'],
    },
  },
];

export async function handleTaskTool(
  client: TcsErpApiClient,
  toolName: string,
  args: Record<string, unknown>
): Promise<unknown> {
  switch (toolName) {
    case 'list_tasks':
      return client.listTasks(args);
    case 'get_task':
      return client.getTask(args.id as number);
    case 'create_task':
      return client.createTask(args);
    case 'update_task': {
      const { id, ...data } = args;
      return client.updateTask(id as number, data);
    }
    case 'delete_task':
      return client.deleteTask(args.id as number);
    case 'complete_task':
      return client.completeTask(args.id as number, args.actual_hours as number | undefined, args.notes as string | undefined);
    default:
      throw new Error(`Unknown task tool: ${toolName}`);
  }
}
