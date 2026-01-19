/**
 * Sales Order Tools - CRUD and workflow operations for sales orders
 */

import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';

export const salesOrderTools: Tool[] = [
  {
    name: 'list_sales_orders',
    description: 'List sales orders with filters (status, partner, date range).',
    inputSchema: {
      type: 'object',
      properties: {
        status: { type: 'string', description: 'Filter by status (draft, confirmed, done, cancelled)' },
        partner_id: { type: 'number', description: 'Filter by partner/customer ID' },
        date_from: { type: 'string', description: 'Filter orders from this date (YYYY-MM-DD)' },
        date_to: { type: 'string', description: 'Filter orders to this date (YYYY-MM-DD)' },
        search: { type: 'string', description: 'Search by order number or reference' },
        page: { type: 'number', description: 'Page number for pagination' },
        per_page: { type: 'number', description: 'Items per page (max 100)' },
      },
    },
  },
  {
    name: 'get_sales_order',
    description: 'Get sales order details by ID with lines and partner info.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Sales order ID' },
        include: {
          type: 'array',
          items: { type: 'string' },
          description: 'Relations to include (lines, partner, invoices)',
        },
      },
      required: ['id'],
    },
  },
  {
    name: 'create_sales_order',
    description: 'Create a new sales order/quote.',
    inputSchema: {
      type: 'object',
      properties: {
        partner_id: { type: 'number', description: 'Customer/partner ID' },
        project_id: { type: 'number', description: 'Related project ID' },
        date_order: { type: 'string', description: 'Order date (YYYY-MM-DD)' },
        validity_date: { type: 'string', description: 'Quote validity date' },
        payment_term_id: { type: 'number', description: 'Payment terms ID' },
        notes: { type: 'string', description: 'Order notes' },
        lines: {
          type: 'array',
          items: {
            type: 'object',
            properties: {
              product_id: { type: 'number' },
              name: { type: 'string' },
              quantity: { type: 'number' },
              price_unit: { type: 'number' },
            },
          },
          description: 'Order line items',
        },
      },
      required: ['partner_id'],
    },
  },
  {
    name: 'update_sales_order',
    description: 'Update sales order fields.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Sales order ID to update' },
        partner_id: { type: 'number', description: 'New customer/partner ID' },
        validity_date: { type: 'string', description: 'New validity date' },
        payment_term_id: { type: 'number', description: 'New payment terms ID' },
        notes: { type: 'string', description: 'New notes' },
      },
      required: ['id'],
    },
  },
  {
    name: 'delete_sales_order',
    description: 'Delete a sales order (only draft orders can be deleted).',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Sales order ID to delete' },
      },
      required: ['id'],
    },
  },
  {
    name: 'confirm_sales_order',
    description: 'Confirm a draft sales order/quote, converting it to a confirmed order.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Sales order ID to confirm' },
      },
      required: ['id'],
    },
  },
  {
    name: 'cancel_sales_order',
    description: 'Cancel a sales order.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Sales order ID to cancel' },
        reason: { type: 'string', description: 'Cancellation reason' },
      },
      required: ['id'],
    },
  },
  {
    name: 'create_sales_order_invoice',
    description: 'Create an invoice from a confirmed sales order.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Sales order ID to invoice' },
        invoice_type: { type: 'string', description: 'Invoice type (full, partial, deposit)' },
        amount: { type: 'number', description: 'Amount for partial/deposit invoices' },
      },
      required: ['id'],
    },
  },
  {
    name: 'send_sales_order_email',
    description: 'Send sales order/quote via email to customer.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Sales order ID to send' },
        email: { type: 'string', description: 'Override recipient email' },
        subject: { type: 'string', description: 'Custom email subject' },
        message: { type: 'string', description: 'Custom email message' },
      },
      required: ['id'],
    },
  },
  // Sales Order Lines
  {
    name: 'list_sales_order_lines',
    description: 'List line items for a sales order.',
    inputSchema: {
      type: 'object',
      properties: {
        order_id: { type: 'number', description: 'Sales order ID' },
      },
      required: ['order_id'],
    },
  },
  {
    name: 'create_sales_order_line',
    description: 'Add a line item to a sales order.',
    inputSchema: {
      type: 'object',
      properties: {
        order_id: { type: 'number', description: 'Sales order ID' },
        product_id: { type: 'number', description: 'Product ID' },
        name: { type: 'string', description: 'Line description' },
        quantity: { type: 'number', description: 'Quantity' },
        price_unit: { type: 'number', description: 'Unit price' },
        discount: { type: 'number', description: 'Discount percentage' },
        tax_ids: { type: 'array', items: { type: 'number' }, description: 'Tax IDs to apply' },
      },
      required: ['order_id', 'quantity', 'price_unit'],
    },
  },
  {
    name: 'update_sales_order_line',
    description: 'Update a sales order line item.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Line item ID' },
        quantity: { type: 'number', description: 'New quantity' },
        price_unit: { type: 'number', description: 'New unit price' },
        discount: { type: 'number', description: 'New discount percentage' },
        name: { type: 'string', description: 'New description' },
      },
      required: ['id'],
    },
  },
  {
    name: 'delete_sales_order_line',
    description: 'Remove a line item from a sales order.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Line item ID to delete' },
      },
      required: ['id'],
    },
  },
];

export async function handleSalesOrderTool(
  client: TcsErpApiClient,
  toolName: string,
  args: Record<string, unknown>
): Promise<unknown> {
  switch (toolName) {
    case 'list_sales_orders':
      return client.listSalesOrders(args);
    case 'get_sales_order':
      return client.getSalesOrder(args.id as number, args.include as string[] | undefined);
    case 'create_sales_order':
      return client.createSalesOrder(args);
    case 'update_sales_order': {
      const { id, ...data } = args;
      return client.updateSalesOrder(id as number, data);
    }
    case 'delete_sales_order':
      return client.deleteSalesOrder(args.id as number);
    case 'confirm_sales_order':
      return client.confirmSalesOrder(args.id as number);
    case 'cancel_sales_order':
      return client.cancelSalesOrder(args.id as number, args.reason as string | undefined);
    case 'create_sales_order_invoice':
      return client.createSalesOrderInvoice(args.id as number, args);
    case 'send_sales_order_email':
      return client.sendSalesOrderEmail(args.id as number, args);
    case 'list_sales_order_lines':
      return client.listSalesOrderLines(args.order_id as number);
    case 'create_sales_order_line': {
      const { order_id, ...data } = args;
      return client.createSalesOrderLine(order_id as number, data);
    }
    case 'update_sales_order_line': {
      const { id, ...data } = args;
      return client.updateSalesOrderLine(id as number, data);
    }
    case 'delete_sales_order_line':
      return client.deleteSalesOrderLine(args.id as number);
    default:
      throw new Error(`Unknown sales order tool: ${toolName}`);
  }
}
