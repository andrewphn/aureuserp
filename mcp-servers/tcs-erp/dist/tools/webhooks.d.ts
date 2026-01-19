/**
 * Webhook Tools - Webhook subscription management
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const webhookTools: Tool[];
export declare function handleWebhookTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
