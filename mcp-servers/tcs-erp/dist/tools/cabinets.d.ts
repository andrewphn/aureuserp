/**
 * Cabinet Tools - CRUD operations for cabinets
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const cabinetTools: Tool[];
export declare function handleCabinetTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
