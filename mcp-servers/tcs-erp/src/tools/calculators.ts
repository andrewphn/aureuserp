/**
 * Calculator Tools - Cabinet and component calculation services
 */

import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';

export const calculatorTools: Tool[] = [
  {
    name: 'calculate_cabinet_dimensions',
    description: 'Calculate cabinet dimensions from exterior measurements. Returns interior cavity dimensions, material thicknesses, and construction details.',
    inputSchema: {
      type: 'object',
      properties: {
        exterior_width: { type: 'number', description: 'Total exterior width in inches' },
        exterior_height: { type: 'number', description: 'Total exterior height in inches' },
        exterior_depth: { type: 'number', description: 'Total exterior depth in inches' },
        cabinet_type: { type: 'string', description: 'Cabinet type (base, wall, tall, vanity)' },
        construction_type: { type: 'string', description: 'Construction type (frameless, face_frame)' },
        material_thickness: { type: 'number', description: 'Material thickness in inches (default 0.75)' },
        back_panel_thickness: { type: 'number', description: 'Back panel thickness (default 0.25)' },
        include_toe_kick: { type: 'boolean', description: 'Include toe kick in calculations' },
        toe_kick_height: { type: 'number', description: 'Toe kick height in inches (default 4)' },
      },
      required: ['exterior_width', 'exterior_height', 'exterior_depth'],
    },
  },
  {
    name: 'calculate_drawer_dimensions',
    description: 'Calculate drawer box dimensions from opening size. Returns drawer box width, height, depth, and slide clearances.',
    inputSchema: {
      type: 'object',
      properties: {
        opening_width: { type: 'number', description: 'Cabinet opening width in inches' },
        opening_height: { type: 'number', description: 'Drawer opening height in inches' },
        opening_depth: { type: 'number', description: 'Cabinet interior depth in inches' },
        slide_type: { type: 'string', description: 'Slide type (undermount, side_mount, center_mount)' },
        slide_length: { type: 'number', description: 'Slide length in inches (12, 14, 16, 18, 20, 21, 22, 24)' },
        drawer_style: { type: 'string', description: 'Drawer box style (standard, soft_close, heavy_duty)' },
        material_thickness: { type: 'number', description: 'Drawer box material thickness (default 0.5)' },
        bottom_thickness: { type: 'number', description: 'Drawer bottom thickness (default 0.25)' },
        face_frame_overlay: { type: 'number', description: 'Face frame overlay if applicable' },
        include_cut_list: { type: 'boolean', description: 'Include cut list in response' },
      },
      required: ['opening_width', 'opening_height', 'opening_depth'],
    },
  },
  {
    name: 'calculate_stretcher_dimensions',
    description: 'Calculate stretcher/rail dimensions for cabinet construction. Returns stretcher positions, sizes, and quantities.',
    inputSchema: {
      type: 'object',
      properties: {
        cabinet_width: { type: 'number', description: 'Cabinet interior width in inches' },
        cabinet_height: { type: 'number', description: 'Cabinet interior height in inches' },
        cabinet_depth: { type: 'number', description: 'Cabinet interior depth in inches' },
        cabinet_type: { type: 'string', description: 'Cabinet type (base, wall, tall)' },
        stretcher_width: { type: 'number', description: 'Stretcher width in inches (default 4)' },
        stretcher_thickness: { type: 'number', description: 'Stretcher thickness in inches (default 0.75)' },
        include_front_stretchers: { type: 'boolean', description: 'Include front stretchers (default true)' },
        include_back_stretchers: { type: 'boolean', description: 'Include back stretchers (default true)' },
        include_mid_stretchers: { type: 'boolean', description: 'Include mid stretchers for wide cabinets' },
        mid_stretcher_threshold: { type: 'number', description: 'Width threshold for adding mid stretcher (default 36)' },
      },
      required: ['cabinet_width', 'cabinet_depth'],
    },
  },
];

export async function handleCalculatorTool(
  client: TcsErpApiClient,
  toolName: string,
  args: Record<string, unknown>
): Promise<unknown> {
  switch (toolName) {
    case 'calculate_cabinet_dimensions':
      return client.calculateCabinetDimensions(args);
    case 'calculate_drawer_dimensions':
      return client.calculateDrawerDimensions(args);
    case 'calculate_stretcher_dimensions':
      return client.calculateStretcherDimensions(args);
    default:
      throw new Error(`Unknown calculator tool: ${toolName}`);
  }
}
