/**
 * Purchase Order Tools - CRUD and workflow operations for purchase orders
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const purchaseOrderTools: Tool[];
export declare function handlePurchaseOrderTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
