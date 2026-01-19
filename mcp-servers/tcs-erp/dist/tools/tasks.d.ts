/**
 * Task Tools - CRUD operations for tasks
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const taskTools: Tool[];
export declare function handleTaskTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
