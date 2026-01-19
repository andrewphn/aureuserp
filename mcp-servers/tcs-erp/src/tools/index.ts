/**
 * Tool Registry - All MCP tools for TCS ERP
 *
 * Exports tool definitions for the MCP server.
 * Total: 88 tools across all categories.
 */

import { Tool } from '@modelcontextprotocol/sdk/types.js';

// Import tool handlers
export { projectTools, handleProjectTool } from './projects.js';
export { roomTools, handleRoomTool } from './rooms.js';
export { locationTools, handleLocationTool } from './locations.js';
export { cabinetRunTools, handleCabinetRunTool } from './cabinet-runs.js';
export { cabinetTools, handleCabinetTool } from './cabinets.js';
export { sectionTools, handleSectionTool } from './sections.js';
export { drawerTools, handleDrawerTool } from './drawers.js';
export { doorTools, handleDoorTool } from './doors.js';
export { productTools, handleProductTool } from './products.js';
export { partnerTools, handlePartnerTool } from './partners.js';
export { employeeTools, handleEmployeeTool } from './employees.js';
export { taskTools, handleTaskTool } from './tasks.js';
export { rhinoTools, handleRhinoTool } from './rhino.js';
export { reviewTools, handleReviewTool } from './reviews.js';
export { webhookTools, handleWebhookTool } from './webhooks.js';
export { batchTools, handleBatchTool } from './batch.js';

import { projectTools } from './projects.js';
import { roomTools } from './rooms.js';
import { locationTools } from './locations.js';
import { cabinetRunTools } from './cabinet-runs.js';
import { cabinetTools } from './cabinets.js';
import { sectionTools } from './sections.js';
import { drawerTools } from './drawers.js';
import { doorTools } from './doors.js';
import { productTools } from './products.js';
import { partnerTools } from './partners.js';
import { employeeTools } from './employees.js';
import { taskTools } from './tasks.js';
import { rhinoTools } from './rhino.js';
import { reviewTools } from './reviews.js';
import { webhookTools } from './webhooks.js';
import { batchTools } from './batch.js';

/**
 * All available MCP tools
 */
export const allTools: Tool[] = [
  ...projectTools,
  ...roomTools,
  ...locationTools,
  ...cabinetRunTools,
  ...cabinetTools,
  ...sectionTools,
  ...drawerTools,
  ...doorTools,
  ...productTools,
  ...partnerTools,
  ...employeeTools,
  ...taskTools,
  ...rhinoTools,
  ...reviewTools,
  ...webhookTools,
  ...batchTools,
];

/**
 * Tool count by category
 */
export const toolCounts = {
  projects: projectTools.length,
  rooms: roomTools.length,
  locations: locationTools.length,
  cabinetRuns: cabinetRunTools.length,
  cabinets: cabinetTools.length,
  sections: sectionTools.length,
  drawers: drawerTools.length,
  doors: doorTools.length,
  products: productTools.length,
  partners: partnerTools.length,
  employees: employeeTools.length,
  tasks: taskTools.length,
  rhino: rhinoTools.length,
  reviews: reviewTools.length,
  webhooks: webhookTools.length,
  batch: batchTools.length,
  total: allTools.length,
};
