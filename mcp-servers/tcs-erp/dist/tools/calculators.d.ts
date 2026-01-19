/**
 * Calculator Tools - Cabinet and component calculation services
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';
export declare const calculatorTools: Tool[];
export declare function handleCalculatorTool(client: TcsErpApiClient, toolName: string, args: Record<string, unknown>): Promise<unknown>;
