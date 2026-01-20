/**
 * Chatter Tools - CRUD operations for messages/notes on any resource
 *
 * Chatter is a polymorphic messaging system that can be attached to any model.
 * Use messageable_type shortcuts (project, cabinet, partner, etc.) for convenience.
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const chatterTools: Tool[];
export declare function handleChatterTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
