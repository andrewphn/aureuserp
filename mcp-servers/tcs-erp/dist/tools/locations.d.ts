/**
 * Location Tools - CRUD operations for room locations
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const locationTools: Tool[];
export declare function handleLocationTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
