/**
 * Rhino Tools - Rhino 3D integration tools
 */

import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';

export const rhinoTools: Tool[] = [
  {
    name: 'get_interpretation_context',
    description: 'Get the context and prompt for AI cabinet dimension interpretation. Returns cabinet data, construction standards, and a detailed prompt for Claude to interpret ambiguous dimensions.',
    inputSchema: {
      type: 'object',
      properties: {
        review_id: { type: 'number', description: 'Review ID to get interpretation context for' },
      },
      required: ['review_id'],
    },
  },
  {
    name: 'save_interpretation',
    description: 'Save AI interpretation results back to the review item.',
    inputSchema: {
      type: 'object',
      properties: {
        review_id: { type: 'number', description: 'Review ID to save interpretation for' },
        interpretation: {
          type: 'object',
          description: 'AI interpretation results (cabinet_type, corrected_dimensions, etc.)',
        },
      },
      required: ['review_id', 'interpretation'],
    },
  },
  {
    name: 'rhino_get_document_info',
    description: 'Get current Rhino document metadata.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },
  {
    name: 'rhino_list_groups',
    description: 'List cabinet groups in Rhino.',
    inputSchema: {
      type: 'object',
      properties: {
        filter: { type: 'string', description: 'Filter groups by name pattern' },
      },
    },
  },
  {
    name: 'rhino_extract_cabinet',
    description: 'Extract single cabinet from Rhino by group name.',
    inputSchema: {
      type: 'object',
      properties: {
        group_name: { type: 'string', description: 'Rhino group name to extract' },
        include_dimensions: { type: 'boolean', description: 'Include dimension annotations' },
      },
      required: ['group_name'],
    },
  },
  {
    name: 'rhino_extract_all',
    description: 'Batch extract all cabinets from Rhino.',
    inputSchema: {
      type: 'object',
      properties: {
        project_id: { type: 'number', description: 'Target project ID for extraction' },
        include_dimensions: { type: 'boolean', description: 'Include dimension annotations' },
      },
      required: ['project_id'],
    },
  },
  {
    name: 'rhino_trigger_extraction',
    description: 'Start async extraction job for a project.',
    inputSchema: {
      type: 'object',
      properties: {
        project_id: { type: 'number', description: 'Target project ID' },
        options: {
          type: 'object',
          properties: {
            auto_approve_high_confidence: { type: 'boolean', description: 'Auto-approve extractions with confidence >= 80%' },
            use_ai_interpretation: { type: 'boolean', description: 'Use AI to interpret ambiguous dimensions' },
            overwrite_existing: { type: 'boolean', description: 'Overwrite existing cabinets' },
          },
        },
      },
      required: ['project_id'],
    },
  },
  {
    name: 'rhino_get_extraction_status',
    description: 'Check extraction job status.',
    inputSchema: {
      type: 'object',
      properties: {
        job_id: { type: 'number', description: 'Extraction job ID' },
      },
      required: ['job_id'],
    },
  },
  {
    name: 'rhino_sync_to_rhino',
    description: 'Push ERP cabinet changes to Rhino.',
    inputSchema: {
      type: 'object',
      properties: {
        cabinet_id: { type: 'number', description: 'Cabinet ID to sync to Rhino' },
        create_if_missing: { type: 'boolean', description: 'Create in Rhino if not exists' },
      },
      required: ['cabinet_id'],
    },
  },
  {
    name: 'rhino_sync_from_rhino',
    description: 'Pull Rhino changes to ERP.',
    inputSchema: {
      type: 'object',
      properties: {
        group_name: { type: 'string', description: 'Rhino group name to sync from' },
        cabinet_id: { type: 'number', description: 'Target cabinet ID in ERP' },
        force: { type: 'boolean', description: 'Force update even with conflicts' },
      },
      required: ['group_name'],
    },
  },
  {
    name: 'rhino_execute_script',
    description: 'Execute RhinoScript Python code.',
    inputSchema: {
      type: 'object',
      properties: {
        script: { type: 'string', description: 'Python script to execute in Rhino' },
        timeout: { type: 'number', description: 'Script timeout in seconds (default 30)' },
      },
      required: ['script'],
    },
  },
];

export async function handleRhinoTool(
  client: TcsErpApiClient,
  toolName: string,
  args: Record<string, unknown>
): Promise<unknown> {
  switch (toolName) {
    case 'get_interpretation_context':
      return client.getInterpretationContext(args.review_id as number);
    case 'save_interpretation':
      return client.saveInterpretation(args.review_id as number, args.interpretation as Record<string, unknown>);
    case 'rhino_get_document_info':
      return client.rhinoGetDocumentInfo();
    case 'rhino_list_groups':
      return client.rhinoListGroups(args.filter as string | undefined);
    case 'rhino_extract_cabinet':
      return client.rhinoExtractCabinet(args.group_name as string, args.include_dimensions as boolean | undefined);
    case 'rhino_extract_all':
      return client.rhinoExtractAll(args.project_id as number, args.include_dimensions as boolean | undefined);
    case 'rhino_trigger_extraction':
      return client.rhinoTriggerExtraction(args.project_id as number, args.options as Record<string, unknown> | undefined);
    case 'rhino_get_extraction_status':
      return client.rhinoGetExtractionStatus(args.job_id as number);
    case 'rhino_sync_to_rhino':
      return client.rhinoSyncToRhino(args.cabinet_id as number, args.create_if_missing as boolean | undefined);
    case 'rhino_sync_from_rhino':
      return client.rhinoSyncFromRhino(args.group_name as string, args.cabinet_id as number | undefined, args.force as boolean | undefined);
    case 'rhino_execute_script':
      return client.rhinoExecuteScript(args.script as string, args.timeout as number | undefined);
    default:
      throw new Error(`Unknown rhino tool: ${toolName}`);
  }
}
