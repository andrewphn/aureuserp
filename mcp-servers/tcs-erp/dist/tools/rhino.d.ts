/**
 * Rhino Tools - Rhino 3D integration tools
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const rhinoTools: Tool[];
export declare function handleRhinoTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
