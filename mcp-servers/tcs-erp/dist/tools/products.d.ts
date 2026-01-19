/**
 * Product Tools - CRUD operations for products
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const productTools: Tool[];
export declare function handleProductTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
