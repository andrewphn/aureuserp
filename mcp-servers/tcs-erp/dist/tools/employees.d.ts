/**
 * Employee Tools - CRUD operations for employees
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const employeeTools: Tool[];
export declare function handleEmployeeTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
