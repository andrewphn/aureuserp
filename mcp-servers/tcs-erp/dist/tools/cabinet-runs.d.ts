/**
 * Cabinet Run Tools - CRUD operations for cabinet runs
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const cabinetRunTools: Tool[];
export declare function handleCabinetRunTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
