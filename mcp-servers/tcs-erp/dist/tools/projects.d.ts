/**
 * Project Tools - CRUD operations for projects
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const projectTools: Tool[];
export declare function handleProjectTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
