/**
 * Drawer Tools - CRUD operations for drawers
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const drawerTools: Tool[];
export declare function handleDrawerTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
