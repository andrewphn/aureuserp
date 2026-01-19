/**
 * Payment Tools - CRUD and workflow operations for payments
 */

import { Tool } from '@modelcontextprotocol/sdk/types.js';
import { TcsErpApiClient } from '../api-client.js';

export const paymentTools: Tool[] = [
  {
    name: 'list_payments',
    description: 'List payments with filters (status, partner, payment type, date range).',
    inputSchema: {
      type: 'object',
      properties: {
        status: { type: 'string', description: 'Filter by status (draft, posted, cancelled)' },
        partner_id: { type: 'number', description: 'Filter by partner ID' },
        payment_type: { type: 'string', description: 'Filter by type (inbound, outbound)' },
        date_from: { type: 'string', description: 'Filter payments from this date (YYYY-MM-DD)' },
        date_to: { type: 'string', description: 'Filter payments to this date (YYYY-MM-DD)' },
        search: { type: 'string', description: 'Search by payment reference' },
        page: { type: 'number', description: 'Page number for pagination' },
        per_page: { type: 'number', description: 'Items per page (max 100)' },
      },
    },
  },
  {
    name: 'get_payment',
    description: 'Get payment details by ID.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Payment ID' },
      },
      required: ['id'],
    },
  },
  {
    name: 'create_payment',
    description: 'Create a new payment record.',
    inputSchema: {
      type: 'object',
      properties: {
        partner_id: { type: 'number', description: 'Partner ID' },
        payment_type: { type: 'string', description: 'Payment type (inbound for received, outbound for sent)' },
        amount: { type: 'number', description: 'Payment amount' },
        payment_date: { type: 'string', description: 'Payment date (YYYY-MM-DD)' },
        payment_method: { type: 'string', description: 'Payment method (check, cash, transfer, card)' },
        journal_id: { type: 'number', description: 'Journal/bank account ID' },
        reference: { type: 'string', description: 'Payment reference' },
        memo: { type: 'string', description: 'Payment memo/notes' },
      },
      required: ['partner_id', 'payment_type', 'amount'],
    },
  },
  {
    name: 'update_payment',
    description: 'Update payment fields (only draft payments can be updated).',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Payment ID to update' },
        payment_date: { type: 'string', description: 'New payment date' },
        amount: { type: 'number', description: 'New amount' },
        reference: { type: 'string', description: 'New reference' },
        memo: { type: 'string', description: 'New memo' },
      },
      required: ['id'],
    },
  },
  {
    name: 'delete_payment',
    description: 'Delete a payment (only draft payments can be deleted).',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Payment ID to delete' },
      },
      required: ['id'],
    },
  },
  {
    name: 'post_payment',
    description: 'Post/validate a draft payment.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Payment ID to post' },
      },
      required: ['id'],
    },
  },
  {
    name: 'cancel_payment',
    description: 'Cancel a payment.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Payment ID to cancel' },
        reason: { type: 'string', description: 'Cancellation reason' },
      },
      required: ['id'],
    },
  },
  {
    name: 'register_payment',
    description: 'Register a payment and automatically apply it to open invoices/bills.',
    inputSchema: {
      type: 'object',
      properties: {
        partner_id: { type: 'number', description: 'Partner ID' },
        payment_type: { type: 'string', description: 'Payment type (inbound for customer, outbound for vendor)' },
        amount: { type: 'number', description: 'Payment amount' },
        payment_date: { type: 'string', description: 'Payment date (YYYY-MM-DD)' },
        payment_method: { type: 'string', description: 'Payment method' },
        journal_id: { type: 'number', description: 'Journal/bank account ID' },
        reference: { type: 'string', description: 'Payment reference' },
        invoice_ids: {
          type: 'array',
          items: { type: 'number' },
          description: 'Invoice/bill IDs to apply payment to',
        },
      },
      required: ['partner_id', 'payment_type', 'amount'],
    },
  },
];

export async function handlePaymentTool(
  client: TcsErpApiClient,
  toolName: string,
  args: Record<string, unknown>
): Promise<unknown> {
  switch (toolName) {
    case 'list_payments':
      return client.listPayments(args);
    case 'get_payment':
      return client.getPayment(args.id as number);
    case 'create_payment':
      return client.createPayment(args);
    case 'update_payment': {
      const { id, ...data } = args;
      return client.updatePayment(id as number, data);
    }
    case 'delete_payment':
      return client.deletePayment(args.id as number);
    case 'post_payment':
      return client.postPayment(args.id as number);
    case 'cancel_payment':
      return client.cancelPayment(args.id as number, args.reason as string | undefined);
    case 'register_payment':
      return client.registerPayment(args);
    default:
      throw new Error(`Unknown payment tool: ${toolName}`);
  }
}
