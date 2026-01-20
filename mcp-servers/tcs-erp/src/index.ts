#!/usr/bin/env node
/**
 * TCS ERP MCP Server
 *
 * Model Context Protocol server for TCS Woodwork ERP system.
 * Provides 150+ tools for managing projects, cabinets, orders, invoices, and more.
 *
 * Environment variables:
 * - TCS_ERP_BASE_URL: Base URL for the ERP API (default: http://aureuserp.test)
 * - TCS_ERP_API_TOKEN: API token for authentication
 */

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from '@modelcontextprotocol/sdk/types.js';

import { TcsErpApiClient } from './api-client.js';
import {
  allTools,
  toolCounts,
  handleProjectTool,
  handleRoomTool,
  handleLocationTool,
  handleCabinetRunTool,
  handleCabinetTool,
  handleSectionTool,
  handleDrawerTool,
  handleDoorTool,
  handleProductTool,
  handlePartnerTool,
  handleEmployeeTool,
  handleTaskTool,
  handleRhinoTool,
  handleReviewTool,
  handleWebhookTool,
  handleBatchTool,
  // New handlers
  handleSalesOrderTool,
  handlePurchaseOrderTool,
  handleInvoiceTool,
  handlePaymentTool,
  handleCalculatorTool,
  handleBomTool,
  handleStockTool,
  handleProductCategoryTool,
  handleChangeOrderTool,
  handleChatterTool,
} from './tools/index.js';

// Tool name prefixes for routing
const TOOL_PREFIXES = {
  // Original tools
  projects: ['list_projects', 'get_project', 'create_project', 'update_project', 'delete_project', 'get_project_tree', 'change_project_stage', 'calculate_project', 'clone_project', 'get_project_gate_status', 'get_project_bom', 'generate_project_order'],
  rooms: ['list_rooms', 'get_room', 'create_room', 'update_room', 'delete_room'],
  locations: ['list_locations', 'get_location', 'create_location', 'update_location', 'delete_location'],
  cabinetRuns: ['list_cabinet_runs', 'get_cabinet_run', 'create_cabinet_run', 'update_cabinet_run', 'delete_cabinet_run'],
  cabinets: ['list_cabinets', 'get_cabinet', 'create_cabinet', 'update_cabinet', 'delete_cabinet', 'calculate_cabinet', 'get_cabinet_cut_list'],
  sections: ['list_sections', 'get_section', 'create_section', 'update_section', 'delete_section'],
  drawers: ['list_drawers', 'get_drawer', 'create_drawer', 'update_drawer', 'delete_drawer'],
  doors: ['list_doors', 'get_door', 'create_door', 'update_door', 'delete_door'],
  products: ['list_products', 'get_product', 'create_product', 'update_product', 'delete_product', 'search_products'],
  partners: ['list_partners', 'get_partner', 'create_partner', 'update_partner', 'delete_partner'],
  employees: ['list_employees', 'get_employee', 'create_employee', 'update_employee', 'delete_employee'],
  tasks: ['list_tasks', 'get_task', 'create_task', 'update_task', 'delete_task', 'complete_task'],
  rhino: ['get_interpretation_context', 'save_interpretation', 'rhino_get_document_info', 'rhino_list_groups', 'rhino_extract_cabinet', 'rhino_extract_all', 'rhino_trigger_extraction', 'rhino_get_extraction_status', 'rhino_sync_to_rhino', 'rhino_sync_from_rhino', 'rhino_execute_script'],
  reviews: ['list_reviews', 'get_review', 'approve_review', 'reject_review'],
  webhooks: ['list_webhooks', 'create_webhook', 'delete_webhook', 'test_webhook'],
  batch: ['batch_create', 'batch_update', 'batch_delete'],
  // New tools
  salesOrders: ['list_sales_orders', 'get_sales_order', 'create_sales_order', 'update_sales_order', 'delete_sales_order', 'confirm_sales_order', 'cancel_sales_order', 'create_sales_order_invoice', 'send_sales_order_email', 'list_sales_order_lines', 'create_sales_order_line', 'update_sales_order_line', 'delete_sales_order_line'],
  purchaseOrders: ['list_purchase_orders', 'get_purchase_order', 'create_purchase_order', 'update_purchase_order', 'delete_purchase_order', 'confirm_purchase_order', 'cancel_purchase_order', 'create_purchase_order_bill', 'send_purchase_order_email', 'list_purchase_order_lines', 'create_purchase_order_line', 'update_purchase_order_line', 'delete_purchase_order_line'],
  invoices: ['list_invoices', 'get_invoice', 'create_invoice', 'update_invoice', 'delete_invoice', 'post_invoice', 'pay_invoice', 'create_invoice_credit_note', 'reset_invoice_to_draft', 'send_invoice_email', 'list_bills', 'get_bill', 'create_bill', 'update_bill', 'delete_bill', 'post_bill', 'pay_bill', 'reset_bill_to_draft'],
  payments: ['list_payments', 'get_payment', 'create_payment', 'update_payment', 'delete_payment', 'post_payment', 'cancel_payment', 'register_payment'],
  calculators: ['calculate_cabinet_dimensions', 'calculate_drawer_dimensions', 'calculate_stretcher_dimensions'],
  bom: ['list_bom', 'get_bom', 'create_bom', 'update_bom', 'delete_bom', 'get_bom_by_project', 'get_bom_by_cabinet', 'generate_bom', 'bulk_update_bom_status'],
  stock: ['list_stock', 'get_stock', 'get_stock_by_product', 'get_stock_by_location', 'adjust_stock', 'transfer_stock'],
  productCategories: ['list_product_categories', 'get_product_categories_tree', 'get_product_category', 'create_product_category', 'update_product_category', 'delete_product_category'],
  changeOrders: ['list_change_orders', 'get_change_order', 'create_change_order', 'update_change_order', 'delete_change_order', 'approve_change_order', 'reject_change_order', 'get_change_orders_by_project'],
  chatter: ['list_chatter', 'get_chatter', 'create_chatter', 'update_chatter', 'delete_chatter', 'get_chatter_for_resource', 'add_chatter_to_resource', 'pin_chatter', 'unpin_chatter', 'get_chatter_types'],
};

/**
 * Route a tool call to the appropriate handler
 */
async function routeToolCall(
  client: TcsErpApiClient,
  toolName: string,
  args: Record<string, unknown>
): Promise<unknown> {
  // Find the appropriate handler based on tool name
  if (TOOL_PREFIXES.projects.includes(toolName)) {
    return handleProjectTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.rooms.includes(toolName)) {
    return handleRoomTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.locations.includes(toolName)) {
    return handleLocationTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.cabinetRuns.includes(toolName)) {
    return handleCabinetRunTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.cabinets.includes(toolName)) {
    return handleCabinetTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.sections.includes(toolName)) {
    return handleSectionTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.drawers.includes(toolName)) {
    return handleDrawerTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.doors.includes(toolName)) {
    return handleDoorTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.products.includes(toolName)) {
    return handleProductTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.partners.includes(toolName)) {
    return handlePartnerTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.employees.includes(toolName)) {
    return handleEmployeeTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.tasks.includes(toolName)) {
    return handleTaskTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.rhino.includes(toolName)) {
    return handleRhinoTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.reviews.includes(toolName)) {
    return handleReviewTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.webhooks.includes(toolName)) {
    return handleWebhookTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.batch.includes(toolName)) {
    return handleBatchTool(client, toolName, args);
  }
  // New tools
  if (TOOL_PREFIXES.salesOrders.includes(toolName)) {
    return handleSalesOrderTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.purchaseOrders.includes(toolName)) {
    return handlePurchaseOrderTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.invoices.includes(toolName)) {
    return handleInvoiceTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.payments.includes(toolName)) {
    return handlePaymentTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.calculators.includes(toolName)) {
    return handleCalculatorTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.bom.includes(toolName)) {
    return handleBomTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.stock.includes(toolName)) {
    return handleStockTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.productCategories.includes(toolName)) {
    return handleProductCategoryTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.changeOrders.includes(toolName)) {
    return handleChangeOrderTool(client, toolName, args);
  }
  if (TOOL_PREFIXES.chatter.includes(toolName)) {
    return handleChatterTool(client, toolName, args);
  }

  throw new Error(`Unknown tool: ${toolName}`);
}

/**
 * Main server initialization
 */
async function main() {
  // Initialize the API client
  const client = new TcsErpApiClient();

  // Create the MCP server
  const server = new Server(
    {
      name: 'tcs-erp',
      version: '1.0.0',
    },
    {
      capabilities: {
        tools: {},
      },
    }
  );

  // Handle list_tools request
  server.setRequestHandler(ListToolsRequestSchema, async () => {
    console.error(`Listing ${toolCounts.total} tools across ${Object.keys(toolCounts).length - 1} categories`);
    return {
      tools: allTools,
    };
  });

  // Handle call_tool request
  server.setRequestHandler(CallToolRequestSchema, async (request) => {
    const { name, arguments: args } = request.params;

    console.error(`Calling tool: ${name}`);

    try {
      const result = await routeToolCall(client, name, args || {});

      return {
        content: [
          {
            type: 'text',
            text: JSON.stringify(result, null, 2),
          },
        ],
      };
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : String(error);
      console.error(`Tool error: ${errorMessage}`);

      return {
        content: [
          {
            type: 'text',
            text: JSON.stringify({
              error: true,
              message: errorMessage,
            }),
          },
        ],
        isError: true,
      };
    }
  });

  // Start the server with stdio transport
  const transport = new StdioServerTransport();
  await server.connect(transport);

  const baseUrl = process.env.TCS_ERP_BASE_URL || 'http://aureuserp.test';
  console.error('TCS ERP MCP Server started');
  console.error(`Base URL: ${baseUrl}`);
  console.error(`Total tools: ${toolCounts.total}`);
  console.error('Categories:');
  console.error(`  Original: projects(${toolCounts.projects}), rooms(${toolCounts.rooms}), locations(${toolCounts.locations}), cabinet-runs(${toolCounts.cabinetRuns}), cabinets(${toolCounts.cabinets}), sections(${toolCounts.sections}), drawers(${toolCounts.drawers}), doors(${toolCounts.doors}), products(${toolCounts.products}), partners(${toolCounts.partners}), employees(${toolCounts.employees}), tasks(${toolCounts.tasks}), rhino(${toolCounts.rhino}), reviews(${toolCounts.reviews}), webhooks(${toolCounts.webhooks}), batch(${toolCounts.batch})`);
  console.error(`  New: salesOrders(${toolCounts.salesOrders}), purchaseOrders(${toolCounts.purchaseOrders}), invoices(${toolCounts.invoices}), payments(${toolCounts.payments}), calculators(${toolCounts.calculators}), bom(${toolCounts.bom}), stock(${toolCounts.stock}), productCategories(${toolCounts.productCategories}), changeOrders(${toolCounts.changeOrders})`);
}

main().catch((error) => {
  console.error('Fatal error:', error);
  process.exit(1);
});
