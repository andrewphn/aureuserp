/**
 * Room Tools - CRUD operations for rooms
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const roomTools: Tool[];
export declare function handleRoomTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
