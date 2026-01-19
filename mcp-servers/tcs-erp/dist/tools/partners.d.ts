/**
 * Partner Tools - CRUD operations for partners (customers, vendors)
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const partnerTools: Tool[];
export declare function handlePartnerTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
