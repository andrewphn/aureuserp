/**
 * Tool Registry - All MCP tools for TCS ERP
 *
 * Exports tool definitions for the MCP server.
 * Total: 150+ tools across all categories.
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
export { salesOrderTools, handleSalesOrderTool } from './sales-orders.js';
export { purchaseOrderTools, handlePurchaseOrderTool } from './purchase-orders.js';
export { invoiceTools, handleInvoiceTool } from './invoices.js';
export { paymentTools, handlePaymentTool } from './payments.js';
export { calculatorTools, handleCalculatorTool } from './calculators.js';
export { bomTools, handleBomTool } from './bom.js';
export { stockTools, handleStockTool } from './stock.js';
export { productCategoryTools, handleProductCategoryTool } from './product-categories.js';
export { changeOrderTools, handleChangeOrderTool } from './change-orders.js';
export { chatterTools, handleChatterTool } from './chatter.js';
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
    salesOrders: number;
    purchaseOrders: number;
    invoices: number;
    payments: number;
    calculators: number;
    bom: number;
    stock: number;
    productCategories: number;
    changeOrders: number;
    chatter: number;
    total: number;
};
