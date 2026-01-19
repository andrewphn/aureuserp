/**
 * Sales Order Tools - CRUD and workflow operations for sales orders
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const salesOrderTools: Tool[];
export declare function handleSalesOrderTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
