/**
 * Batch Tools - Batch operations for multiple records
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const batchTools: Tool[];
export declare function handleBatchTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
