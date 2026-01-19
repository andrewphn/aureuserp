/**
 * Review Tools - Rhino extraction review queue management
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const reviewTools: Tool[];
export declare function handleReviewTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
