/**
 * Invoice Tools - CRUD and workflow operations for customer invoices and vendor bills
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const invoiceTools: Tool[];
export declare function handleInvoiceTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
