/**
 * Payment Tools - CRUD and workflow operations for payments
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const paymentTools: Tool[];
export declare function handlePaymentTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
