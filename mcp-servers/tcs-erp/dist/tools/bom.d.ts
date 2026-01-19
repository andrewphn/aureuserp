/**
 * Bill of Materials (BOM) Tools - CRUD and generation operations for BOMs
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const bomTools: Tool[];
export declare function handleBomTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
