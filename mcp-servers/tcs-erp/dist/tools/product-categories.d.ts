/**
 * Product Category Tools - CRUD operations for product categories
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const productCategoryTools: Tool[];
export declare function handleProductCategoryTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
