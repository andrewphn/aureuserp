/**
 * Change Order Tools - CRUD and approval operations for change orders
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const changeOrderTools: Tool[];
export declare function handleChangeOrderTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
