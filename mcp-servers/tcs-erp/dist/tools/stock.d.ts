/**
 * Stock/Inventory Tools - Product quantities and stock operations
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const stockTools: Tool[];
export declare function handleStockTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
