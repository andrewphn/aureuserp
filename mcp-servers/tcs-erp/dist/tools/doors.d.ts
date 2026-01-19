/**
 * Door Tools - CRUD operations for doors
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const doorTools: Tool[];
export declare function handleDoorTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
