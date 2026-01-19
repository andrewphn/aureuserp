/**
 * Employee Tools - CRUD operations for employees
 */

import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';

export const employeeTools: Tool[] = [
  {
    name: 'list_employees',
    description: 'List employees.',
    inputSchema: {
      type: 'object',
      properties: {
        department_id: { type: 'number', description: 'Filter by department ID' },
        active: { type: 'boolean', description: 'Filter by active status' },
        search: { type: 'string', description: 'Search by name or employee ID' },
        page: { type: 'number', description: 'Page number for pagination' },
        per_page: { type: 'number', description: 'Items per page (max 100)' },
      },
    },
  },
  {
    name: 'get_employee',
    description: 'Get employee details by ID.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Employee ID' },
      },
      required: ['id'],
    },
  },
  {
    name: 'create_employee',
    description: 'Create a new employee.',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Employee name' },
        email: { type: 'string', description: 'Email address' },
        employee_id: { type: 'string', description: 'Employee ID/number' },
        department_id: { type: 'number', description: 'Department ID' },
        job_title: { type: 'string', description: 'Job title' },
        hire_date: { type: 'string', description: 'Hire date (YYYY-MM-DD)' },
        hourly_rate: { type: 'number', description: 'Hourly rate' },
      },
      required: ['name'],
    },
  },
  {
    name: 'update_employee',
    description: 'Update employee fields.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Employee ID to update' },
        name: { type: 'string', description: 'New name' },
        email: { type: 'string', description: 'New email' },
        employee_id: { type: 'string', description: 'New employee ID' },
        department_id: { type: 'number', description: 'New department ID' },
        job_title: { type: 'string', description: 'New job title' },
        hourly_rate: { type: 'number', description: 'New hourly rate' },
        active: { type: 'boolean', description: 'Active status' },
      },
      required: ['id'],
    },
  },
  {
    name: 'delete_employee',
    description: 'Delete an employee.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Employee ID to delete' },
      },
      required: ['id'],
    },
  },
];

export async function handleEmployeeTool(
  client: TcsErpApiClient,
  toolName: string,
  args: Record<string, unknown>
): Promise<unknown> {
  switch (toolName) {
    case 'list_employees':
      return client.listEmployees(args);
    case 'get_employee':
      return client.getEmployee(args.id as number);
    case 'create_employee':
      return client.createEmployee(args);
    case 'update_employee': {
      const { id, ...data } = args;
      return client.updateEmployee(id as number, data);
    }
    case 'delete_employee':
      return client.deleteEmployee(args.id as number);
    default:
      throw new Error(`Unknown employee tool: ${toolName}`);
  }
}
