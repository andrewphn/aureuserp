/**
 * Section Tools - CRUD operations for cabinet sections
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const sectionTools: Tool[];
export declare function handleSectionTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
