/**
 * Tool Registry - All MCP tools for TCS ERP
 *
 * Exports tool definitions for the MCP server.
 * Total: 88 tools across all categories.
 */
import { Tool } from '@modelcontextprotocol/sdk/types.js';
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
/**
 * All available MCP tools
 */
export declare const allTools: Tool[];
/**
 * Tool count by category
 */
export declare const toolCounts: {
    projects: number;
    rooms: number;
    locations: number;
    cabinetRuns: number;
    cabinets: number;
    sections: number;
    drawers: number;
    doors: number;
    products: number;
    partners: number;
    employees: number;
    tasks: number;
    rhino: number;
    reviews: number;
    webhooks: number;
    batch: number;
    total: number;
};
